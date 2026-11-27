<?php

namespace App\Http\Controllers\Admin;

use App\Events\TicketReplied;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with('department', 'client');
        if ($request->filled('status')) {
            $st = (string) $request->status;
            if ($st === 'any') {
                // Explicit "- Any -": show all tickets regardless of status
            } elseif ($st === 'awaiting') {
                $query->whereIn('status', ['Open', 'Customer-Reply']);
            } elseif ($st === 'flagged') {
                $query->whereNotNull('flag')->whereRaw('LOWER(status) <> ?', ['closed']);
            } elseif ($st === 'active') {
                $query->whereRaw('LOWER(status) <> ?', ['closed']);
            } else {
                $query->where('status', $st);
            }
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->boolean('flagged')) {
            $query->whereNotNull('flag');
        }
        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('tid', 'like', "%{$s}%")
                    ->orWhere('title', 'like', "%{$s}%")
                    ->orWhere('message', 'like', "%{$s}%");
            });
        }
        if ($request->filled('email')) {
            $em = trim((string) $request->input('email'));
            $query->where(function ($q) use ($em) {
                $q->where('email', 'like', "%{$em}%")
                    ->orWhereHas('client', fn ($c) => $c->where('email', 'like', "%{$em}%"));
            });
        }
        // Tickets with no account behind them: the public contact form, or
        // mail from an unknown sender. Where contact-form spam collects.
        if ($request->boolean('guests')) {
            $query->whereNull('client_id');
        }
        $tickets = $query->orderBy('last_reply', 'desc')->orderBy('created_at', 'desc')->paginate(25);
        $departments = TicketDepartment::all();

        return view('admin.tickets.index', compact('tickets', 'departments'));
    }

    /**
     * Open a ticket for a customer: the customer rang, wrote from somewhere
     * else, or staff need something from them. Nothing in the admin area
     * could; the client page's "New ticket" link went to the ticket list.
     */
    public function create(Request $request)
    {
        $client = $request->filled('client') ? $this->findClient((string) $request->input('client')) : null;
        if ($client) {
            $client->loadMissing('currency:id,code,prefix,suffix', 'contacts');
        }

        $initialClients = \App\Models\Client::orderBy('first_name')
            ->orderBy('last_name')
            ->limit(150)
            ->get(['id', 'first_name', 'last_name', 'company_name', 'email']);

        if ($client && ! $initialClients->contains('id', $client->id)) {
            $initialClients->prepend($client);
        }

        $clientOptions = $initialClients->map(fn (\App\Models\Client $c) => $this->serializeTicketClient($c))->values();

        $servicesCollection = $client
            ? \App\Models\Service::where('client_id', $client->id)
                ->with('product.group')
                ->orderByDesc('id')
                ->limit(10)
                ->get()
            : collect();

        $totalServicesCount = $client ? \App\Models\Service::where('client_id', $client->id)->count() : 0;

        $predefined = \App\Models\TicketPredefinedCategory::with(['replies' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->filter(fn ($cat) => $cat->replies->isNotEmpty())
            ->values();

        $kbArticles = class_exists(\App\Models\KbArticle::class)
            ? \App\Models\KbArticle::where('private', false)->orderBy('title')->limit(100)->get(['id', 'title'])
            : collect();

        return view('admin.tickets.create', [
            'client' => $client,
            'selectedClientOption' => $client ? $this->serializeTicketClient($client) : null,
            'clientOptions' => $clientOptions,
            'departments' => TicketDepartment::orderBy('sort_order')->get(['id', 'name']),
            'services' => $servicesCollection,
            'serializedServices' => $client ? $servicesCollection->map(fn ($s) => $this->serializeClientService($s, $client))->values() : collect(),
            'totalServicesCount' => $totalServicesCount,
            'serializedContacts' => $client ? $client->contacts->map(fn ($ct) => [
                'id' => $ct->id,
                'name' => trim($ct->first_name . ' ' . $ct->last_name),
                'email' => (string) $ct->email,
            ])->values() : collect(),
            'predefined' => $predefined,
            'kbArticles' => $kbArticles,
        ]);
    }

    public function clientLookup(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = \App\Models\Client::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                if (ctype_digit($q)) {
                    $sub->orWhere('id', (int) $q);
                }
                $sub->orWhere('first_name', 'like', '%' . $q . '%')
                    ->orWhere('last_name', 'like', '%' . $q . '%')
                    ->orWhere('company_name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%')
                    ->orWhere('phone_number', 'like', '%' . $q . '%');
            });
        }

        $clients = $query->orderBy('first_name')->orderBy('last_name')->limit(40)->get(['id', 'first_name', 'last_name', 'company_name', 'email']);

        return response()->json([
            'clients' => $clients->map(fn (\App\Models\Client $c) => $this->serializeTicketClient($c))->values(),
        ]);
    }

    public function clientContext(Request $request, \App\Models\Client $client)
    {
        $client->loadMissing('currency:id,code,prefix,suffix', 'contacts');
        $showAll = $request->boolean('all', false);

        $svcQuery = \App\Models\Service::where('client_id', $client->id)
            ->with('product.group')
            ->orderByRaw("CASE WHEN LOWER(status) = 'active' THEN 0 WHEN LOWER(status) = 'pending' THEN 1 WHEN LOWER(status) = 'suspended' THEN 2 ELSE 3 END")
            ->orderByDesc('id');

        $totalCount = (clone $svcQuery)->count();
        $services = $showAll ? $svcQuery->limit(200)->get() : $svcQuery->limit(10)->get();

        return response()->json([
            'client' => $this->serializeTicketClient($client),
            'services' => $services->map(fn ($s) => $this->serializeClientService($s, $client))->values(),
            'total_services' => $totalCount,
            'contacts' => $client->contacts->map(fn ($ct) => [
                'id' => $ct->id,
                'name' => trim($ct->first_name . ' ' . $ct->last_name),
                'email' => (string) $ct->email,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $hasClientInput = $request->filled('client');

        $v = $request->validate([
            'client' => [$hasClientInput ? 'required' : 'nullable', 'nullable', 'string', 'max:255'],
            'name' => [$hasClientInput ? 'nullable' : 'required', 'nullable', 'string', 'max:255'],
            'email' => [$hasClientInput ? 'nullable' : 'required', 'nullable', 'email', 'max:255'],
            'send_email' => 'nullable|boolean',
            'cc' => 'nullable|string|max:255',
            'department_id' => 'required|exists:ticket_departments,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:Low,Medium,High',
            'related_service' => 'nullable|integer',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,zip,csv',
        ]);

        $client = null;
        if ($hasClientInput) {
            $client = $this->findClient((string) $v['client']);
            if (! $client) {
                return back()->withInput()->withErrors(['client' => __('admin.tickets.open_no_client')]);
            }
            if (! empty($v['related_service']) && ! \App\Models\Service::where('id', $v['related_service'])->where('client_id', $client->id)->exists()) {
                return back()->withInput()->withErrors(['related_service' => __('admin.tickets.open_service_not_theirs')]);
            }
        } elseif (! empty($v['related_service'])) {
            return back()->withInput()->withErrors(['related_service' => __('admin.tickets.open_service_not_theirs')]);
        }

        $ccNormalized = null;
        if (! empty($v['cc'])) {
            $rawParts = preg_split('/[\s,;]+/', (string) $v['cc'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $validCc = [];
            foreach ($rawParts as $part) {
                $cleanAddr = trim($part);
                if (! filter_var($cleanAddr, FILTER_VALIDATE_EMAIL)) {
                    return back()->withInput()->withErrors(['cc' => 'Invalid CC recipient email address: ' . $cleanAddr]);
                }
                $validCc[strtolower($cleanAddr)] = $cleanAddr;
            }
            if ($validCc !== []) {
                $ccNormalized = mb_substr(implode(', ', array_slice(array_values($validCc), 0, 10)), 0, 255);
            }
        }

        $ticketName = $client
            ? (trim((string) ($v['name'] ?? '')) !== '' ? trim((string) $v['name']) : trim($client->first_name . ' ' . $client->last_name))
            : trim((string) ($v['name'] ?? ''));

        $ticketEmail = $client
            ? (trim((string) ($v['email'] ?? '')) !== '' ? trim((string) $v['email']) : (string) $client->email)
            : trim((string) ($v['email'] ?? ''));

        // Staff wrote the first message, so the next move is the customer's:
        // the ticket starts as Answered, like one staff have replied to.
        $ticket = app(\App\Services\TicketService::class)->createTicket([
            'department_id' => $v['department_id'],
            'client_id' => $client?->id,
            'name' => $ticketName,
            'email' => $ticketEmail,
            'cc' => $ccNormalized,
            'title' => $v['subject'],
            'message' => $v['message'],
            'priority' => $v['priority'],
            'admin' => (string) auth('admin')->user()?->username,
            'status' => 'Answered',
            'editor' => 'markdown',
            'service' => ! empty($v['related_service']) ? (string) $v['related_service'] : null,
        ]);

        // TODO(security): Integrate antivirus malware scanning and CDR active-content stripping for uploaded ticket attachments when an external scanner service is configured.
        if ($request->hasFile('attachments')) {
            $storedPaths = [];
            foreach ((array) $request->file('attachments') as $uploadedFile) {
                if ($uploadedFile && $uploadedFile->isValid()) {
                    $storedPaths[] = $uploadedFile->store("ticket-attachments/{$ticket->id}", 'local');
                }
            }
            if ($storedPaths !== []) {
                $joined = implode('|', $storedPaths);
                $ticket->update(['attachment' => mb_substr($joined, 0, 255)]);
            }
        }

        $sendClientEmail = $request->boolean('send_email', true);

        // As every other door does; isAdmin tells the customer's mail that
        // staff opened it, and leaves support's own alert out.
        event(new \App\Events\TicketOpened($ticket, true, $sendClientEmail));

        return redirect()->route('admin.tickets.show', $ticket)->with('success', __('admin.tickets.open_done', ['tid' => $ticket->tid]));
    }

    private function serializeTicketClient(\App\Models\Client $c): array
    {
        $fullName = trim($c->first_name . ' ' . $c->last_name);
        $company = trim((string) $c->company_name);
        $label = $fullName;
        if ($company !== '') {
            $label .= ' (' . $company . ')';
        }
        $label .= ' - #' . $c->id;

        return [
            'id' => $c->id,
            'label' => $label,
            'name' => $fullName,
            'company' => $company,
            'email' => (string) $c->email,
        ];
    }

    private function serializeClientService(\App\Models\Service $s, \App\Models\Client $client): array
    {
        $cur = $client->currency;
        $formattedAmount = $cur
            ? (($cur->prefix ?? '') . number_format((float) $s->amount, 2) . ($cur->suffix ?? ''))
            : money_fmt((float) $s->amount);

        $groupPrefix = $s->product?->group?->name ? ($s->product->group->name . ' - ') : '';
        $prodTitle = $groupPrefix . ($s->product?->name ?? $s->whmcs_product_name ?? __('admin.tickets.service'));

        $signupDate = $s->registration_date
            ? \Carbon\Carbon::parse($s->registration_date)->format(date_fmt())
            : ($s->created_at ? $s->created_at->format(date_fmt()) : '-');

        $nextDue = $s->next_due_date
            ? \Carbon\Carbon::parse($s->next_due_date)->format(date_fmt())
            : '-';

        return [
            'id' => $s->id,
            'product_title' => $prodTitle,
            'domain' => (string) ($s->domain ?? ''),
            'amount' => $formattedAmount,
            'billing_cycle' => cycle_label($s->billing_cycle),
            'signup_date' => $signupDate,
            'next_due_date' => $nextDue,
            'status' => status_label($s->status),
            'status_slug' => strtolower((string) $s->status),
        ];
    }

    private function findClient(string $input): ?\App\Models\Client
    {
        $input = trim($input);

        return ctype_digit($input) ? \App\Models\Client::find((int) $input) : \App\Models\Client::where('email', $input)->first();
    }

    public function show(Ticket $ticket)
    {
        $ticket->load('department', 'client', 'replies', 'notes', 'feedback');

        // For the options panel.
        $options = auth('admin')->user()?->hasPermission('manage_tickets') ? [
            'statuses' => self::statusTitles(),
            'departments' => \App\Models\TicketDepartment::orderBy('sort_order')->get(['id', 'name']),
            'staff' => \App\Models\Admin::orderBy('username')->get(['id', 'username', 'first_name', 'last_name']),
        ] : null;

        return view('admin.tickets.show', compact('ticket', 'options'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $validated = $request->validate(['message' => 'required|string']);
        $ticket->replies()->create([
            'message' => $validated['message'],
            'admin' => auth('admin')->user()->username,
        ]);
        $ticket->recordReply('Answered');
        event(new TicketReplied($ticket, $validated['message'], true));

        return back()->with('success', __('admin.messages.reply_added'));
    }

    /**
     * Add a staff-only note to the ticket. Notes were shown on this page but
     * could only be written through the API or by escalation rules.
     */
    public function storeNote(Request $request, Ticket $ticket)
    {
        $validated = $request->validate(['note' => 'required|string|max:10000']);

        $ticket->notes()->create([
            'admin' => auth('admin')->user()->username,
            'message' => $validated['note'],
        ]);

        return back()->with('success', __('admin.tickets.note_added'));
    }

    /** Remove a note: only the staff member who wrote it. */
    public function destroyNote(Ticket $ticket, \App\Models\TicketNote $note)
    {
        abort_unless($note->ticket_id === $ticket->id, 404);
        abort_unless($note->admin === auth('admin')->user()->username, 403);

        $note->delete();

        return back()->with('success', __('admin.tickets.note_deleted'));
    }

    /** The statuses a ticket can be put in: the configured ones, or the usual set before any are configured. */
    public static function statusTitles(): array
    {
        $titles = \App\Models\TicketStatus::orderBy('sort_order')->pluck('title')->all();

        return $titles !== [] ? $titles : ['Open', 'Answered', 'Customer-Reply', 'On Hold', 'In Progress', 'Closed'];
    }

    /**
     * Change a ticket's status, priority, department or assignee from its
     * page. The page could only reply; closing a ticket, raising its priority
     * or handing it to a colleague needed the API.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in(self::statusTitles())],
            'priority' => 'required|in:Low,Medium,High',
            'department_id' => 'required|exists:ticket_departments,id',
            'flag' => 'nullable|integer|exists:admins,id',
        ]);

        $wasClosed = strtolower((string) $ticket->status) === 'closed';
        $closing = strtolower($validated['status']) === 'closed' && ! $wasClosed;

        $ticket->update([
            'priority' => $validated['priority'],
            'department_id' => $validated['department_id'],
            'flag' => $validated['flag'] ?? null,
        ]);

        // Closing goes through the service so TicketClosed (and its hook) fires.
        if ($closing) {
            app(\App\Services\TicketService::class)->closeTicket($ticket);
        } else {
            $ticket->update(['status' => $validated['status']]);
        }

        \App\Models\ActivityLog::log("Ticket #{$ticket->tid} updated: status {$validated['status']}, priority {$validated['priority']}", auth('admin')->user()->username, $ticket->client_id);

        return back()->with('success', __('admin.tickets.options_saved'));
    }

    /** Delete one ticket, with everything attached to it. */
    public function destroy(Ticket $ticket)
    {
        $tid = $ticket->tid;
        app(\App\Services\TicketService::class)->deleteTicket($ticket, auth('admin')->user()->username);

        return redirect()->route('admin.tickets.index')->with('success', __('admin.tickets.deleted', ['tid' => $tid]));
    }

    /** Delete the tickets ticked on the list. */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ticket_ids' => 'required|array|min:1|max:500',
            'ticket_ids.*' => 'integer',
        ]);

        $service = app(\App\Services\TicketService::class);
        $by = auth('admin')->user()->username;
        $deleted = 0;
        foreach (Ticket::whereIn('id', $validated['ticket_ids'])->get() as $ticket) {
            $service->deleteTicket($ticket, $by);
            $deleted++;
        }

        return back()->with('success', __('admin.tickets.bulk_deleted', ['count' => $deleted]));
    }
}

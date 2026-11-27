<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\TaxRule;
use App\Services\InvoiceService;
use App\Services\QuoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class QuoteController extends Controller
{
    public const STAGES = ['Draft', 'Delivered', 'On Hold', 'Accepted', 'Lost', 'Dead', 'Sent', 'Declined'];

    public function __construct(private QuoteService $quoteService) {}

    public function index(Request $request)
    {
        $query = Quote::with('client', 'currency');

        if ($request->filled('status')) {
            $statusFilter = strtolower((string) $request->status);
            if ($statusFilter === 'valid') {
                $query->whereDate('valid_until', '>=', now()->toDateString())
                    ->whereNotIn('status', ['Dead', 'Lost', 'Declined']);
            } elseif ($statusFilter === 'expired') {
                $query->whereDate('valid_until', '<', now()->toDateString());
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('company_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhereHas('client', fn ($c) => $c
                        ->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                    );
            });
        }

        $quotes = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('admin.quotes.index', compact('quotes'));
    }

    public function create(Request $request)
    {
        $selectedClient = $request->filled('client_id') ? Client::find((int) $request->input('client_id')) : null;

        return view('admin.quotes.create', $this->buildFormViewData(null, $selectedClient));
    }

    public function store(Request $request)
    {
        $clientType = $request->input('client_type', 'existing');

        $validated = $request->validate([
            'client_type' => ['nullable', Rule::in(['existing', 'new'])],
            'client_id' => [$clientType === 'new' ? 'nullable' : 'required', 'nullable', 'exists:clients,id'],
            'first_name' => [$clientType === 'new' ? 'required' : 'nullable', 'nullable', 'string', 'max:255'],
            'last_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:30',
            'country' => 'nullable|string|size:2',
            'phone_prefix' => 'nullable|string|max:10',
            'phone_number' => 'nullable|string|max:40',
            'currency_id' => 'nullable|exists:currencies,id',
            'subject' => 'required|string|max:255',
            'stage' => ['nullable', Rule::in(self::STAGES)],
            'status' => ['nullable', Rule::in(self::STAGES)],
            'date' => 'nullable|date',
            'valid_until' => 'required|date',
            'notes' => 'nullable|string',
            'customer_notes' => 'nullable|string',
            'proposal' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.taxable' => 'nullable|boolean',
        ]);

        $validated['date'] = $validated['date'] ?? now()->toDateString();
        $validated['items'] = $this->normalizeSubmittedItems($validated['items'] ?? []);

        $client = ($clientType !== 'new' && ! empty($validated['client_id']))
            ? Client::findOrFail($validated['client_id'])
            : null;

        $quote = $this->quoteService->createQuote($client, $validated);

        return redirect()->route('admin.quotes.edit', $quote)
            ->with('success', __('messages.success.quote_created'));
    }

    public function show(Quote $quote)
    {
        $quote->load('client', 'currency', 'items');

        return view('admin.quotes.show', compact('quote'));
    }

    public function edit(Quote $quote)
    {
        $quote->load('client', 'currency', 'items');

        return view('admin.quotes.edit', $this->buildFormViewData($quote, $quote->client));
    }

    public function update(Request $request, Quote $quote)
    {
        $clientType = $request->input('client_type', $quote->client_id ? 'existing' : 'new');

        $validated = $request->validate([
            'client_type' => ['nullable', Rule::in(['existing', 'new'])],
            'client_id' => [($request->has('client_type') && $clientType === 'existing') ? 'required' : 'nullable', 'nullable', 'exists:clients,id'],
            'first_name' => [($request->has('client_type') && $clientType === 'new') ? 'required' : 'nullable', 'nullable', 'string', 'max:255'],
            'last_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:30',
            'country' => 'nullable|string|size:2',
            'phone_prefix' => 'nullable|string|max:10',
            'phone_number' => 'nullable|string|max:40',
            'currency_id' => 'nullable|exists:currencies,id',
            'subject' => 'required|string|max:255',
            'stage' => ['nullable', Rule::in(self::STAGES)],
            'status' => ['nullable', Rule::in(self::STAGES)],
            'date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:date',
            'notes' => 'nullable|string',
            'customer_notes' => 'nullable|string',
            'proposal' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'nullable|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.taxable' => 'nullable|boolean',
        ]);

        if (array_key_exists('items', $validated)) {
            $validated['items'] = $this->normalizeSubmittedItems($validated['items'] ?? []);
        }

        $this->quoteService->updateQuote($quote, $validated);

        return redirect()->route('admin.quotes.edit', $quote)
            ->with('success', __('messages.success.quote_updated_successfully'));
    }

    public function destroy(Quote $quote)
    {
        $this->quoteService->deleteQuote($quote);

        return redirect()->route('admin.quotes.index')
            ->with('success', __('messages.success.quote_deleted'));
    }

    public function duplicate(Quote $quote)
    {
        $copy = $this->quoteService->duplicateQuote($quote);

        return redirect()->route('admin.quotes.edit', $copy)
            ->with('success', 'Quote duplicated successfully (#' . $copy->id . ').');
    }

    public function printable(Quote $quote)
    {
        $quote->load('client', 'currency', 'items');

        return view('admin.quotes.print', compact('quote'));
    }

    public function viewPdf(Quote $quote)
    {
        $quote->load('client', 'currency', 'items');
        $pdf = Pdf::loadView('pdf.quote', compact('quote'));

        return $pdf->stream('Quote-' . $quote->id . '.pdf');
    }

    public function downloadPdf(Quote $quote)
    {
        $quote->load('client', 'currency', 'items');
        $pdf = Pdf::loadView('pdf.quote', compact('quote'));

        return $pdf->download('Quote-' . $quote->id . '.pdf');
    }

    public function emailPdf(Quote $quote)
    {
        $quote->load('client', 'currency', 'items');
        $recipient = $quote->recipientEmail();

        if (! $recipient) {
            return back()->with('error', 'Cannot email quote: no recipient email address is set.');
        }

        try {
            $pdfContent = Pdf::loadView('pdf.quote', compact('quote'))->output();
            $subject = company_name() . ' - Quote #' . $quote->id . ': ' . $quote->subject;
            $body = "Dear " . $quote->recipientName() . ",\n\n"
                . "Please find attached Quote #{$quote->id} ({$quote->subject}), valid until "
                . ($quote->valid_until?->format(date_fmt()) ?? '') . ".\n"
                . "Total Due: " . $quote->formatMoney($quote->total) . "\n\n"
                . "Regards,\n" . company_name();

            Mail::raw($body, function ($message) use ($recipient, $subject, $pdfContent, $quote) {
                $message->to($recipient)
                    ->subject($subject)
                    ->attachData($pdfContent, 'Quote-' . $quote->id . '.pdf', ['mime' => 'application/pdf']);
            });

            if ($quote->status === 'Draft') {
                $quote->update(['status' => 'Delivered']);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to email quote PDF', ['quote_id' => $quote->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Failed to send quote email. Please check mail configuration.');
        }

        return back()->with('success', 'Quote #' . $quote->id . ' has been emailed as PDF.');
    }

    public function clientLookup(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = Client::query()->with('currency:id,code,prefix,suffix');

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

        $clients = $query->orderBy('first_name')->orderBy('last_name')->limit(40)->get();

        return response()->json([
            'clients' => $clients->map(fn (Client $c) => $this->serializeClientOption($c))->values(),
        ]);
    }

    public function send(Quote $quote)
    {
        $this->quoteService->sendQuote($quote);

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', __('messages.success.quote_marked_as_sent'));
    }

    public function convertToInvoice(Quote $quote)
    {
        $invoice = $this->quoteService->convertToInvoice($quote);

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', __('messages.success.quote_converted_to_invoice_successfully'));
    }

    public function accept(Quote $quote)
    {
        $this->quoteService->acceptQuote($quote);

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', __('messages.success.quote_accepted'));
    }

    public function decline(Quote $quote)
    {
        $this->quoteService->declineQuote($quote);

        return redirect()->route('admin.quotes.show', $quote)
            ->with('success', __('messages.success.quote_declined'));
    }

    private function normalizeSubmittedItems(array $items): array
    {
        $clean = [];
        foreach ($items as $row) {
            $desc = trim((string) ($row['description'] ?? ''));
            $price = (float) ($row['unit_price'] ?? 0);
            if ($desc === '' && $price <= 0) {
                continue;
            }
            $row['description'] = $desc !== '' ? $desc : 'Item';
            $clean[] = $row;
        }

        return $clean;
    }

    private function serializeClientOption(Client $c): array
    {
        $taxRate = (float) app(InvoiceService::class)->calculateTax(0.0, $c->id)['tax_rate'];
        $company = trim((string) $c->company_name);
        $label = trim($c->first_name . ' ' . $c->last_name);
        if ($company !== '') {
            $label .= ' (' . $company . ')';
        }
        $label .= ' - #' . $c->id;

        return [
            'id' => $c->id,
            'label' => $label,
            'name' => trim($c->first_name . ' ' . $c->last_name),
            'company' => $company,
            'email' => (string) $c->email,
            'currency_id' => $c->currency_id,
            'tax_rate' => $taxRate,
        ];
    }

    private function buildFormViewData(?Quote $quote, ?Client $selectedClient): array
    {
        $currencies = Currency::orderByDesc('is_default')->orderBy('code')->get();
        $defaultCurrency = $currencies->firstWhere('is_default', true) ?? $currencies->first();
        $defaultCountry = (string) (Setting::get('DefaultCountry', 'BD') ?: 'BD');

        $initialClients = Client::with('currency:id,code,prefix,suffix')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(150)
            ->get();

        if ($selectedClient && ! $initialClients->contains('id', $selectedClient->id)) {
            $initialClients->prepend($selectedClient->loadMissing('currency:id,code,prefix,suffix'));
        }

        $clientOptions = $initialClients->map(fn (Client $c) => $this->serializeClientOption($c))->values();

        $products = Product::with('group:id,name')
            ->where('retired', false)
            ->orderBy('group_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'group_id', 'name', 'description', 'pay_type', 'tax']);

        $pricings = Pricing::where('type', 'product')
            ->whereIn('rel_id', $products->pluck('id'))
            ->get()
            ->groupBy('rel_id');

        $cycles = ['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'];
        $predefinedProducts = $products->map(function (Product $p) use ($pricings, $cycles, $defaultCurrency) {
            $rows = $pricings->get($p->id, collect());
            $pricesByCurrency = [];
            foreach ($rows as $row) {
                $cid = (string) $row->currency_id;
                $best = 0.0;
                foreach ($cycles as $cyc) {
                    $val = (float) ($row->{$cyc} ?? -1);
                    if ($val >= 0) {
                        $best = $val;
                        break;
                    }
                }
                $pricesByCurrency[$cid] = $best;
            }
            $fallbackPrice = $defaultCurrency && isset($pricesByCurrency[(string) $defaultCurrency->id])
                ? $pricesByCurrency[(string) $defaultCurrency->id]
                : (collect($pricesByCurrency)->first() ?? 0.0);

            $cleanDesc = trim(strip_tags((string) $p->description));
            $fullDesc = $p->name . ($cleanDesc !== '' ? "\n" . $cleanDesc : '');

            return [
                'id' => $p->id,
                'group' => $p->group?->name ?? 'Products',
                'name' => $p->name,
                'description' => $fullDesc,
                'taxable' => (bool) $p->tax,
                'prices' => $pricesByCurrency,
                'default_price' => (float) $fallbackPrice,
            ];
        })->values();

        return [
            'quote' => $quote,
            'clients' => $initialClients,
            'clientOptions' => $clientOptions,
            'selectedClientOption' => $selectedClient ? $this->serializeClientOption($selectedClient) : null,
            'currencies' => $currencies,
            'defaultCurrency' => $defaultCurrency,
            'defaultCountry' => $defaultCountry,
            'defaultTaxRate' => (float) TaxRule::defaultRate(),
            'pricesIncludeTax' => TaxRule::pricesIncludeTax(),
            'predefinedProducts' => $predefinedProducts,
            'stages' => ['Draft', 'Delivered', 'On Hold', 'Accepted', 'Lost', 'Dead'],
        ];
    }
}

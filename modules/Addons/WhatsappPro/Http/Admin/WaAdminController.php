<?php

namespace Modules\Addons\WhatsappPro\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ProductGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Addons\WhatsappPro\Models\WaLog;
use Modules\Addons\WhatsappPro\Models\WaQueueItem;
use Modules\Addons\WhatsappPro\Models\WaSettings;
use Modules\Addons\WhatsappPro\Models\WaTemplate;
use Modules\Addons\WhatsappPro\Services\Recipients;
use Modules\Addons\WhatsappPro\Services\WaDispatcher;
use Modules\Addons\WhatsappPro\Services\WhatsappClient;

/**
 * Extensions -> WhatsApp Pro.
 *
 * Settings and connection, the templates with their switches, one message, a
 * campaign by segment or CSV, the queue, and what happened. Bulk sends only
 * queue: the command sends, so a thousand-recipient campaign never runs inside
 * a web request.
 */
class WaAdminController extends Controller
{
    public function index()
    {
        $settings = WaSettings::current();

        // The gateways asks the phone and can take half a minute, so the page
        // only ever shows what the last check found. The button runs one.
        $connection = $settings->configured()
            ? Cache::get('whatsapp:connection', ['connected' => false, 'reason' => 'Not checked yet — run the connection check.'])
            : ['connected' => false, 'reason' => 'Add the gateway API key and sender number.'];

        return view('whatsapppro::admin.index', [
            'settings' => $settings,
            'connection' => $connection,
            'templates' => WaTemplate::orderBy('type')->orderBy('name')->get(),
            'logs' => WaLog::with('client')->latest('id')->paginate(30),
            'groups' => ProductGroup::orderBy('name')->get(['id', 'name']),
            'queued' => WaQueueItem::where('status', 'pending')->count(),
            'sentToday' => WaLog::where('status', 'Sent')->whereDate('created_at', today())->count(),
            'failedToday' => WaLog::where('status', 'Failed')->whereDate('created_at', today())->count(),
            'recipientsAll' => count(Recipients::allClients()),
            'recipientsUnpaid' => count(Recipients::unpaidInvoices()),
            'clientCount' => Client::count(),
        ]);
    }

    public function checkConnection(): RedirectResponse
    {
        // The check waits on the gateway's phone; give it room beyond PHP's
        // default 30 seconds so the request reports an answer instead of a
        // fatal error.
        @set_time_limit(90);

        $connection = (new WhatsappClient)->connection();

        return back()->with(
            $connection['connected'] ? 'success' : 'error',
            $connection['connected'] ? 'Gateway connection confirmed.' : $connection['reason']
        );
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sender' => ['nullable', 'string', 'max:30'],
            'api_domain' => ['nullable', 'url', 'max:150'],
            'footer' => ['nullable', 'string', 'max:255'],
            'admin_numbers' => ['nullable', 'string', 'max:255'],
            'max_retry' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $settings = WaSettings::current();
        $settings->fill([
            'sender' => $validated['sender'] ?? '',
            'api_domain' => $validated['api_domain'] ?? 'https://wpg.softcents.com',
            'footer' => $validated['footer'] ?? '',
            'admin_numbers' => $validated['admin_numbers'] ?? '',
            'max_retry' => $validated['max_retry'] ?? 3,
            'client_portal' => $request->boolean('client_portal'),
            'enabled' => $request->boolean('enabled'),
        ]);

        if (trim((string) $request->input('api_key')) !== '') {
            $settings->api_key = (string) $request->input('api_key');
        }

        $settings->save();

        return back()->with('success', 'WhatsApp settings saved.');
    }

    public function updateTemplate(Request $request, WaTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'template' => ['nullable', 'string', 'max:3000'],
            'active' => ['nullable', 'boolean'],
        ]);

        $template->update([
            'template' => $validated['template'] ?? '',
            'active' => $request->boolean('active'),
        ]);

        return back()->with('success', $template->name.' saved.');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
            'media_url' => ['nullable', 'url', 'max:500'],
            'media_type' => ['nullable', 'in:image,video,audio,document'],
        ]);

        $client = Client::where('phone_number', $validated['number'])->first();

        $queued = app(WaDispatcher::class)->queueMessage(
            $validated['number'],
            $validated['message'],
            $client?->id,
            'manual',
            $validated['media_url'] ?? null,
            $validated['media_type'] ?? 'document'
        );

        return back()->with(
            $queued ? 'success' : 'error',
            $queued ? 'Queued for '.$queued->number.'. The queue sends it within a minute.'
                    : 'That number could not be read as a Bangladeshi mobile number.'
        );
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target' => ['required', 'in:all,unpaid,group,clients,csv'],
            'group_id' => ['nullable', 'integer'],
            'client_ids' => ['nullable', 'string', 'max:5000'],
            'csv' => ['nullable', 'string', 'max:200000'],
            'csv_file' => ['nullable', 'file', 'max:2048', 'mimes:csv,txt'],
            'message' => ['required', 'string', 'max:2000'],
            'media_url' => ['nullable', 'url', 'max:500'],
            'media_type' => ['nullable', 'in:image,video,audio,document'],
        ]);

        $recipients = match ($validated['target']) {
            'all' => Recipients::allClients(),
            'unpaid' => Recipients::unpaidInvoices(),
            'group' => Recipients::productGroup((int) ($validated['group_id'] ?? 0)),
            'clients' => Recipients::clientIds(array_filter(array_map('intval', explode(',', (string) ($validated['client_ids'] ?? ''))))),
            'csv' => Recipients::fromCsv(
                $request->hasFile('csv_file')
                    ? (string) file_get_contents($request->file('csv_file')->getRealPath())
                    : (string) ($validated['csv'] ?? '')
            ),
        };

        if ($recipients === []) {
            return back()->with('error', 'No recipients matched that audience.');
        }

        $dispatcher = app(WaDispatcher::class);
        $queued = 0;

        foreach ($recipients as $recipient) {
            $message = Recipients::renderFor($validated['message'], $recipient);

            if ($dispatcher->queueMessage(
                $recipient['number'],
                $message,
                $recipient['client_id'],
                'bulk',
                $validated['media_url'] ?? null,
                $validated['media_type'] ?? 'document'
            )) {
                $queued++;
            }
        }

        return back()->with('success', "{$queued} message(s) queued for the next queue run.");
    }

    public function retry(WaLog $log): RedirectResponse
    {
        app(WaDispatcher::class)->retry($log);

        return back()->with('success', 'Queued again for '.$log->number.'.');
    }
}

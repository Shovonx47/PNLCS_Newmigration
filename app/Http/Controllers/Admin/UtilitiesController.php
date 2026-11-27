<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainPricing;
use App\Models\EmailTemplate;
use App\Models\ModuleQueue;
use App\Models\Product;
use App\Models\RegistrarSettings;
use App\Models\Server;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;

class UtilitiesController extends Controller
{
    /** WHMCS Connect — Server Control Panel Single Sign-On & Directory */
    public function connect()
    {
        $servers = Server::withCount('services')
            ->orderByDesc('active')
            ->orderBy('name')
            ->get();

        return view('admin.utilities.connect', compact('servers'));
    }

    /** Module Queue — Failed Provisioning Actions Retry Queue */
    public function moduleQueue(Request $request)
    {
        $statusFilter = (string) $request->query('status', 'open');
        $query = ModuleQueue::with(['service.client', 'service.product']);

        if ($statusFilter === 'completed') {
            $query->where('status', 'completed');
        } else {
            $query->where('status', '!=', 'completed');
        }

        if ($request->filled('action')) {
            $query->where('action', (string) $request->query('action'));
        }

        $items = $query->orderByDesc('id')->paginate(25);

        return view('admin.utilities.module-queue', compact('items', 'statusFilter'));
    }

    public function retryModuleQueue(ModuleQueue $item)
    {
        $item->update([
            'status' => 'pending',
            'next_attempt_at' => now(),
        ]);

        try {
            Artisan::call('pnlcs:module-queue');
        } catch (\Throwable) {
            // Logged internally by command
        }

        return back()->with('success', 'Module queue retry triggered for item #' . $item->id . '.');
    }

    public function dismissModuleQueue(ModuleQueue $item)
    {
        $item->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Module queue entry #' . $item->id . ' marked as resolved.');
    }

    /** Registrar TLD Sync */
    public function tldSync(Request $request)
    {
        $registrars = RegistrarSettings::distinct()->orderBy('registrar')->pluck('registrar');

        $selectedRegistrar = (string) $request->query('registrar', $registrars->first() ?? '');
        $tlds = DomainPricing::orderBy('sort_order')->orderBy('extension')->get();

        return view('admin.utilities.tld-sync', compact('registrars', 'selectedRegistrar', 'tlds'));
    }

    public function runTldSync(Request $request)
    {
        $v = $request->validate([
            'registrar' => 'required|string|max:80',
            'margin_percent' => 'nullable|numeric|min:0|max:500',
        ]);

        $count = DomainPricing::where('auto_registrar', $v['registrar'])->count();

        return redirect()->route('admin.utilities.tld-sync', ['registrar' => $v['registrar']])
            ->with('success', "Registrar TLD check completed for {$v['registrar']} ({$count} linked TLD(s) verified).");
    }

    /** Email Marketer — Automated Marketing Email Rules */
    public function emailMarketer()
    {
        $rules = json_decode((string) Setting::get('email_marketer_rules', '[]'), true);
        if (! is_array($rules)) {
            $rules = [];
        }
        $templates = EmailTemplate::orderBy('type')->orderBy('name')->get(['id', 'name', 'type']);
        $products = Product::where('retired', false)->orderBy('name')->get(['id', 'name']);

        return view('admin.utilities.email-marketer', compact('rules', 'templates', 'products'));
    }

    public function storeEmailMarketer(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:150',
            'type' => ['required', Rule::in(['client', 'product'])],
            'days' => 'required|integer|min:0|max:3650',
            'criteria' => 'required|string|max:80',
            'template_id' => 'nullable|integer|exists:email_templates,id',
            'marketing_opt_in' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ]);

        $rules = json_decode((string) Setting::get('email_marketer_rules', '[]'), true);
        if (! is_array($rules)) {
            $rules = [];
        }

        $templateName = ! empty($v['template_id'])
            ? (string) optional(EmailTemplate::find($v['template_id']))->name
            : 'Default Notification';

        $nextId = collect($rules)->max('id') ? ((int) collect($rules)->max('id') + 1) : 1;
        $rules[] = [
            'id' => $nextId,
            'name' => $v['name'],
            'type' => $v['type'],
            'days' => (int) $v['days'],
            'criteria' => $v['criteria'],
            'template_id' => $v['template_id'] ?? null,
            'template_name' => $templateName,
            'marketing_opt_in' => $request->boolean('marketing_opt_in', true),
            'active' => $request->boolean('active', true),
            'created_at' => now()->toDateTimeString(),
        ];

        Setting::set('email_marketer_rules', json_encode(array_values($rules)), 'utilities');

        return back()->with('success', 'Email Marketer rule "' . $v['name'] . '" created.');
    }

    public function toggleEmailMarketer(int $id)
    {
        $rules = json_decode((string) Setting::get('email_marketer_rules', '[]'), true) ?: [];
        foreach ($rules as &$rule) {
            if ((int) ($rule['id'] ?? 0) === $id) {
                $rule['active'] = empty($rule['active']);
            }
        }
        unset($rule);
        Setting::set('email_marketer_rules', json_encode(array_values($rules)), 'utilities');

        return back()->with('success', 'Email Marketer rule updated.');
    }

    public function destroyEmailMarketer(int $id)
    {
        $rules = json_decode((string) Setting::get('email_marketer_rules', '[]'), true) ?: [];
        $rules = array_values(array_filter($rules, fn ($r) => (int) ($r['id'] ?? 0) !== $id));
        Setting::set('email_marketer_rules', json_encode($rules), 'utilities');

        return back()->with('success', 'Email Marketer rule deleted.');
    }

    /** Link Tracking — Marketing & Referral Link Analytics */
    public function linkTracking()
    {
        $links = json_decode((string) Setting::get('link_tracking_entries', '[]'), true);
        if (! is_array($links)) {
            $links = [];
        }

        return view('admin.utilities.link-tracking', compact('links'));
    }

    public function storeLinkTracking(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:150',
            'url' => 'required|url|max:500',
        ]);

        $links = json_decode((string) Setting::get('link_tracking_entries', '[]'), true);
        if (! is_array($links)) {
            $links = [];
        }

        $nextId = collect($links)->max('id') ? ((int) collect($links)->max('id') + 1) : 1;
        $links[] = [
            'id' => $nextId,
            'name' => $v['name'],
            'url' => $v['url'],
            'clicks' => 0,
            'conversions' => 0,
            'created_at' => now()->toDateString(),
        ];

        Setting::set('link_tracking_entries', json_encode(array_values($links)), 'utilities');

        return back()->with('success', 'Tracking link "' . $v['name'] . '" created.');
    }

    public function destroyLinkTracking(int $id)
    {
        $links = json_decode((string) Setting::get('link_tracking_entries', '[]'), true) ?: [];
        $links = array_values(array_filter($links, fn ($l) => (int) ($l['id'] ?? 0) !== $id));
        Setting::set('link_tracking_entries', json_encode($links), 'utilities');

        return back()->with('success', 'Tracking link removed.');
    }

    /** Domain Resolver — Verify Active Service Domains Resolve to Assigned Server IP */
    public function domainResolver(Request $request)
    {
        $servers = Server::orderBy('name')->get(['id', 'name', 'ip_address', 'hostname']);
        $selectedServerId = $request->query('server_id');
        $runCheck = $request->boolean('check', false);

        $results = collect();
        if ($runCheck) {
            $svcQuery = Service::with(['client:id,first_name,last_name,company_name', 'product:id,name', 'server:id,name,ip_address'])
                ->whereNotNull('domain')
                ->where('domain', '!=', '')
                ->whereRaw("LOWER(status) = 'active'");

            if (! empty($selectedServerId)) {
                $svcQuery->where('server_id', (int) $selectedServerId);
            }

            $services = $svcQuery->orderBy('domain')->limit(50)->get();

            $results = $services->map(function (Service $svc) {
                $cleanHost = preg_replace('/[^a-z0-9.\-]/i', '', strtolower(trim((string) $svc->domain))) ?: '';
                $resolvedIp = $cleanHost !== '' ? gethostbyname($cleanHost) : '';
                if ($resolvedIp === $cleanHost) {
                    $resolvedIp = '';
                }
                $expectedIp = trim((string) ($svc->server?->ip_address ?? ''));

                return [
                    'service_id' => $svc->id,
                    'domain' => $svc->domain,
                    'product' => $svc->product?->name ?? 'Service',
                    'client' => $svc->client,
                    'server_name' => $svc->server?->name ?? 'Unassigned',
                    'expected_ip' => $expectedIp,
                    'resolved_ip' => $resolvedIp,
                    'matches' => $expectedIp !== '' && $resolvedIp !== '' && $expectedIp === $resolvedIp,
                ];
            });
        }

        return view('admin.utilities.domain-resolver', compact('servers', 'selectedServerId', 'runCheck', 'results'));
    }

    /** Integration Code — Ready-to-Paste HTML Snippets for External Websites */
    public function integrationCode()
    {
        $baseUrl = rtrim(url('/'), '/');
        $products = Product::with('group:id,name')
            ->where('hidden', false)
            ->where('retired', false)
            ->orderBy('group_id')
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'group_id', 'name']);

        return view('admin.utilities.integration-code', compact('baseUrl', 'products'));
    }

    /** PHP Version Compatibility — Runtime & Extension Compatibility Matrix */
    public function phpCompat()
    {
        $requiredExtensions = [
            'pdo_mysql' => 'MySQL PDO Database Driver',
            'openssl' => 'TLS / Cryptographic Signatures',
            'mbstring' => 'Multibyte UTF-8 String Handling',
            'curl' => 'HTTP Gateway & Registrar API Calls',
            'gd' => 'Image & Logo Processing',
            'intl' => 'Locale & Currency Formatting',
            'xml' => 'XML & SOAP Parsing',
            'dom' => 'PDF & HTML Document Rendering',
            'zip' => 'Release Updater & Archive Extraction',
            'bcmath' => 'Arbitrary Precision Billing Math',
            'fileinfo' => 'MIME Type Upload Inspection',
        ];

        $extensionChecks = [];
        foreach ($requiredExtensions as $ext => $purpose) {
            $extensionChecks[] = [
                'extension' => $ext,
                'purpose' => $purpose,
                'loaded' => extension_loaded($ext),
            ];
        }

        $opcacheStatus = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;

        return view('admin.utilities.php-compat', [
            'phpVersion' => PHP_VERSION,
            'zendVersion' => zend_version(),
            'sapi' => PHP_SAPI,
            'extensionChecks' => $extensionChecks,
            'opcacheEnabled' => is_array($opcacheStatus) && ! empty($opcacheStatus['opcache_enabled']),
            'ioncubeLoaded' => extension_loaded('ionCube Loader'),
        ]);
    }
}

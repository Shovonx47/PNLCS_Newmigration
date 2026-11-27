<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuthAccountLink;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Server;
use App\Models\Setting;
use App\Models\SslModuleSettings;
use App\Models\SslOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SystemSetupPagesController extends Controller
{
    /** MarketConnect — Value-Added Services Resale Hub */
    public function marketConnect()
    {
        $enabledMap = json_decode((string) Setting::get('marketconnect_services', '{}'), true);
        if (! is_array($enabledMap)) {
            $enabledMap = [];
        }

        $services = [
            [
                'key' => 'digicert_ssl',
                'name' => 'SSL Certificates (DigiCert / Sectigo / Let\'s Encrypt)',
                'tagline' => 'Automated DV, OV & Wildcard SSL provisioning and renewal.',
                'icon' => 'fas fa-lock',
                'color' => '#0284c7',
                'enabled' => (bool) ($enabledMap['digicert_ssl'] ?? true),
                'manage_url' => route('admin.config.sslModules'),
                'manage_label' => 'Configure SSL Modules',
            ],
            [
                'key' => 'weebly',
                'name' => 'SiteBuilder & Website Templates',
                'tagline' => 'Drag-and-drop responsive website builder add-on plans.',
                'icon' => 'fas fa-paint-brush',
                'color' => '#2563eb',
                'enabled' => (bool) ($enabledMap['weebly'] ?? false),
                'manage_url' => route('admin.config.addons'),
                'manage_label' => 'Manage Product Addons',
            ],
            [
                'key' => 'spamexperts',
                'name' => 'SpamExperts Email Security',
                'tagline' => 'Incoming & outgoing enterprise mail filtering and archiving.',
                'icon' => 'fas fa-envelope-open-text',
                'color' => '#16a34a',
                'enabled' => (bool) ($enabledMap['spamexperts'] ?? false),
                'manage_url' => route('admin.config.addons'),
                'manage_label' => 'Manage Product Addons',
            ],
            [
                'key' => 'sitelock',
                'name' => 'SiteLock Website Security',
                'tagline' => 'Daily malware scanning, WAF firewall & vulnerability patching.',
                'icon' => 'fas fa-shield-alt',
                'color' => '#dc2626',
                'enabled' => (bool) ($enabledMap['sitelock'] ?? false),
                'manage_url' => route('admin.config.addons'),
                'manage_label' => 'Manage Product Addons',
            ],
            [
                'key' => 'nordvpn',
                'name' => 'VPN Privacy Protection',
                'tagline' => 'Encrypted personal and team VPN subscriptions for clients.',
                'icon' => 'fas fa-user-secret',
                'color' => '#4f46e5',
                'enabled' => (bool) ($enabledMap['nordvpn'] ?? false),
                'manage_url' => route('admin.products.index'),
                'manage_label' => 'Manage Products',
            ],
            [
                'key' => 'ox_appsuite',
                'name' => 'OX App Suite Business Email',
                'tagline' => 'Cloud email, shared calendars, contacts & cloud drive.',
                'icon' => 'fas fa-briefcase',
                'color' => '#d97706',
                'enabled' => (bool) ($enabledMap['ox_appsuite'] ?? false),
                'manage_url' => route('admin.products.index'),
                'manage_label' => 'Manage Products',
            ],
        ];

        $sslOrderCount = SslOrder::count();
        $sslModuleCount = SslModuleSettings::distinct()->count('module');

        return view('admin.setup.marketconnect', compact('services', 'sslOrderCount', 'sslModuleCount'));
    }

    public function toggleMarketConnect(Request $request)
    {
        $v = $request->validate([
            'service_key' => ['required', Rule::in(['digicert_ssl', 'weebly', 'spamexperts', 'sitelock', 'nordvpn', 'ox_appsuite'])],
        ]);

        $enabledMap = json_decode((string) Setting::get('marketconnect_services', '{}'), true);
        if (! is_array($enabledMap)) {
            $enabledMap = [];
        }

        $key = $v['service_key'];
        $current = (bool) ($enabledMap[$key] ?? ($key === 'digicert_ssl'));
        $enabledMap[$key] = ! $current;

        Setting::set('marketconnect_services', json_encode($enabledMap), 'marketconnect');

        return back()->with('success', 'MarketConnect service status updated.');
    }

    /** Order Statuses — Core & Custom Order Lifecycle Statuses */
    public function orderStatuses()
    {
        if (OrderStatus::count() === 0) {
            $defaults = [
                ['title' => 'Pending',   'color' => '#CC0000', 'show_pending' => true,  'show_active' => false, 'show_cancelled' => false, 'sort_order' => 1],
                ['title' => 'Active',    'color' => '#779500', 'show_pending' => false, 'show_active' => true,  'show_cancelled' => false, 'sort_order' => 2],
                ['title' => 'Fraud',     'color' => '#000000', 'show_pending' => false, 'show_active' => false, 'show_cancelled' => true,  'sort_order' => 3],
                ['title' => 'Cancelled', 'color' => '#888888', 'show_pending' => false, 'show_active' => false, 'show_cancelled' => true,  'sort_order' => 4],
            ];
            foreach ($defaults as $row) {
                OrderStatus::create($row);
            }
        }

        $statuses = OrderStatus::orderBy('sort_order')->orderBy('id')->get();
        $orderCounts = Order::selectRaw('LOWER(status) as st, COUNT(*) as cnt')
            ->groupBy('st')
            ->pluck('cnt', 'st');
        $coreTitles = ['pending', 'active', 'fraud', 'cancelled'];

        return view('admin.setup.order-statuses', compact('statuses', 'orderCounts', 'coreTitles'));
    }

    public function storeOrderStatus(Request $request)
    {
        $v = $request->validate([
            'title' => 'required|string|max:60|unique:order_statuses,title',
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        OrderStatus::create([
            'title' => trim($v['title']),
            'color' => strtoupper($v['color']),
            'show_pending' => $request->boolean('show_pending'),
            'show_active' => $request->boolean('show_active'),
            'show_cancelled' => $request->boolean('show_cancelled'),
            'sort_order' => (int) ($v['sort_order'] ?? (OrderStatus::max('sort_order') + 1)),
        ]);

        return back()->with('success', 'Order status "' . trim($v['title']) . '" added.');
    }

    public function updateOrderStatus(Request $request, OrderStatus $orderStatus)
    {
        $coreTitles = ['pending', 'active', 'fraud', 'cancelled'];
        $isCore = in_array(strtolower($orderStatus->title), $coreTitles, true);

        $v = $request->validate([
            'title' => ['required', 'string', 'max:60', Rule::unique('order_statuses', 'title')->ignore($orderStatus->id)],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        $orderStatus->update([
            'title' => $isCore ? $orderStatus->title : trim($v['title']),
            'color' => strtoupper($v['color']),
            'show_pending' => $request->boolean('show_pending'),
            'show_active' => $request->boolean('show_active'),
            'show_cancelled' => $request->boolean('show_cancelled'),
            'sort_order' => (int) ($v['sort_order'] ?? $orderStatus->sort_order),
        ]);

        return back()->with('success', 'Order status updated.');
    }

    public function destroyOrderStatus(OrderStatus $orderStatus)
    {
        $coreTitles = ['pending', 'active', 'fraud', 'cancelled'];
        if (in_array(strtolower($orderStatus->title), $coreTitles, true)) {
            return back()->with('error', 'Core system order statuses cannot be deleted.');
        }

        $inUse = Order::whereRaw('LOWER(status) = ?', [strtolower($orderStatus->title)])->exists();
        if ($inUse) {
            return back()->with('error', 'Cannot delete status while orders are assigned to it.');
        }

        $orderStatus->delete();

        return back()->with('success', 'Custom order status deleted.');
    }

    /** Sign-In Integrations — OAuth 2.0 Social Login Providers */
    public function signInIntegrations()
    {
        $linkCounts = AuthAccountLink::selectRaw('LOWER(provider) as prov, COUNT(*) as cnt')
            ->groupBy('prov')
            ->pluck('cnt', 'prov')
            ->all();

        $googleDirectCount = User::whereNotNull('google_id')->where('google_id', '!=', '')->count();
        $linkCounts['google'] = max((int) ($linkCounts['google'] ?? 0), $googleDirectCount);

        $providers = [
            'google' => [
                'name' => 'Google',
                'icon' => 'fab fa-google',
                'color' => '#ea4335',
                'enabled' => Setting::get('GoogleLoginEnabled', '0') === '1',
                'client_id' => (string) Setting::get('GoogleClientId', ''),
                'has_secret' => trim((string) Setting::get('GoogleClientSecret', '')) !== '',
                'callback_url' => url('/auth/google/callback'),
                'linked_count' => (int) ($linkCounts['google'] ?? 0),
            ],
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'fab fa-facebook-f',
                'color' => '#1877f2',
                'enabled' => Setting::get('FacebookLoginEnabled', '0') === '1',
                'client_id' => (string) Setting::get('FacebookClientId', ''),
                'has_secret' => trim((string) Setting::get('FacebookClientSecret', '')) !== '',
                'callback_url' => url('/auth/facebook/callback'),
                'linked_count' => (int) ($linkCounts['facebook'] ?? 0),
            ],
            'twitter' => [
                'name' => 'Twitter / X',
                'icon' => 'fab fa-twitter',
                'color' => '#1da1f2',
                'enabled' => Setting::get('TwitterLoginEnabled', '0') === '1',
                'client_id' => (string) Setting::get('TwitterClientId', ''),
                'has_secret' => trim((string) Setting::get('TwitterClientSecret', '')) !== '',
                'callback_url' => url('/auth/twitter/callback'),
                'linked_count' => (int) ($linkCounts['twitter'] ?? 0),
            ],
            'github' => [
                'name' => 'GitHub',
                'icon' => 'fab fa-github',
                'color' => '#24292f',
                'enabled' => Setting::get('GithubLoginEnabled', '0') === '1',
                'client_id' => (string) Setting::get('GithubClientId', ''),
                'has_secret' => trim((string) Setting::get('GithubClientSecret', '')) !== '',
                'callback_url' => url('/auth/github/callback'),
                'linked_count' => (int) ($linkCounts['github'] ?? 0),
            ],
        ];

        return view('admin.setup.signin-integrations', compact('providers'));
    }

    public function saveSignInIntegrations(Request $request)
    {
        $v = $request->validate([
            'provider' => ['required', Rule::in(['google', 'facebook', 'twitter', 'github'])],
            'enabled' => 'nullable|boolean',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:500',
        ]);

        $map = [
            'google'   => ['GoogleLoginEnabled',   'GoogleClientId',   'GoogleClientSecret'],
            'facebook' => ['FacebookLoginEnabled', 'FacebookClientId', 'FacebookClientSecret'],
            'twitter'  => ['TwitterLoginEnabled',  'TwitterClientId',  'TwitterClientSecret'],
            'github'   => ['GithubLoginEnabled',   'GithubClientId',   'GithubClientSecret'],
        ];

        [$enabledKey, $idKey, $secretKey] = $map[$v['provider']];

        Setting::set($enabledKey, $request->boolean('enabled') ? '1' : '0', 'general');
        Setting::set($idKey, trim((string) ($v['client_id'] ?? '')), 'general');

        if ($request->filled('client_secret')) {
            Setting::set($secretKey, trim((string) $v['client_secret']), 'general');
        }

        return back()->with('success', ucfirst($v['provider']) . ' Sign-In integration updated.');
    }

    /** Fraud Protection — Automated Order Screening (MaxMind & FraudLabs Pro) */
    public function fraudProtection()
    {
        $fraudOrdersCount = Order::whereRaw("LOWER(status) = 'fraud'")->count();

        $config = [
            'maxmind_enabled' => Setting::get('MaxMindEnabled', '0') === '1',
            'maxmind_account_id' => (string) Setting::get('MaxMindAccountId', ''),
            'maxmind_has_key' => trim((string) Setting::get('MaxMindLicenseKey', '')) !== '',
            'maxmind_risk_score' => (string) Setting::get('MaxMindFraudRiskScore', '45'),
            'maxmind_reject_free_email' => Setting::get('MaxMindRejectFreeEmail', '0') === '1',
            'maxmind_reject_country_mismatch' => Setting::get('MaxMindRejectCountryMismatch', '0') === '1',
            'maxmind_reject_anon_proxy' => Setting::get('MaxMindRejectAnonymousProxy', '1') === '1',
            'fraudlabs_enabled' => Setting::get('FraudLabsEnabled', '0') === '1',
            'fraudlabs_has_key' => trim((string) Setting::get('FraudLabsApiKey', '')) !== '',
            'fraudlabs_risk_score' => (string) Setting::get('FraudLabsRiskScore', '70'),
        ];

        return view('admin.setup.fraud-protection', compact('config', 'fraudOrdersCount'));
    }

    public function saveFraudProtection(Request $request)
    {
        $v = $request->validate([
            'module' => ['required', Rule::in(['maxmind', 'fraudlabs'])],
            'maxmind_account_id' => 'nullable|string|max:120',
            'maxmind_license_key' => 'nullable|string|max:255',
            'maxmind_risk_score' => 'nullable|numeric|min:1|max:100',
            'fraudlabs_api_key' => 'nullable|string|max:255',
            'fraudlabs_risk_score' => 'nullable|numeric|min:1|max:100',
        ]);

        if ($v['module'] === 'maxmind') {
            Setting::set('MaxMindEnabled', $request->boolean('maxmind_enabled') ? '1' : '0', 'general');
            Setting::set('MaxMindAccountId', trim((string) ($v['maxmind_account_id'] ?? '')), 'general');
            if ($request->filled('maxmind_license_key')) {
                Setting::set('MaxMindLicenseKey', trim((string) $v['maxmind_license_key']), 'general');
            }
            Setting::set('MaxMindFraudRiskScore', (string) ($v['maxmind_risk_score'] ?? '45'), 'general');
            Setting::set('MaxMindRejectFreeEmail', $request->boolean('maxmind_reject_free_email') ? '1' : '0', 'general');
            Setting::set('MaxMindRejectCountryMismatch', $request->boolean('maxmind_reject_country_mismatch') ? '1' : '0', 'general');
            Setting::set('MaxMindRejectAnonymousProxy', $request->boolean('maxmind_reject_anon_proxy') ? '1' : '0', 'general');
        } else {
            Setting::set('FraudLabsEnabled', $request->boolean('fraudlabs_enabled') ? '1' : '0', 'general');
            if ($request->filled('fraudlabs_api_key')) {
                Setting::set('FraudLabsApiKey', trim((string) $v['fraudlabs_api_key']), 'general');
            }
            Setting::set('FraudLabsRiskScore', (string) ($v['fraudlabs_risk_score'] ?? '70'), 'general');
        }

        return back()->with('success', 'Fraud protection settings saved.');
    }

    /** Two-Factor Authentication — Staff & Client MFA Enforcement */
    public function twoFactor()
    {
        $staffTotal = Admin::where('is_disabled', false)->count();
        $staffWith2fa = Admin::where('is_disabled', false)->whereNotNull('second_factor_secret')->count();
        $usersWith2fa = User::whereNotNull('second_factor_secret')->count();

        $settings = [
            'totp_enabled' => Setting::get('TwoFactorTotpEnabled', '1') === '1',
            'backup_codes_enabled' => Setting::get('TwoFactorBackupCodesEnabled', '1') === '1',
            'require_admin_2fa' => Setting::get('RequireAdmin2FA', '0') === '1',
            'require_client_2fa' => Setting::get('RequireClient2FA', '0') === '1',
            'twilio_enabled' => Setting::get('TwilioVerifyEnabled', '0') === '1',
            'twilio_sid' => (string) Setting::get('TwilioAccountSid', ''),
            'twilio_service_sid' => (string) Setting::get('TwilioVerifyServiceSid', ''),
            'twilio_has_token' => trim((string) Setting::get('TwilioAuthToken', '')) !== '',
        ];

        return view('admin.setup.two-factor', compact('settings', 'staffTotal', 'staffWith2fa', 'usersWith2fa'));
    }

    public function saveTwoFactor(Request $request)
    {
        $v = $request->validate([
            'twilio_sid' => 'nullable|string|max:120',
            'twilio_auth_token' => 'nullable|string|max:255',
            'twilio_service_sid' => 'nullable|string|max:120',
        ]);

        Setting::set('TwoFactorTotpEnabled', $request->boolean('totp_enabled') ? '1' : '0', 'security');
        Setting::set('TwoFactorBackupCodesEnabled', $request->boolean('backup_codes_enabled') ? '1' : '0', 'security');
        Setting::set('RequireAdmin2FA', $request->boolean('require_admin_2fa') ? '1' : '0', 'security');
        Setting::set('RequireClient2FA', $request->boolean('require_client_2fa') ? '1' : '0', 'security');
        Setting::set('TwilioVerifyEnabled', $request->boolean('twilio_enabled') ? '1' : '0', 'general');

        if ($request->has('twilio_sid')) {
            Setting::set('TwilioAccountSid', trim((string) ($v['twilio_sid'] ?? '')), 'general');
        }
        if ($request->has('twilio_service_sid')) {
            Setting::set('TwilioVerifyServiceSid', trim((string) ($v['twilio_service_sid'] ?? '')), 'general');
        }
        if ($request->filled('twilio_auth_token')) {
            Setting::set('TwilioAuthToken', trim((string) $v['twilio_auth_token']), 'general');
        }

        return back()->with('success', 'Two-Factor Authentication settings updated.');
    }

    /** Security Questions — Account Verification Questions */
    public function securityQuestions()
    {
        $questions = json_decode((string) Setting::get('security_questions', '[]'), true);
        if (! is_array($questions)) {
            $questions = [];
        }

        return view('admin.setup.security-questions', compact('questions'));
    }

    public function storeSecurityQuestion(Request $request)
    {
        $v = $request->validate([
            'question' => 'required|string|min:5|max:255',
        ]);

        $questions = json_decode((string) Setting::get('security_questions', '[]'), true);
        if (! is_array($questions)) {
            $questions = [];
        }

        $nextId = collect($questions)->max('id') ? ((int) collect($questions)->max('id') + 1) : 1;
        $questions[] = [
            'id' => $nextId,
            'question' => trim($v['question']),
            'uses' => 0,
            'created_at' => now()->toDateString(),
        ];

        Setting::set('security_questions', json_encode(array_values($questions)), 'security');

        return back()->with('success', 'Security question added.');
    }

    public function updateSecurityQuestion(Request $request, int $id)
    {
        $v = $request->validate([
            'question' => 'required|string|min:5|max:255',
        ]);

        $questions = json_decode((string) Setting::get('security_questions', '[]'), true) ?: [];
        foreach ($questions as &$q) {
            if ((int) ($q['id'] ?? 0) === $id) {
                $q['question'] = trim($v['question']);
            }
        }
        unset($q);

        Setting::set('security_questions', json_encode(array_values($questions)), 'security');

        return back()->with('success', 'Security question updated.');
    }

    public function destroySecurityQuestion(int $id)
    {
        $questions = json_decode((string) Setting::get('security_questions', '[]'), true) ?: [];
        $questions = array_values(array_filter($questions, fn ($q) => (int) ($q['id'] ?? 0) !== $id));
        Setting::set('security_questions', json_encode($questions), 'security');

        return back()->with('success', 'Security question deleted.');
    }

    /** Application Links — cPanel / Panelica / DirectAdmin Single Sign-On Linking */
    public function applicationLinks()
    {
        $servers = Server::withCount('services')
            ->orderByDesc('active')
            ->orderBy('name')
            ->get();

        $enabled = Setting::get('ApplicationLinksEnabled', '1') === '1';
        $clientAreaShortcuts = Setting::get('ApplicationLinksClientShortcuts', '1') === '1';

        return view('admin.setup.application-links', compact('servers', 'enabled', 'clientAreaShortcuts'));
    }

    public function saveApplicationLinks(Request $request)
    {
        Setting::set('ApplicationLinksEnabled', $request->boolean('enabled') ? '1' : '0', 'security');
        Setting::set('ApplicationLinksClientShortcuts', $request->boolean('client_shortcuts') ? '1' : '0', 'security');

        return back()->with('success', 'Application Links configuration saved.');
    }

    /** OpenID Connect — Relying Party API Credentials */
    public function openIdConnect()
    {
        $clients = json_decode((string) Setting::get('openid_connect_clients', '[]'), true);
        if (! is_array($clients)) {
            $clients = [];
        }

        return view('admin.setup.openid-connect', compact('clients'));
    }

    public function storeOpenIdClient(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'redirect_uris' => 'required|string|max:1000',
        ]);

        $uris = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $v['redirect_uris']) ?: [])));
        foreach ($uris as $uri) {
            if (! filter_var($uri, FILTER_VALIDATE_URL)) {
                return back()->withInput()->withErrors(['redirect_uris' => "Invalid redirect URI: {$uri}"]);
            }
        }

        $clients = json_decode((string) Setting::get('openid_connect_clients', '[]'), true);
        if (! is_array($clients)) {
            $clients = [];
        }

        $clientId = 'oidc_' . bin2hex(random_bytes(12));
        $plainSecret = 'oidcs_' . bin2hex(random_bytes(24));
        $nextId = collect($clients)->max('id') ? ((int) collect($clients)->max('id') + 1) : 1;

        $clients[] = [
            'id' => $nextId,
            'name' => trim($v['name']),
            'description' => trim((string) ($v['description'] ?? '')),
            'client_id' => $clientId,
            'secret_hash' => Hash::make($plainSecret),
            'redirect_uris' => $uris,
            'created_at' => now()->toDateTimeString(),
        ];

        Setting::set('openid_connect_clients', json_encode(array_values($clients)), 'security');

        return back()
            ->with('success', 'OpenID Connect client credentials generated.')
            ->with('generated_oidc_client_id', $clientId)
            ->with('generated_oidc_secret', $plainSecret);
    }

    public function destroyOpenIdClient(int $id)
    {
        $clients = json_decode((string) Setting::get('openid_connect_clients', '[]'), true) ?: [];
        $clients = array_values(array_filter($clients, fn ($c) => (int) ($c['id'] ?? 0) !== $id));
        Setting::set('openid_connect_clients', json_encode($clients), 'security');

        return back()->with('success', 'OpenID Connect client credentials revoked.');
    }

    /** Storage Settings — Local & S3-Compatible Asset Locations */
    public function storageSettings()
    {
        $localPath = storage_path('app');
        $freeBytes = @disk_free_space($localPath) ?: 0;
        $totalBytes = @disk_total_space($localPath) ?: 0;

        $storage = [
            'downloads' => (string) Setting::get('StorageAssetDownloads', 'local'),
            'ticket_attachments' => (string) Setting::get('StorageAssetTicketAttachments', 'local'),
            'invoices' => (string) Setting::get('StorageAssetInvoices', 'local'),
            'email_attachments' => (string) Setting::get('StorageAssetEmailAttachments', 'local'),
            'backups' => (string) Setting::get('StorageAssetBackups', 'local'),
            's3_enabled' => Setting::get('StorageS3Enabled', '0') === '1',
            's3_bucket' => (string) Setting::get('StorageS3Bucket', ''),
            's3_region' => (string) Setting::get('StorageS3Region', 'us-east-1'),
            's3_endpoint' => (string) Setting::get('StorageS3Endpoint', ''),
            's3_access_key' => (string) Setting::get('StorageS3AccessKey', ''),
            's3_has_secret' => trim((string) Setting::get('StorageS3SecretKey', '')) !== '',
            'local_path' => $localPath,
            'free_gb' => $freeBytes > 0 ? round($freeBytes / 1073741824, 2) : null,
            'total_gb' => $totalBytes > 0 ? round($totalBytes / 1073741824, 2) : null,
        ];

        return view('admin.setup.storage-settings', compact('storage'));
    }

    public function saveStorageSettings(Request $request)
    {
        $allowedDisks = ['local', 's3'];
        $v = $request->validate([
            'downloads' => ['required', Rule::in($allowedDisks)],
            'ticket_attachments' => ['required', Rule::in($allowedDisks)],
            'invoices' => ['required', Rule::in($allowedDisks)],
            'email_attachments' => ['required', Rule::in($allowedDisks)],
            'backups' => ['required', Rule::in($allowedDisks)],
            's3_bucket' => 'nullable|string|max:120',
            's3_region' => 'nullable|string|max:60',
            's3_endpoint' => 'nullable|url|max:255',
            's3_access_key' => 'nullable|string|max:180',
            's3_secret_key' => 'nullable|string|max:255',
        ]);

        Setting::set('StorageAssetDownloads', $v['downloads'], 'storage');
        Setting::set('StorageAssetTicketAttachments', $v['ticket_attachments'], 'storage');
        Setting::set('StorageAssetInvoices', $v['invoices'], 'storage');
        Setting::set('StorageAssetEmailAttachments', $v['email_attachments'], 'storage');
        Setting::set('StorageAssetBackups', $v['backups'], 'storage');
        Setting::set('StorageS3Enabled', $request->boolean('s3_enabled') ? '1' : '0', 'storage');
        Setting::set('StorageS3Bucket', trim((string) ($v['s3_bucket'] ?? '')), 'storage');
        Setting::set('StorageS3Region', trim((string) ($v['s3_region'] ?? 'us-east-1')), 'storage');
        Setting::set('StorageS3Endpoint', trim((string) ($v['s3_endpoint'] ?? '')), 'storage');
        Setting::set('StorageS3AccessKey', trim((string) ($v['s3_access_key'] ?? '')), 'storage');

        if ($request->filled('s3_secret_key')) {
            Setting::set('StorageS3SecretKey', trim((string) $v['s3_secret_key']), 'storage');
        }

        return back()->with('success', 'Storage configuration saved.');
    }

    /** Database Backups — Automated Schedule & On-Demand Encrypted/Gzipped SQL Dumps */
    public function databaseBackups()
    {
        $backupDir = storage_path('app/backups/db');
        $files = [];

        if (is_dir($backupDir)) {
            $rawFiles = glob($backupDir . DIRECTORY_SEPARATOR . 'pnlcs-*.sql.gz') ?: [];
            rsort($rawFiles);
            foreach ($rawFiles as $path) {
                $base = basename($path);
                if (preg_match('/^pnlcs-\d{8}-\d{6}\.sql\.gz$/', $base)) {
                    $files[] = [
                        'name' => $base,
                        'size_kb' => round((filesize($path) ?: 0) / 1024, 1),
                        'modified_at' => date('Y-m-d H:i:s', filemtime($path) ?: time()),
                    ];
                }
            }
        }

        $config = [
            'enabled' => Setting::get('db_backup_enabled', '1') === '1',
            'retention' => (int) Setting::get('db_backup_retention', '7'),
            'email_to' => (string) Setting::get('db_backup_email', ''),
            'ftp_enabled' => Setting::get('db_backup_ftp_enabled', '0') === '1',
            'ftp_host' => (string) Setting::get('db_backup_ftp_host', ''),
            'ftp_port' => (int) Setting::get('db_backup_ftp_port', '21'),
            'ftp_user' => (string) Setting::get('db_backup_ftp_user', ''),
            'ftp_has_pass' => trim((string) Setting::get('DbBackupFtpPassword', '')) !== '',
            'ftp_path' => (string) Setting::get('db_backup_ftp_path', '/backups'),
        ];

        return view('admin.setup.database-backups', compact('config', 'files'));
    }

    public function saveDatabaseBackups(Request $request)
    {
        $v = $request->validate([
            'retention' => 'required|integer|min:1|max:365',
            'email_to' => 'nullable|email|max:255',
            'ftp_host' => 'nullable|string|max:190',
            'ftp_port' => 'nullable|integer|between:1,65535',
            'ftp_user' => 'nullable|string|max:120',
            'ftp_pass' => 'nullable|string|max:255',
            'ftp_path' => 'nullable|string|max:255',
        ]);

        Setting::set('db_backup_enabled', $request->boolean('enabled') ? '1' : '0', 'backups');
        Setting::set('db_backup_retention', (string) $v['retention'], 'backups');
        Setting::set('db_backup_email', trim((string) ($v['email_to'] ?? '')), 'backups');
        Setting::set('db_backup_ftp_enabled', $request->boolean('ftp_enabled') ? '1' : '0', 'backups');
        Setting::set('db_backup_ftp_host', trim((string) ($v['ftp_host'] ?? '')), 'backups');
        Setting::set('db_backup_ftp_port', (string) ($v['ftp_port'] ?? 21), 'backups');
        Setting::set('db_backup_ftp_user', trim((string) ($v['ftp_user'] ?? '')), 'backups');
        Setting::set('db_backup_ftp_path', trim((string) ($v['ftp_path'] ?? '/backups')), 'backups');

        if ($request->filled('ftp_pass')) {
            Setting::set('DbBackupFtpPassword', trim((string) $v['ftp_pass']), 'backups');
        }

        return back()->with('success', 'Database backup settings saved.');
    }

    public function runDatabaseBackup()
    {
        $prevEnabled = Setting::get('db_backup_enabled', '1');
        if ($prevEnabled !== '1') {
            Setting::set('db_backup_enabled', '1', 'backups');
        }

        $code = Artisan::call('pnlcs:db-backup', ['--php' => true]);

        if ($prevEnabled !== '1') {
            Setting::set('db_backup_enabled', $prevEnabled, 'backups');
        }

        if ($code === 0) {
            return back()->with('success', 'Database backup archive generated successfully.');
        }

        return back()->with('error', 'Database backup generation encountered an error.');
    }

    public function downloadDatabaseBackup(string $filename)
    {
        $safePath = $this->resolveSafeBackupFile($filename);
        if ($safePath === null) {
            abort(404);
        }

        return response()->download($safePath, basename($safePath), [
            'Content-Type' => 'application/gzip',
            'Content-Disposition' => 'attachment; filename="' . basename($safePath) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroyDatabaseBackup(string $filename)
    {
        $safePath = $this->resolveSafeBackupFile($filename);
        if ($safePath === null) {
            abort(404);
        }

        @unlink($safePath);

        return back()->with('success', 'Backup archive ' . basename($safePath) . ' deleted.');
    }

    private function resolveSafeBackupFile(string $filename): ?string
    {
        $cleanName = basename($filename);
        if (! preg_match('/^pnlcs-\d{8}-\d{6}\.sql\.gz$/', $cleanName)) {
            return null;
        }

        $sandboxDir = realpath(storage_path('app/backups/db'));
        if ($sandboxDir === false) {
            return null;
        }

        $candidate = realpath($sandboxDir . DIRECTORY_SEPARATOR . $cleanName);
        if ($candidate === false || ! is_file($candidate)) {
            return null;
        }

        if (! str_starts_with($candidate, rtrim($sandboxDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $candidate;
    }
}

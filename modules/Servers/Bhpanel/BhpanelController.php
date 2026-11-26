<?php

namespace Modules\Servers\Bhpanel;

use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\Service;
use App\Services\Module\ModuleRegistry;
use Illuminate\Http\Request;

class BhpanelController extends Controller
{
    use ResolvesClient;

    public function __construct(private readonly ModuleRegistry $registry) {}

    public function signin(Request $request, Service $service)
    {
        $this->authorizeService($service);
        if (strtolower((string) $service->status) !== 'active') {
            return redirect()->route('client.services.show', $service)
                ->with('error', 'This hosting service is not currently active.');
        }

        $to = trim((string) $request->input('to', ''));
        $res = $this->module()->ssoLogin($service, $to);

        if (($res['success'] ?? false) && ! empty($res['data']['url'])) {
            return redirect()->away((string) $res['data']['url']);
        }

        return redirect()->route('client.services.show', $service)
            ->with('error', (string) ($res['message'] ?? 'Control panel sign-in is temporarily unavailable.'));
    }

    // ── 1. WordPress ─────────────────────────────────────────────────────────

    public function wordpress(Request $request, Service $service)
    {
        $this->authorizeService($service);
        $api = $this->module()->apiForService($service);
        if (! $api) {
            return $this->unavailableRedirect($service);
        }

        $rel = self::cleanRel((string) ($request->isMethod('post') ? $request->input('rel', '') : $request->query('rel', '')));
        $jobId = trim((string) $request->query('job', ''));

        if ($request->isMethod('post')) {
            if (strtolower((string) $service->status) !== 'active') {
                return back()->with('error', 'This hosting service is not active.');
            }

            $act = (string) $request->input('bhp_action', '');
            if ($act === 'login') {
                $r = $api->accountCall($service, 'POST', '/wordpress/login', ['rel' => $rel], BhpanelClient::CLICK_TIMEOUT);
                $d = is_array($r['data'] ?? null) ? $r['data'] : [];
                if ($r['ok']
                    && preg_match('#^https://[^/\s"<>]+(/[^\s"<>]*)?\z#', (string) ($d['url'] ?? '')) === 1
                    && preg_match('/^[a-z_]{1,40}\z/', (string) ($d['field'] ?? '')) === 1
                    && preg_match('/^[A-Za-z0-9._-]{16,200}\z/', (string) ($d['token'] ?? '')) === 1) {
                    return redirect()->route('client.services.bhpanel.wordpress', ['service' => $service] + ($rel !== '' ? ['rel' => $rel] : []))
                        ->with('bhp_wp_login', [
                            'url' => (string) $d['url'],
                            'field' => (string) $d['field'],
                            'token' => (string) $d['token'],
                            'user' => (string) ($d['user'] ?? ''),
                        ])
                        ->with('success', 'WordPress sign-in ready. Click Continue below to open wp-admin.');
                }

                return back()->with('error', $this->friendlyError($r, 'WordPress one-click sign-in is unavailable.'));
            }

            $calls = [
                'update' => ['/wordpress/update', ['rel' => $rel, 'target' => 'all']],
                'backup' => ['/wordpress/backup', ['rel' => $rel]],
                'restore' => ['/wordpress/restore', [
                    'rel' => $rel,
                    'file' => trim((string) $request->input('file', '')),
                    'where' => trim((string) $request->input('where', 'both')),
                ]],
                'clone' => ['/wordpress/clone', [
                    'rel' => $rel,
                    'domain' => strtolower(trim((string) $request->input('domain', ''))),
                    'subdir' => trim((string) $request->input('subdir', '')),
                ]],
                'install' => ['/wordpress/install', [
                    'domain' => strtolower(trim((string) $request->input('domain', ''))),
                    'subdir' => trim((string) $request->input('subdir', '')),
                    'title' => trim((string) $request->input('title', 'My Website')),
                    'admin_user' => trim((string) $request->input('admin_user', 'admin')),
                    'admin_email' => trim((string) $request->input('admin_email', '')),
                ]],
            ];

            if (! isset($calls[$act])) {
                return back()->with('error', 'Unsupported WordPress action.');
            }

            [$path, $body] = $calls[$act];
            $r = $api->accountCall($service, 'POST', $path, $body, BhpanelClient::ACTION_TIMEOUT);
            $job = is_string($r['data']['job'] ?? null) ? $r['data']['job'] : '';

            if ($r['ok'] && $job !== '') {
                return redirect()->route('client.services.bhpanel.wordpress', array_filter([
                    'service' => $service->id,
                    'job' => $job,
                    'rel' => $act === 'install' ? '' : $rel,
                ], 'strlen'));
            }

            return back()->with('error', $this->friendlyError($r, 'Could not start WordPress task.'));
        }

        $wpLogin = session('bhp_wp_login');
        $job = null;
        $user = strtolower(trim((string) $service->username));

        if ($jobId !== '' && preg_match('/^app-[a-z]+:'.preg_quote($user, '/').'-[0-9]{8}-[0-9]{6}\z/', $jobId) === 1) {
            $transient = false;
            if ($api->ownAccount($service, false, BhpanelClient::PAGE_TIMEOUT, $transient) === null) {
                $jr = $api->api('GET', '/v1/jobs/'.rawurlencode($jobId), null, BhpanelClient::PAGE_TIMEOUT);
                $j = $jr['ok'] && is_array($jr['data']) ? ($jr['data']['job'] ?? $jr['data']) : null;
                $job = is_array($j) && in_array($j['state'] ?? null, ['running', 'done', 'failed'], true)
                    ? $j
                    : ['state' => 'unreadable'];
                $finished = is_array($job) ? strtotime((string) ($job['finished'] ?? '')) : false;
                if (is_array($job['result'] ?? null) && (! $finished || time() - $finished > 900)) {
                    unset($job['result']['admin_password']);
                }
            } elseif ($transient) {
                $job = ['state' => 'unreadable'];
            }
        }

        $installs = [];
        $info = null;
        $backups = [];
        $sites = [];
        $pageError = null;

        if (! $wpLogin) {
            $r = $api->accountCall($service, 'GET', '/wordpress', null, BhpanelClient::PAGE_TIMEOUT);
            if ($r['ok'] && is_array($r['data']['installs'] ?? null)) {
                $latest = (string) ($r['data']['latest'] ?? '');
                foreach ($r['data']['installs'] as $in) {
                    if (is_array($in)) {
                        $in['update'] = $latest !== '' && version_compare((string) ($in['version'] ?? '0'), $latest, '<');
                        $installs[] = $in;
                    }
                }
            } else {
                $pageError = $this->friendlyError($r, 'WordPress management could not be loaded right now.');
            }

            if ($rel !== '' && $pageError === null) {
                $ri = $api->accountCall($service, 'GET', '/wordpress/info?rel='.rawurlencode($rel), null, BhpanelClient::PAGE_TIMEOUT);
                $info = $ri['ok'] && is_array($ri['data']) ? $ri['data'] : null;

                $rb = $api->accountCall($service, 'GET', '/wordpress/backups?rel='.rawurlencode($rel), null, BhpanelClient::PAGE_TIMEOUT);
                $backups = $rb['ok'] && is_array($rb['data']['snapshots'] ?? null) ? $rb['data']['snapshots'] : [];
            }

            foreach ($this->fetchSites($api, $service) ?? [] as $s) {
                $sites[] = (string) $s['domain'];
            }
        }

        $service->loadMissing('client');

        return view('bhpanel::wordpress', [
            'service' => $service,
            'activeTab' => 'wordpress',
            'installs' => $installs,
            'rel' => $rel,
            'info' => $info,
            'backups' => $backups,
            'sites' => $sites,
            'job' => $job,
            'wpLogin' => is_array($wpLogin) ? $wpLogin : null,
            'pageError' => $pageError,
            'clientEmail' => (string) ($service->client?->email ?? ''),
        ]);
    }

    // ── 2. Domains ───────────────────────────────────────────────────────────

    public function domains(Request $request, Service $service)
    {
        $this->authorizeService($service);
        $api = $this->module()->apiForService($service);
        if (! $api) {
            return $this->unavailableRedirect($service);
        }

        if ($request->isMethod('post')) {
            if (strtolower((string) $service->status) !== 'active') {
                return back()->with('error', 'This hosting service is not active.');
            }

            $act = (string) $request->input('bhp_action', '');
            $calls = [
                'php' => ['/domains/php', [
                    'domain' => strtolower(trim((string) $request->input('domain', ''))),
                    'version' => trim((string) $request->input('version', '')),
                ], 'PHP version updated.'],
                'redirect' => ['/domains/redirect', [
                    'domain' => strtolower(trim((string) $request->input('domain', ''))),
                    'to' => trim((string) $request->input('to', '')),
                    'code' => $request->input('code') === '302' ? 302 : 301,
                ], 'Domain redirect saved.'],
                'unredirect' => ['/domains/redirect', [
                    'domain' => strtolower(trim((string) $request->input('domain', ''))),
                    'remove' => true,
                ], 'Domain redirect removed.'],
            ];

            if ($act === 'add') {
                if ($request->input('kind') === 'subdomain') {
                    $parent = strtolower(trim((string) $request->input('parent', '')));
                    $label = strtolower(trim((string) $request->input('label', ''), '. '));
                    $calls['add'] = ['/domains', [
                        'domain' => "{$label}.{$parent}",
                        'kind' => 'subdomain',
                        'parent' => $parent,
                    ], 'Subdomain added.'];
                } else {
                    $calls['add'] = ['/domains', [
                        'domain' => strtolower(trim((string) $request->input('domain', ''))),
                        'kind' => 'addon',
                    ], 'Domain added to hosting account.'];
                }
            }

            if (! isset($calls[$act])) {
                return back()->with('error', 'Unsupported domain action.');
            }

            [$path, $body, $successMsg] = $calls[$act];
            $r = $api->accountCall($service, 'POST', $path, $body, BhpanelClient::ACTION_TIMEOUT);

            return redirect()->route('client.services.bhpanel.domains', $service)
                ->with($r['ok'] ? 'success' : 'error', $r['ok'] ? $successMsg : $this->friendlyError($r, 'Could not complete domain change.'));
        }

        $sites = $this->fetchSites($api, $service, $r);
        $ssl = $sites !== null ? ($this->fetchSslByDomain($api, $service) ?? []) : [];
        $phpVersions = [];

        if ($sites !== null) {
            $rp = $api->api('GET', '/v1/php', null, BhpanelClient::PAGE_TIMEOUT);
            if ($rp['ok'] && is_array($rp['data'])) {
                foreach ($rp['data'] as $v) {
                    if (is_array($v) && ! empty($v['installed']) && ! empty($v['ready']) && is_string($v['version'] ?? null)) {
                        $phpVersions[] = $v['version'];
                    }
                }
            }
        }

        $parents = [];
        foreach ($sites ?? [] as $s) {
            if (($s['kind'] ?? '') !== 'subdomain') {
                $parents[] = (string) $s['domain'];
            }
        }

        return view('bhpanel::domains', [
            'service' => $service,
            'activeTab' => 'domains',
            'sites' => $sites ?? [],
            'parents' => $parents,
            'ssl' => $ssl,
            'phpVersions' => $phpVersions,
            'pageError' => $sites === null ? $this->friendlyError($r ?? [], 'Domains list could not be loaded.') : null,
        ]);
    }

    // ── 3. Email ─────────────────────────────────────────────────────────────

    public function email(Request $request, Service $service)
    {
        $this->authorizeService($service);
        $api = $this->module()->apiForService($service);
        if (! $api) {
            return $this->unavailableRedirect($service);
        }

        if ($request->isMethod('post')) {
            if (strtolower((string) $service->status) !== 'active') {
                return back()->with('error', 'This hosting service is not active.');
            }

            $act = (string) $request->input('bhp_action', '');
            $pw = (string) $request->input('password', '');
            $local = strtolower(trim((string) $request->input('local', '')));
            $dom = strtolower(trim((string) $request->input('domain', '')));
            $quotaRaw = (string) $request->input('quota_mb', '1024');
            $quota = ctype_digit($quotaRaw) && (int) $quotaRaw <= 4194304 ? (int) $quotaRaw : 1024;

            if (in_array($act, ['create', 'password'], true) && mb_strlen($pw, 'UTF-8') < 12) {
                return back()->with('error', 'Mailbox passwords must be at least 12 characters.');
            }

            $calls = [
                'create' => ['/email', ['address' => "{$local}@{$dom}", 'password' => $pw, 'quota_mb' => $quota], 'Mailbox created.'],
                'password' => ['/email/password', ['address' => trim((string) $request->input('address', '')), 'password' => $pw], 'Mailbox password updated.'],
                'quota' => ['/email/quota', ['address' => trim((string) $request->input('address', '')), 'quota_mb' => $quota], 'Mailbox storage quota updated.'],
                'forward' => ['/email/forward', ['address' => "{$local}@{$dom}", 'to' => trim((string) $request->input('to', ''))], 'Email forwarder saved.'],
                'unforward' => ['/email/unforward', ['address' => trim((string) $request->input('address', ''))], 'Email forwarder removed.'],
                'enable' => ['/email/enable', ['domain' => $dom], 'Email enabled for domain.'],
            ];

            if (! isset($calls[$act])) {
                return back()->with('error', 'Unsupported email action.');
            }

            [$path, $body, $successMsg] = $calls[$act];
            $r = $api->accountCall($service, 'POST', $path, $body, BhpanelClient::ACTION_TIMEOUT);

            $redir = redirect()->route('client.services.bhpanel.email', $service);
            if ($r['ok']) {
                $warn = is_string($r['data']['warning'] ?? null) ? trim($r['data']['warning']) : '';

                return $redir->with('success', $warn !== '' ? "{$successMsg} ({$warn})" : $successMsg);
            }

            return $redir->with('error', $this->friendlyError($r, 'Could not complete email action.'));
        }

        $q = strtolower(trim(substr((string) $request->query('q', ''), 0, 100)));
        $r = $api->accountCall($service, 'GET', '/email', null, BhpanelClient::PAGE_TIMEOUT);

        $mailboxes = [];
        $forwards = [];
        $mailDomains = [];
        $noMailDomains = [];
        $mailHosts = [];
        $pageError = null;

        if ($r['ok'] && is_array($r['data']['domains'] ?? null)) {
            $serverHost = strtolower(trim((string) ($service->server?->hostname ?? '')));
            foreach ($r['data']['domains'] as $d) {
                if (! is_array($d) || ! is_string($d['domain'] ?? null)) {
                    continue;
                }
                $domainName = strtolower($d['domain']);
                $mailDomains[] = $domainName;
                $mailHosts[$domainName] = is_string($d['mail_host'] ?? null) && $d['mail_host'] !== '' ? $d['mail_host'] : $serverHost;

                foreach ((array) ($d['mailboxes'] ?? []) as $b) {
                    if (is_array($b) && is_string($b['address'] ?? null)) {
                        if ($q === '' || str_contains(strtolower($b['address']), $q)) {
                            $mailboxes[] = $b;
                        }
                    }
                }
                foreach ((array) ($d['aliases'] ?? []) as $a) {
                    if (is_array($a) && is_string($a['address'] ?? null)) {
                        $forwards[] = [
                            'address' => $a['address'],
                            'to' => array_values(array_filter((array) ($a['to'] ?? []), 'is_string')),
                        ];
                    }
                }
            }

            foreach ($this->fetchSites($api, $service) ?? [] as $s) {
                $sd = strtolower((string) $s['domain']);
                if (! in_array($sd, $mailDomains, true)) {
                    $noMailDomains[] = $sd;
                }
            }
        } else {
            $pageError = $this->friendlyError($r, 'Email accounts could not be loaded.');
        }

        return view('bhpanel::email', [
            'service' => $service,
            'activeTab' => 'email',
            'mailboxes' => $mailboxes,
            'forwards' => $forwards,
            'mailDomains' => $mailDomains,
            'noMailDomains' => $noMailDomains,
            'mailHosts' => $mailHosts,
            'q' => $q,
            'pageError' => $pageError,
        ]);
    }

    // ── 4. Databases ─────────────────────────────────────────────────────────

    public function databases(Request $request, Service $service)
    {
        $this->authorizeService($service);
        $api = $this->module()->apiForService($service);
        if (! $api) {
            return $this->unavailableRedirect($service);
        }

        $engines = ['mysql' => 3306, 'postgresql' => 5432, 'mongodb' => 27017];
        $engine = (string) ($request->input('engine') ?? $request->query('engine', 'mysql'));
        if (! isset($engines[$engine])) {
            $engine = 'mysql';
        }

        if ($request->isMethod('post')) {
            if (strtolower((string) $service->status) !== 'active') {
                return back()->with('error', 'This hosting service is not active.');
            }

            $act = (string) $request->input('bhp_action', '');
            $pw = (string) $request->input('password', '');
            if (mb_strlen($pw, 'UTF-8') < 12) {
                return back()->with('error', 'Database passwords must be at least 12 characters.');
            }
            if ($act === 'password' && $engine === 'mongodb') {
                return back()->with('error', 'MongoDB user passwords can be changed directly inside the control panel.');
            }

            if ($act === 'create') {
                $r = $api->accountCall($service, 'POST', '/databases', [
                    'engine' => $engine,
                    'name' => strtolower(trim((string) $request->input('name', ''))),
                    'password' => $pw,
                ], BhpanelClient::ACTION_TIMEOUT);
                $msg = 'Database created.';
            } elseif ($act === 'password') {
                $r = $api->accountCall($service, 'POST', '/databases/password', [
                    'engine' => $engine,
                    'db_user' => trim((string) $request->input('db_user', '')),
                    'password' => $pw,
                ], BhpanelClient::ACTION_TIMEOUT);
                $msg = 'Database user password updated.';
            } else {
                return back()->with('error', 'Unsupported database action.');
            }

            return redirect()->route('client.services.bhpanel.databases', ['service' => $service, 'engine' => $engine])
                ->with($r['ok'] ? 'success' : 'error', $r['ok'] ? $msg : $this->friendlyError($r, 'Database action could not be completed.'));
        }

        $r = $api->accountCall($service, 'GET', '/databases?engine='.rawurlencode($engine), null, BhpanelClient::PAGE_TIMEOUT);
        $dbs = [];
        $logins = [];
        $missingEngine = false;
        $pageError = null;
        $port = $engines[$engine];

        if ($r['ok'] && is_array($r['data']['databases'] ?? null)) {
            $byDb = $r['data']['db_users'] ?? null;
            foreach ($r['data']['databases'] as $d) {
                $name = is_array($d) ? (string) ($d['name'] ?? '') : (is_string($d) ? $d : '');
                if ($name === '') {
                    continue;
                }
                $users = is_array($d) ? ($d['users'] ?? []) : (is_array($byDb) ? ($byDb[$name] ?? []) : []);
                $dbs[] = [
                    'name' => $name,
                    'size_mb' => is_array($d) && isset($d['size_mb']) ? (float) $d['size_mb'] : null,
                    'users' => is_array($users) ? array_values(array_filter($users, 'is_string')) : [],
                ];
            }
            foreach ((array) ($r['data']['users'] ?? []) as $u) {
                $uName = is_string($u) ? $u : (is_array($u) ? (string) ($u['name'] ?? $u['user'] ?? '') : '');
                if ($uName !== '') {
                    $logins[] = $uName;
                }
            }
            if (is_int($r['data']['port'] ?? null)) {
                $port = $r['data']['port'];
            }
        } elseif (($r['code'] ?? '') === 'unsupported' && $engine !== 'mysql') {
            $missingEngine = true;
        } else {
            $pageError = $this->friendlyError($r, 'Databases could not be loaded.');
        }

        return view('bhpanel::databases', [
            'service' => $service,
            'activeTab' => 'databases',
            'engine' => $engine,
            'engines' => array_keys($engines),
            'dbs' => $dbs,
            'logins' => array_values(array_unique($logins)),
            'missingEngine' => $missingEngine,
            'port' => $port,
            'prefix' => strtolower(trim((string) $service->username)).'_',
            'pageError' => $pageError,
        ]);
    }

    // ── 5. SSL Certificates ──────────────────────────────────────────────────

    public function ssl(Request $request, Service $service)
    {
        $this->authorizeService($service);
        $api = $this->module()->apiForService($service);
        if (! $api) {
            return $this->unavailableRedirect($service);
        }

        if ($request->isMethod('post')) {
            if (strtolower((string) $service->status) !== 'active') {
                return back()->with('error', 'This hosting service is not active.');
            }

            $act = (string) $request->input('bhp_action', '');
            $domain = strtolower(trim((string) $request->input('domain', '')));

            if ($act === 'issue') {
                $r = $api->accountCall($service, 'POST', '/ssl/issue', ['domain' => $domain], 180);
                if ($r['ok'] && ($r['data']['issued'] ?? null) === true) {
                    return redirect()->route('client.services.bhpanel.ssl', $service)
                        ->with('success', "SSL certificate issued for {$domain}.");
                }
                $reason = is_string($r['data']['reason'] ?? null) && $r['data']['reason'] !== ''
                    ? $r['data']['reason']
                    : $this->friendlyError($r, 'SSL certificate order is pending or could not be completed yet.');

                return redirect()->route('client.services.bhpanel.ssl', $service)->with('error', $reason);
            }

            if ($act === 'https') {
                $enable = $request->input('enable') === 'yes';
                $r = $api->accountCall($service, 'POST', '/ssl/https', ['domain' => $domain, 'enable' => $enable], BhpanelClient::ACTION_TIMEOUT);

                return redirect()->route('client.services.bhpanel.ssl', $service)
                    ->with($r['ok'] ? 'success' : 'error', $r['ok']
                        ? ($enable ? "Forced HTTPS enabled for {$domain}." : "Forced HTTPS turned off for {$domain}.")
                        : $this->friendlyError($r, 'Could not update HTTPS redirect setting.'));
            }

            return back()->with('error', 'Unsupported SSL action.');
        }

        $sites = $this->fetchSites($api, $service, $r);
        $ssl = $sites !== null ? ($this->fetchSslByDomain($api, $service) ?? []) : [];

        return view('bhpanel::ssl', [
            'service' => $service,
            'activeTab' => 'ssl',
            'sites' => $sites ?? [],
            'ssl' => $ssl,
            'pageError' => $sites === null ? $this->friendlyError($r ?? [], 'SSL status could not be loaded.') : null,
        ]);
    }

    // ── 6. Security (2FA, Passkeys, Firewall Unblock) ────────────────────────

    public function security(Request $request, Service $service)
    {
        $this->authorizeService($service);
        $api = $this->module()->apiForService($service);
        if (! $api) {
            return $this->unavailableRedirect($service);
        }

        if ($request->isMethod('post')) {
            if (strtolower((string) $service->status) !== 'active') {
                return back()->with('error', 'This hosting service is not active.');
            }

            $act = (string) $request->input('bhp_action', '');
            if ($act === 'unblock') {
                $fwIp = trim((string) $request->input('fw_ip', ''));
                if (! filter_var($fwIp, FILTER_VALIDATE_IP)) {
                    return back()->with('error', 'Please enter a valid IPv4 or IPv6 address.');
                }
                $r = $api->accountCall($service, 'POST', '/security/firewall/unblock', ['ip' => $fwIp], BhpanelClient::CLICK_TIMEOUT);

                return redirect()->route('client.services.bhpanel.security', ['service' => $service, 'fwip' => $fwIp])
                    ->with($r['ok'] ? 'success' : 'error', $r['ok'] ? "Unblocked {$fwIp} on hosting firewall." : $this->friendlyError($r, 'Could not lift firewall restriction for that address.'));
            }

            if (in_array($act, ['enable', 'disable'], true)) {
                $otp = trim((string) $request->input('otp', ''));
                if ($otp === '' || strlen($otp) > 64) {
                    return back()->with('error', 'Enter a valid verification code.');
                }
                $r = $api->accountCall($service, 'POST', '/security/2fa/'.$act, ['otp' => $otp], BhpanelClient::CLICK_TIMEOUT);
                if ($r['ok']) {
                    $redir = redirect()->route('client.services.bhpanel.security', $service)
                        ->with('success', $act === 'enable' ? 'Two-factor authentication enabled.' : 'Two-factor authentication turned off.');
                    if ($act === 'enable' && is_array($r['data']['recovery_codes'] ?? null)) {
                        $redir->with('bhp_recovery_codes', array_values(array_filter($r['data']['recovery_codes'], 'is_string')));
                    }

                    return $redir;
                }

                return redirect()->route('client.services.bhpanel.security', ['service' => $service] + ($act === 'enable' ? ['setup' => '1'] : []))
                    ->with('error', ($r['code'] ?? '') === 'denied'
                        ? 'Too many incorrect verification codes. Please wait 15 minutes before trying again.'
                        : $this->friendlyError($r, 'Verification code was not accepted.'));
            }

            return back()->with('error', 'Unsupported security action.');
        }

        $sec = null;
        $setup = null;
        $pageError = null;
        $isActive = strtolower((string) $service->status) === 'active';

        if ($isActive) {
            $r = $api->accountCall($service, 'GET', '/security', null, BhpanelClient::PAGE_TIMEOUT);
            if ($r['ok'] && is_array($r['data']) && is_bool($r['data']['two_fa_on'] ?? null)) {
                $sec = $r['data'];
            } else {
                $pageError = $this->friendlyError($r, 'Security settings could not be loaded.');
            }

            if (is_array($sec) && ! $sec['two_fa_on'] && $request->query('setup') === '1') {
                $s = $api->accountCall($service, 'POST', '/security/2fa/start', null, BhpanelClient::CLICK_TIMEOUT);
                if ($s['ok'] && is_string($s['data']['secret'] ?? null)) {
                    $setup = [
                        'secret' => $s['data']['secret'],
                        'otpauth' => (string) ($s['data']['otpauth'] ?? ''),
                    ];
                }
            }
        }

        $visitorIp = (string) ($request->ip() ?? '');
        $fwIp = trim((string) $request->query('fwip', $visitorIp));
        if (! filter_var($fwIp, FILTER_VALIDATE_IP)) {
            $fwIp = $visitorIp;
        }

        $fw = null;
        $fwOffered = false;
        if ($isActive && $fwIp !== '') {
            $f = $api->accountCall($service, 'GET', '/security/firewall?ip='.rawurlencode($fwIp), null, BhpanelClient::PAGE_TIMEOUT);
            $fwOffered = $f['ok'] || ! in_array((string) ($f['code'] ?? ''), ['denied', 'forbidden', 'not_found'], true);
            if ($f['ok'] && is_array($f['data'])) {
                $fw = $f['data'];
            }
        }

        return view('bhpanel::security', [
            'service' => $service,
            'activeTab' => 'security',
            'sec' => $sec,
            'setup' => $setup,
            'recoveryCodes' => session('bhp_recovery_codes'),
            'fw' => $fw,
            'fwOffered' => $fwOffered,
            'fwIp' => $fwIp,
            'visitorIp' => $visitorIp,
            'pageError' => $pageError,
        ]);
    }

    // ── Admin SSO Endpoints ──────────────────────────────────────────────────

    public function adminServerSso(Server $server)
    {
        $res = $this->module()->adminServerSso($server);
        if (($res['success'] ?? false) && ! empty($res['data']['url'])) {
            return redirect()->away((string) $res['data']['url']);
        }

        return back()->with('error', (string) ($res['message'] ?? 'Unable to open server control panel.'));
    }

    public function adminServiceSso(Service $service)
    {
        $res = $this->module()->ssoLogin($service);
        if (($res['success'] ?? false) && ! empty($res['data']['url'])) {
            return redirect()->away((string) $res['data']['url']);
        }

        return back()->with('error', (string) ($res['message'] ?? 'Unable to open customer control panel.'));
    }

    // ── Internal Helpers ─────────────────────────────────────────────────────

    private function authorizeService(Service $service): void
    {
        abort_if($service->client_id !== $this->getClientId(), 403);
        abort_unless($service->isLive(), 403);
    }

    private function unavailableRedirect(Service $service)
    {
        return redirect()->route('client.services.show', $service)
            ->with('error', 'Hosting control panel connection is not available yet.');
    }

    private function fetchSites(BhpanelClient $api, Service $service, ?array &$raw = null): ?array
    {
        $raw = $api->accountCall($service, 'GET', '/domains', null, BhpanelClient::PAGE_TIMEOUT);
        if (! $raw['ok'] || ! is_array($raw['data'])) {
            return null;
        }

        $sites = [];
        foreach ($raw['data'] as $s) {
            if (! is_array($s) || ! is_string($s['domain'] ?? null)) {
                return null;
            }
            $sites[] = $s;
        }

        usort($sites, static fn ($a, $b) => (($a['kind'] ?? '') === 'primary' ? 0 : 1) <=> (($b['kind'] ?? '') === 'primary' ? 0 : 1)
            ?: strcmp((string) $a['domain'], (string) $b['domain']));

        return $sites;
    }

    private function fetchSslByDomain(BhpanelClient $api, Service $service): ?array
    {
        $rs = $api->accountCall($service, 'GET', '/domains/ssl', null, BhpanelClient::PAGE_TIMEOUT);
        if (! $rs['ok'] || ! is_array($rs['data']['domains'] ?? null)) {
            return null;
        }

        $out = [];
        foreach ($rs['data']['domains'] as $c) {
            if (is_array($c) && is_string($c['domain'] ?? null)) {
                $out[strtolower($c['domain'])] = $c;
            }
        }

        return $out;
    }

    private function friendlyError(array $r, string $fallback): string
    {
        if (! empty($r['transport'])) {
            return 'The hosting control panel did not respond in time. Please try again in a moment.';
        }

        $msg = trim((string) ($r['error'] ?? ''));

        return $msg !== '' ? mb_substr($msg, 0, 240) : $fallback;
    }

    private static function cleanRel(string $rel): string
    {
        $rel = trim(str_replace('\\', '/', $rel), '/');
        if ($rel === '' || strlen($rel) > 512 || preg_match('#^[A-Za-z0-9._-]+(/[A-Za-z0-9._-]+){0,15}\z#', $rel) !== 1) {
            return '';
        }
        foreach (explode('/', $rel) as $seg) {
            if ($seg === '.' || $seg === '..') {
                return '';
            }
        }

        return $rel;
    }

    private function module(): BhpanelModule
    {
        /** @var BhpanelModule $module */
        $module = $this->registry->getServerModule(BhpanelModule::NAME) ?? app(BhpanelModule::class);

        return $module;
    }
}

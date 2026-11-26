<?php

namespace Modules\Servers\Bhpanel;

use App\Models\Server;
use App\Models\Service;
use Illuminate\Contracts\Support\Htmlable;
use Modules\Servers\AbstractServerModule;

class BhpanelModule extends AbstractServerModule
{
    public const NAME = 'bhpanel';

    public const ALLOWED_DESTINATIONS = [
        '', 'files', 'mail', 'databases', 'ssl', 'wordpress', 'softaculous',
        'sites', 'backups', 'dns', 'ftp', 'logs', 'cron', 'apps', 'ssh', 'passkeys',
    ];

    public function getModuleName(): string
    {
        return self::NAME;
    }

    public function getConfigFields(): array
    {
        return [
            ['name' => 'api_token', 'label' => 'BHPanel API Token (bhp_live_...)', 'type' => 'password'],
        ];
    }

    public function listPackages(Server $server): array
    {
        $api = BhpanelClient::fromServer($server);
        if (! $api) {
            return [];
        }

        $r = $api->api('GET', '/v1/plans', null, BhpanelClient::CLICK_TIMEOUT);
        $list = $r['data']['packages'] ?? [];
        if (! $r['ok'] || ! is_array($list)) {
            return [];
        }

        $packages = [];
        foreach ($list as $p) {
            if (! is_array($p) || ! is_string($p['name'] ?? null) || ! empty($p['above_ceiling'])) {
                continue;
            }
            $tier = (string) ($p['tier'] ?? 'user');
            $label = is_string($p['label'] ?? null) && $p['label'] !== '' ? $p['label'] : $p['name'];
            $packages[] = [
                'id' => $p['name'],
                'name' => $label.($tier === 'reseller' ? ' (reseller)' : ''),
            ];
        }

        return $packages;
    }

    public function listAccounts(Server $server): array
    {
        return $this->listAccountDetails($server);
    }

    public function listAccountDetails(Server $server): array
    {
        $api = BhpanelClient::fromServer($server);
        if (! $api) {
            return [];
        }

        $r = $api->api('GET', '/v1/accounts', null, BhpanelClient::CLICK_TIMEOUT);
        if (! $r['ok'] || ! is_array($r['data'])) {
            return [];
        }

        $list = is_array($r['data']['accounts'] ?? null) ? $r['data']['accounts'] : $r['data'];
        $accounts = [];

        foreach ($list as $row) {
            $a = is_array($row['account'] ?? null) ? $row['account'] : $row;
            if (! is_array($a) || (string) ($a['tier'] ?? 'user') === 'admin') {
                continue;
            }
            $username = strtolower(trim((string) ($a['username'] ?? '')));
            if ($username === '') {
                continue;
            }
            $made = strtotime((string) ($a['created'] ?? '')) ?: 0;
            $accounts[] = [
                'id' => $username,
                'domain' => (string) ($a['domain'] ?? ''),
                'owner' => $username,
                'email' => (string) ($a['email'] ?? ''),
                'status' => ! empty($a['suspended']) ? 'suspended' : 'active',
                'plan' => (string) ($a['package'] ?? '') ?: null,
                'package' => (string) ($a['package'] ?? '') ?: null,
                'created' => $made > 0 ? date('Y-m-d', $made) : null,
                'kind' => (string) ($a['tier'] ?? 'user') === 'reseller' ? 'reseller' : 'account',
            ];
        }

        return $accounts;
    }

    public function linkAccount(Service $service, string $accountId): array
    {
        $username = strtolower(trim($accountId));
        if ($username === '') {
            return $this->buildResult(false, 'Invalid BHPanel username.');
        }

        $service->forceFill(['username' => $username])->save();
        $this->setModuleData($service, ['bhpanel_username' => $username]);

        return $this->buildResult(true, "Linked BHPanel account {$username}.");
    }

    public function linkedPanelAccount(Service $service): string
    {
        return strtolower(trim((string) $service->username));
    }

    public function testConnection(Server $server): bool
    {
        $api = BhpanelClient::fromServer($server);
        if (! $api) {
            return false;
        }

        $ping = $api->api('GET', '/v1/ping', null, BhpanelClient::CLICK_TIMEOUT);
        if (! $ping['ok']) {
            return false;
        }

        $plans = $api->api('GET', '/v1/plans', null, BhpanelClient::CLICK_TIMEOUT);
        if (! $plans['ok'] && ($plans['code'] ?? '') === 'forbidden') {
            return false;
        }

        return true;
    }

    public function create(Service $service): array
    {
        $service->loadMissing('client', 'product');
        $server = $this->getServer($service);
        $api = BhpanelClient::fromServer($server);

        if (! $api) {
            return $this->buildResult(false, 'No active BHPanel server with a valid bhp_live_ API token configured.');
        }

        $plan = trim((string) $this->getRemotePackage($service));
        if ($plan === '') {
            return $this->buildResult(false, 'Select a BHPanel hosting plan in the product settings first.');
        }

        $domain = BhpanelClient::cleanDomain($service->domain);
        $email = trim((string) ($service->client?->email ?? ''));
        if ($domain === '' || $email === '') {
            return $this->buildResult(false, 'Service requires both a domain and client email address.');
        }

        $body = [
            'domain' => $domain,
            'package' => $plan,
            'email' => $email,
        ];

        $password = (string) ($service->password ?? '');
        if (strlen($password) >= 12 && strlen($password) <= 72) {
            $body['password'] = $password;
        }

        $user = strtolower(trim((string) $service->username));
        if (preg_match('/^[a-z][a-z0-9]{2,15}\z/', $user) === 1) {
            $body['username'] = $user;
        }

        $r = $api->api('POST', '/v1/accounts', $body, BhpanelClient::ACTION_TIMEOUT);
        $made = '';
        $panelPassword = '';

        if ($r['ok']) {
            $made = strtolower(trim((string) ($r['data']['account']['username'] ?? $user)));
            $panelPassword = (string) ($r['data']['password'] ?? '');
        } else {
            // Lost-response adoption check (strictly within 30 minutes, matching domain + email + unused username).
            $found = ! empty($r['transport'])
                ? $this->findRecentAccount($api, $server->id, $service->id, $domain, $body['username'] ?? '', $email)
                : null;

            if ($found === null) {
                return $this->buildResult(false, $r['error']);
            }

            $made = $found;
            if (! isset($body['password'])) {
                $generated = bin2hex(random_bytes(10));
                $pwRes = $api->api('POST', '/v1/accounts/'.rawurlencode($made).'/password', [
                    'password' => $generated,
                    'expect_domain' => $domain,
                ], BhpanelClient::CLICK_TIMEOUT);
                if ($pwRes['ok']) {
                    $panelPassword = $generated;
                }
            }
        }

        $updates = ['domain' => $domain];
        if ($made !== '') {
            $updates['username'] = $made;
        }
        if (! isset($body['password']) && $panelPassword !== '') {
            $updates['password'] = $panelPassword;
        }
        $service->forceFill($updates)->save();
        $this->setModuleData($service, ['bhpanel_username' => $made ?: $user]);

        $result = $this->buildResult(true, 'Hosting account provisioned.');
        $this->logAction($service, 'create', $result);

        return $result;
    }

    public function suspend(Service $service, string $reason = ''): array
    {
        $api = $this->apiForService($service);
        if (! $api) {
            return $this->buildResult(false, 'No BHPanel server configured.');
        }

        if (($err = $api->ownAccount($service)) !== null) {
            return $this->buildResult(false, $err);
        }

        $r = $api->api(
            'POST',
            '/v1/accounts/'.BhpanelClient::encodedUser($service->username).'/suspend',
            ['reason' => trim($reason) !== '' ? trim($reason) : 'Suspended by billing'],
            BhpanelClient::ACTION_TIMEOUT,
            $service
        );

        return $this->buildResult($r['ok'], $r['ok'] ? 'Hosting account suspended.' : $r['error']);
    }

    public function unsuspend(Service $service): array
    {
        $api = $this->apiForService($service);
        if (! $api) {
            return $this->buildResult(false, 'No BHPanel server configured.');
        }

        if (($err = $api->ownAccount($service)) !== null) {
            return $this->buildResult(false, $err);
        }

        $r = $api->api(
            'POST',
            '/v1/accounts/'.BhpanelClient::encodedUser($service->username).'/unsuspend',
            null,
            BhpanelClient::ACTION_TIMEOUT,
            $service
        );

        return $this->buildResult($r['ok'], $r['ok'] ? 'Hosting account unsuspended.' : $r['error']);
    }

    public function terminate(Service $service): array
    {
        $api = $this->apiForService($service);
        if (! $api) {
            return $this->buildResult(false, 'No BHPanel server configured.');
        }

        $err = $api->ownAccount($service, true);
        if ($err === 'gone') {
            return $this->buildResult(true, 'Account already absent on panel; recorded as terminated.');
        }
        if ($err !== null) {
            return $this->buildResult(false, $err);
        }

        $r = $api->api(
            'DELETE',
            '/v1/accounts/'.BhpanelClient::encodedUser($service->username),
            null,
            BhpanelClient::ACTION_TIMEOUT,
            $service
        );

        return $this->buildResult($r['ok'], $r['ok'] ? 'Hosting account terminated.' : $r['error']);
    }

    public function changePassword(Service $service, string $newPassword): array
    {
        if (strlen($newPassword) < 12 || strlen($newPassword) > 72) {
            return $this->buildResult(false, 'Control panel passwords must be between 12 and 72 characters.');
        }

        $api = $this->apiForService($service);
        if (! $api) {
            return $this->buildResult(false, 'No BHPanel server configured.');
        }

        if (($err = $api->ownAccount($service)) !== null) {
            return $this->buildResult(false, $err);
        }

        $r = $api->api(
            'POST',
            '/v1/accounts/'.BhpanelClient::encodedUser($service->username).'/password',
            ['password' => $newPassword],
            BhpanelClient::ACTION_TIMEOUT,
            $service
        );

        return $this->buildResult($r['ok'], $r['ok'] ? 'Control panel password updated.' : $r['error']);
    }

    public function changePackage(Service $service, array $newPackage): array
    {
        $cfg = is_string($newPackage['config_options'] ?? null)
            ? (json_decode($newPackage['config_options'], true) ?: [])
            : (array) ($newPackage['config_options'] ?? []);
        $plan = trim((string) ($cfg['package_name'] ?? $this->getRemotePackage($service)));

        if ($plan === '') {
            return $this->buildResult(false, 'The target product has no BHPanel plan configured.');
        }

        $api = $this->apiForService($service);
        if (! $api) {
            return $this->buildResult(false, 'No BHPanel server configured.');
        }

        if (($err = $api->ownAccount($service)) !== null) {
            return $this->buildResult(false, $err);
        }

        $r = $api->api(
            'POST',
            '/v1/accounts/'.BhpanelClient::encodedUser($service->username).'/plan',
            ['package' => $plan],
            BhpanelClient::ACTION_TIMEOUT,
            $service
        );

        return $this->buildResult($r['ok'], $r['ok'] ? "Hosting plan changed to {$plan}." : $r['error']);
    }

    public function usageUpdate(Server $server): array
    {
        $api = BhpanelClient::fromServer($server);
        if (! $api) {
            return ['success' => false, 'message' => 'Invalid BHPanel server token or hostname.'];
        }

        $services = Service::where('server_id', $server->id)
            ->whereIn('status', ['active', 'suspended'])
            ->get();

        $updated = 0;
        foreach ($services as $svc) {
            $u = strtolower(trim((string) $svc->username));
            if (preg_match('/^[a-z][a-z0-9]{2,15}\z/', $u) !== 1) {
                continue;
            }

            $r = $api->api('GET', '/v1/accounts/'.$u.'/usage', null, 20, $svc);
            if (! empty($r['transport'])) {
                return ['success' => false, 'message' => $r['error'], 'updated' => $updated];
            }
            if (! $r['ok'] || ! is_array($r['data'])) {
                continue;
            }

            $d = $r['data'];
            $replyDomain = strtolower((string) ($d['domain'] ?? ''));
            if ($replyDomain !== '' && $replyDomain !== BhpanelClient::cleanDomain($svc->domain)) {
                continue;
            }

            $svc->forceFill([
                'disk_usage' => (int) round((float) ($d['disk_used_mb'] ?? 0)),
                'disk_limit' => (int) round((float) ($d['disk_limit_mb'] ?? 0)),
                'bw_usage' => (int) round((float) ($d['bandwidth_used_mb'] ?? 0)),
                'bw_limit' => (int) round((float) ($d['bandwidth_limit_mb'] ?? 0)),
            ])->save();
            $updated++;
        }

        return ['success' => true, 'updated' => $updated];
    }

    public function liveUsage(Service $service): array
    {
        $api = $this->apiForService($service);
        if (! $api || strtolower((string) $service->status) !== 'active') {
            return ['available' => false];
        }

        $r = $api->accountCall($service, 'GET', '/usage', null, BhpanelClient::PAGE_TIMEOUT);
        if (! $r['ok'] || ! is_array($r['data'])) {
            return ['available' => false];
        }

        $d = $r['data'];

        return [
            'available' => true,
            'disk' => [
                'used_mb' => (float) ($d['disk_used_mb'] ?? 0),
                'quota_mb' => (float) ($d['disk_limit_mb'] ?? 0),
            ],
            'bandwidth' => [
                'used_mb' => (float) ($d['bandwidth_used_mb'] ?? 0),
                'limit_mb' => (float) ($d['bandwidth_limit_mb'] ?? 0),
            ],
        ];
    }

    public function ssoLogin(Service $service, string $destination = ''): array
    {
        $sorry = 'The control panel could not be opened right now. Please try again shortly.';
        if (strtolower((string) $service->status) !== 'active') {
            return $this->buildResult(false, 'This service is not active.');
        }

        $api = $this->apiForService($service);
        if (! $api) {
            return $this->buildResult(false, $sorry);
        }

        $to = in_array($destination, self::ALLOWED_DESTINATIONS, true) ? $destination : '';
        $r = $api->accountCall(
            $service,
            'POST',
            '/signin',
            $to !== '' ? ['to' => $to] : null,
            BhpanelClient::CLICK_TIMEOUT
        );

        $url = trim((string) ($r['data']['url'] ?? ''));
        if (! $r['ok'] || ! BhpanelClient::isValidSsoUrl($url)) {
            return $this->buildResult(false, $sorry);
        }

        return $this->buildResult(true, 'OK', ['url' => $url]);
    }

    public function adminServerSso(Server $server): array
    {
        $api = BhpanelClient::fromServer($server);
        if (! $api) {
            return $this->buildResult(false, 'Server lacks a valid BHPanel API token.');
        }

        $r = $api->api('POST', '/v1/signin', [], BhpanelClient::CLICK_TIMEOUT);
        $url = trim((string) ($r['data']['url'] ?? ''));

        if ($r['ok'] && BhpanelClient::isValidSsoUrl($url)) {
            return $this->buildResult(true, 'OK', ['url' => $url]);
        }

        return $this->buildResult(false, ($r['code'] ?? '') === 'forbidden'
            ? 'Token requires the "Panel" switch enabled on BHPanel for administrator SSO.'
            : $r['error']);
    }

    public function clientAreaPanel(Service $service): ?Htmlable
    {
        $service->loadMissing('server', 'product');
        $nameservers = [];
        if ($service->server) {
            foreach (['nameserver1', 'nameserver2', 'nameserver3', 'nameserver4'] as $nsCol) {
                $val = trim((string) ($service->server->{$nsCol} ?? ''));
                if ($val !== '') {
                    $nameservers[] = $val;
                }
            }
        }

        return view('bhpanel::overview', [
            'service' => $service,
            'isActive' => strtolower((string) $service->status) === 'active',
            'nameservers' => $nameservers,
        ]);
    }

    public function adminAreaPanel(Service $service): ?Htmlable
    {
        $service->loadMissing('server', 'product');
        $usage = null;
        $summary = null;

        $api = $this->apiForService($service);
        if ($api && in_array(strtolower((string) $service->status), ['active', 'suspended'], true)) {
            if ($api->ownAccount($service, false, BhpanelClient::PAGE_TIMEOUT) === null) {
                $u = $api->api('GET', '/v1/accounts/'.BhpanelClient::encodedUser($service->username).'/usage', null, BhpanelClient::PAGE_TIMEOUT, $service);
                $usage = $u['ok'] && is_array($u['data']) ? $u['data'] : null;

                if (empty($u['transport'])) {
                    $s = $api->api('GET', '/v1/accounts/'.BhpanelClient::encodedUser($service->username).'/summary', null, BhpanelClient::PAGE_TIMEOUT, $service);
                    $summary = $s['ok'] && is_array($s['data']) ? $s['data'] : null;
                }
            }
        }

        return view('bhpanel::admin-panel', [
            'service' => $service,
            'server' => $service->server,
            'usage' => $usage,
            'summary' => $summary,
        ]);
    }

    public function apiForService(Service $service): ?BhpanelClient
    {
        $server = $service->server ?: ($service->server_id ? Server::find($service->server_id) : $this->getServer($service));

        return BhpanelClient::fromServer($server);
    }

    private function findRecentAccount(BhpanelClient $api, int $serverId, int $serviceId, string $domain, string $user, string $email): ?string
    {
        $r = $api->api('GET', '/v1/accounts', null, BhpanelClient::CLICK_TIMEOUT);
        if (! $r['ok'] || ! is_array($r['data'])) {
            return null;
        }

        $list = is_array($r['data']['accounts'] ?? null) ? $r['data']['accounts'] : $r['data'];
        foreach ($list as $row) {
            $a = is_array($row['account'] ?? null) ? $row['account'] : $row;
            if (! is_array($a)) {
                continue;
            }
            $name = strtolower(trim((string) ($a['username'] ?? '')));
            $made = strtotime((string) ($a['created'] ?? '')) ?: 0;

            if ($name !== ''
                && strtolower((string) ($a['domain'] ?? '')) === $domain
                && ($user === '' || $name === $user)
                && strcasecmp((string) ($a['email'] ?? ''), $email) === 0
                && $made > time() - 1800) {
                $alreadyHeld = Service::where('server_id', $serverId)
                    ->where('username', $name)
                    ->where('id', '!=', $serviceId)
                    ->exists();
                if (! $alreadyHeld) {
                    return $name;
                }
            }
        }

        return null;
    }
}

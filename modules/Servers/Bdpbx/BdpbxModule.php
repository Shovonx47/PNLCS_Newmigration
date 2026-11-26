<?php

namespace Modules\Servers\Bdpbx;

use App\Models\CustomField;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Modules\Servers\AbstractServerModule;

class BdpbxModule extends AbstractServerModule
{
    public const NAME = 'bdpbx';

    public function getModuleName(): string
    {
        return self::NAME;
    }

    public function getConfigFields(): array
    {
        return [
            ['name' => 'api_token', 'label' => 'PBX Billing API Key', 'type' => 'password'],
        ];
    }

    /**
     * Product-level configuration fields rendered on Admin -> Products -> Edit.
     */
    public function productConfigFields(?Server $server = null): array
    {
        return [
            [
                'name' => 'ext_count',
                'label' => 'Package Extensions Count',
                'type' => 'number',
                'default' => '3',
                'description' => 'Default extensions created (e.g. 3 creates 101, 102, 103 + Free Voice SMS Ext 999).',
            ],
            [
                'name' => 'domain_suffix',
                'label' => 'Subdomain Suffix',
                'type' => 'text',
                'default' => 'bdpbx.com',
                'description' => 'Base domain for auto-generated tenant subdomains ({company}-pbx.bdpbx.com).',
            ],
            [
                'name' => 'default_did',
                'label' => 'Default Inbound DID',
                'type' => 'text',
                'default' => '',
                'description' => 'Optional default inbound DID / Caller ID (overridden by order custom field "DID").',
            ],
            [
                'name' => 'auto_cf_dns',
                'label' => 'Auto Cloudflare DNS Record',
                'type' => 'yesno',
                'default' => '0',
                'description' => 'Create/update A-record via Cloudflare API if wildcard DNS is not used.',
            ],
            [
                'name' => 'cf_zone_id',
                'label' => 'Cloudflare Zone ID',
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'cf_api_token',
                'label' => 'Cloudflare API Token',
                'type' => 'password',
                'default' => '',
            ],
        ];
    }

    public function servicePageHides(): array
    {
        // Abstract internal server node hostnames/IPs and generic password box from customers.
        return ['server_details', 'change_password'];
    }

    public function customFunctions(?Service $service = null): array
    {
        return [
            'syncStatus' => 'Sync Extensions & Status',
            'testApiConnection' => 'Test PBX Connection',
        ];
    }

    public function create(Service $service): array
    {
        $service->loadMissing('client', 'product', 'configOptions.option', 'configOptions.sub');
        $server = $this->getServer($service);
        $cfg = $this->productConfig($service);
        $clientApi = BdpbxClient::fromServer($server, $cfg);

        if (! $clientApi) {
            return $this->buildResult(false, 'No active BDPBX server or API credentials configured.');
        }

        $client = $service->client;
        $company = trim((string) ($client?->company_name ?? ''));
        $displayName = $company !== ''
            ? $company
            : (trim((string) (($client?->first_name ?? '').' '.($client?->last_name ?? ''))) ?: 'Client '.$service->id);

        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $displayName));
        if ($slug === '') {
            $slug = 'client'.$service->id;
        }

        $suffix = ltrim(trim((string) ($cfg['domain_suffix'] ?? 'bdpbx.com')), '.') ?: 'bdpbx.com';
        $domain = BdpbxClient::cleanDomain((string) $service->domain);
        if ($domain === '' || ! str_contains($domain, '.')) {
            $domain = "{$slug}-pbx.{$suffix}";
        }

        $adminUser = trim((string) $service->username);
        if ($adminUser === '' || strtolower($adminUser) === 'admin') {
            $adminUser = substr($slug, 0, 24);
        }

        $adminPass = (string) ($service->password ?: Str::password(14, symbols: true));

        $extCount = $this->resolveExtensionCount($service, $cfg);
        $did = $this->resolveDid($service, $cfg);

        $res = $clientApi->call('create', $domain, [
            'domain' => $domain,
            'company_name' => $displayName,
            'admin_username' => $adminUser,
            'admin_password' => $adminPass,
            'ext_count' => $extCount,
            'did' => $did,
        ]);

        if (($res['status'] ?? '') !== 'success') {
            $result = $this->buildResult(false, (string) ($res['message'] ?? 'PBX tenant provisioning failed.'));
            $this->logAction($service, 'create', $result);

            return $result;
        }

        $effectiveDomain = BdpbxClient::cleanDomain((string) ($res['domain'] ?? $domain)) ?: $domain;

        $service->forceFill([
            'domain' => $effectiveDomain,
            'username' => $adminUser,
            'password' => $adminPass,
        ])->save();

        $targetIp = trim((string) ($server?->ip_address ?: ($res['server_ip'] ?? '')));
        if (! empty($cfg['auto_cf_dns']) && (string) $cfg['auto_cf_dns'] !== '0' && $targetIp !== '') {
            BdpbxClient::ensureCloudflareDns(
                $effectiveDomain,
                $targetIp,
                (string) ($cfg['cf_api_token'] ?? ''),
                (string) ($cfg['cf_zone_id'] ?? '')
            );
        }

        $this->setModuleData($service, [
            'pbx_domain' => $effectiveDomain,
            'ext_count' => $extCount,
            'did' => $did,
            'package_extensions' => is_array($res['package_extensions'] ?? null) ? $res['package_extensions'] : [],
            'free_extension_999' => is_array($res['free_extension_999'] ?? null) ? $res['free_extension_999'] : null,
            'soft_terminated' => false,
            'provisioned_at' => now()->toIso8601String(),
        ]);

        $result = $this->buildResult(true, "BDPBX tenant {$effectiveDomain} provisioned with {$extCount} extensions (+ Ext 999).");
        $this->logAction($service, 'create', $result);

        return $result;
    }

    /**
     * Suspend package extensions while keeping Extension 999 active.
     */
    public function suspend(Service $service, string $reason = ''): array
    {
        $clientApi = $this->apiForService($service);
        if (! $clientApi) {
            return $this->buildResult(false, 'No PBX server configured for this service.');
        }

        $res = $clientApi->call('suspend', $this->serviceDomain($service));
        if (($res['status'] ?? '') !== 'success') {
            return $this->buildResult(false, (string) ($res['message'] ?? 'PBX suspension failed.'));
        }

        $this->setModuleData($service, ['suspended_at' => now()->toIso8601String()]);

        $result = $this->buildResult(true, 'Package extensions suspended; Voice SMS Extension 999 remains active.');
        $this->logAction($service, 'suspend', $result);

        return $result;
    }

    public function unsuspend(Service $service): array
    {
        $clientApi = $this->apiForService($service);
        if (! $clientApi) {
            return $this->buildResult(false, 'No PBX server configured for this service.');
        }

        $res = $clientApi->call('unsuspend', $this->serviceDomain($service));
        if (($res['status'] ?? '') !== 'success') {
            return $this->buildResult(false, (string) ($res['message'] ?? 'PBX reactivation failed.'));
        }

        $this->setModuleData($service, ['suspended_at' => null, 'soft_terminated' => false]);

        $result = $this->buildResult(true, 'All package extensions restored and reactivated.');
        $this->logAction($service, 'unsuspend', $result);

        return $result;
    }

    /**
     * Strictly enforces SoftCents "terminet hobe na" soft-lock policy:
     * locks extensions while preserving tenant domain, CDRs, and call recordings.
     */
    public function terminate(Service $service): array
    {
        $clientApi = $this->apiForService($service);
        if (! $clientApi) {
            return $this->buildResult(false, 'No PBX server configured for this service.');
        }

        $res = $clientApi->call('terminate', $this->serviceDomain($service));
        if (($res['status'] ?? '') !== 'success') {
            return $this->buildResult(false, (string) ($res['message'] ?? 'PBX soft-termination lock failed.'));
        }

        $this->setModuleData($service, [
            'soft_terminated' => true,
            'soft_terminated_at' => now()->toIso8601String(),
        ]);

        $result = $this->buildResult(true, 'Soft-lock applied ("terminet hobe na" policy): extensions locked, CDRs and recordings preserved.');
        $this->logAction($service, 'terminate', $result);

        return $result;
    }

    public function changePassword(Service $service, string $newPassword): array
    {
        return $this->buildResult(false, 'Manage individual extension SIP passwords from the PBX Extensions panel.');
    }

    public function changePackage(Service $service, array $newPackage): array
    {
        $clientApi = $this->apiForService($service);
        if (! $clientApi) {
            return $this->buildResult(false, 'No PBX server configured for this service.');
        }

        $newCfg = is_string($newPackage['config_options'] ?? null)
            ? (json_decode($newPackage['config_options'], true) ?: [])
            : (array) ($newPackage['config_options'] ?? []);

        $extCount = $this->resolveExtensionCount($service, $newCfg ?: $this->productConfig($service));
        $res = $clientApi->call('change_package', $this->serviceDomain($service), ['ext_count' => $extCount]);

        if (($res['status'] ?? '') !== 'success') {
            return $this->buildResult(false, (string) ($res['message'] ?? 'Failed to update extension package on PBX.'));
        }

        $this->setModuleData($service, ['ext_count' => $extCount]);

        $result = $this->buildResult(true, "PBX package updated to {$extCount} extensions.");
        $this->logAction($service, 'changePackage', $result);

        return $result;
    }

    public function usageUpdate(Server $server): array
    {
        return ['success' => true, 'updated' => 0];
    }

    public function testConnection(Server $server): bool
    {
        $clientApi = BdpbxClient::fromServer($server);
        if (! $clientApi) {
            return false;
        }

        $res = $clientApi->call('ping', $server->hostname ?: 'bdpbx.com');

        return ($res['status'] ?? '') === 'success';
    }

    public function ssoLogin(Service $service): array
    {
        $clientApi = $this->apiForService($service);
        if (! $clientApi) {
            return $this->buildResult(false, 'Cloud PBX portal sign-in is currently unavailable.');
        }

        $domain = $this->serviceDomain($service);
        $res = $clientApi->call('sso', $domain);
        $url = trim((string) ($res['sso_url'] ?? ''));

        if (($res['status'] ?? '') === 'success' && $this->isSafePortalUrl($url)) {
            return $this->buildResult(true, 'OK', ['url' => $url]);
        }

        return $this->buildResult(false, 'Unable to open the Cloud PBX portal right now. Please try again.');
    }

    public function superAdminSso(Server $server, ?string $requestedUser = null): array
    {
        $clientApi = BdpbxClient::fromServer($server);
        if (! $clientApi) {
            return $this->buildResult(false, 'Server API credentials are missing.');
        }

        $extra = ['is_superadmin' => true];
        if ($requestedUser !== null && $requestedUser !== '') {
            $extra['username'] = $requestedUser;
        }

        $res = $clientApi->call('superadmin_sso', $server->hostname ?: '', $extra);
        $url = trim((string) ($res['sso_url'] ?? ''));

        if (($res['status'] ?? '') === 'success' && $this->isSafePortalUrl($url)) {
            return $this->buildResult(true, 'OK', ['url' => $url]);
        }

        return $this->buildResult(false, (string) ($res['message'] ?? 'Failed to generate SuperAdmin SSO link.'));
    }

    public function syncStatus(Service $service): array
    {
        $clientApi = $this->apiForService($service);
        if (! $clientApi) {
            return $this->buildResult(false, 'No PBX server configured.');
        }

        $res = $clientApi->call('status', $this->serviceDomain($service));
        if (($res['status'] ?? '') !== 'success') {
            return $this->buildResult(false, (string) ($res['message'] ?? 'Could not sync PBX status.'));
        }

        $this->setModuleData($service, [
            'pbx_domain' => BdpbxClient::cleanDomain((string) ($res['domain'] ?? $this->serviceDomain($service))),
            'cached_extensions' => is_array($res['extensions'] ?? null) ? $res['extensions'] : [],
            'recordings_count_30d' => (int) ($res['recordings_count_30d'] ?? 0),
            'synced_at' => now()->toIso8601String(),
        ]);

        $count = (int) ($res['extension_count'] ?? count($res['extensions'] ?? []));

        return $this->buildResult(true, "Synced {$count} extensions from PBX.");
    }

    public function testApiConnection(Service $service): array
    {
        $clientApi = $this->apiForService($service);
        if (! $clientApi) {
            return $this->buildResult(false, 'No PBX server configured.');
        }

        $res = $clientApi->call('ping', $this->serviceDomain($service));

        return ($res['status'] ?? '') === 'success'
            ? $this->buildResult(true, 'PBX API connection succeeded.')
            : $this->buildResult(false, (string) ($res['message'] ?? 'PBX API ping failed.'));
    }

    public function updateExtensionPassword(Service $service, string $extension, string $newPassword, bool $isAdmin = false): array
    {
        $extension = trim($extension);
        $newPassword = trim($newPassword);

        if (! preg_match('/^[0-9]{2,8}$/', $extension)) {
            return $this->buildResult(false, 'Invalid extension number.');
        }

        if (! $isAdmin && $extension === '999') {
            return $this->buildResult(false, 'Extension 999 is reserved for automated Voice SMS operations and cannot be modified.');
        }

        if (strlen($newPassword) < 6 || strlen($newPassword) > 64) {
            return $this->buildResult(false, 'Extension password must be between 6 and 64 characters.');
        }

        $clientApi = $this->apiForService($service);
        if (! $clientApi) {
            return $this->buildResult(false, 'Unable to reach Cloud PBX service.');
        }

        $res = $clientApi->call('update_extension_password', $this->serviceDomain($service), [
            'extension' => $extension,
            'password' => $newPassword,
        ]);

        if (($res['status'] ?? '') !== 'success') {
            return $this->buildResult(false, $isAdmin
                ? (string) ($res['message'] ?? 'Failed to update extension password.')
                : 'Unable to update the extension password right now. Please try again.');
        }

        return $this->buildResult(true, "Password updated for Extension {$extension}.");
    }

    public function unblockIp(Service $service, string $ipAddress): array
    {
        $ipAddress = trim($ipAddress);
        if (! filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return $this->buildResult(false, 'Please enter a valid IPv4 or IPv6 address.');
        }

        $clientApi = $this->apiForService($service);
        if (! $clientApi) {
            return $this->buildResult(false, 'Network unblocking is temporarily unavailable.');
        }

        $res = $clientApi->call('unblock_ip', $this->serviceDomain($service), ['ip' => $ipAddress]);
        if (($res['status'] ?? '') !== 'success') {
            return $this->buildResult(false, 'Unable to unblock the specified IP address right now.');
        }

        return $this->buildResult(true, "Access restored for IP {$ipAddress}. You may now reconnect your devices.");
    }

    public function clientAreaPanel(Service $service): ?Htmlable
    {
        $domain = $this->serviceDomain($service);
        $data = $this->getModuleData($service);
        $extensions = [];
        $recordingsCount = (int) ($data['recordings_count_30d'] ?? 0);
        $visitorIp = (string) (request()->ip() ?? '');
        $ipBlocked = false;

        $clientApi = $this->apiForService($service);
        if ($clientApi && $domain !== '' && in_array(strtolower((string) $service->status), ['active', 'suspended'], true)) {
            $statusRes = $clientApi->call('status', $domain);
            if (($statusRes['status'] ?? '') === 'success') {
                $domain = BdpbxClient::cleanDomain((string) ($statusRes['domain'] ?? $domain)) ?: $domain;
                $extensions = is_array($statusRes['extensions'] ?? null) ? $statusRes['extensions'] : [];
                $recordingsCount = (int) ($statusRes['recordings_count_30d'] ?? $recordingsCount);
            }

            if (filter_var($visitorIp, FILTER_VALIDATE_IP)) {
                $check = $clientApi->call('check_ip', $domain, ['ip' => $visitorIp]);
                $ipBlocked = ! empty($check['is_blocked']);
            }
        }

        if ($extensions === [] && ! empty($data['package_extensions'])) {
            foreach ((array) $data['package_extensions'] as $pe) {
                if (is_array($pe) && isset($pe['extension'])) {
                    $extensions[] = [
                        'extension' => $pe['extension'],
                        'effective_caller_id_name' => 'Extension '.$pe['extension'],
                        'password' => $pe['password'] ?? '',
                        'enabled' => strtolower((string) $service->status) === 'active',
                    ];
                }
            }
            if (is_array($data['free_extension_999'] ?? null)) {
                $extensions[] = [
                    'extension' => '999',
                    'effective_caller_id_name' => 'Voice SMS & OTP',
                    'password' => '',
                    'enabled' => true,
                ];
            }
        }

        return view('bdpbx::client-panel', [
            'service' => $service,
            'domain' => $domain,
            'portalUrl' => $domain !== '' ? 'https://'.$domain : null,
            'username' => $service->username ?: ($domain !== '' ? explode('.', $domain)[0] : '—'),
            'password' => (string) ($service->password ?? ''),
            'extensions' => $extensions,
            'recordingsCount' => $recordingsCount,
            'visitorIp' => $visitorIp,
            'ipBlocked' => $ipBlocked,
            'isSuspended' => strtolower((string) $service->status) === 'suspended',
        ]);
    }

    public function adminAreaPanel(Service $service): ?Htmlable
    {
        $domain = $this->serviceDomain($service);
        $data = $this->getModuleData($service);
        $extensions = is_array($data['cached_extensions'] ?? null) ? $data['cached_extensions'] : [];
        $recordingsCount = (int) ($data['recordings_count_30d'] ?? 0);

        $clientApi = $this->apiForService($service);
        if ($clientApi && $domain !== '') {
            $statusRes = $clientApi->call('status', $domain);
            if (($statusRes['status'] ?? '') === 'success') {
                $extensions = is_array($statusRes['extensions'] ?? null) ? $statusRes['extensions'] : $extensions;
                $recordingsCount = (int) ($statusRes['recordings_count_30d'] ?? $recordingsCount);
            }
        }

        return view('bdpbx::admin-panel', [
            'service' => $service,
            'server' => $service->server,
            'domain' => $domain,
            'extensions' => $extensions,
            'recordingsCount' => $recordingsCount,
            'moduleData' => $data,
        ]);
    }

    public function apiForService(Service $service): ?BdpbxClient
    {
        $server = $service->server ?: ($service->server_id ? Server::find($service->server_id) : $this->getServer($service));

        return BdpbxClient::fromServer($server, $this->productConfig($service));
    }

    public function serviceDomain(Service $service): string
    {
        $data = $this->getModuleData($service);

        return BdpbxClient::cleanDomain((string) ($service->domain ?: ($data['pbx_domain'] ?? '')));
    }

    private function productConfig(Service $service): array
    {
        $raw = $service->product?->config_options;

        return is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
    }

    private function resolveExtensionCount(Service $service, array $cfg): int
    {
        $service->loadMissing('configOptions.option', 'configOptions.sub');
        foreach ($service->configOptions as $opt) {
            $optName = strtolower((string) ($opt->option?->option_name ?? ''));
            if (str_contains($optName, 'ext')) {
                if ((int) $opt->qty > 0 && $opt->option?->isQuantity()) {
                    return (int) $opt->qty;
                }
                $subName = (string) ($opt->sub?->option_name ?? '');
                if (preg_match('/([0-9]+)/', $subName, $m) && (int) $m[1] > 0) {
                    return (int) $m[1];
                }
            }
        }

        if ($service->product_id) {
            foreach (CustomField::productFields((int) $service->product_id)->get() as $field) {
                if (str_contains(strtolower((string) $field->field_name), 'ext')) {
                    $val = (int) $field->valueFor($service->id);
                    if ($val > 0) {
                        return $val;
                    }
                }
            }
        }

        $configured = (int) ($cfg['ext_count'] ?? 3);

        return $configured > 0 ? $configured : 3;
    }

    private function resolveDid(Service $service, array $cfg): string
    {
        if ($service->product_id) {
            foreach (CustomField::productFields((int) $service->product_id)->get() as $field) {
                $name = strtolower((string) $field->field_name);
                if (str_contains($name, 'did') || str_contains($name, 'caller') || str_contains($name, 'phone')) {
                    $val = trim((string) $field->valueFor($service->id));
                    if ($val !== '') {
                        return $val;
                    }
                }
            }
        }

        return trim((string) ($cfg['default_did'] ?? ''));
    }

    private function isSafePortalUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return false;
        }

        if ($scheme === 'https') {
            return true;
        }

        return $scheme === 'http'
            && app()->environment(['local', 'testing'])
            && in_array($host, ['127.0.0.1', 'localhost'], true);
    }
}

<?php

namespace Modules\Servers\SoftcentsVsms;

use App\Models\Server;
use App\Models\Service;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Modules\Servers\AbstractServerModule;
use Modules\Servers\SoftcentsSmsPanel\SoftcentsProvisioningClient;
use Modules\Servers\SoftcentsSmsPanel\SoftcentsSmsPanelModule;

class SoftcentsVsmsModule extends AbstractServerModule
{
    public const NAME = 'softcents_vsms';

    public function getModuleName(): string
    {
        return self::NAME;
    }

    public function getConfigFields(): array
    {
        return [
            ['name' => 'api_token', 'label' => 'Voice SMS Provisioning Bearer Token', 'type' => 'password'],
        ];
    }

    public function servicePageHides(): array
    {
        return ['server_details'];
    }

    public function customFunctions(?Service $service = null): array
    {
        return [
            'adminSync' => 'Sync Voice SMS Account',
        ];
    }

    public function listPackages(Server $server): array
    {
        $api = SoftcentsProvisioningClient::fromServer($server, 'vsms.softcents.com', self::NAME);
        if (! $api) {
            return [];
        }

        $res = $api->getResources();
        $plans = $res['data']['tariff_plans'] ?? [];
        if (! $res['success'] || ! is_array($plans)) {
            return [];
        }

        $out = [['id' => '0', 'name' => 'Default Enabled Tariff Plan']];
        foreach ($plans as $plan) {
            $id = (string) ($plan['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $rate = number_format((float) ($plan['selling_rate'] ?? 0), 2);
            $dur = (int) ($plan['pulse_duration'] ?? 30);
            $tag = ! empty($plan['is_default']) ? ' [Default]' : '';
            $out[] = [
                'id' => $id,
                'name' => ($plan['name'] ?? 'Tariff '.$id)." ({$rate} BDT / {$dur}s){$tag}",
            ];
        }

        return $out;
    }

    public function productConfigFields(?Server $server = null): array
    {
        $callerChoices = ['' => '-- None / Extra Caller IDs Only --'];
        if ($server && ($api = SoftcentsProvisioningClient::fromServer($server, 'vsms.softcents.com', self::NAME))) {
            $res = $api->getResources();
            foreach ((array) ($res['data']['callers'] ?? []) as $caller) {
                $id = (string) ($caller['id'] ?? '');
                if ($id !== '') {
                    $callerChoices[$id] = (string) ($caller['label'] ?? (($caller['caller_name'] ?? '').' ('.($caller['caller_number'] ?? $id).')'));
                }
            }
        }

        return [
            [
                'name' => 'account_type',
                'label' => 'Account Role',
                'type' => 'select',
                'default' => 'user',
                'options' => [
                    'user' => 'Customer (user)',
                    'sp' => 'Reseller (sp)',
                    'ssp' => 'Sub-Reseller (ssp)',
                ],
            ],
            [
                'name' => 'manual_plan_id',
                'label' => 'Manual Tariff Plan ID Override',
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'initial_balance',
                'label' => 'Initial Wallet Balance (BDT)',
                'type' => 'text',
                'default' => '0',
                'description' => 'Credited on initial order provisioning (supports Configurable Option "Balance"/"Credit").',
            ],
            [
                'name' => 'renewal_balance',
                'label' => 'Renewal Wallet Balance (BDT)',
                'type' => 'text',
                'default' => '0',
                'description' => 'Credited automatically each time a recurring renewal invoice is paid.',
            ],
            [
                'name' => 'default_caller_id',
                'label' => 'Primary Caller CLI',
                'type' => 'select',
                'default' => '',
                'options' => $callerChoices,
            ],
            [
                'name' => 'extra_caller_ids',
                'label' => 'Extra Caller IDs (comma-separated)',
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'pulse_rate',
                'label' => 'Pulse Rate Override (BDT)',
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'pulse_duration',
                'label' => 'Pulse Duration Override (Seconds)',
                'type' => 'number',
                'default' => '',
            ],
            [
                'name' => 'existing_mode',
                'label' => 'Existing Email Behavior',
                'type' => 'select',
                'default' => 'reuse_and_credit',
                'options' => [
                    'reuse_and_credit' => 'Top-Up & Link Existing Account',
                    'error' => 'Fail if Email Already Exists',
                ],
            ],
            [
                'name' => 'auto_approve',
                'label' => 'Auto-Approve Audio & Campaigns',
                'type' => 'yesno',
                'default' => '1',
            ],
        ];
    }

    public function create(Service $service): array
    {
        $service->loadMissing('client', 'product', 'configOptions.option', 'configOptions.sub');
        $server = $this->getServer($service);
        $api = SoftcentsProvisioningClient::fromServer($server, 'vsms.softcents.com', self::NAME);

        if (! $api) {
            return $this->buildResult(false, 'No active Softcents Voice SMS server configured.');
        }

        $client = $service->client;
        $email = trim((string) ($client?->email ?? ''));
        if ($email === '') {
            return $this->buildResult(false, 'Client email address is required to provision a Voice SMS account.');
        }

        $cfg = $this->productConfig($service);
        $overrides = SoftcentsSmsPanelModule::extractConfigurableOverrides($service);

        $fullName = trim((string) (($client?->first_name ?? '').' '.($client?->last_name ?? '')))
            ?: (string) ($client?->company_name ?: 'Voice SMS Customer');
        $phone = trim((string) ($client?->full_phone ?: ($client?->phone_number ?? '')));
        $company = trim((string) ($client?->company_name ?? ''));

        $accountType = $overrides['account_type'] ?? ((string) ($cfg['account_type'] ?? 'user') ?: 'user');
        $manualPlan = trim((string) ($cfg['manual_plan_id'] ?? ''));
        $tariffPlanId = $overrides['plan_id'] ?? ($manualPlan !== '' ? (int) $manualPlan : (int) ($cfg['package_name'] ?? 0));

        $initialBalance = isset($overrides['balance'])
            ? (float) $overrides['balance']
            : (float) ($cfg['initial_balance'] ?? 0);
        if (! empty($overrides['extra_balance'])) {
            $initialBalance += (float) $overrides['extra_balance'];
        }

        $callerIds = $this->collectIds($cfg['default_caller_id'] ?? '', $cfg['extra_caller_ids'] ?? '', $overrides['caller_ids'] ?? []);
        $existingMode = ($cfg['existing_mode'] ?? '') === 'error' ? 'error' : 'reuse_and_credit';
        $autoApprove = (string) ($cfg['auto_approve'] ?? '1') !== '0';

        $password = (string) ($service->password ?: Str::password(12, symbols: false));

        $payload = [
            'name' => $fullName,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'company_name' => $company !== '' ? $company : null,
            'username' => trim((string) $service->username) ?: null,
            'password' => $password,
            'account_type' => $accountType,
            'tariff_plan_id' => $tariffPlanId > 0 ? $tariffPlanId : null,
            'caller_ids' => $callerIds,
            'initial_balance' => max(0, round($initialBalance, 4)),
            'existing_user_mode' => $existingMode,
            'reference' => 'create_svc_'.$service->id,
            'whmcs_service_id' => (string) $service->id,
            'whmcs_client_id' => (string) $service->client_id,
            'auto_approve_audio' => $autoApprove,
            'auto_approve_campaigns' => $autoApprove,
            'update_password_if_exists' => false,
        ];

        if (($cfg['pulse_rate'] ?? '') !== '' && is_numeric($cfg['pulse_rate'])) {
            $payload['pulse_rate'] = (float) $cfg['pulse_rate'];
        }
        if (($cfg['pulse_duration'] ?? '') !== '' && is_numeric($cfg['pulse_duration'])) {
            $payload['pulse_duration'] = (int) $cfg['pulse_duration'];
        }

        $res = $api->provisionAccount($payload);
        $remoteUser = $res['data']['user'] ?? null;

        if (! $res['success'] || empty($remoteUser['id'])) {
            return $this->buildResult(false, (string) ($res['message'] ?? 'Voice SMS provisioning failed.'));
        }

        $portalHost = preg_replace('#^https?://#i', '', trim((string) ($server->hostname ?: 'vsms.softcents.com')));

        $updates = [
            'username' => (string) ($remoteUser['username'] ?? $service->username),
            'domain' => $portalHost,
        ];
        if (! empty($res['data']['created'])) {
            $updates['password'] = $password;
        }
        $service->forceFill($updates)->save();

        $this->setModuleData($service, [
            'remote_user_id' => (int) $remoteUser['id'],
            'portal_host' => $portalHost,
            'provisioned_at' => now()->toIso8601String(),
        ]);

        $result = $this->buildResult(true, 'Softcents Voice SMS account #'.$remoteUser['id'].' provisioned.');
        $this->logAction($service, 'create', $result);

        return $result;
    }

    public function renew(Service $service): array
    {
        $service->loadMissing('client', 'product', 'configOptions.option', 'configOptions.sub');
        $cfg = $this->productConfig($service);
        $overrides = SoftcentsSmsPanelModule::extractConfigurableOverrides($service);

        $renewalBalance = (float) ($cfg['renewal_balance'] ?? 0);
        if ($renewalBalance <= 0 && isset($overrides['balance']) && (float) $overrides['balance'] > 0) {
            $renewalBalance = (float) $overrides['balance'];
        }
        if (! empty($overrides['extra_balance'])) {
            $renewalBalance += (float) $overrides['extra_balance'];
        }

        if ($renewalBalance <= 0) {
            return $this->buildResult(true, 'No renewal wallet credit configured.');
        }

        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote Voice SMS account for renewal credit.');
        }

        $invoiceId = SoftcentsSmsPanelModule::latestPaidInvoiceId($service->id);
        $reference = $invoiceId > 0
            ? "renew_svc_{$service->id}_inv_{$invoiceId}"
            : "renew_svc_{$service->id}_".now()->format('YmdH');

        $res = $api->creditAccount(
            $userId,
            round($renewalBalance, 4),
            $reference,
            "Renewal wallet credit (Service #{$service->id}".($invoiceId > 0 ? ", Invoice #{$invoiceId}" : '').')',
            [
                'whmcs_service_id' => (string) $service->id,
                'whmcs_invoice_id' => $invoiceId > 0 ? (string) $invoiceId : null,
            ]
        );

        if (! $res['success']) {
            return $this->buildResult(false, (string) ($res['message'] ?? 'Failed to apply Voice SMS renewal credit.'));
        }

        $result = $this->buildResult(true, 'Credited '.number_format($renewalBalance, 2).' BDT to Voice SMS wallet.');
        $this->logAction($service, 'renew', $result);

        return $result;
    }

    public function suspend(Service $service, string $reason = ''): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote Voice SMS account.');
        }

        $res = $api->suspendAccount($userId, $reason);

        return $this->buildResult($res['success'], $res['success'] ? 'Voice SMS account suspended.' : $res['message']);
    }

    public function unsuspend(Service $service): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote Voice SMS account.');
        }

        $res = $api->unsuspendAccount($userId);

        return $this->buildResult($res['success'], $res['success'] ? 'Voice SMS account unsuspended.' : $res['message']);
    }

    public function terminate(Service $service): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote Voice SMS account.');
        }

        $res = $api->terminateAccount($userId, true);
        if ($res['success']) {
            $this->setModuleData($service, ['remote_user_id' => null, 'terminated_at' => now()->toIso8601String()]);
        }

        return $this->buildResult($res['success'], $res['success'] ? 'Voice SMS account terminated.' : $res['message']);
    }

    public function changePassword(Service $service, string $newPassword): array
    {
        if (strlen($newPassword) < 6) {
            return $this->buildResult(false, 'Password must be at least 6 characters.');
        }

        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote Voice SMS account.');
        }

        $res = $api->changePassword($userId, $newPassword);

        return $this->buildResult($res['success'], $res['success'] ? 'Voice SMS portal password updated.' : $res['message']);
    }

    public function changePackage(Service $service, array $newPackage): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote Voice SMS account.');
        }

        $cfg = is_string($newPackage['config_options'] ?? null)
            ? (json_decode($newPackage['config_options'], true) ?: [])
            : (array) ($newPackage['config_options'] ?? $this->productConfig($service));
        $overrides = SoftcentsSmsPanelModule::extractConfigurableOverrides($service);

        $manualPlan = trim((string) ($cfg['manual_plan_id'] ?? ''));
        $tariffPlanId = $overrides['plan_id'] ?? ($manualPlan !== '' ? (int) $manualPlan : (int) ($cfg['package_name'] ?? 0));
        $callerIds = $this->collectIds($cfg['default_caller_id'] ?? '', $cfg['extra_caller_ids'] ?? '', $overrides['caller_ids'] ?? []);

        $payload = [
            'account_type' => $overrides['account_type'] ?? ((string) ($cfg['account_type'] ?? 'user') ?: 'user'),
        ];
        if ($tariffPlanId > 0) {
            $payload['tariff_plan_id'] = $tariffPlanId;
        }
        if ($callerIds !== []) {
            $payload['caller_ids'] = $callerIds;
        }
        if (($cfg['pulse_rate'] ?? '') !== '' && is_numeric($cfg['pulse_rate'])) {
            $payload['pulse_rate'] = (float) $cfg['pulse_rate'];
        }
        if (($cfg['pulse_duration'] ?? '') !== '' && is_numeric($cfg['pulse_duration'])) {
            $payload['pulse_duration'] = (int) $cfg['pulse_duration'];
        }

        $res = $api->changePackage($userId, $payload);

        return $this->buildResult($res['success'], $res['success'] ? 'Voice SMS package updated.' : $res['message']);
    }

    public function usageUpdate(Server $server): array
    {
        return ['success' => true, 'updated' => 0];
    }

    public function testConnection(Server $server): bool
    {
        $api = SoftcentsProvisioningClient::fromServer($server, 'vsms.softcents.com', self::NAME);

        return $api ? $api->ping()['success'] : false;
    }

    public function ssoLogin(Service $service): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Voice SMS portal login is not ready yet.');
        }

        $res = $api->generateSsoUrl($userId);
        $url = trim((string) ($res['data']['sso_url'] ?? ''));

        if ($res['success'] && (str_starts_with($url, 'https://') || (app()->environment(['local', 'testing']) && str_starts_with($url, 'http://127.0.0.1')))) {
            return $this->buildResult(true, 'OK', ['url' => $url]);
        }

        return $this->buildResult(false, 'Could not open the Voice SMS portal right now. Please try again.');
    }

    public function adminSync(Service $service): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Remote Voice SMS account could not be found.');
        }

        $res = $api->getAccount($userId);
        $u = $res['data']['user'] ?? null;
        if (! $res['success'] || ! is_array($u)) {
            return $this->buildResult(false, $res['message'] ?: 'Failed to sync Voice SMS account.');
        }

        $service->forceFill(['username' => (string) ($u['username'] ?? $service->username)])->save();
        $this->setModuleData($service, ['remote_user_id' => (int) $u['id'], 'synced_at' => now()->toIso8601String()]);

        return $this->buildResult(true, 'Synced Voice SMS account #'.$u['id'].' (Balance: '.number_format((float) ($u['balance'] ?? 0), 2).' BDT).');
    }

    public function clientAreaPanel(Service $service): ?Htmlable
    {
        $api = $this->apiForService($service);
        $userId = ($api && in_array(strtolower((string) $service->status), ['active', 'suspended'], true))
            ? $this->resolveUserId($api, $service)
            : 0;
        $account = null;

        if ($api && $userId) {
            $res = $api->getAccount($userId);
            if ($res['success'] && is_array($res['data']['user'] ?? null)) {
                $account = $res['data']['user'];
            }
        }

        $host = preg_replace('#^https?://#i', '', trim((string) ($service->server?->hostname ?: ($service->domain ?: 'vsms.softcents.com'))));

        return view('softcents_vsms::client-panel', [
            'service' => $service,
            'portalUrl' => 'https://'.$host.'/login',
            'account' => $account,
        ]);
    }

    public function adminAreaPanel(Service $service): ?Htmlable
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        $account = null;

        if ($api && $userId) {
            $res = $api->getAccount($userId);
            if ($res['success'] && is_array($res['data']['user'] ?? null)) {
                $account = $res['data']['user'];
            }
        }

        return view('softcents_vsms::admin-panel', [
            'service' => $service,
            'userId' => $userId,
            'account' => $account,
        ]);
    }

    private function apiForService(Service $service): ?SoftcentsProvisioningClient
    {
        $server = $service->server ?: ($service->server_id ? Server::find($service->server_id) : $this->getServer($service));

        return SoftcentsProvisioningClient::fromServer($server, 'vsms.softcents.com', self::NAME);
    }

    private function resolveUserId(SoftcentsProvisioningClient $api, Service $service): int
    {
        $data = $this->getModuleData($service);
        $stored = (int) ($data['remote_user_id'] ?? $data['subscriptionid'] ?? 0);
        if ($stored > 0) {
            return $stored;
        }

        $service->loadMissing('client');
        $email = trim((string) ($service->client?->email ?? ''));
        $username = trim((string) $service->username);

        if ($email !== '' || $username !== '') {
            $lookup = $api->lookupAccount($email, $username);
            $uid = (int) ($lookup['data']['user']['id'] ?? 0);
            if ($lookup['success'] && $uid > 0) {
                $this->setModuleData($service, ['remote_user_id' => $uid]);

                return $uid;
            }
        }

        return 0;
    }

    private function collectIds(mixed $primary, mixed $extraCsv, array $overrideIds): array
    {
        $ids = [];
        if (is_numeric($primary) && (int) $primary > 0) {
            $ids[] = (int) $primary;
        }
        foreach (preg_split('/[\s,;]+/', (string) $extraCsv, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            if (is_numeric($part) && (int) $part > 0) {
                $ids[] = (int) $part;
            }
        }
        foreach ($overrideIds as $id) {
            if ((int) $id > 0) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function productConfig(Service $service): array
    {
        $raw = $service->product?->config_options;

        return is_string($raw) ? (json_decode($raw, true) ?: []) : (is_array($raw) ? $raw : []);
    }
}

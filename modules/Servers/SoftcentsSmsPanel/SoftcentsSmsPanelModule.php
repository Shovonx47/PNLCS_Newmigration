<?php

namespace Modules\Servers\SoftcentsSmsPanel;

use App\Enums\InvoiceStatus;
use App\Models\InvoiceItem;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Modules\Servers\AbstractServerModule;

class SoftcentsSmsPanelModule extends AbstractServerModule
{
    public const NAME = 'softcents_sms_panel';

    public function getModuleName(): string
    {
        return self::NAME;
    }

    public function getConfigFields(): array
    {
        return [
            ['name' => 'api_token', 'label' => 'SMS Provisioning Bearer Token', 'type' => 'password'],
        ];
    }

    public function servicePageHides(): array
    {
        return ['server_details'];
    }

    public function customFunctions(?Service $service = null): array
    {
        return [
            'adminSync' => 'Sync SMS Account',
        ];
    }

    public function listPackages(Server $server): array
    {
        $client = SoftcentsProvisioningClient::fromServer($server, 'sms.softcents.com', self::NAME);
        if (! $client) {
            return [];
        }

        $res = $client->getResources();
        $plans = $res['data']['plans'] ?? [];
        if (! $res['success'] || ! is_array($plans)) {
            return [];
        }

        $out = [['id' => '0', 'name' => 'Custom Rates / No Plan']];
        foreach ($plans as $plan) {
            $id = (string) ($plan['id'] ?? '');
            if ($id === '') {
                continue;
            }
            $m = number_format((float) ($plan['masking_rate'] ?? 0), 2);
            $nm = number_format((float) ($plan['non_masking_rate'] ?? 0), 2);
            $out[] = [
                'id' => $id,
                'name' => ($plan['title'] ?? 'Plan '.$id)." (NM: {$nm} / M: {$m} BDT)",
            ];
        }

        return $out;
    }

    public function productConfigFields(?Server $server = null): array
    {
        $senderChoices = ['' => '-- None / Extra Sender IDs Only --'];
        if ($server && ($client = SoftcentsProvisioningClient::fromServer($server, 'sms.softcents.com', self::NAME))) {
            $res = $client->getResources();
            foreach ((array) ($res['data']['senderids'] ?? []) as $sid) {
                $id = (string) ($sid['id'] ?? '');
                if ($id !== '') {
                    $senderChoices[$id] = (string) ($sid['label'] ?? (($sid['sender'] ?? $id).' ('.($sid['type'] ?? 'Non-Masking').')'));
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
                'label' => 'Manual Plan ID Override',
                'type' => 'text',
                'default' => '',
                'description' => 'Optional numeric SMS Plan ID override (leave blank to use selected Package).',
            ],
            [
                'name' => 'initial_balance',
                'label' => 'Initial Wallet Balance (BDT)',
                'type' => 'text',
                'default' => '0',
                'description' => 'Credited on initial order provisioning (also reads Configurable Option "Balance"/"Credit").',
            ],
            [
                'name' => 'renewal_balance',
                'label' => 'Renewal Wallet Balance (BDT)',
                'type' => 'text',
                'default' => '0',
                'description' => 'Credited automatically each time a recurring renewal invoice is paid.',
            ],
            [
                'name' => 'default_sender_id',
                'label' => 'Primary Sender ID',
                'type' => 'select',
                'default' => '',
                'options' => $senderChoices,
            ],
            [
                'name' => 'extra_sender_ids',
                'label' => 'Extra Sender IDs (comma-separated)',
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'masking_rate',
                'label' => 'Masking Rate Override (BDT)',
                'type' => 'text',
                'default' => '',
            ],
            [
                'name' => 'non_masking_rate',
                'label' => 'Non-Masking Rate Override (BDT)',
                'type' => 'text',
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
                'name' => 'allow_api',
                'label' => 'Enable Developer API Access',
                'type' => 'yesno',
                'default' => '1',
            ],
        ];
    }

    public function create(Service $service): array
    {
        $service->loadMissing('client', 'product', 'configOptions.option', 'configOptions.sub');
        $server = $this->getServer($service);
        $api = SoftcentsProvisioningClient::fromServer($server, 'sms.softcents.com', self::NAME);

        if (! $api) {
            return $this->buildResult(false, 'No active Softcents Bulk SMS server configured.');
        }

        $client = $service->client;
        $email = trim((string) ($client?->email ?? ''));
        if ($email === '') {
            return $this->buildResult(false, 'Client email address is required to provision an SMS account.');
        }

        $cfg = $this->productConfig($service);
        $overrides = self::extractConfigurableOverrides($service);

        $fullName = trim((string) (($client?->first_name ?? '').' '.($client?->last_name ?? '')))
            ?: (string) ($client?->company_name ?: 'SMS Customer');
        $phone = trim((string) ($client?->full_phone ?: ($client?->phone_number ?? '')));
        $company = trim((string) ($client?->company_name ?? ''));

        $accountType = $overrides['account_type'] ?? ((string) ($cfg['account_type'] ?? 'user') ?: 'user');
        $manualPlan = trim((string) ($cfg['manual_plan_id'] ?? ''));
        $planId = $overrides['plan_id'] ?? ($manualPlan !== '' ? (int) $manualPlan : (int) ($cfg['package_name'] ?? 0));

        $initialBalance = isset($overrides['balance'])
            ? (float) $overrides['balance']
            : (float) ($cfg['initial_balance'] ?? 0);
        if (! empty($overrides['extra_balance'])) {
            $initialBalance += (float) $overrides['extra_balance'];
        }

        $senderIds = $this->collectIds($cfg['default_sender_id'] ?? '', $cfg['extra_sender_ids'] ?? '', $overrides['sender_ids'] ?? []);
        $existingMode = ($cfg['existing_mode'] ?? '') === 'error' ? 'error' : 'reuse_and_credit';
        $allowApi = (string) ($cfg['allow_api'] ?? '1') !== '0';

        $password = (string) ($service->password ?: Str::password(12, symbols: false));

        $payload = [
            'name' => $fullName,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'company_name' => $company !== '' ? $company : null,
            'username' => trim((string) $service->username) ?: null,
            'password' => $password,
            'account_type' => $accountType,
            'plan_id' => $planId > 0 ? $planId : null,
            'sender_ids' => $senderIds,
            'initial_balance' => max(0, round($initialBalance, 4)),
            'existing_user_mode' => $existingMode,
            'reference' => 'create_svc_'.$service->id,
            'whmcs_service_id' => (string) $service->id,
            'whmcs_client_id' => (string) $service->client_id,
            'allow_api' => $allowApi,
            'update_password_if_exists' => false,
        ];

        if (($cfg['masking_rate'] ?? '') !== '' && is_numeric($cfg['masking_rate'])) {
            $payload['masking_rate'] = (float) $cfg['masking_rate'];
        }
        if (($cfg['non_masking_rate'] ?? '') !== '' && is_numeric($cfg['non_masking_rate'])) {
            $payload['non_masking_rate'] = (float) $cfg['non_masking_rate'];
        }

        $res = $api->provisionAccount($payload);
        $remoteUser = $res['data']['user'] ?? null;

        if (! $res['success'] || empty($remoteUser['id'])) {
            return $this->buildResult(false, (string) ($res['message'] ?? 'SMS provisioning failed.'));
        }

        $portalHost = preg_replace('#^https?://#i', '', trim((string) ($server->hostname ?: 'sms.softcents.com')));

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

        $result = $this->buildResult(true, 'Softcents Bulk SMS account #'.$remoteUser['id'].' provisioned.');
        $this->logAction($service, 'create', $result);

        return $result;
    }

    public function renew(Service $service): array
    {
        $service->loadMissing('client', 'product', 'configOptions.option', 'configOptions.sub');
        $cfg = $this->productConfig($service);
        $overrides = self::extractConfigurableOverrides($service);

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
        if (! $api) {
            return $this->buildResult(false, 'SMS server unavailable for renewal credit.');
        }

        $userId = $this->resolveUserId($api, $service);
        if (! $userId) {
            return $this->buildResult(false, 'Could not locate remote SMS account for renewal credit.');
        }

        $invoiceId = self::latestPaidInvoiceId($service->id);
        $reference = $invoiceId > 0
            ? "renew_inv_{$invoiceId}_svc_{$service->id}"
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
            return $this->buildResult(false, (string) ($res['message'] ?? 'Failed to apply SMS renewal credit.'));
        }

        $result = $this->buildResult(true, 'Credited '.number_format($renewalBalance, 2).' BDT to SMS wallet.');
        $this->logAction($service, 'renew', $result);

        return $result;
    }

    public function suspend(Service $service, string $reason = ''): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote SMS account.');
        }

        $res = $api->suspendAccount($userId, $reason);

        return $this->buildResult($res['success'], $res['success'] ? 'SMS account suspended.' : $res['message']);
    }

    public function unsuspend(Service $service): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote SMS account.');
        }

        $res = $api->unsuspendAccount($userId);

        return $this->buildResult($res['success'], $res['success'] ? 'SMS account unsuspended.' : $res['message']);
    }

    public function terminate(Service $service): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote SMS account.');
        }

        $res = $api->terminateAccount($userId);

        return $this->buildResult($res['success'], $res['success'] ? 'SMS account terminated.' : $res['message']);
    }

    public function changePassword(Service $service, string $newPassword): array
    {
        if (strlen($newPassword) < 6) {
            return $this->buildResult(false, 'Password must be at least 6 characters.');
        }

        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote SMS account.');
        }

        $res = $api->changePassword($userId, $newPassword);

        return $this->buildResult($res['success'], $res['success'] ? 'SMS portal password updated.' : $res['message']);
    }

    public function changePackage(Service $service, array $newPackage): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Could not locate remote SMS account.');
        }

        $cfg = is_string($newPackage['config_options'] ?? null)
            ? (json_decode($newPackage['config_options'], true) ?: [])
            : (array) ($newPackage['config_options'] ?? $this->productConfig($service));
        $overrides = self::extractConfigurableOverrides($service);

        $manualPlan = trim((string) ($cfg['manual_plan_id'] ?? ''));
        $planId = $overrides['plan_id'] ?? ($manualPlan !== '' ? (int) $manualPlan : (int) ($cfg['package_name'] ?? 0));
        $senderIds = $this->collectIds($cfg['default_sender_id'] ?? '', $cfg['extra_sender_ids'] ?? '', $overrides['sender_ids'] ?? []);

        $payload = [
            'account_type' => $overrides['account_type'] ?? ((string) ($cfg['account_type'] ?? 'user') ?: 'user'),
            'plan_id' => $planId > 0 ? $planId : null,
            'allow_api' => (string) ($cfg['allow_api'] ?? '1') !== '0',
            'sync_senders' => false,
        ];
        if ($senderIds !== []) {
            $payload['sender_ids'] = $senderIds;
        }
        if (($cfg['masking_rate'] ?? '') !== '' && is_numeric($cfg['masking_rate'])) {
            $payload['masking_rate'] = (float) $cfg['masking_rate'];
        }
        if (($cfg['non_masking_rate'] ?? '') !== '' && is_numeric($cfg['non_masking_rate'])) {
            $payload['non_masking_rate'] = (float) $cfg['non_masking_rate'];
        }

        $res = $api->changePackage($userId, $payload);

        return $this->buildResult($res['success'], $res['success'] ? 'SMS package updated.' : $res['message']);
    }

    public function usageUpdate(Server $server): array
    {
        return ['success' => true, 'updated' => 0];
    }

    public function testConnection(Server $server): bool
    {
        $api = SoftcentsProvisioningClient::fromServer($server, 'sms.softcents.com', self::NAME);

        return $api ? $api->ping()['success'] : false;
    }

    public function ssoLogin(Service $service): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'SMS portal login is not ready yet.');
        }

        $res = $api->generateSsoUrl($userId);
        $url = trim((string) ($res['data']['sso_url'] ?? ''));

        if ($res['success'] && (str_starts_with($url, 'https://') || (app()->environment(['local', 'testing']) && str_starts_with($url, 'http://127.0.0.1')))) {
            return $this->buildResult(true, 'OK', ['url' => $url]);
        }

        return $this->buildResult(false, 'Could not open the SMS portal right now. Please try again.');
    }

    public function adminSync(Service $service): array
    {
        $api = $this->apiForService($service);
        $userId = $api ? $this->resolveUserId($api, $service) : 0;
        if (! $api || ! $userId) {
            return $this->buildResult(false, 'Remote SMS account could not be found.');
        }

        $res = $api->getAccount($userId);
        $u = $res['data']['user'] ?? null;
        if (! $res['success'] || ! is_array($u)) {
            return $this->buildResult(false, $res['message'] ?: 'Failed to sync SMS account.');
        }

        $service->forceFill(['username' => (string) ($u['username'] ?? $service->username)])->save();
        $this->setModuleData($service, ['remote_user_id' => (int) $u['id'], 'synced_at' => now()->toIso8601String()]);

        return $this->buildResult(true, 'Synced SMS account #'.$u['id'].' (Balance: '.number_format((float) ($u['balance'] ?? 0), 2).' BDT).');
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

        $host = preg_replace('#^https?://#i', '', trim((string) ($service->server?->hostname ?: ($service->domain ?: 'sms.softcents.com'))));

        return view('softcents_sms_panel::client-panel', [
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

        return view('softcents_sms_panel::admin-panel', [
            'service' => $service,
            'userId' => $userId,
            'account' => $account,
        ]);
    }

    public static function extractConfigurableOverrides(Service $service): array
    {
        $overrides = [];
        $service->loadMissing('configOptions.option', 'configOptions.sub');

        foreach ($service->configOptions as $selected) {
            $key = strtolower(trim((string) ($selected->option?->option_name ?? '')));
            $val = $selected->option?->isQuantity()
                ? (string) $selected->qty
                : trim((string) ($selected->sub?->option_name ?? ''));

            if ($key === '' || $val === '') {
                continue;
            }

            if (preg_match('/^(extra|addon)[ _-]*(balance|credit|credits|amount)/i', $key)) {
                $num = self::parseNumeric($val);
                if ($num > 0) {
                    $overrides['extra_balance'] = ($overrides['extra_balance'] ?? 0.0) + $num;
                }

                continue;
            }

            if (preg_match('/(balance|credit|credits|amount|topup|top-up)/i', $key)) {
                $num = self::parseNumeric($val);
                if ($num > 0) {
                    $overrides['balance'] = $num;
                }

                continue;
            }

            if (preg_match('/(plan|package|tariff)/i', $key)) {
                $id = (int) self::parseNumeric($val);
                if ($id > 0) {
                    $overrides['plan_id'] = $id;
                }

                continue;
            }

            if (preg_match('/(sender|caller|cli)/i', $key)) {
                foreach (preg_split('/[\s,;|]+/', $val, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
                    if (is_numeric($part) && (int) $part > 0) {
                        $overrides['sender_ids'][] = (int) $part;
                        $overrides['caller_ids'][] = (int) $part;
                    }
                }

                continue;
            }

            if (preg_match('/(account[ _-]*type|role)/i', $key)) {
                $v = strtolower($val);
                if (in_array($v, ['user', 'sp', 'ssp'], true)) {
                    $overrides['account_type'] = $v;
                }
            }
        }

        return $overrides;
    }

    public static function latestPaidInvoiceId(int $serviceId): int
    {
        if ($serviceId <= 0) {
            return 0;
        }

        return (int) (InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoice_items.rel_id', $serviceId)
            ->whereIn('invoice_items.type', ['Hosting', 'Service', 'hosting', 'service'])
            ->where('invoices.status', InvoiceStatus::Paid->value)
            ->orderByDesc('invoices.id')
            ->value('invoices.id') ?? 0);
    }

    private function apiForService(Service $service): ?SoftcentsProvisioningClient
    {
        $server = $service->server ?: ($service->server_id ? Server::find($service->server_id) : $this->getServer($service));

        return SoftcentsProvisioningClient::fromServer($server, 'sms.softcents.com', self::NAME);
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
                if (! empty($lookup['data']['user']['username']) && ! $service->username) {
                    $service->forceFill(['username' => (string) $lookup['data']['user']['username']])->save();
                }

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

    private static function parseNumeric(string $val): float
    {
        if (is_numeric($val)) {
            return (float) $val;
        }
        if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', $val, $m)) {
            return (float) $m[1];
        }

        return 0.0;
    }
}

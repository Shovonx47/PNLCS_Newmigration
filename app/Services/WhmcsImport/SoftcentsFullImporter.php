<?php

namespace App\Services\WhmcsImport;

use App\Enums\InvoiceStatus;
use App\Models\AddonSetting;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Affiliate;
use App\Models\Announcement;
use App\Models\BillableItem;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\ConfigOption;
use App\Models\ConfigOptionGroup;
use App\Models\ConfigOptionLink;
use App\Models\ConfigOptionSub;
use App\Models\Contact;
use App\Models\Credit;
use App\Models\Currency;
use App\Models\CustomField;
use App\Models\CustomFieldValue;
use App\Models\Domain;
use App\Models\GatewaySettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\Language;
use App\Models\Order;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Promotion;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Server;
use App\Models\ServerGroup;
use App\Models\Service;
use App\Models\ServiceConfigOption;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketDepartment;
use App\Models\TicketReply;
use App\Models\TicketSpamFilter;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Mojibake;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Addons\AdvancedClientVerification\AdvancedClientVerificationModule;
use Modules\Addons\AdvancedClientVerification\Models\AcvVerification;
use Modules\Addons\BdpbxAccounting\Models\BdpbxDailyStat;
use Modules\Addons\CancelInvoices\CancelInvoicesModule;
use Modules\Addons\DriveBackup\DriveBackupModule;
use Modules\Addons\ItalkBalance\Models\ItalkBalanceEndpoint;
use Modules\Addons\ItalkBalance\Models\ItalkBalanceService;
use Modules\Addons\ItalkBalance\Services\BalanceRegistry;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeAccount;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeLog;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeProvider;
use Modules\Addons\RechargeRequests\Models\RechargeRequest;
use Modules\Addons\ResellerCenter\Models\Reseller;
use Modules\Addons\ResellerCenter\Models\ResellerClient;
use Modules\Addons\ResellerCenter\Models\ResellerGroup;
use Modules\Addons\ResellerCenter\Models\ResellerPayout;
use Modules\Addons\ResellerCenter\Models\ResellerPricing;
use Modules\Addons\ResellerCenter\Models\ResellerPromo;
use Modules\Addons\ResellerCenter\ResellerCenterModule;
use Modules\Addons\ScrollingNotice\Models\ScrollingNotice;
use Modules\Addons\SoftcentsSms\Models\SmsSetting;
use Modules\Addons\SoftcentsSms\Models\SmsTemplate;
use Modules\Addons\SoftcentsVoice\Models\VoiceEvent;
use Modules\Addons\SoftcentsVoice\Models\VoiceGroupOverride;
use Modules\Addons\SoftcentsVoice\Models\VoiceLog;
use Modules\Addons\SoftcentsVoice\Models\VoiceSettings;
use Modules\Addons\TelegramBot\Models\TelegramClient;
use Modules\Addons\WhatsappPro\Models\WaSettings;
use Modules\Addons\WhatsappPro\Models\WaTemplate;
use PDO;

/**
 * Full idempotent SoftCents WHMCS -> PNLCS importer (Phase 5).
 *
 * Reads WHMCS core tables + SoftCents custom mod_* addon tables via read-only PDO,
 * dynamically decrypts WHMCS AES/hash-encrypted secrets via $cc_encryption_hash,
 * repairs double-encoded UTF-8/UTF-8MB4 Mojibake, preserves active test credentials
 * unless --force-passwords is given, and explicitly excludes Domain Reseller.
 */
class SoftcentsFullImporter
{
    /** @var array<int, int> */
    private array $currencyMap = [];

    /** @var array<int, int> */
    private array $clientGroupMap = [];

    /** @var array<int, int> */
    private array $adminMap = [];

    /** @var array<int, int> */
    private array $clientMap = [];

    /** @var array<int, int> */
    private array $userMap = [];

    /** @var array<int, int> */
    private array $contactMap = [];

    /** @var array<int, int> */
    private array $serverGroupMap = [];

    /** @var array<int, int> */
    private array $serverMap = [];

    /** @var array<int, int> */
    private array $productGroupMap = [];

    /** @var array<int, int> */
    private array $productMap = [];

    /** @var array<int, int> */
    private array $customFieldMap = [];

    /** @var array<int, int> */
    private array $orderMap = [];

    /** @var array<int, int> */
    private array $serviceMap = [];

    /** @var array<int, int> */
    private array $domainMap = [];

    /** @var array<int, int> */
    private array $invoiceMap = [];

    /** @var array<int, int> */
    private array $departmentMap = [];

    /** @var array<int, int> */
    private array $ticketMap = [];

    /** @var array<int, int> */
    private array $kbCatMap = [];

    /** @var array<int, int> */
    private array $endpointMap = [];

    /** @var array<int, int> */
    private array $providerMap = [];

    /** @var array<int, int> */
    private array $accountMap = [];

    /** @var array<int, int> */
    private array $resellerGroupMap = [];

    /** @var array<int, int> */
    private array $resellerMap = [];

    /** @var array<int, int> */
    private array $configGroupMap = [];

    /** @var array<int, int> */
    private array $configOptionMap = [];

    /** @var array<int, int> */
    private array $configSubMap = [];

    /** @var array<string, array{source: int, synced: int, skipped: int}> */
    private array $stats = [];

    private int $defaultCurrencyId = 1;

    private ?int $defaultClientGroupId = null;

    /** @var array<int, string> */
    private array $whmcsTables = [];

    public function __construct(
        private readonly PDO $whmcs,
        private readonly string $ccEncryptionHash = '',
        private readonly bool $dryRun = false,
        private readonly bool $forcePasswords = false,
        private readonly ?\Closure $logger = null,
    ) {}

    public static function connectBillingDb(): PDO
    {
        $host = (string) env('WHMCS_DB_HOST', config('database.connections.mysql.host', '127.0.0.1'));
        $port = (int) env('WHMCS_DB_PORT', config('database.connections.mysql.port', 3306));
        $db = (string) env('WHMCS_DB_DATABASE', 'billing_db');
        $user = (string) env('WHMCS_DB_USERNAME', 'root');
        $pass = (string) env('WHMCS_DB_PASSWORD', '');

        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        try {
            $pdo->exec('SET SESSION TRANSACTION READ ONLY');
        } catch (\Throwable) {
            // SQLite/read-only fixtures do not support SET SESSION TRANSACTION READ ONLY.
        }

        return $pdo;
    }

    public static function readWhmcsEncryptionHash(?string $path = null): string
    {
        $path ??= (string) env('WHMCS_CONFIG_PATH', 'E:/Softcents_New/1111/billing.softcents.com/configuration.php');
        if (! is_file($path)) {
            return '';
        }

        $cc_encryption_hash = '';
        require $path;

        return (string) $cc_encryption_hash;
    }

    /**
     * Decrypts a WHMCS hash-encrypted string (servers/services/gateway settings) using $cc_encryption_hash.
     */
    public static function decryptWhmcsLegacy(string $encodedString, string $ccEncryptionHash): string
    {
        if ($encodedString === '' || $ccEncryptionHash === '') {
            return '';
        }

        $hashFn = static function (string $value): string {
            $hex = sha1($value);
            $out = '';
            $len = strlen($hex);
            for ($c = 0; $c < $len; $c += 2) {
                $out .= chr((int) hexdec($hex[$c].$hex[$c + 1]));
            }

            return $out;
        };

        $key = md5(md5($ccEncryptionHash)).md5($ccEncryptionHash);
        $hashKey = $hashFn($key);
        $hashLen = strlen($hashKey);
        $raw = base64_decode($encodedString, true);
        if ($raw === false || strlen($raw) < $hashLen) {
            return '';
        }

        $tmpIv = substr($raw, 0, $hashLen);
        $cipher = substr($raw, $hashLen);
        $iv = '';
        for ($c = 0; $c < $hashLen; $c++) {
            $iv .= chr(ord($tmpIv[$c]) ^ ord($hashKey[$c]));
        }

        $currKey = $iv;
        $out = '';
        $len = strlen($cipher);
        for ($c = 0; $c < $len; $c++) {
            if ($c !== 0 && $c % $hashLen === 0) {
                $currKey = $hashFn($currKey.substr($out, $c - $hashLen, $hashLen));
            }
            $out .= chr(ord($currKey[$c % $hashLen]) ^ ord($cipher[$c]));
        }

        return $out;
    }

    /**
     * Execute full migration from WHMCS PDO into PNLCS DB. If $dryRun is true,
     * executes inside a rolled-back transaction/savepoint so zero rows persist.
     *
     * @return array<string, array{source: int, synced: int, skipped: int}>
     */
    public function run(): array
    {
        $this->stats = [];
        $this->loadWhmcsTables();

        if ($this->dryRun) {
            DB::beginTransaction();
            try {
                Model::withoutEvents(fn () => $this->executeAllSteps());
            } finally {
                DB::rollBack();
            }

            return $this->stats;
        }

        Model::withoutEvents(fn () => $this->executeAllSteps());

        return $this->stats;
    }

    private function executeAllSteps(): void
    {
        $this->importConfigurationAndLanguages();
        $this->importCurrencies();
        $this->importClientGroups();
        $this->importAdmins();
        $this->importClients();
        $this->importUsersAndLinks();
        $this->importContacts();
        $this->importServerGroupsAndServers();
        $this->importProductGroupsAndFeatures();
        $this->importProductsAndPricing();
        $this->importConfigOptions();
        $this->importCustomFields();
        $this->importOrders();
        $this->importServices();
        $this->importServiceConfigOptions();
        $this->importDomainsExcludingReseller();
        $this->importInvoicesAndItems();
        $this->importTransactions();
        $this->importCredits();
        $this->importPromotions();
        $this->importQuotes();
        $this->importBillableItems();
        $this->importTickets();
        $this->importCustomFieldValues();
        $this->importKnowledgeBase();
        $this->importAnnouncements();
        $this->importAffiliates();
        $this->importPaymentGateways();

        // Phase 2 - Telecom & BDPBX Addons
        $this->importPhase2TelecomAddons();

        // Phase 3 - Notification Addons (SMS, WhatsApp, Voice, Telegram)
        $this->importPhase3NotificationAddons();

        // Phase 4 - KYC, Scrolling Notice, Auto-Cancel, GDrive Backup
        $this->importPhase4UtilityAddons();

        // Phase 5 - Reseller Center & Reseller Gateway (excluding Domain Reseller)
        $this->importPhase5ResellerCenter();
    }

    private function importConfigurationAndLanguages(): void
    {
        if (! $this->hasTable('tblconfiguration')) {
            return;
        }

        $cfg = $this->whmcs->query('SELECT setting, value FROM tblconfiguration')->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach (['CompanyName', 'SystemURL', 'LogoURL'] as $key) {
            if (! empty($cfg[$key])) {
                Setting::set($key, Mojibake::repair((string) $cfg[$key]), 'general');
            }
        }
        Setting::set('DefaultLanguage', 'en', 'general');
        Setting::set('DefaultPaymentMethod', 'piprapay', 'general');
        Setting::set('customer_currency_choice', '1', 'billing');

        Language::updateOrCreate(
            ['code' => 'bn'],
            [
                'name' => 'Bengali',
                'native_name' => 'বাংলা',
                'flag_code' => 'bd',
                'direction' => 'ltr',
                'is_active' => true,
                'sort_order' => 10,
            ]
        );
    }

    private function importCurrencies(): void
    {
        $rows = $this->fetchAll('tblcurrencies', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            if ($code === '') {
                continue;
            }

            $isDefault = (int) ($row['default'] ?? 0) === 1 || $code === 'BDT';
            if ($isDefault) {
                Currency::query()->update(['is_default' => false]);
            }

            $currency = Currency::updateOrCreate(
                ['code' => $code],
                [
                    'prefix' => Mojibake::repair((string) ($row['prefix'] ?? '')) ?: ($code === 'BDT' ? '৳' : $code.' '),
                    'suffix' => Mojibake::repair((string) ($row['suffix'] ?? '')),
                    'format' => max(1, (int) ($row['format'] ?? 1)),
                    'rate' => (float) ($row['rate'] ?? 1.0),
                    'is_default' => $isDefault,
                ]
            );

            $this->currencyMap[(int) $row['id']] = $currency->id;
            if ($isDefault) {
                $this->defaultCurrencyId = $currency->id;
            }
            $synced++;
        }

        if ($this->defaultCurrencyId === 1) {
            $fallback = Currency::where('is_default', true)->first() ?? Currency::first();
            if ($fallback) {
                $this->defaultCurrencyId = $fallback->id;
            }
        }

        $this->recordStat('currencies', count($rows), $synced);
    }

    private function importClientGroups(): void
    {
        $rows = $this->fetchAll('tblclientgroups', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $name = trim(Mojibake::repair((string) ($row['groupname'] ?? '')));
            if ($name === '') {
                continue;
            }

            $color = trim((string) ($row['groupcolour'] ?? ''));
            if ($color !== '' && ! str_starts_with($color, '#')) {
                $color = '#'.$color;
            }
            $exempt = $this->whmcsBool($row['susptermexempt'] ?? '');

            $group = ClientGroup::updateOrCreate(
                ['name' => $name],
                [
                    'color' => preg_match('/^#[0-9A-Fa-f]{3,6}$/', $color) ? $color : '#405189',
                    'discount_percent' => max(0.0, (float) ($row['discountpercent'] ?? 0)),
                    'suspend_exempt' => $exempt,
                    'terminate_exempt' => $exempt,
                    'separate_invoices' => $this->whmcsBool($row['separateinvoices'] ?? ''),
                ]
            );

            $this->clientGroupMap[(int) $row['id']] = $group->id;
            $this->defaultClientGroupId ??= $group->id;
            $synced++;
        }

        $this->recordStat('client_groups', count($rows), $synced);
    }

    private function importAdmins(): void
    {
        $roleMap = [];
        $fullRole = AdminRole::where('is_full_admin', true)->first()
            ?? AdminRole::create(['name' => 'Full Administrator', 'description' => 'Full access', 'is_full_admin' => true]);

        foreach ($this->fetchAll('tbladminroles', 'id') as $roleRow) {
            $name = trim(Mojibake::repair((string) ($roleRow['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            $lowerName = strtolower($name);
            $isFull = str_contains($lowerName, 'full')
                || str_contains($lowerName, 'developer')
                || str_contains($lowerName, 'super');
            $role = AdminRole::updateOrCreate(
                ['name' => $name],
                [
                    'is_full_admin' => $isFull,
                    'permissions' => $isFull ? ($fullRole->permissions ?? []) : ($fullRole->permissions ?? []),
                    'system_emails' => ! empty($roleRow['systememails']),
                    'account_emails' => ! empty($roleRow['accountemails']),
                    'support_emails' => ! empty($roleRow['supportemails']),
                ]
            );
            $roleMap[(int) $roleRow['id']] = $role->id;
        }

        $adminRows = $this->fetchAll('tbladmins', 'id');
        $fallbackPw = (string) env('PNLCS_LOCAL_ADMIN_PASSWORD', 'SoftCents@Admin2026!');
        $synced = 0;

        foreach ($adminRows as $adm) {
            $username = trim((string) ($adm['username'] ?? ''));
            if ($username === '') {
                continue;
            }

            $existing = Admin::where('username', $username)->first();
            $data = [
                'role_id' => $roleMap[(int) ($adm['roleid'] ?? 0)] ?? $fullRole->id,
                'email' => trim((string) ($adm['email'] ?? '')),
                'first_name' => trim(Mojibake::repair((string) ($adm['firstname'] ?? ''))) ?: 'Admin',
                'last_name' => trim(Mojibake::repair((string) ($adm['lastname'] ?? ''))) ?: 'User',
                'signature' => Mojibake::repair((string) ($adm['signature'] ?? '')) ?: null,
                'is_disabled' => (int) ($adm['disabled'] ?? 0) === 1,
                'language' => trim((string) ($adm['language'] ?? 'en')) ?: 'en',
            ];

            if (! $existing || $this->forcePasswords) {
                $data['password'] = Hash::make($fallbackPw);
            }

            if ($existing) {
                $existing->update($data);
                $admin = $existing;
            } else {
                $admin = new Admin($data);
                $admin->username = $username;
                $admin->uuid = (string) Str::uuid();
                $admin->save();
            }

            $this->adminMap[(int) $adm['id']] = $admin->id;
            $synced++;
        }

        $this->recordStat('admins', count($adminRows), $synced);
    }

    private function importClients(): void
    {
        $rows = $this->fetchAll('tblclients', 'id');
        $synced = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $whmcsId = (int) $row['id'];
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($email === '') {
                $email = "whmcs-client-{$whmcsId}@imported.local";
            }

            $existing = Client::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();
            $status = match (strtolower(trim((string) ($row['status'] ?? 'active')))) {
                'closed' => 'closed',
                'inactive' => 'inactive',
                default => 'active',
            };

            $payload = [
                'first_name' => trim(Mojibake::repair((string) ($row['firstname'] ?? ''))) ?: 'Client',
                'last_name' => trim(Mojibake::repair((string) ($row['lastname'] ?? ''))),
                'company_name' => trim(Mojibake::repair((string) ($row['companyname'] ?? ''))) ?: null,
                'email' => $email,
                'address1' => trim(Mojibake::repair((string) ($row['address1'] ?? ''))) ?: null,
                'address2' => trim(Mojibake::repair((string) ($row['address2'] ?? ''))) ?: null,
                'city' => trim(Mojibake::repair((string) ($row['city'] ?? ''))) ?: null,
                'state' => trim(Mojibake::repair((string) ($row['state'] ?? ''))) ?: null,
                'postcode' => substr(trim((string) ($row['postcode'] ?? '')), 0, 20) ?: null,
                'country' => strtoupper(substr(trim((string) ($row['country'] ?? 'BD')) ?: 'BD', 0, 2)),
                'phone_number' => substr(preg_replace('/[^0-9+]/', '', (string) ($row['phonenumber'] ?? '')), 0, 30) ?: null,
                'tax_id' => substr(trim((string) ($row['tax_id'] ?? '')), 0, 50) ?: null,
                'status' => $status,
                'group_id' => $this->clientGroupMap[(int) ($row['groupid'] ?? 0)] ?? null,
                'currency_id' => $this->currencyMap[(int) ($row['currency'] ?? 0)] ?? $this->defaultCurrencyId,
                'default_payment_method' => $this->mapPaymentMethod($row['defaultgateway'] ?? null),
                'credit' => (float) ($row['credit'] ?? 0),
                'tax_exempt' => (int) ($row['taxexempt'] ?? 0) === 1,
                'late_fee_overide' => (int) ($row['latefeeoveride'] ?? 0) === 1,
                'override_auto_suspend' => (int) ($row['overideduenotices'] ?? 0) === 1,
                'notes' => trim(Mojibake::repair((string) ($row['notes'] ?? ''))) ?: null,
                'language' => substr(trim((string) ($row['language'] ?? 'en')) ?: 'en', 0, 10),
                'ip_address' => substr(trim((string) ($row['ip'] ?? '')), 0, 45) ?: null,
            ];

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->fill($payload);
                $existing->late_fee_overide = (int) ($row['latefeeoveride'] ?? 0) === 1;
                $existing->save();
                $client = $existing;
            } else {
                $client = new Client($payload);
                $client->uuid = (string) Str::uuid();
                $client->late_fee_overide = (int) ($row['latefeeoveride'] ?? 0) === 1;
                $client->save();
            }

            $this->clientMap[$whmcsId] = $client->id;
            $synced++;
        }

        $this->recordStat('clients', count($rows), $synced, $skipped);
    }

    private function importUsersAndLinks(): void
    {
        $users = $this->fetchAll('tblusers', 'id');
        $fallbackPw = (string) env('PNLCS_LOCAL_CLIENT_PASSWORD', 'SoftCents@Test2026!');
        $synced = 0;

        foreach ($users as $u) {
            $email = strtolower(trim((string) ($u['email'] ?? '')));
            if ($email === '') {
                continue;
            }

            $existing = User::whereRaw('LOWER(email) = ?', [$email])->first();
            $attrs = [
                'first_name' => trim(Mojibake::repair((string) ($u['first_name'] ?? ''))) ?: 'User',
                'last_name' => trim(Mojibake::repair((string) ($u['last_name'] ?? ''))),
                'email' => $email,
                'language' => trim((string) ($u['language'] ?? 'en')) ?: 'en',
                'is_active' => true,
            ];

            if ($this->forcePasswords) {
                $attrs['password'] = Hash::make($fallbackPw);
            } elseif (! $existing) {
                $rawHash = (string) ($u['password'] ?? '');
                $attrs['password'] = (str_starts_with($rawHash, '$2y$') || str_starts_with($rawHash, '$2a$') || str_starts_with($rawHash, '$2b$'))
                    ? $rawHash
                    : Hash::make($fallbackPw);
            }

            if ($existing) {
                $existing->fill($attrs);
                if (! empty($u['email_verified_at']) && ! str_starts_with((string) $u['email_verified_at'], '0000-00-00')) {
                    $existing->email_verified_at = $u['email_verified_at'];
                }
                $existing->save();
                $user = $existing;
            } else {
                $user = new User($attrs);
                $user->email_verified_at = (! empty($u['email_verified_at']) && ! str_starts_with((string) $u['email_verified_at'], '0000-00-00'))
                    ? $u['email_verified_at']
                    : now();
                $user->save();
            }

            $this->userMap[(int) $u['id']] = $user->id;
            $synced++;
        }

        foreach ($this->fetchAll('tblusers_clients', 'id') as $link) {
            $uid = $this->userMap[(int) ($link['auth_user_id'] ?? 0)] ?? null;
            $cid = $this->clientMap[(int) ($link['client_id'] ?? 0)] ?? null;
            if (! $uid || ! $cid) {
                continue;
            }

            $rawPerms = trim((string) ($link['permissions'] ?? ''));
            $permsJson = null;
            if ($rawPerms !== '') {
                $decoded = json_decode($rawPerms, true);
                $list = is_array($decoded)
                    ? $decoded
                    : array_values(array_filter(array_map('trim', explode(',', $rawPerms))));
                $permsJson = $list !== [] ? json_encode($list) : null;
            }

            DB::table('user_client')->updateOrInsert(
                ['user_id' => $uid, 'client_id' => $cid],
                [
                    'owner' => (int) ($link['owner'] ?? 1) === 1,
                    'permissions' => $permsJson,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->recordStat('users', count($users), $synced);
    }

    private function importContacts(): void
    {
        $rows = $this->fetchAll('tblcontacts', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $cid = $this->clientMap[(int) ($row['userid'] ?? 0)] ?? null;
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if (! $cid || $email === '') {
                continue;
            }

            $rawPerms = trim((string) ($row['permissions'] ?? ''));
            $perms = $rawPerms !== ''
                ? (json_decode($rawPerms, true) ?: array_values(array_filter(array_map('trim', explode(',', $rawPerms)))))
                : [];

            $contact = Contact::updateOrCreate(
                ['client_id' => $cid, 'email' => $email],
                [
                    'first_name' => trim(Mojibake::repair((string) ($row['firstname'] ?? ''))),
                    'last_name' => trim(Mojibake::repair((string) ($row['lastname'] ?? ''))),
                    'company_name' => trim(Mojibake::repair((string) ($row['companyname'] ?? ''))) ?: null,
                    'address1' => trim(Mojibake::repair((string) ($row['address1'] ?? ''))) ?: null,
                    'city' => trim(Mojibake::repair((string) ($row['city'] ?? ''))) ?: null,
                    'state' => trim(Mojibake::repair((string) ($row['state'] ?? ''))) ?: null,
                    'postcode' => trim((string) ($row['postcode'] ?? '')) ?: null,
                    'country' => trim((string) ($row['country'] ?? 'BD')) ?: 'BD',
                    'phone_number' => preg_replace('/[^0-9+]/', '', (string) ($row['phonenumber'] ?? '')) ?: null,
                    'is_sub_account' => (int) ($row['subaccount'] ?? 0) === 1,
                    'permissions' => $perms,
                    'general_emails' => (int) ($row['generalemails'] ?? 0) === 1,
                    'product_emails' => (int) ($row['productemails'] ?? 0) === 1,
                    'domain_emails' => (int) ($row['domainemails'] ?? 0) === 1,
                    'invoice_emails' => (int) ($row['invoiceemails'] ?? 0) === 1,
                    'support_emails' => (int) ($row['supportemails'] ?? 0) === 1,
                ]
            );

            $this->contactMap[(int) $row['id']] = $contact->id;
            $synced++;
        }

        $this->recordStat('contacts', count($rows), $synced);
    }

    private function importServerGroupsAndServers(): void
    {
        $sgRows = $this->fetchAll('tblservergroups', 'id');
        foreach ($sgRows as $sg) {
            $name = trim(Mojibake::repair((string) ($sg['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            $group = ServerGroup::updateOrCreate(
                ['name' => $name],
                ['fill_type' => (int) ($sg['filltype'] ?? 1)]
            );
            $this->serverGroupMap[(int) $sg['id']] = $group->id;
        }

        $srvRows = $this->fetchAll('tblservers', 'id');
        $synced = 0;
        foreach ($srvRows as $s) {
            $name = trim(Mojibake::repair((string) ($s['name'] ?? '')));
            $hostname = trim((string) ($s['hostname'] ?? ''));
            $type = trim((string) ($s['type'] ?? 'cpanel')) ?: 'cpanel';
            if ($name === '') {
                continue;
            }

            $existing = Server::where('name', $name)
                ->where('type', $type)
                ->first();

            $decryptedPw = ! empty($s['password'])
                ? self::decryptWhmcsLegacy((string) $s['password'], $this->ccEncryptionHash)
                : '';

            $attrs = [
                'name' => $name,
                'hostname' => $hostname,
                'ip_address' => trim((string) ($s['ipaddress'] ?? '')) ?: null,
                'max_accounts' => (int) ($s['maxaccounts'] ?? 0),
                'type' => $type,
                'username' => trim((string) ($s['username'] ?? '')) ?: null,
                'port' => ($s['port'] ?? null) ?: null,
                'active' => (int) ($s['active'] ?? 1) === 1,
                'disabled' => (int) ($s['disabled'] ?? 0) === 1,
                'nameserver1' => trim((string) ($s['nameserver1'] ?? '')) ?: null,
                'nameserver2' => trim((string) ($s['nameserver2'] ?? '')) ?: null,
                'nameserver3' => trim((string) ($s['nameserver3'] ?? '')) ?: null,
                'nameserver4' => trim((string) ($s['nameserver4'] ?? '')) ?: null,
                'nameserver5' => trim((string) ($s['nameserver5'] ?? '')) ?: null,
            ];

            if ($decryptedPw !== '') {
                $attrs['password'] = $decryptedPw;
            }
            if (trim((string) ($s['accesshash'] ?? '')) !== '') {
                $attrs['access_hash'] = trim((string) $s['accesshash']);
            }

            if ($existing) {
                $existing->update($attrs);
                $server = $existing;
            } else {
                $server = Server::create($attrs);
            }

            $this->serverMap[(int) $s['id']] = $server->id;
            $synced++;
        }

        foreach ($this->fetchAll('tblservergroupsrel', 'id') as $rel) {
            $gid = $this->serverGroupMap[(int) ($rel['groupid'] ?? 0)] ?? null;
            $sid = $this->serverMap[(int) ($rel['serverid'] ?? 0)] ?? null;
            if (! $gid || ! $sid) {
                continue;
            }
            DB::table('server_group_server')->updateOrInsert(
                ['server_group_id' => $gid, 'server_id' => $sid],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }

        $this->recordStat('servers', count($srvRows), $synced);
    }

    private function importProductGroupsAndFeatures(): void
    {
        $rows = $this->fetchAll('tblproductgroups', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $whmcsId = (int) $row['id'];
            $name = trim(Mojibake::repair((string) ($row['name'] ?? '')));
            if ($name === '') {
                continue;
            }

            $existing = ProductGroup::where('slug', 'like', "sc-grp-{$whmcsId}-%")->first();
            $slug = $existing?->slug ?: Str::slug("sc-grp-{$whmcsId}-".$name);
            if ($slug === '' || (! $existing && ProductGroup::where('slug', $slug)->exists())) {
                $slug = "sc-grp-{$whmcsId}-".Str::lower(Str::random(6));
            }

            $group = ProductGroup::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'headline' => html_entity_decode(trim(Mojibake::repair((string) ($row['headline'] ?? ''))), ENT_QUOTES, 'UTF-8') ?: null,
                    'tagline' => trim(Mojibake::repair((string) ($row['tagline'] ?? ''))) ?: null,
                    'order_form_template' => trim((string) ($row['orderfrmtpl'] ?? '')) ?: 'standard_cart',
                    'hidden' => (int) ($row['hidden'] ?? 0) === 1,
                    'sort_order' => (int) ($row['order'] ?? 0),
                ]
            );

            $this->productGroupMap[$whmcsId] = $group->id;
            $synced++;
        }

        foreach ($this->fetchAll('tblproduct_group_features', 'id') as $feat) {
            $gid = $this->productGroupMap[(int) ($feat['product_group_id'] ?? 0)] ?? null;
            $label = trim(Mojibake::repair((string) ($feat['feature'] ?? '')));
            if (! $gid || $label === '') {
                continue;
            }

            DB::table('product_features')->updateOrInsert(
                ['product_group_id' => $gid, 'feature' => $label],
                ['sort_order' => (int) ($feat['order'] ?? 0), 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $this->recordStat('product_groups', count($rows), $synced);
    }

    private function importProductsAndPricing(): void
    {
        $rows = $this->fetchAll('tblproducts', 'id');
        $trials = $this->readWhmcsConfigJson('softcents_product_trials');
        $compares = $this->readWhmcsConfigJson('softcents_compare_prices');
        $synced = 0;

        foreach ($rows as $row) {
            $whmcsId = (int) $row['id'];
            $gid = $this->productGroupMap[(int) ($row['gid'] ?? 0)] ?? null;
            if (! $gid) {
                continue;
            }

            $name = trim(Mojibake::repair((string) ($row['name'] ?? '')));
            if ($name === '') {
                $name = "Product #{$whmcsId}";
            }

            $existing = Product::withTrashed()->where('slug', 'like', "sc-prod-{$whmcsId}-%")->first();
            $slug = $existing?->slug ?: Str::slug("sc-prod-{$whmcsId}-".$name);
            if ($slug === '' || (! $existing && Product::withTrashed()->where('slug', $slug)->exists())) {
                $slug = "sc-prod-{$whmcsId}-".Str::lower(Str::random(6));
            }

            $type = match (strtolower(trim((string) ($row['type'] ?? 'other')))) {
                'hostingaccount' => 'hosting',
                'reselleraccount' => 'reseller',
                'server' => 'server',
                default => 'other',
            };

            $payType = match (strtolower(trim((string) ($row['paytype'] ?? 'recurring')))) {
                'onetime' => 'one_time',
                'free' => 'free',
                default => 'recurring',
            };

            $autoSetup = match (strtolower(trim((string) ($row['autosetup'] ?? 'payment')))) {
                'order', 'payment', 'on' => strtolower((string) $row['autosetup']),
                default => 'manual',
            };

            $trialDays = isset($trials[$whmcsId]) ? max(0, (int) $trials[$whmcsId]) : ($existing?->trial_days ?? 0);
            $comparePrice = $existing?->compare_price;
            if (isset($compares[$whmcsId])) {
                $num = (float) preg_replace('/[^0-9.]/', '', (string) $compares[$whmcsId]);
                if ($num > 0) {
                    $comparePrice = round($num, 2);
                }
            }

            $attrs = [
                'type' => $type,
                'group_id' => $gid,
                'name' => $name,
                'description' => trim(Mojibake::repair((string) ($row['description'] ?? ''))) ?: $name,
                'hidden' => (int) ($row['hidden'] ?? 0) === 1,
                'show_domain_options' => (int) ($row['showdomainoptions'] ?? 0) === 1,
                'free_domain' => trim((string) ($row['freedomain'] ?? '')) !== '' && trim((string) ($row['freedomain'] ?? '')) !== '0',
                'free_domain_tlds' => trim((string) ($row['freedomaintlds'] ?? '')) ?: null,
                'free_domain_cycles' => trim((string) ($row['freedomainpaymentterms'] ?? '')) ?: null,
                'is_featured' => (int) ($row['is_featured'] ?? 0) === 1 || in_array($whmcsId, [3, 114, 287, 310, 311], true),
                'retired' => (int) ($row['retired'] ?? 0) === 1,
                'allow_upgrades' => (int) ($row['configoptionsupgrade'] ?? 1) === 1 || (int) ($row['billingcycleupgrade'] ?? 0) === 1,
                'pay_type' => $payType,
                'auto_setup' => $autoSetup,
                'terminate_after_days' => max(0, (int) ($row['autoterminatedays'] ?? 0)),
                'trial_days' => $trialDays,
                'compare_price' => $comparePrice,
                'server_type' => trim((string) ($row['servertype'] ?? '')) ?: null,
                'server_group_id' => $this->serverGroupMap[(int) ($row['servergroup'] ?? 0)] ?? null,
                'stock_control' => (int) ($row['stockcontrol'] ?? 0) === 1,
                'stock_qty' => (int) ($row['qty'] ?? 0),
                'sort_order' => (int) ($row['order'] ?? 0),
                'config_options' => $this->mapProductConfigOptions($row),
                'tax' => (int) ($row['tax'] ?? 0) === 1,
            ];

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($attrs);
                $prod = $existing;
            } else {
                $attrs['slug'] = $slug;
                $prod = Product::create($attrs);
            }

            $this->productMap[$whmcsId] = $prod->id;
            $synced++;
        }

        // Import product pricing rows
        $pricingSynced = 0;
        foreach ($this->fetchAll('tblpricing', 'id') as $pr) {
            if (strtolower((string) ($pr['type'] ?? '')) !== 'product') {
                continue;
            }
            $pid = $this->productMap[(int) ($pr['relid'] ?? 0)] ?? null;
            $cid = $this->currencyMap[(int) ($pr['currency'] ?? 0)] ?? $this->defaultCurrencyId;
            if (! $pid) {
                continue;
            }

            Pricing::updateOrCreate(
                ['type' => 'product', 'rel_id' => $pid, 'currency_id' => $cid],
                [
                    'monthly' => (float) ($pr['monthly'] ?? -1),
                    'quarterly' => (float) ($pr['quarterly'] ?? -1),
                    'semiannually' => (float) ($pr['semiannually'] ?? -1),
                    'annually' => (float) ($pr['annually'] ?? -1),
                    'biennially' => (float) ($pr['biennially'] ?? -1),
                    'triennially' => (float) ($pr['triennially'] ?? -1),
                    'monthly_setup' => (float) ($pr['msetupfee'] ?? 0),
                    'quarterly_setup' => (float) ($pr['qsetupfee'] ?? 0),
                    'semiannually_setup' => (float) ($pr['ssetupfee'] ?? 0),
                    'annually_setup' => (float) ($pr['asetupfee'] ?? 0),
                    'biennially_setup' => (float) ($pr['bsetupfee'] ?? 0),
                    'triennially_setup' => (float) ($pr['tsetupfee'] ?? 0),
                ]
            );
            $pricingSynced++;
        }

        $this->recordStat('products', count($rows), $synced);
        $this->recordStat('pricing', $pricingSynced, $pricingSynced);
    }

    private function importConfigOptions(): void
    {
        $groups = $this->fetchAll('tblproductconfiggroups', 'id');
        foreach ($groups as $g) {
            $name = trim(Mojibake::repair((string) ($g['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            $model = ConfigOptionGroup::updateOrCreate(
                ['name' => $name],
                ['description' => trim(Mojibake::repair((string) ($g['description'] ?? ''))) ?: null]
            );
            $this->configGroupMap[(int) $g['id']] = $model->id;
        }

        foreach ($this->fetchAll('tblproductconfiglinks', 'id') as $link) {
            $gid = $this->configGroupMap[(int) ($link['gid'] ?? 0)] ?? null;
            $pid = $this->productMap[(int) ($link['pid'] ?? 0)] ?? null;
            if ($gid && $pid) {
                ConfigOptionLink::firstOrCreate(['group_id' => $gid, 'product_id' => $pid]);
            }
        }

        foreach ($this->fetchAll('tblproductconfigoptions', 'id') as $opt) {
            $gid = $this->configGroupMap[(int) ($opt['gid'] ?? 0)] ?? null;
            $name = trim(Mojibake::repair((string) ($opt['optionname'] ?? '')));
            if (! $gid || $name === '') {
                continue;
            }
            $type = match ((string) ($opt['optiontype'] ?? '1')) {
                '2' => 'radio',
                '3' => 'yesno',
                '4' => 'quantity',
                default => 'dropdown',
            };
            $model = ConfigOption::updateOrCreate(
                ['group_id' => $gid, 'option_name' => $name],
                [
                    'option_type' => $type,
                    'qty_minimum' => (int) ($opt['qtyminimum'] ?? 0),
                    'qty_maximum' => (int) ($opt['qtymaximum'] ?? 0),
                    'sort_order' => (int) ($opt['order'] ?? 0),
                    'hidden' => (int) ($opt['hidden'] ?? 0) === 1,
                ]
            );
            $this->configOptionMap[(int) $opt['id']] = $model->id;
        }

        foreach ($this->fetchAll('tblproductconfigoptionssub', 'id') as $sub) {
            $cid = $this->configOptionMap[(int) ($sub['configid'] ?? 0)] ?? null;
            $name = trim(Mojibake::repair((string) ($sub['optionname'] ?? '')));
            if (! $cid || $name === '') {
                continue;
            }
            $model = ConfigOptionSub::updateOrCreate(
                ['config_id' => $cid, 'option_name' => $name],
                ['sort_order' => (int) ($sub['sortorder'] ?? 0), 'hidden' => (int) ($sub['hidden'] ?? 0) === 1]
            );
            $this->configSubMap[(int) $sub['id']] = $model->id;
        }
    }

    private function importCustomFields(): void
    {
        $rows = $this->fetchAll('tblcustomfields', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $rawType = strtolower(trim((string) ($row['type'] ?? 'client')));
            $relId = (int) ($row['relid'] ?? 0);
            if ($rawType === 'product') {
                $relId = $this->productMap[$relId] ?? 0;
                if ($relId === 0) {
                    continue;
                }
            }

            $fullFieldName = trim(Mojibake::repair((string) ($row['fieldname'] ?? '')));
            if ($fullFieldName === '') {
                continue;
            }
            $fieldName = mb_strlen($fullFieldName) > 250 ? mb_substr($fullFieldName, 0, 250) : $fullFieldName;
            $descText = trim(Mojibake::repair((string) ($row['description'] ?? '')));
            if (mb_strlen($fullFieldName) > 250) {
                $descText = trim($fullFieldName.($descText !== '' ? "\n".$descText : ''));
            }

            $fieldType = match (strtolower(trim((string) ($row['fieldtype'] ?? 'text')))) {
                'dropdown' => 'select',
                'tickbox' => 'checkbox',
                default => strtolower(trim((string) ($row['fieldtype'] ?? 'text'))) ?: 'text',
            };

            $cf = CustomField::updateOrCreate(
                ['type' => $rawType, 'rel_id' => $relId, 'field_name' => $fieldName],
                [
                    'field_type' => $fieldType,
                    'description' => $descText ?: null,
                    'field_options' => trim(Mojibake::repair((string) ($row['fieldoptions'] ?? ''))) ?: null,
                    'regex' => trim((string) ($row['regexpr'] ?? '')) ?: null,
                    'admin_only' => (int) ($row['adminonly'] ?? 0) === 1,
                    'required' => (int) ($row['required'] ?? 0) === 1,
                    'show_on_order' => (int) ($row['showorder'] ?? 0) === 1,
                    'show_on_invoice' => (int) ($row['showinvoice'] ?? 0) === 1,
                    'sort_order' => (int) ($row['sortorder'] ?? 0),
                ]
            );

            $this->customFieldMap[(int) $row['id']] = $cf->id;
            $synced++;
        }

        $this->recordStat('custom_fields', count($rows), $synced);
    }

    private function importOrders(): void
    {
        $rows = $this->fetchAll('tblorders', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $cid = $this->clientMap[(int) ($row['userid'] ?? 0)] ?? null;
            if (! $cid) {
                continue;
            }

            $orderNum = trim((string) ($row['ordernum'] ?? '')) ?: 'WHMCS-ORD-'.$row['id'];
            $status = match (strtolower(trim((string) ($row['status'] ?? 'pending')))) {
                'active' => 'active',
                'cancelled' => 'cancelled',
                'fraud' => 'fraud',
                default => 'pending',
            };

            $order = Order::updateOrCreate(
                ['order_num' => $orderNum],
                [
                    'client_id' => $cid,
                    'contact_id' => $this->contactMap[(int) ($row['contactid'] ?? 0)] ?? null,
                    'date' => $this->normalizeDate($row['date'] ?? null),
                    'promo_code' => trim((string) ($row['promocode'] ?? '')) ?: null,
                    'amount' => (float) ($row['amount'] ?? 0),
                    'payment_method' => $this->mapPaymentMethod($row['paymentmethod'] ?? null),
                    'status' => $status,
                    'ip_address' => trim((string) ($row['ipaddress'] ?? '')) ?: null,
                    'fraud_module' => trim((string) ($row['fraudmodule'] ?? '')) ?: null,
                    'fraud_output' => trim((string) ($row['fraudoutput'] ?? '')) ?: null,
                    'notes' => trim(Mojibake::repair((string) ($row['notes'] ?? ''))) ?: null,
                ]
            );

            $this->orderMap[(int) $row['id']] = $order->id;
            $synced++;
        }

        $this->recordStat('orders', count($rows), $synced);
    }

    private function importServices(): void
    {
        $rows = $this->fetchAll('tblhosting', 'id');
        $synced = 0;
        $claimedServiceIds = [];

        foreach ($rows as $h) {
            $whmcsId = (int) $h['id'];
            $cid = $this->clientMap[(int) ($h['userid'] ?? 0)] ?? null;
            $pid = $this->productMap[(int) ($h['packageid'] ?? 0)] ?? null;
            if (! $cid || ! $pid) {
                continue;
            }

            $sid = $this->serverMap[(int) ($h['server'] ?? 0)] ?? null;
            $oid = $this->orderMap[(int) ($h['orderid'] ?? 0)] ?? null;
            $domain = trim((string) ($h['domain'] ?? ''));
            $username = trim((string) ($h['username'] ?? ''));
            $regDate = $this->normalizeDate($h['regdate'] ?? null);
            $nextDue = $this->normalizeDate($h['nextduedate'] ?? null, false);
            $cycle = $this->mapBillingCycle((string) ($h['billingcycle'] ?? 'monthly'));
            $status = strtolower(trim((string) ($h['domainstatus'] ?? 'pending')));

            $existing = null;
            if ($oid) {
                $existing = Service::where('order_id', $oid)
                    ->where('client_id', $cid)
                    ->where('product_id', $pid)
                    ->whereNotIn('id', $claimedServiceIds)
                    ->first();
            }
            if (! $existing && ($domain !== '' || $username !== '')) {
                $q = Service::where('client_id', $cid)
                    ->where('product_id', $pid)
                    ->whereNotIn('id', $claimedServiceIds);
                if ($domain !== '') {
                    $q->where('domain', $domain);
                }
                if ($username !== '') {
                    $q->where('username', $username);
                }
                $existing = $q->first();
            }
            if (! $existing) {
                $existing = Service::where('client_id', $cid)
                    ->where('product_id', $pid)
                    ->where('registration_date', $regDate)
                    ->where('notes', 'like', "%[WHMCS:#{$whmcsId}]%")
                    ->first();
            }

            $product = Product::find($pid);
            $decryptedPw = ! empty($h['password'])
                ? self::decryptWhmcsLegacy((string) $h['password'], $this->ccEncryptionHash)
                : '';

            $moduleData = match ($product?->server_type) {
                'bdpbx' => array_filter([
                    'pbx_domain' => $domain,
                    'ext_count' => (int) ($product->config_options['ext_count'] ?? 5),
                ]),
                'bhpanel' => array_filter(['bhpanel_username' => $username]),
                'softcents_sms_panel', 'softcents_vsms' => array_filter([
                    'remote_user_id' => (int) ($h['subscriptionid'] ?? 0) ?: null,
                    'portal_host' => $domain,
                ]),
                default => [],
            };

            $baseNotes = trim(Mojibake::repair((string) ($h['notes'] ?? '')));
            $marker = "[WHMCS:#{$whmcsId}]";
            $notes = str_contains($baseNotes, $marker) ? $baseNotes : trim($baseNotes.' '.$marker);

            $attrs = [
                'client_id' => $cid,
                'order_id' => $oid,
                'product_id' => $pid,
                'whmcs_product_name' => $product?->name,
                'server_id' => $sid,
                'domain' => $domain,
                'payment_method' => $this->mapPaymentMethod($h['paymentmethod'] ?? null),
                'qty' => max(1, (int) ($h['qty'] ?? 1)),
                'first_payment_amount' => (float) ($h['firstpaymentamount'] ?? 0),
                'amount' => (float) ($h['amount'] ?? 0),
                'billing_cycle' => $cycle,
                'registration_date' => $regDate,
                'next_due_date' => $nextDue,
                'termination_date' => $this->normalizeDate($h['termination_date'] ?? null, false),
                'status' => $status,
                'username' => $username,
                'disk_usage' => (int) ($h['diskusage'] ?? 0),
                'disk_limit' => (int) ($h['disklimit'] ?? 0),
                'bw_usage' => (int) ($h['bwusage'] ?? 0),
                'bw_limit' => (int) ($h['bwlimit'] ?? 0),
                'suspension_reason' => trim(Mojibake::repair((string) ($h['suspendreason'] ?? ''))) ?: null,
                'override_auto_suspend_date' => $this->normalizeDate($h['overidesuspenduntil'] ?? null, false),
                'notes' => $notes,
                'auto_renew' => true,
                'module_data' => $moduleData ?: ($existing?->module_data ?? []),
            ];

            if ($decryptedPw !== '') {
                $attrs['password'] = $decryptedPw;
            }

            if ($existing) {
                $existing->update($attrs);
                $svc = $existing;
            } else {
                $svc = Service::create($attrs);
            }

            $claimedServiceIds[] = $svc->id;
            $this->serviceMap[$whmcsId] = $svc->id;
            $synced++;
        }

        $this->recordStat('services', count($rows), $synced);
    }

    private function importServiceConfigOptions(): void
    {
        foreach ($this->fetchAll('tblhostingconfigoptions', 'id') as $row) {
            $sid = $this->serviceMap[(int) ($row['relid'] ?? 0)] ?? null;
            $cid = $this->configOptionMap[(int) ($row['configid'] ?? 0)] ?? null;
            $oid = $this->configSubMap[(int) ($row['optionid'] ?? 0)] ?? null;
            if (! $sid || ! $cid || ! $oid) {
                continue;
            }

            ServiceConfigOption::updateOrCreate(
                ['service_id' => $sid, 'config_id' => $cid],
                ['option_id' => $oid, 'qty' => (int) ($row['qty'] ?? 0), 'unit_price' => 0]
            );
        }
    }

    private function importDomainsExcludingReseller(): void
    {
        $rows = $this->fetchAll('tbldomains', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $cid = $this->clientMap[(int) ($row['userid'] ?? 0)] ?? null;
            $domainName = Domain::normalise((string) ($row['domain'] ?? ''));
            if (! $cid || $domainName === '') {
                continue;
            }

            // Domain Reseller (BdwebsReseller / DomainsReseller) is explicitly excluded from scope -> map to Manual.
            $rawReg = trim((string) ($row['registrar'] ?? ''));
            $registrar = ($rawReg === '' || strcasecmp($rawReg, 'BdwebsReseller') === 0 || strcasecmp($rawReg, 'DomainsReseller') === 0)
                ? 'Manual'
                : $rawReg;

            $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
            $model = Domain::updateOrCreate(
                ['client_id' => $cid, 'domain' => $domainName],
                [
                    'order_id' => $this->orderMap[(int) ($row['orderid'] ?? 0)] ?? null,
                    'type' => strtolower(trim((string) ($row['type'] ?? 'register'))) ?: 'register',
                    'registrar' => $registrar,
                    'registration_period' => max(1, (int) ($row['registrationperiod'] ?? 1)),
                    'registration_date' => $this->normalizeDate($row['registrationdate'] ?? null),
                    'expiry_date' => $this->normalizeDate($row['expirydate'] ?? null, false),
                    'next_due_date' => $this->normalizeDate($row['nextduedate'] ?? null, false),
                    'status' => $status,
                    'dns_management' => (int) ($row['dnsmanagement'] ?? 0) === 1,
                    'email_forwarding' => (int) ($row['emailforwarding'] ?? 0) === 1,
                    'id_protection' => (int) ($row['idprotection'] ?? 0) === 1,
                    'is_premium' => (int) ($row['is_premium'] ?? 0) === 1,
                    'payment_method' => ((int) ($row['donotrenew'] ?? 0) === 1) ? 'none' : $this->mapPaymentMethod($row['paymentmethod'] ?? null),
                    'first_payment_amount' => (float) ($row['firstpaymentamount'] ?? 0),
                    'recurring_amount' => (float) ($row['recurringamount'] ?? 0),
                    'notes' => trim(Mojibake::repair((string) ($row['additionalnotes'] ?? ''))) ?: null,
                ]
            );

            $this->domainMap[(int) $row['id']] = $model->id;
            $synced++;
        }

        $this->recordStat('domains', count($rows), $synced);
    }

    private function importInvoicesAndItems(): void
    {
        $rows = $this->fetchAll('tblinvoices', 'id');
        $synced = 0;
        $claimedNums = [];

        foreach ($rows as $row) {
            $whmcsId = (int) $row['id'];
            $cid = $this->clientMap[(int) ($row['userid'] ?? 0)] ?? null;
            if (! $cid) {
                continue;
            }

            $legacyNum = trim((string) ($row['invoicenum'] ?? ''));
            $altNum = 'INV-WHMCS-'.$whmcsId;

            $existing = Invoice::where('invoice_num', $altNum)->first()
                ?? ($legacyNum !== '' ? Invoice::where('invoice_num', $legacyNum)->first() : null);

            $invNum = $existing?->invoice_num;
            if (! $invNum) {
                $candidate = $legacyNum !== '' ? $legacyNum : $altNum;
                $invNum = in_array($candidate, $claimedNums, true) ? $altNum : $candidate;
            }
            $claimedNums[] = $invNum;

            $status = match (strtolower(trim((string) ($row['status'] ?? 'unpaid')))) {
                'paid' => InvoiceStatus::Paid->value,
                'cancelled' => InvoiceStatus::Cancelled->value,
                'draft' => InvoiceStatus::Draft->value,
                'refunded' => 'refunded',
                default => InvoiceStatus::Unpaid->value,
            };

            $datePaid = $this->normalizeDateTime($row['datepaid'] ?? null);
            $client = Client::find($cid);
            $currencyCode = Currency::find($client?->currency_id ?? $this->defaultCurrencyId)?->code ?? 'BDT';

            $attrs = [
                'client_id' => $cid,
                'date' => $this->normalizeDate($row['date'] ?? null),
                'due_date' => $this->normalizeDate($row['duedate'] ?? null),
                'date_paid' => $status === InvoiceStatus::Paid->value ? $datePaid : null,
                'subtotal' => (float) ($row['subtotal'] ?? 0),
                'credit' => (float) ($row['credit'] ?? 0),
                'tax' => (float) ($row['tax'] ?? 0),
                'tax2' => (float) ($row['tax2'] ?? 0),
                'total' => (float) ($row['total'] ?? 0),
                'tax_rate' => (float) ($row['taxrate'] ?? 0),
                'tax_rate2' => (float) ($row['taxrate2'] ?? 0),
                'status' => $status,
                'payment_method' => $this->mapPaymentMethod($row['paymentmethod'] ?? null),
                'notes' => trim(Mojibake::repair((string) ($row['notes'] ?? ''))) ?: null,
                'source_currency' => strtoupper($currencyCode),
            ];

            if ($existing) {
                $existing->update($attrs);
                $invoice = $existing;
            } else {
                $attrs['invoice_num'] = $invNum;
                $attrs['billing_currency'] = strtoupper($currencyCode);
                $attrs['exchange_rate'] = 1.0;
                $invoice = Invoice::create($attrs);
            }

            $this->invoiceMap[$whmcsId] = $invoice->id;
            $synced++;
        }

        // Link orders.invoice_id now that invoiceMap exists
        foreach ($this->fetchAll('tblorders', 'id') as $ord) {
            $pnlcsOrderId = $this->orderMap[(int) $ord['id']] ?? null;
            $pnlcsInvId = $this->invoiceMap[(int) ($ord['invoiceid'] ?? 0)] ?? null;
            if ($pnlcsOrderId && $pnlcsInvId) {
                Order::whereKey($pnlcsOrderId)->update(['invoice_id' => $pnlcsInvId]);
            }
        }

        // Import invoice items
        $items = $this->fetchAll('tblinvoiceitems', 'id');
        $itemsByInv = [];
        foreach ($items as $it) {
            $whmcsInvId = (int) ($it['invoiceid'] ?? 0);
            if (! isset($this->invoiceMap[$whmcsInvId])) {
                continue;
            }
            $itemsByInv[$whmcsInvId][] = $it;
        }

        $itemSynced = 0;
        foreach ($itemsByInv as $whmcsInvId => $invItems) {
            $pnlcsInvId = $this->invoiceMap[$whmcsInvId];
            $existingCount = InvoiceItem::where('invoice_id', $pnlcsInvId)->count();
            if ($existingCount >= count($invItems)) {
                $itemSynced += $existingCount;

                continue;
            }
            // Replace incomplete item list atomically so re-runs stay idempotent with exact lines
            InvoiceItem::where('invoice_id', $pnlcsInvId)->delete();
            foreach ($invItems as $it) {
                $type = trim((string) ($it['type'] ?? ''));
                $relId = (int) ($it['relid'] ?? 0);
                $mappedRel = match (true) {
                    in_array($type, ['Hosting', 'PromoHosting'], true) => $this->serviceMap[$relId] ?? 0,
                    in_array($type, ['Domain', 'DomainRegister', 'DomainTransfer'], true) => $this->domainMap[$relId] ?? 0,
                    $type === 'Invoice' => $this->invoiceMap[$relId] ?? 0,
                    default => $relId,
                };

                InvoiceItem::create([
                    'invoice_id' => $pnlcsInvId,
                    'client_id' => $this->clientMap[(int) ($it['userid'] ?? 0)] ?? Invoice::whereKey($pnlcsInvId)->value('client_id'),
                    'type' => $type,
                    'rel_id' => $mappedRel,
                    'description' => Mojibake::repair((string) ($it['description'] ?? '')),
                    'qty' => 1,
                    'amount' => (float) ($it['amount'] ?? 0),
                    'taxed' => (int) ($it['taxed'] ?? 0) === 1,
                    'due_date' => $this->normalizeDate($it['duedate'] ?? null, false),
                ]);
                $itemSynced++;
            }
        }

        $this->recordStat('invoices', count($rows), $synced);
        $this->recordStat('invoice_items', count($items), $itemSynced);
    }

    private function importTransactions(): void
    {
        $rows = $this->fetchAll('tblaccounts', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $cid = $this->clientMap[(int) ($row['userid'] ?? 0)] ?? null;
            $invId = $this->invoiceMap[(int) ($row['invoiceid'] ?? 0)] ?? null;
            $transId = trim((string) ($row['transid'] ?? ''));
            $date = $this->normalizeDate($row['date'] ?? null) ?? now()->toDateString();

            $match = ['client_id' => $cid, 'invoice_id' => $invId, 'date' => $date];
            if ($transId !== '') {
                $match['transaction_id'] = $transId;
            } else {
                $match['amount_in'] = (float) ($row['amountin'] ?? 0);
                $match['amount_out'] = (float) ($row['amountout'] ?? 0);
                $match['description'] = Mojibake::repair((string) ($row['description'] ?? ''));
            }

            Transaction::updateOrCreate($match, [
                'currency_id' => $this->currencyMap[(int) ($row['currency'] ?? 0)] ?? $this->defaultCurrencyId,
                'gateway' => $this->mapPaymentMethod($row['gateway'] ?? null),
                'description' => Mojibake::repair((string) ($row['description'] ?? '')),
                'amount_in' => (float) ($row['amountin'] ?? 0),
                'fees' => (float) ($row['fees'] ?? 0),
                'amount_out' => (float) ($row['amountout'] ?? 0),
                'rate' => (float) ($row['rate'] ?? 1.0),
                'transaction_id' => $transId ?: null,
                'refund_id' => (int) ($row['refundid'] ?? 0) ?: null,
            ]);
            $synced++;
        }

        $this->recordStat('transactions', count($rows), $synced);
    }

    private function importCredits(): void
    {
        $rows = $this->fetchAll('tblcredit', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $cid = $this->clientMap[(int) ($row['clientid'] ?? 0)] ?? null;
            if (! $cid) {
                continue;
            }

            $date = $this->normalizeDate($row['date'] ?? null);
            $desc = trim(Mojibake::repair((string) ($row['description'] ?? '')));
            $amt = (float) ($row['amount'] ?? 0);

            Credit::updateOrCreate(
                ['client_id' => $cid, 'date' => $date, 'amount' => $amt, 'description' => $desc],
                ['admin_id' => $this->adminMap[(int) ($row['admin_id'] ?? 0)] ?? null]
            );
            $synced++;
        }

        $this->recordStat('credits', count($rows), $synced);
    }

    private function importPromotions(): void
    {
        $rows = $this->fetchAll('tblpromotions', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            if ($code === '') {
                continue;
            }

            $type = match (strtolower(trim((string) ($row['type'] ?? 'percentage')))) {
                'fixed amount', 'fixed_amount' => 'fixed_amount',
                'free setup', 'free_setup' => 'free_setup',
                'price override', 'price_override' => 'price_override',
                default => 'percentage',
            };

            $mapList = fn (?string $raw) => implode(',', array_filter(array_map(
                fn ($v) => $this->productMap[(int) trim($v)] ?? null,
                explode(',', (string) $raw)
            ))) ?: null;

            $cycles = implode(',', array_filter(array_map(
                fn ($c) => Promotion::cycleKey($c) ?: null,
                explode(',', (string) ($row['cycles'] ?? ''))
            ))) ?: null;

            Promotion::updateOrCreate(
                ['code' => $code],
                [
                    'type' => $type,
                    'recurring' => (int) ($row['recurring'] ?? 0) === 1,
                    'value' => (float) ($row['value'] ?? 0),
                    'cycles' => max(0, (int) ($row['recurfor'] ?? 0)),
                    'billing_cycles' => $cycles,
                    'max_products' => 0,
                    'applies_to' => $mapList($row['appliesto'] ?? ''),
                    'requires' => $mapList($row['requires'] ?? ''),
                    'start_date' => $this->normalizeDate($row['startdate'] ?? null, false),
                    'expiration_date' => $this->normalizeDate($row['expirationdate'] ?? null, false),
                    'max_uses' => max(0, (int) ($row['maxuses'] ?? 0)),
                    'uses' => max(0, (int) ($row['uses'] ?? 0)),
                    'lifetime_promo' => (int) ($row['lifetimepromo'] ?? 0) === 1,
                    'apply_once' => (int) ($row['applyonce'] ?? 0) === 1 || (int) ($row['onceperclient'] ?? 0) === 1,
                    'new_signups_only' => (int) ($row['newsignups'] ?? 0) === 1,
                    'existing_client' => (int) ($row['existingclient'] ?? 0) === 1,
                    'upgrades' => (int) ($row['upgrades'] ?? 0) === 1,
                    'notes' => mb_substr(trim(Mojibake::repair((string) ($row['notes'] ?? ''))), 0, 255) ?: null,
                ]
            );
            $synced++;
        }

        $this->recordStat('promotions', count($rows), $synced);
    }

    private function importQuotes(): void
    {
        $rows = $this->fetchAll('tblquotes', 'id');
        $quoteMap = [];
        $synced = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $cid = $this->clientMap[(int) ($row['userid'] ?? 0)] ?? null;
            if (! $cid && ! empty($row['email'])) {
                $cid = Client::whereRaw('LOWER(email) = ?', [strtolower(trim((string) $row['email']))])->value('id');
            }
            if (! $cid) {
                $skipped++;

                continue;
            }
            $subject = trim(Mojibake::repair((string) ($row['subject'] ?? ''))) ?: 'Quote #'.$row['id'];
            $date = $this->normalizeDate($row['datecreated'] ?? null);
            $status = match (strtolower(trim((string) ($row['stage'] ?? 'draft')))) {
                'accepted' => 'accepted',
                'delivered' => 'delivered',
                'lost' => 'lost',
                'dead' => 'dead',
                default => 'draft',
            };

            $quote = Quote::updateOrCreate(
                ['client_id' => $cid, 'subject' => $subject, 'date' => $date],
                [
                    'valid_until' => $this->normalizeDate($row['validuntil'] ?? null, false) ?? $date,
                    'subtotal' => (float) ($row['subtotal'] ?? 0),
                    'tax' => (float) ($row['tax1'] ?? 0) + (float) ($row['tax2'] ?? 0),
                    'total' => (float) ($row['total'] ?? 0),
                    'status' => $status,
                    'notes' => trim(Mojibake::repair((string) ($row['adminnotes'] ?? ''))) ?: null,
                    'customer_notes' => trim(Mojibake::repair((string) ($row['customernotes'] ?? ''))) ?: null,
                    'proposal' => mb_substr(trim(Mojibake::repair((string) ($row['proposal'] ?? ''))), 0, 255) ?: null,
                ]
            );

            $quoteMap[(int) $row['id']] = $quote->id;
            $synced++;
        }

        $itemsByQuote = [];
        foreach ($this->fetchAll('tblquoteitems', 'id') as $it) {
            $qid = $quoteMap[(int) ($it['quoteid'] ?? 0)] ?? null;
            if ($qid) {
                $itemsByQuote[$qid][] = $it;
            }
        }

        foreach ($itemsByQuote as $qid => $qItems) {
            if (QuoteItem::where('quote_id', $qid)->count() >= count($qItems)) {
                continue;
            }
            QuoteItem::where('quote_id', $qid)->delete();
            foreach ($qItems as $idx => $it) {
                QuoteItem::create([
                    'quote_id' => $qid,
                    'description' => Mojibake::repair((string) ($it['description'] ?? '')),
                    'quantity' => max(1, (int) ($it['quantity'] ?? 1)),
                    'unit_price' => (float) ($it['unitprice'] ?? 0),
                    'discount' => (float) ($it['discount'] ?? 0),
                    'taxable' => (int) ($it['taxable'] ?? 0) === 1,
                    'sort_order' => $idx + 1,
                ]);
            }
        }

        $this->recordStat('quotes', count($rows), $synced, $skipped);
    }

    private function importBillableItems(): void
    {
        $rows = $this->fetchAll('tblbillableitems', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $cid = $this->clientMap[(int) ($row['userid'] ?? 0)] ?? null;
            if (! $cid) {
                continue;
            }

            $desc = trim(Mojibake::repair((string) ($row['description'] ?? '')));
            $amt = (float) ($row['amount'] ?? 0);
            $due = $this->normalizeDate($row['duedate'] ?? null, false);

            BillableItem::updateOrCreate(
                ['client_id' => $cid, 'description' => $desc, 'amount' => $amt, 'due_date' => $due],
                [
                    'recur' => (int) ($row['recur'] ?? 0),
                    'recur_cycle' => trim((string) ($row['recurcycle'] ?? '')) ?: null,
                    'recur_for' => (int) ($row['recurfor'] ?? 0),
                ]
            );
            $synced++;
        }

        $this->recordStat('billable_items', count($rows), $synced);
    }

    private function importTickets(): void
    {
        $depts = $this->fetchAll('tblticketdepartments', 'id');
        foreach ($depts as $d) {
            $name = trim(Mojibake::repair((string) ($d['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            $pw = ! empty($d['password']) ? self::decryptWhmcsLegacy((string) $d['password'], $this->ccEncryptionHash) : '';
            $attrs = [
                'description' => trim(Mojibake::repair((string) ($d['description'] ?? ''))) ?: null,
                'email' => trim((string) ($d['email'] ?? '')) ?: null,
                'clients_only' => (int) ($d['clientsonly'] ?? 0) === 1,
                'hidden' => (int) ($d['hidden'] ?? 0) === 1,
                'sort_order' => (int) ($d['order'] ?? 0),
                'feedback_request' => (int) ($d['feedback_request'] ?? 0) === 1,
                'import_host' => trim((string) ($d['host'] ?? '')) ?: null,
                'import_port' => (int) ($d['port'] ?? 0) ?: null,
                'import_username' => trim((string) ($d['login'] ?? '')) ?: null,
            ];
            if ($pw !== '') {
                $attrs['import_password'] = $pw;
            }
            $dept = TicketDepartment::updateOrCreate(['name' => $name], $attrs);
            $this->departmentMap[(int) $d['id']] = $dept->id;
        }

        $fallbackDeptId = TicketDepartment::value('id');
        $tickets = $this->fetchAll('tbltickets', 'id');
        $synced = 0;

        foreach ($tickets as $t) {
            $tid = trim((string) ($t['tid'] ?? '')) ?: 'WHMCS-TKT-'.$t['id'];
            $cid = $this->clientMap[(int) ($t['userid'] ?? 0)] ?? null;
            $deptId = $this->departmentMap[(int) ($t['did'] ?? 0)] ?? $fallbackDeptId;
            if (! $deptId) {
                continue;
            }

            $svcId = is_numeric($t['service'] ?? null) ? ($this->serviceMap[(int) $t['service']] ?? null) : null;
            $ticket = Ticket::updateOrCreate(
                ['tid' => $tid],
                [
                    'department_id' => $deptId,
                    'client_id' => $cid,
                    'contact_id' => $this->contactMap[(int) ($t['contactid'] ?? 0)] ?? null,
                    'name' => trim(Mojibake::repair((string) ($t['name'] ?? ''))) ?: 'Customer',
                    'email' => trim((string) ($t['email'] ?? '')) ?: '',
                    'cc' => trim((string) ($t['cc'] ?? '')) ?: null,
                    'title' => trim(Mojibake::repair((string) ($t['title'] ?? ''))) ?: 'Support Ticket',
                    'message' => Mojibake::repair((string) ($t['message'] ?? '')),
                    'status' => trim((string) ($t['status'] ?? 'Open')) ?: 'Open',
                    'priority' => strtolower(trim((string) ($t['urgency'] ?? 'medium'))) ?: 'medium',
                    'admin' => trim((string) ($t['admin'] ?? '')) ?: null,
                    'attachment' => trim((string) ($t['attachment'] ?? '')) ?: null,
                    'last_reply' => $this->normalizeDateTime($t['lastreply'] ?? null),
                    'flag' => (int) ($t['flag'] ?? 0) ?: null,
                    'service' => $svcId ? (string) $svcId : (trim((string) ($t['service'] ?? '')) ?: null),
                    'editor' => trim((string) ($t['editor'] ?? 'plain')) ?: 'plain',
                ]
            );

            $this->ticketMap[(int) $t['id']] = $ticket->id;
            $synced++;
        }

        $repliesByTicket = [];
        foreach ($this->fetchAll('tblticketreplies', 'id') as $r) {
            $pnlcsTicketId = $this->ticketMap[(int) ($r['tid'] ?? 0)] ?? null;
            if ($pnlcsTicketId) {
                $repliesByTicket[$pnlcsTicketId][] = $r;
            }
        }

        $replySynced = 0;
        foreach ($repliesByTicket as $pnlcsTicketId => $rows) {
            if (TicketReply::where('ticket_id', $pnlcsTicketId)->count() >= count($rows)) {
                $replySynced += count($rows);

                continue;
            }
            TicketReply::where('ticket_id', $pnlcsTicketId)->delete();
            foreach ($rows as $r) {
                TicketReply::create([
                    'ticket_id' => $pnlcsTicketId,
                    'client_id' => $this->clientMap[(int) ($r['userid'] ?? 0)] ?? null,
                    'contact_id' => $this->contactMap[(int) ($r['contactid'] ?? 0)] ?? null,
                    'message' => Mojibake::repair((string) ($r['message'] ?? '')),
                    'admin' => trim((string) ($r['admin'] ?? '')) ?: null,
                    'attachment' => trim((string) ($r['attachment'] ?? '')) ?: null,
                    'rating' => (int) ($r['rating'] ?? 0),
                    'editor' => trim((string) ($r['editor'] ?? 'plain')) ?: 'plain',
                ]);
                $replySynced++;
            }
        }

        foreach ($this->fetchAll('tblticketspamfilters', 'id') as $spam) {
            $type = trim((string) ($spam['type'] ?? 'sender'));
            $content = trim(Mojibake::repair((string) ($spam['content'] ?? '')));
            if ($content !== '') {
                TicketSpamFilter::firstOrCreate(['type' => $type, 'content' => $content]);
            }
        }

        $this->recordStat('tickets', count($tickets), $synced);
        $this->recordStat('ticket_replies', array_sum(array_map('count', $repliesByTicket)), $replySynced);
    }

    private function importCustomFieldValues(): void
    {
        $cfTypes = CustomField::pluck('type', 'id')->all();
        $rows = $this->fetchAll('tblcustomfieldsvalues', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $fid = $this->customFieldMap[(int) ($row['fieldid'] ?? 0)] ?? null;
            $rawRel = (int) ($row['relid'] ?? 0);
            if (! $fid || $rawRel <= 0) {
                continue;
            }

            $type = $cfTypes[$fid] ?? 'client';
            $mappedRel = match ($type) {
                'client' => $this->clientMap[$rawRel] ?? null,
                'product' => $this->serviceMap[$rawRel] ?? null,
                'support' => $this->ticketMap[$rawRel] ?? null,
                default => $this->clientMap[$rawRel] ?? null,
            };

            if (! $mappedRel) {
                continue;
            }

            CustomFieldValue::updateOrCreate(
                ['field_id' => $fid, 'rel_id' => $mappedRel],
                ['value' => Mojibake::repair((string) ($row['value'] ?? ''))]
            );
            $synced++;
        }

        $this->recordStat('custom_field_values', count($rows), $synced);
    }

    private function importKnowledgeBase(): void
    {
        $cats = $this->fetchAll('tblknowledgebasecats', 'id');
        foreach ($cats as $cat) {
            $name = trim(Mojibake::repair((string) ($cat['name'] ?? '')));
            if ($name === '') {
                continue;
            }

            $model = KbCategory::updateOrCreate(
                ['name' => $name],
                [
                    'parent_id' => 0,
                    'description' => trim(Mojibake::repair((string) ($cat['description'] ?? ''))) ?: null,
                    'hidden' => (int) ($cat['hidden'] ?? 0) === 1,
                    'sort_order' => (int) $cat['id'],
                ]
            );
            $this->kbCatMap[(int) $cat['id']] = $model->id;
        }

        foreach ($cats as $cat) {
            $cid = $this->kbCatMap[(int) $cat['id']] ?? null;
            $parentId = $this->kbCatMap[(int) ($cat['parentid'] ?? 0)] ?? 0;
            if ($cid && $parentId && $parentId !== $cid) {
                KbCategory::whereKey($cid)->update(['parent_id' => $parentId]);
            }
        }

        $linkMap = [];
        foreach ($this->fetchAll('tblknowledgebaselinks', 'articleid') as $lnk) {
            $linkMap[(int) $lnk['articleid']] = (int) $lnk['categoryid'];
        }

        $defaultCatId = KbCategory::value('id') ?? 1;
        $articles = $this->fetchAll('tblknowledgebase', 'id');
        $synced = 0;

        foreach ($articles as $art) {
            $title = trim(Mojibake::repair((string) ($art['title'] ?? '')));
            if ($title === '') {
                continue;
            }

            $whmcsCatId = $linkMap[(int) $art['id']] ?? 0;
            $catId = $this->kbCatMap[$whmcsCatId] ?? $defaultCatId;

            KbArticle::updateOrCreate(
                ['category_id' => $catId, 'title' => $title],
                [
                    'article' => Mojibake::repair((string) ($art['article'] ?? '')),
                    'views' => (int) ($art['views'] ?? 0),
                    'useful' => (int) ($art['useful'] ?? 0),
                    'votes' => (int) ($art['votes'] ?? 0),
                    'private' => (int) ($art['private'] ?? 0) === 1,
                    'sort_order' => (int) ($art['order'] ?? 0),
                ]
            );
            $synced++;
        }

        $this->recordStat('kb_categories', count($cats), count($this->kbCatMap));
        $this->recordStat('kb_articles', count($articles), $synced);
    }

    private function importAnnouncements(): void
    {
        $rows = $this->fetchAll('tblannouncements', 'id');
        $synced = 0;

        foreach ($rows as $row) {
            $title = trim(Mojibake::repair((string) ($row['title'] ?? '')));
            if ($title === '') {
                continue;
            }

            Announcement::updateOrCreate(
                ['title' => $title],
                [
                    'announcement' => Mojibake::repair((string) ($row['announcement'] ?? '')),
                    'published' => (int) ($row['published'] ?? 1) === 1,
                ]
            );
            $synced++;
        }

        $this->recordStat('announcements', count($rows), $synced);
    }

    private function importAffiliates(): void
    {
        $rows = $this->fetchAll('tblaffiliates', 'id');
        $affMap = [];
        $synced = 0;

        foreach ($rows as $row) {
            $cid = $this->clientMap[(int) ($row['clientid'] ?? 0)] ?? null;
            if (! $cid) {
                continue;
            }

            $aff = Affiliate::updateOrCreate(
                ['client_id' => $cid],
                [
                    'visitors' => (int) ($row['visitors'] ?? 0),
                    'pay_type' => strtolower(trim((string) ($row['paytype'] ?? 'percentage'))) ?: 'percentage',
                    'pay_amount' => (float) ($row['payamount'] ?? 0),
                    'recurring_pay_amount' => 0,
                    'onetime' => (int) ($row['onetime'] ?? 0) === 1,
                    'balance' => (float) ($row['balance'] ?? 0),
                    'withdrawn' => (float) ($row['withdrawn'] ?? 0),
                ]
            );
            $affMap[(int) $row['id']] = $aff->id;
            $synced++;
        }

        $accMap = [];
        foreach ($this->fetchAll('tblaffiliatesaccounts', 'id') as $acc) {
            $aid = $affMap[(int) ($acc['affiliateid'] ?? 0)] ?? null;
            $sid = $this->serviceMap[(int) ($acc['relid'] ?? 0)] ?? null;
            if (! $aid || ! $sid) {
                continue;
            }

            DB::table('affiliate_accounts')->updateOrInsert(
                ['affiliate_id' => $aid, 'service_id' => $sid],
                [
                    'last_paid' => $this->normalizeDate($acc['lastpaid'] ?? null, false),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $accMap[(int) $acc['id']] = (int) DB::table('affiliate_accounts')
                ->where('affiliate_id', $aid)
                ->where('service_id', $sid)
                ->value('id');
        }

        foreach ($this->fetchAll('tblaffiliateshistory', 'id') as $hist) {
            $aid = $affMap[(int) ($hist['affiliateid'] ?? 0)] ?? null;
            if (! $aid) {
                continue;
            }
            $date = $this->normalizeDate($hist['date'] ?? null);
            $desc = trim(Mojibake::repair((string) ($hist['description'] ?? '')));
            $amt = (float) ($hist['amount'] ?? 0);

            DB::table('affiliate_history')->updateOrInsert(
                ['affiliate_id' => $aid, 'date' => $date, 'amount' => $amt, 'description' => $desc],
                [
                    'affiliate_account_id' => $accMap[(int) ($hist['affaccid'] ?? 0)] ?? null,
                    'invoice_id' => $this->invoiceMap[(int) ($hist['invoice_id'] ?? 0)] ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->recordStat('affiliates', count($rows), $synced);
    }

    private function importPaymentGateways(): void
    {
        if (! $this->hasTable('tblpaymentgateways')) {
            return;
        }

        $rows = $this->fetchAll('tblpaymentgateways', 'id');
        $byGateway = [];
        foreach ($rows as $row) {
            $gw = strtolower(trim((string) ($row['gateway'] ?? '')));
            $setting = trim((string) ($row['setting'] ?? ''));
            if ($gw === '' || $setting === '') {
                continue;
            }
            $byGateway[$gw][$setting] = (string) ($row['value'] ?? '');
        }

        if (isset($byGateway['piprapay'])) {
            $pp = $byGateway['piprapay'];
            $apiKey = trim(self::decryptWhmcsLegacy((string) ($pp['apikey'] ?? ''), $this->ccEncryptionHash) ?: ($pp['apikey'] ?? ''));
            $baseUrl = trim(self::decryptWhmcsLegacy((string) ($pp['baseUrl'] ?? ''), $this->ccEncryptionHash) ?: ($pp['baseUrl'] ?? ''));
            GatewaySettings::updateOrCreate(['gateway' => 'piprapay', 'setting' => 'active'], ['value' => ($pp['visible'] ?? '') === 'on' ? '1' : '0']);
            if ($apiKey !== '' && ctype_print($apiKey)) {
                GatewaySettings::updateOrCreate(['gateway' => 'piprapay', 'setting' => 'api_key'], ['value' => $apiKey]);
            }
            if ($baseUrl !== '' && filter_var($baseUrl, FILTER_VALIDATE_URL)) {
                GatewaySettings::updateOrCreate(['gateway' => 'piprapay', 'setting' => 'base_url'], ['value' => rtrim($baseUrl, '/')]);
            }
        }

        // Enable resellergateway whenever reseller_gateway existed in WHMCS or reseller data exists
        if (isset($byGateway['reseller_gateway']) || $this->hasTable('mod_resellers')) {
            GatewaySettings::updateOrCreate(['gateway' => 'resellergateway', 'setting' => 'active'], ['value' => '1']);
        }
    }

    private function importPhase2TelecomAddons(): void
    {
        foreach (['italk_balance', 'italk_recharge', 'recharge_requests', 'bdpbx_accounting'] as $addon) {
            Setting::set("addon_{$addon}_active", '1', 'addons');
            Setting::set("addon_{$addon}_version", '1.0.0', 'addons');
        }

        // Endpoints
        foreach ($this->fetchAll('mod_italk_balance_urls', 'id') as $row) {
            $program = stripos((string) $row['base_url'], 'icc') !== false ? 'icc' : 'italk';
            $ep = ItalkBalanceEndpoint::updateOrCreate(
                ['label' => trim((string) $row['label'])],
                ['base_url' => trim((string) $row['base_url']), 'program' => $program, 'is_active' => true]
            );
            $this->endpointMap[(int) $row['id']] = $ep->id;
        }

        // Map selected product IDs for iTalk and ICC
        $italkSelRaw = $this->hasTable('mod_italk_balance_settings')
            ? (string) $this->whmcs->query("SELECT setting_value FROM mod_italk_balance_settings WHERE setting_key = 'selected_products' LIMIT 1")->fetchColumn()
            : '';
        $iccSelRaw = $this->hasTable('mod_icc_balance_settings')
            ? (string) $this->whmcs->query("SELECT setting_value FROM mod_icc_balance_settings WHERE setting_key = 'selected_products' LIMIT 1")->fetchColumn()
            : '';

        if ($italkSelRaw !== '') {
            $mappedIds = array_values(array_unique(array_filter(array_map(
                fn ($id) => $this->productMap[(int) trim($id)] ?? null,
                explode(',', $italkSelRaw)
            ))));
            app(BalanceRegistry::class)->storeSelectedProducts('italk', $mappedIds);
        }
        if ($iccSelRaw !== '') {
            $mappedIds = array_values(array_unique(array_filter(array_map(
                fn ($id) => $this->productMap[(int) trim($id)] ?? null,
                explode(',', $iccSelRaw)
            ))));
            app(BalanceRegistry::class)->storeSelectedProducts('icc', $mappedIds);
        }

        // iTalk & ICC balance service mappings
        $defaultItalkEp = ItalkBalanceEndpoint::where('program', 'italk')->value('id') ?? ItalkBalanceEndpoint::value('id');
        $defaultIccEp = ItalkBalanceEndpoint::where('program', 'icc')->value('id') ?? $defaultItalkEp;

        foreach ($this->fetchAll('mod_italk_balance_v2', 'id') as $b) {
            $cid = $this->clientMap[(int) ($b['client_id'] ?? 0)] ?? null;
            $sid = $this->serviceMap[(int) ($b['service_id'] ?? 0)] ?? null;
            $pid = $this->productMap[(int) ($b['product_id'] ?? 0)] ?? null;
            $epId = $this->endpointMap[(int) ($b['url_id'] ?? 0)] ?? $defaultItalkEp;
            if (! $cid || ! $sid || ! $epId) {
                continue;
            }
            ItalkBalanceService::updateOrCreate(
                ['service_id' => $sid],
                [
                    'client_id' => $cid,
                    'product_id' => $pid ?? Service::whereKey($sid)->value('product_id'),
                    'ip_number' => trim((string) $b['ip_number']),
                    'balance' => is_numeric($b['balance']) ? (float) $b['balance'] : null,
                    'endpoint_id' => $epId,
                ]
            );
        }

        foreach ($this->fetchAll('mod_icc_balance_v2', 'id') as $b) {
            $cid = $this->clientMap[(int) ($b['client_id'] ?? 0)] ?? null;
            $sid = $this->serviceMap[(int) ($b['service_id'] ?? 0)] ?? null;
            $pid = $this->productMap[(int) ($b['product_id'] ?? 0)] ?? null;
            if (! $cid || ! $sid || ! $defaultIccEp) {
                continue;
            }
            ItalkBalanceService::updateOrCreate(
                ['service_id' => $sid],
                [
                    'client_id' => $cid,
                    'product_id' => $pid ?? Service::whereKey($sid)->value('product_id'),
                    'ip_number' => trim((string) $b['ip_number']),
                    'balance' => is_numeric($b['balance']) ? (float) $b['balance'] : null,
                    'endpoint_id' => $defaultIccEp,
                ]
            );
        }

        // Recharge providers
        foreach ($this->fetchAll('mod_italk_providers', 'id') as $row) {
            $provider = ItalkRechargeProvider::firstOrNew(['name' => trim((string) $row['name'])]);
            $provider->fill([
                'base_url' => (string) $row['base_url'],
                'base_path' => (string) ($row['base_path'] ?: '/iconiptsp/'),
                'integration_type' => (string) ($row['integration_type'] ?: 'scraper'),
                'api_type' => (string) ($row['api_type'] ?: 'italk'),
                'username' => (string) $row['username'],
                'status' => (string) ($row['status'] ?: 'active'),
            ]);
            if ((string) ($row['password'] ?? '') !== '') {
                $provider->password = (string) $row['password'];
            }
            if ((string) ($row['api_key'] ?? '') !== '') {
                $provider->api_key = (string) $row['api_key'];
            }
            $provider->save();
            $this->providerMap[(int) $row['id']] = $provider->id;
        }

        // Recharge PIN accounts (16 rows)
        $accRows = $this->fetchAll('mod_italk_recharge', 'id');
        $accSynced = 0;
        foreach ($accRows as $row) {
            $cid = $this->clientMap[(int) ($row['client_id'] ?? 0)] ?? null;
            $pid = $this->providerMap[(int) ($row['provider_id'] ?? 0)] ?? null;
            if (! $cid || ! $pid) {
                continue;
            }
            $acc = ItalkRechargeAccount::updateOrCreate(
                ['client_id' => $cid, 'provider_id' => $pid],
                [
                    'pin_number' => trim((string) $row['pin_number']),
                    'total_recharged' => (float) ($row['total_recharged'] ?? 0),
                    'last_recharge' => $this->normalizeDateTime($row['last_recharge'] ?? null),
                    'status' => trim((string) ($row['status'] ?? 'active')) ?: 'active',
                ]
            );
            $this->accountMap[(int) $row['id']] = $acc->id;
            $accSynced++;
        }

        // Recharge logs (43 rows)
        foreach ($this->fetchAll('mod_italk_recharge_log', 'id') as $log) {
            $cid = $this->clientMap[(int) ($log['client_id'] ?? 0)] ?? null;
            $pid = $this->providerMap[(int) ($log['provider_id'] ?? 0)] ?? null;
            if (! $cid || ! $pid) {
                continue;
            }
            $status = in_array(strtolower((string) ($log['status'] ?? '')), ItalkRechargeLog::STATUSES, true)
                ? strtolower((string) $log['status'])
                : 'failed';
            $invId = $this->invoiceMap[(int) ($log['invoice_id'] ?? 0)] ?? null;
            $pin = ItalkRechargeAccount::where('client_id', $cid)->where('provider_id', $pid)->value('pin_number') ?: 'unknown';

            $createdAt = $this->normalizeDateTime($log['created_at'] ?? null) ?? now()->toDateTimeString();
            DB::table('italk_recharge_logs')->updateOrInsert(
                [
                    'client_id' => $cid,
                    'provider_id' => $pid,
                    'invoice_id' => $invId,
                    'amount' => (float) ($log['amount'] ?? 0),
                    'created_at' => $createdAt,
                ],
                [
                    'pin_number' => $pin,
                    'status' => $status,
                    'attempts' => 1,
                    'last_attempt_at' => $createdAt,
                    'api_response' => (string) ($log['api_response'] ?? ''),
                    'updated_at' => $createdAt,
                ]
            );
        }

        // Recharge requests (98 rows)
        $reqRows = $this->fetchAll('mod_recharge_requests', 'id');
        $reqSynced = 0;
        foreach ($reqRows as $row) {
            $cid = $this->clientMap[(int) ($row['client_id'] ?? 0)] ?? null;
            if (! $cid) {
                continue;
            }
            $invId = $this->invoiceMap[(int) ($row['invoice_id'] ?? 0)] ?? null;
            RechargeRequest::updateOrCreate(
                [
                    'client_id' => $cid,
                    'mobile_number' => trim((string) $row['mobile_number']),
                    'requested_at' => $row['requested_at'],
                ],
                [
                    'amount' => (float) ($row['amount'] ?? 0),
                    'invoice_id' => $invId,
                    'status' => in_array($row['status'] ?? '', RechargeRequest::STATUSES, true) ? $row['status'] : 'Pending',
                ]
            );
            $reqSynced++;
        }

        // BDPBX Daily Stats (6 rows)
        foreach ($this->fetchAll('mod_bdpbx_daily_stats', 'id') as $st) {
            $rawSrv = (int) ($st['server_id'] ?? 0);
            $mappedSrv = $rawSrv === 0 ? 0 : ($this->serverMap[$rawSrv] ?? $rawSrv);
            BdpbxDailyStat::updateOrCreate(
                ['snapshot_date' => $this->normalizeDate($st['snapshot_date'] ?? null), 'server_id' => $mappedSrv],
                [
                    'server_name' => (string) ($st['server_name'] ?? ($mappedSrv === 0 ? 'All PBX Servers (Combined)' : 'PBX Server')),
                    'server_hostname' => ($st['server_hostname'] ?? null) ?: null,
                    'total_services' => (int) ($st['total_services'] ?? 0),
                    'active_services' => (int) ($st['active_services'] ?? 0),
                    'suspended_services' => (int) ($st['suspended_services'] ?? 0),
                    'pending_services' => (int) ($st['pending_services'] ?? 0),
                    'cancelled_services' => (int) ($st['cancelled_services'] ?? 0),
                    'active_mrr' => (float) ($st['active_mrr'] ?? 0),
                    'paid_clients_month' => (int) ($st['paid_clients_month'] ?? 0),
                    'paid_invoices_month' => (int) ($st['paid_invoices_month'] ?? 0),
                    'paid_amount_month' => (float) ($st['paid_amount_month'] ?? 0),
                    'paid_today_amount' => (float) ($st['paid_today_amount'] ?? 0),
                    'due_clients' => (int) ($st['due_clients'] ?? 0),
                    'due_invoices' => (int) ($st['due_invoices'] ?? 0),
                    'due_amount' => (float) ($st['due_amount'] ?? 0),
                    'due_details' => json_decode((string) ($st['due_details_json'] ?? '[]'), true) ?: [],
                    'services_details' => json_decode((string) ($st['services_details_json'] ?? '[]'), true) ?: [],
                    'last_run_at' => $this->normalizeDateTime($st['last_run_at'] ?? null),
                ]
            );
        }

        $this->recordStat('italk_recharge_accounts', count($accRows), $accSynced);
        $this->recordStat('recharge_requests', count($reqRows), $reqSynced);
    }

    private function importPhase3NotificationAddons(): void
    {
        foreach (['softcents_sms', 'whatsapp_pro', 'telegram_bot', 'softcents_voice'] as $addon) {
            Setting::set("addon_{$addon}_active", '1', 'addons');
            Setting::set("addon_{$addon}_version", '1.0.0', 'addons');
        }

        // SMS
        if ($this->hasTable('mod_softcents_sms_settings')) {
            $smsCfg = $this->whmcs->query('SELECT * FROM mod_softcents_sms_settings ORDER BY id LIMIT 1')->fetch();
            if ($smsCfg) {
                SmsSetting::current()->fill([
                    'client_id' => (string) $smsCfg['client_id'],
                    'client_secret' => (string) $smsCfg['client_secret'],
                    'sender_id' => (string) $smsCfg['sender_id'],
                    'date_format' => (string) ($smsCfg['dateformat'] ?: 'd-m-Y'),
                    'otp_enabled' => (bool) $smsCfg['otp_enabled'],
                    'otp_checkout_enabled' => (bool) $smsCfg['otp_checkout_enabled'],
                    'otp_length' => (int) $smsCfg['otp_length'],
                    'otp_expiry_minutes' => (int) $smsCfg['otp_expiry_minutes'],
                    'otp_resend_cooldown' => (int) $smsCfg['otp_resend_cooldown'],
                    'otp_max_per_hour' => (int) $smsCfg['otp_max_per_hour'],
                    'otp_template' => (string) $smsCfg['otp_template'],
                ])->save();
            }
        }
        foreach ($this->fetchAll('mod_softcents_sms_templates', 'id') as $tpl) {
            $name = trim(Mojibake::repair((string) ($tpl['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            SmsTemplate::updateOrCreate(
                ['name' => $name, 'type' => trim((string) ($tpl['type'] ?? 'client')) ?: 'client'],
                [
                    'template' => Mojibake::repair((string) ($tpl['template'] ?? '')),
                    'variables' => (string) ($tpl['variables'] ?? ''),
                    'description' => Mojibake::repair((string) ($tpl['description'] ?? '')),
                    'active' => (bool) ($tpl['active'] ?? true),
                ]
            );
        }

        // WhatsApp
        if ($this->hasTable('mod_bdhost_wa_settings')) {
            $waCfg = $this->whmcs->query('SELECT * FROM mod_bdhost_wa_settings ORDER BY id LIMIT 1')->fetch();
            if ($waCfg) {
                WaSettings::current()->fill([
                    'api_key' => (string) $waCfg['apikey'],
                    'sender' => (string) $waCfg['whatsapp'],
                    'api_domain' => rtrim((string) ($waCfg['custom_domain'] ?: 'https://wpg.softcents.com'), '/'),
                    'footer' => Mojibake::repair((string) ($waCfg['footer_message'] ?? '')),
                    'admin_numbers' => (string) ($waCfg['update_admin_number'] ?? ''),
                    'client_portal' => (bool) ($waCfg['client_portal_enabled'] ?? true),
                    'max_retry' => (int) ($waCfg['max_retry_count'] ?: 3),
                    'enabled' => true,
                ])->save();
            }
        }
        foreach ($this->fetchAll('mod_bdhost_wa_templates', 'id') as $tpl) {
            $name = trim(Mojibake::repair((string) ($tpl['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            WaTemplate::updateOrCreate(
                ['name' => $name, 'type' => trim((string) ($tpl['type'] ?? 'client')) ?: 'client'],
                [
                    'template' => Mojibake::repair((string) ($tpl['template'] ?? '')),
                    'variables' => (string) ($tpl['variables'] ?? ''),
                    'description' => Mojibake::repair((string) ($tpl['description'] ?? '')),
                    'active' => (bool) ($tpl['active'] ?? true),
                ]
            );
        }
        foreach ($this->fetchAll('mod_bdhost_wa_reports', 'id') as $rep) {
            $cid = $this->clientMap[(int) ($rep['userid'] ?? 0)] ?? null;
            $gsm = trim((string) ($rep['gsm'] ?? ''));
            if ($gsm === '') {
                continue;
            }
            $createdAt = $this->normalizeDateTime($rep['date'] ?? null) ?? now()->toDateTimeString();
            DB::table('whatsapp_logs')->updateOrInsert(
                ['number' => $gsm, 'created_at' => $createdAt],
                [
                    'client_id' => $cid,
                    'message' => Mojibake::repair((string) ($rep['message'] ?? '')),
                    'status' => strtolower(trim((string) ($rep['status'] ?? 'sent'))),
                    'type' => trim((string) ($rep['type'] ?? 'text')) ?: 'text',
                    'media_url' => ($rep['media_url'] ?? null) ?: null,
                    'source' => ($rep['source'] ?? null) ?: 'whmcs',
                    'template_name' => ($rep['template_name'] ?? null) ?: null,
                    'retry_count' => (int) ($rep['retry_count'] ?? 0),
                    'updated_at' => $createdAt,
                ]
            );
        }
        foreach ($this->fetchAll('mod_bdhost_wa_auto_queue', 'id') as $q) {
            $gsm = trim((string) ($q['gsm'] ?? ''));
            if ($gsm === '') {
                continue;
            }
            // Keep historical status; do not re-enqueue already processed legacy items.
            $createdAt = $this->normalizeDateTime($q['created_at'] ?? null) ?? now()->toDateTimeString();
            DB::table('whatsapp_queue')->updateOrInsert(
                ['number' => $gsm, 'created_at' => $createdAt],
                [
                    'client_id' => $this->clientMap[(int) ($q['userid'] ?? 0)] ?? null,
                    'message' => Mojibake::repair((string) ($q['message'] ?? '')),
                    'type' => trim((string) ($q['type'] ?? 'text')) ?: 'text',
                    'template_name' => ($q['template_name'] ?? null) ?: null,
                    'status' => trim((string) ($q['status'] ?? 'sent')) ?: 'sent',
                    'attempts' => (int) ($q['attempts'] ?? 1),
                    'processed_at' => $this->normalizeDateTime($q['processed_at'] ?? null),
                    'updated_at' => $createdAt,
                ]
            );
        }

        // Telegram
        $tgSettings = $this->hasTable('tbladdonmodules')
            ? $this->whmcs->query("SELECT setting, value FROM tbladdonmodules WHERE module = 'softcents_telegram'")->fetchAll(PDO::FETCH_KEY_PAIR)
            : [];
        if (! empty($tgSettings['bot_token'])) {
            AddonSetting::setSetting('telegram_bot', 'bot_token', (string) $tgSettings['bot_token']);
            AddonSetting::setSetting('telegram_bot', 'webhook_secret', (string) ($tgSettings['webhook_secret'] ?? ''));
            AddonSetting::setSetting('telegram_bot', 'enabled', ($tgSettings['enabled'] ?? '') === 'on' ? '1' : '0');
            AddonSetting::setSetting('telegram_bot', 'notify_invoices', ($tgSettings['notify_invoices'] ?? '') === 'on' ? '1' : '0');
            AddonSetting::setSetting('telegram_bot', 'notify_services', ($tgSettings['notify_services'] ?? '') === 'on' ? '1' : '0');
            AddonSetting::setSetting('telegram_bot', 'notify_tickets', ($tgSettings['notify_tickets'] ?? '') === 'on' ? '1' : '0');
        }
        $tgClients = $this->fetchAll('mod_softcents_telegram_clients', 'id');
        $tgSynced = 0;
        foreach ($tgClients as $tg) {
            $cid = $this->clientMap[(int) ($tg['userid'] ?? 0)] ?? null;
            if (! $cid) {
                continue;
            }
            TelegramClient::updateOrCreate(
                ['client_id' => $cid],
                [
                    'chat_id' => trim((string) ($tg['chat_id'] ?? '')) ?: null,
                    'telegram_username' => trim((string) ($tg['telegram_username'] ?? '')) ?: null,
                    'telegram_name' => Mojibake::repair((string) ($tg['telegram_name'] ?? '')) ?: null,
                    'link_token' => trim((string) ($tg['link_token'] ?? '')) ?: Str::random(32),
                    'notify_enabled' => (bool) ($tg['notify_enabled'] ?? true),
                ]
            );
            $tgSynced++;
        }
        foreach ($this->fetchAll('mod_softcents_telegram_logs', 'id') as $tml) {
            $createdAt = $this->normalizeDateTime($tml['created_at'] ?? null) ?? now()->toDateTimeString();
            DB::table('telegram_messages')->updateOrInsert(
                [
                    'chat_id' => (string) ($tml['chat_id'] ?? ''),
                    'event' => (string) ($tml['event'] ?? ''),
                    'created_at' => $createdAt,
                ],
                [
                    'direction' => 'outbound',
                    'body' => Mojibake::repair((string) ($tml['preview'] ?? '')),
                    'status' => (string) ($tml['status'] ?? 'sent'),
                    'updated_at' => $createdAt,
                ]
            );
        }

        // Voice
        $voiceSettings = $this->hasTable('tbladdonmodules')
            ? $this->whmcs->query("SELECT setting, value FROM tbladdonmodules WHERE module = 'softcents_voice'")->fetchAll(PDO::FETCH_KEY_PAIR)
            : [];
        if ($voiceSettings !== []) {
            VoiceSettings::current()->fill([
                'api_token' => (string) ($voiceSettings['api_token'] ?? ''),
                'api_base_url' => (string) ($voiceSettings['api_base_url'] ?? 'https://vsms.softcents.com/api'),
                'caller_id' => (string) ($voiceSettings['caller_id'] ?? '376'),
                'calling_start' => (string) ($voiceSettings['calling_start_time'] ?? '09:00'),
                'calling_end' => (string) ($voiceSettings['calling_end_time'] ?? '21:00'),
                'timezone' => (string) ($voiceSettings['calling_timezone'] ?? 'Asia/Dhaka'),
                'master_paused' => ($voiceSettings['master_voice_dispatch'] ?? 'paused') === 'paused',
                'retry_enabled' => ($voiceSettings['retry_enabled'] ?? 'on') === 'on',
            ])->save();
        }
        foreach ($this->fetchAll('mod_softcents_voice_events', 'id') as $ev) {
            VoiceEvent::updateOrCreate(
                ['event_key' => (string) $ev['event_key']],
                [
                    'category' => (string) $ev['category'],
                    'name' => Mojibake::repair((string) ($ev['name_en'] ?? $ev['event_key'])),
                    'description' => Mojibake::repair((string) ($ev['description'] ?? '')),
                    'audio_id' => (string) ($ev['audio_id'] ?? ''),
                    'caller_id' => (string) ($ev['caller_id'] ?? ''),
                    'is_enabled' => (bool) $ev['is_enabled'],
                ]
            );
        }
        foreach ($this->fetchAll('mod_softcents_voice_group_events', 'id') as $ge) {
            $gid = $this->clientGroupMap[(int) ($ge['group_id'] ?? 0)] ?? null;
            if (! $gid) {
                continue;
            }
            VoiceGroupOverride::updateOrCreate(
                ['group_id' => $gid, 'event_key' => (string) $ge['event_key']],
                [
                    'audio_id' => (string) ($ge['audio_id'] ?? ''),
                    'caller_id' => (string) ($ge['caller_id'] ?? ''),
                    'is_enabled' => (bool) $ge['is_enabled'],
                ]
            );
        }
        foreach ($this->fetchAll('mod_softcents_voice_logs', 'id') as $vl) {
            VoiceLog::firstOrCreate(
                ['call_id' => (string) ($vl['remote_call_id'] ?? ''), 'phone' => (string) $vl['phone_number']],
                [
                    'event_key' => (string) ($vl['call_type'] ?? 'manual_test'),
                    'audio_id' => (string) ($vl['audio_id'] ?? ''),
                    'caller_id' => (string) ($vl['caller_id'] ?? ''),
                    'status' => (string) ($vl['status'] ?? 'pending'),
                    'attempt' => 1,
                ]
            );
        }

        $this->recordStat('telegram_clients', count($tgClients), $tgSynced);
    }

    private function importPhase4UtilityAddons(): void
    {
        foreach ([
            'scrolling_notice', 'advanced_client_verification', 'cancel_invoices', 'portal_extras',
            'price_updater', 'invoice_export', 'gdrive_backup', 'support_pin', 'force_client_active', 'unblock_ip',
        ] as $addon) {
            Setting::set("addon_{$addon}_active", '1', 'addons');
            Setting::set("addon_{$addon}_version", '1.0.0', 'addons');
        }

        // Scrolling Notice
        foreach ($this->fetchAll('mod_scrolling_notice', 'id') as $sn) {
            $content = trim(Mojibake::repair((string) ($sn['content'] ?? '')));
            if ($content === '') {
                continue;
            }
            ScrollingNotice::updateOrCreate(
                ['title' => trim(Mojibake::repair((string) ($sn['title'] ?? ''))) ?: null, 'priority' => (int) ($sn['priority'] ?? 0)],
                [
                    'content' => $content,
                    'url_target' => trim((string) ($sn['url_target'] ?? '')) ?: null,
                    'start_time' => $this->normalizeDateTime($sn['start_time'] ?? null),
                    'expiry_time' => $this->normalizeDateTime($sn['expiry_time'] ?? null),
                    'is_active' => (int) ($sn['status'] ?? 1) === 1,
                    'login_status' => in_array($sn['login_status'] ?? 'all', ['all', 'logged_in', 'logged_out'], true) ? $sn['login_status'] : 'all',
                    'coupon_enabled' => (int) ($sn['coupon_enabled'] ?? 0) === 1,
                    'coupon_code' => trim((string) ($sn['coupon_code'] ?? '')) ?: null,
                    'scroll_speed' => max(1, min(20, (int) ($sn['scroll_speed'] ?? 5))),
                ]
            );
        }

        // Cancel invoices settings
        if ($this->hasTable('mod_cancelinvoicesconfig')) {
            $cfg = $this->whmcs->query('SELECT setting, value FROM mod_cancelinvoicesconfig')->fetchAll(PDO::FETCH_KEY_PAIR);
            AddonSetting::setSetting(CancelInvoicesModule::NAME, 'enable_days', $this->whmcsBool($cfg['EnableDays'] ?? 'on') ? '1' : '0');
            AddonSetting::setSetting(CancelInvoicesModule::NAME, 'days', (string) max(1, (int) ($cfg['Days'] ?? 7)));
            AddonSetting::setSetting(CancelInvoicesModule::NAME, 'enable_all_invoices', $this->whmcsBool($cfg['EnableDaysAllInvoices'] ?? '') ? '1' : '0');
            AddonSetting::setSetting(CancelInvoicesModule::NAME, 'all_invoice_days', (string) max(1, (int) ($cfg['AllInvoiceDays'] ?? 7)));
            AddonSetting::setSetting(CancelInvoicesModule::NAME, 'enable_add_funds', $this->whmcsBool($cfg['EnableAddFunds'] ?? 'on') ? '1' : '0');
            AddonSetting::setSetting(CancelInvoicesModule::NAME, 'add_funds_days', (string) max(1, (int) ($cfg['AddFundsDays'] ?? 7)));
        }

        // Google Drive Backup settings + logs
        if ($this->hasTable('mod_gdrive_backup_settings')) {
            $cfg = $this->whmcs->query('SELECT setting, value FROM mod_gdrive_backup_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach (['client_id', 'client_secret', 'refresh_token', 'drive_folder_id'] as $key) {
                if (! empty($cfg[$key])) {
                    AddonSetting::setSetting(DriveBackupModule::NAME, $key, trim((string) $cfg[$key]));
                }
            }
            AddonSetting::setSetting(DriveBackupModule::NAME, 'max_backups', (string) max(1, (int) ($cfg['max_backups'] ?? 10)));
            AddonSetting::setSetting(DriveBackupModule::NAME, 'backup_db', $this->whmcsBool($cfg['backup_db'] ?? 'on') ? '1' : '0');
            AddonSetting::setSetting(DriveBackupModule::NAME, 'backup_files', $this->whmcsBool($cfg['backup_files'] ?? 'on') ? '1' : '0');
            AddonSetting::setSetting(DriveBackupModule::NAME, 'keep_local', $this->whmcsBool($cfg['keep_local'] ?? 'off') ? '1' : '0');
        }
        foreach ($this->fetchAll('mod_gdrive_backup_log', 'id') as $glog) {
            $createdAt = $this->normalizeDateTime($glog['created_at'] ?? null) ?? now()->toDateTimeString();
            DB::table('gdrive_backup_logs')->updateOrInsert(
                ['file_name' => (string) ($glog['filename'] ?? ''), 'created_at' => $createdAt],
                [
                    'type' => (string) ($glog['type'] ?? 'database'),
                    'status' => (string) ($glog['status'] ?? 'success'),
                    'drive_file_id' => ($glog['drive_file_id'] ?? null) ?: null,
                    'size_bytes' => (int) ($glog['size_bytes'] ?? 0),
                    'message' => (string) ($glog['message'] ?? ''),
                    'triggered_by' => (string) ($glog['triggered_by'] ?? 'system'),
                    'updated_at' => $createdAt,
                ]
            );
        }

        // KYC (Advanced Client Verification) config + 106 verifications + 777 logs
        $kycCfg = $this->hasTable('tbladdonmodules')
            ? $this->whmcs->query("SELECT setting, value FROM tbladdonmodules WHERE module = 'advanced_client_verification'")->fetchAll(PDO::FETCH_KEY_PAIR)
            : [];
        if ($kycCfg !== []) {
            AddonSetting::setSetting(AdvancedClientVerificationModule::NAME, 'verification_mode', 'hybrid');
            foreach (['api_key', 'workflow_id', 'webhook_secret'] as $k) {
                if (! empty($kycCfg[$k])) {
                    AddonSetting::setSetting(AdvancedClientVerificationModule::NAME, $k, trim((string) $kycCfg[$k]));
                }
            }
            AddonSetting::setSetting(AdvancedClientVerificationModule::NAME, 'verification_requirement', (string) ($kycCfg['verification_requirement'] ?? 'disabled') ?: 'disabled');
            AddonSetting::setSetting(AdvancedClientVerificationModule::NAME, 'restricted_groups', (string) ($kycCfg['restricted_groups'] ?? ''));
        }

        $acvRows = $this->fetchAll('mod_advanced_client_verification', 'id');
        $acvSynced = 0;
        foreach ($acvRows as $row) {
            $cid = $this->clientMap[(int) ($row['client_id'] ?? 0)] ?? null;
            if (! $cid) {
                continue;
            }
            $status = in_array(strtolower((string) ($row['status'] ?? '')), ['verified', 'pending', 'failed', 'not_started'], true)
                ? strtolower((string) $row['status'])
                : 'pending';

            AcvVerification::updateOrCreate(
                ['client_id' => $cid],
                [
                    'session_id' => trim((string) ($row['session_id'] ?? '')) ?: null,
                    'verification_url' => trim((string) ($row['verification_url'] ?? '')) ?: null,
                    'status' => $status,
                    'verification_method' => in_array($row['verification_method'] ?? 'api', ['api', 'manual'], true) ? $row['verification_method'] : 'api',
                    'document_type' => trim((string) ($row['document_type'] ?? '')) ?: null,
                    'document_number' => trim((string) ($row['document_number'] ?? '')) ?: null,
                    'business_type' => trim((string) ($row['business_type'] ?? '')) ?: null,
                    'trade_license_number' => trim((string) ($row['trade_license_number'] ?? '')) ?: null,
                    'decline_reason' => $status === 'failed' ? (trim(Mojibake::repair((string) ($row['notes'] ?? ''))) ?: null) : null,
                    'webhook_payload' => ($row['webhook_data'] ?? null) ?: null,
                    'verified_at' => $this->normalizeDateTime($row['verified_at'] ?? null),
                ]
            );
            $acvSynced++;
        }

        foreach ($this->fetchAll('mod_acv_activity_log', 'id') as $alog) {
            $cid = $this->clientMap[(int) ($alog['client_id'] ?? 0)] ?? null;
            if (! $cid) {
                continue;
            }
            $createdAt = $this->normalizeDateTime($alog['created_at'] ?? null) ?? now()->toDateTimeString();
            DB::table('acv_activity_logs')->updateOrInsert(
                [
                    'client_id' => $cid,
                    'action' => (string) $alog['action'],
                    'created_at' => $createdAt,
                ],
                [
                    'description' => Mojibake::repair((string) ($alog['description'] ?? '')),
                    'ip_address' => ($alog['ip_address'] ?? null) ?: null,
                    'admin_name' => ($alog['admin_name'] ?? null) ?: null,
                    'updated_at' => $createdAt,
                ]
            );
        }

        $this->recordStat('acv_verifications', count($acvRows), $acvSynced);
    }

    private function importPhase5ResellerCenter(): void
    {
        Setting::set('addon_'.ResellerCenterModule::NAME.'_active', '1', 'addons');
        Setting::set('addon_'.ResellerCenterModule::NAME.'_version', '2.0.0', 'addons');

        // Groups
        $rgRows = $this->fetchAll('mod_reseller_groups', 'id');
        foreach ($rgRows as $rg) {
            $name = trim(Mojibake::repair((string) ($rg['name'] ?? '')));
            if ($name === '') {
                continue;
            }
            $perms = json_decode((string) ($rg['permissions'] ?? '{}'), true) ?: [];
            $settings = json_decode((string) ($rg['settings'] ?? '{}'), true) ?: [];
            $group = ResellerGroup::updateOrCreate(
                ['name' => $name],
                [
                    'description' => trim(Mojibake::repair((string) ($rg['description'] ?? ''))) ?: null,
                    'permissions' => $perms,
                    'payout_method' => in_array($rg['payout_method'] ?? '', ['credit_balance', 'paypal', 'manual'], true) ? $rg['payout_method'] : 'credit_balance',
                    'invoicing_method' => in_array($rg['invoicing_method'] ?? '', ['provider', 'reseller'], true) ? $rg['invoicing_method'] : 'provider',
                    'allow_deferred' => (bool) ($rg['allow_deferred'] ?? false),
                    'credit_limit' => (float) ($rg['credit_limit'] ?? 0),
                    'allow_promos' => (bool) ($rg['allow_promos'] ?? true),
                    'allow_own_smtp' => (bool) ($rg['allow_own_smtp'] ?? false),
                    'allow_suspend' => (bool) ($rg['allow_suspend'] ?? true),
                    'allow_global_search' => (bool) ($rg['allow_global_search'] ?? true),
                    'default_commission_percent' => 10.00,
                    'settings' => $settings,
                ]
            );
            $this->resellerGroupMap[(int) $rg['id']] = $group->id;
        }

        // Resellers
        $rRows = $this->fetchAll('mod_resellers', 'id');
        $rSynced = 0;
        foreach ($rRows as $r) {
            $cid = $this->clientMap[(int) ($r['client_id'] ?? 0)] ?? null;
            $gid = $this->resellerGroupMap[(int) ($r['group_id'] ?? 0)] ?? ResellerGroup::value('id');
            if (! $cid || ! $gid) {
                continue;
            }

            $branding = json_decode((string) ($r['branding_settings'] ?? '{}'), true) ?: [];
            $smtp = json_decode((string) ($r['smtp_settings'] ?? '{}'), true) ?: [];

            $reseller = Reseller::updateOrCreate(
                ['client_id' => $cid],
                [
                    'group_id' => $gid,
                    'cname' => trim((string) ($r['cname'] ?? '')) ?: null,
                    'company_name' => trim(Mojibake::repair((string) ($r['company_name'] ?? ''))) ?: null,
                    'email_address' => trim((string) ($r['email_address'] ?? '')) ?: null,
                    'store_template' => trim((string) ($r['store_template'] ?? 'standard_cart')) ?: 'standard_cart',
                    'logo_url' => trim((string) ($r['logo_url'] ?? '')) ?: null,
                    'invoice_logo_url' => trim((string) ($r['invoice_logo_url'] ?? '')) ?: null,
                    'branding_settings' => $branding,
                    'smtp_settings' => $smtp,
                    'balance' => (float) ($r['balance'] ?? 0),
                    'status' => strtolower(trim((string) ($r['status'] ?? 'active'))) === 'active' ? 'active' : 'inactive',
                    'paypal_email' => trim((string) ($r['paypal_email'] ?? '')) ?: null,
                ]
            );
            $this->resellerMap[(int) $r['id']] = $reseller->id;
            $rSynced++;
        }

        // End clients
        foreach ($this->fetchAll('mod_reseller_clients', 'id') as $rc) {
            $rid = $this->resellerMap[(int) ($rc['reseller_id'] ?? 0)] ?? null;
            $cid = $this->clientMap[(int) ($rc['client_id'] ?? 0)] ?? null;
            if (! $rid || ! $cid || Reseller::where('client_id', $cid)->exists()) {
                continue;
            }
            ResellerClient::updateOrCreate(['client_id' => $cid], ['reseller_id' => $rid]);
        }

        // Pricing rules
        foreach ($this->fetchAll('mod_reseller_pricing', 'id') as $rp) {
            $gid = $this->resellerGroupMap[(int) ($rp['group_id'] ?? 0)] ?? null;
            $relId = $this->productMap[(int) ($rp['rel_id'] ?? 0)] ?? null;
            if (! $gid || ! $relId) {
                continue;
            }
            ResellerPricing::updateOrCreate(
                [
                    'group_id' => $gid,
                    'type' => trim((string) ($rp['type'] ?? 'product')) ?: 'product',
                    'rel_id' => $relId,
                    'billing_cycle' => trim((string) ($rp['billing_cycle'] ?? '')) ?: null,
                ],
                [
                    'min_price' => isset($rp['min_price']) ? (float) $rp['min_price'] : null,
                    'max_price' => isset($rp['max_price']) ? (float) $rp['max_price'] : null,
                    'payout_type' => trim((string) ($rp['payout_type'] ?? 'percent')) ?: 'percent',
                    'payout_value' => (float) ($rp['payout_value'] ?? 0),
                ]
            );
        }

        // Promos
        foreach ($this->fetchAll('mod_reseller_promos', 'id') as $pm) {
            $rid = $this->resellerMap[(int) ($pm['reseller_id'] ?? 0)] ?? null;
            $code = strtoupper(trim((string) ($pm['code'] ?? '')));
            if (! $rid || $code === '') {
                continue;
            }
            ResellerPromo::updateOrCreate(
                ['code' => $code],
                [
                    'reseller_id' => $rid,
                    'type' => in_array($pm['type'] ?? '', ['percent', 'fixed'], true) ? $pm['type'] : 'percent',
                    'value' => (float) ($pm['value'] ?? 0),
                    'max_uses' => (int) ($pm['max_uses'] ?? 0),
                    'uses' => (int) ($pm['uses'] ?? 0),
                    'expires_at' => $this->normalizeDateTime($pm['expires_at'] ?? null),
                    'active' => (bool) ($pm['active'] ?? true),
                ]
            );
        }

        // Payouts
        foreach ($this->fetchAll('mod_reseller_payouts', 'id') as $po) {
            $rid = $this->resellerMap[(int) ($po['reseller_id'] ?? 0)] ?? null;
            if (! $rid) {
                continue;
            }
            $cid = $this->clientMap[(int) ($po['client_id'] ?? 0)] ?? null;
            $invId = $this->invoiceMap[(int) ($po['invoice_id'] ?? 0)] ?? null;
            $desc = trim(Mojibake::repair((string) ($po['description'] ?? '')));
            ResellerPayout::updateOrCreate(
                ['reseller_id' => $rid, 'description' => $desc, 'amount' => (float) ($po['amount'] ?? 0)],
                [
                    'invoice_id' => $invId,
                    'client_id' => $cid,
                    'payout_method' => trim((string) ($po['payout_method'] ?? 'credit_balance')) ?: 'credit_balance',
                    'status' => trim((string) ($po['status'] ?? 'pending')) ?: 'pending',
                    'paid_at' => $this->normalizeDateTime($po['paid_at'] ?? null),
                ]
            );
        }

        $this->recordStat('resellers', count($rRows), $rSynced);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProductConfigOptions(array $p): array
    {
        return match ((string) ($p['servertype'] ?? '')) {
            'bhpanel' => [
                'package_name' => trim((string) ($p['configoption1'] ?? '')),
            ],
            'bdpbx' => [
                'ext_count' => trim((string) ($p['configoption1'] ?? '3')) ?: '3',
                'domain_suffix' => trim((string) ($p['configoption2'] ?? 'bdpbx.com')) ?: 'bdpbx.com',
                'default_did' => trim((string) ($p['configoption3'] ?? '')),
                'pbx_api_url' => trim((string) ($p['configoption4'] ?? '')),
                'pbx_api_key' => trim((string) ($p['configoption5'] ?? '')),
            ],
            'softcents_sms_panel' => [
                'account_type' => trim((string) ($p['configoption1'] ?? 'user')) ?: 'user',
                'package_name' => trim((string) ($p['configoption2'] ?? '0')),
                'manual_plan_id' => trim((string) ($p['configoption3'] ?? '')),
                'initial_balance' => trim((string) ($p['configoption4'] ?? '0')) ?: '0',
                'renewal_balance' => trim((string) ($p['configoption5'] ?? '0')) ?: '0',
                'default_sender_id' => trim((string) ($p['configoption6'] ?? '')),
                'existing_mode' => 'reuse_and_credit',
                'allow_api' => '1',
            ],
            'softcents_vsms' => [
                'account_type' => trim((string) ($p['configoption1'] ?? 'user')) ?: 'user',
                'package_name' => trim((string) ($p['configoption2'] ?? '0')),
                'manual_plan_id' => trim((string) ($p['configoption3'] ?? '')),
                'initial_balance' => trim((string) ($p['configoption4'] ?? '0')) ?: '0',
                'renewal_balance' => trim((string) ($p['configoption5'] ?? '0')) ?: '0',
                'default_caller_id' => trim((string) ($p['configoption6'] ?? '')),
                'existing_mode' => 'reuse_and_credit',
                'auto_approve' => '1',
            ],
            default => [],
        };
    }

    private function loadWhmcsTables(): void
    {
        $driver = $this->whmcs->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $rows = $this->whmcs->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $rows = $this->whmcs->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        }

        $this->whmcsTables = array_flip(array_map('strtolower', $rows));
    }

    private function hasTable(string $table): bool
    {
        return isset($this->whmcsTables[strtolower($table)]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAll(string $table, ?string $orderBy = null): array
    {
        if (! $this->hasTable($table)) {
            return [];
        }

        $sql = 'SELECT * FROM '.$table;
        if ($orderBy !== null) {
            $sql .= ' ORDER BY '.$orderBy.' ASC';
        }

        return $this->whmcs->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function readWhmcsConfigJson(string $setting): array
    {
        if (! $this->hasTable('tblconfiguration')) {
            return [];
        }

        $stmt = $this->whmcs->prepare('SELECT value FROM tblconfiguration WHERE setting = ? LIMIT 1');
        $stmt->execute([$setting]);
        $raw = (string) ($stmt->fetchColumn() ?: '');

        return json_decode($raw, true) ?: [];
    }

    private function mapPaymentMethod(?string $method): string
    {
        $clean = strtolower(trim((string) $method));

        return match ($clean) {
            '' => 'piprapay',
            'reseller_gateway' => 'resellergateway',
            default => $clean,
        };
    }

    private function mapBillingCycle(string $cycle): string
    {
        $norm = strtolower(str_replace([' ', '-', '_'], '', trim($cycle)));

        return match ($norm) {
            'freeaccount', 'free' => 'free',
            'onetime' => 'one_time',
            'semiannually' => 'semiannually',
            'annually' => 'annually',
            'biennially' => 'biennially',
            'triennially' => 'triennially',
            'quarterly' => 'quarterly',
            default => 'monthly',
        };
    }

    private function normalizeDate(?string $value, bool $fallbackNow = true): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
            return $fallbackNow ? now()->toDateString() : null;
        }

        return substr($raw, 0, 10);
    }

    private function normalizeDateTime(?string $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
            return null;
        }

        return substr($raw, 0, 19);
    }

    private function whmcsBool(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'on', 'true', 'yes'], true);
    }

    private function recordStat(string $section, int $source, int $synced, int $skipped = 0): void
    {
        $this->stats[$section] = ['source' => $source, 'synced' => $synced, 'skipped' => $skipped];
        if ($this->logger) {
            ($this->logger)("{$section}: synced {$synced}/{$source}".($skipped > 0 ? " (skipped {$skipped})" : ''));
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\AddonSetting;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\Language;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Mojibake;
use Illuminate\Database\Seeder;
use Modules\Addons\AdvancedClientVerification\AdvancedClientVerificationModule;
use Modules\Addons\AdvancedClientVerification\Models\AcvActivityLog;
use Modules\Addons\AdvancedClientVerification\Models\AcvVerification;
use Modules\Addons\CancelInvoices\CancelInvoicesModule;
use Modules\Addons\DriveBackup\DriveBackupModule;
use Modules\Addons\ForceClientActive\ForceClientActiveModule;
use Modules\Addons\InvoiceExport\InvoiceExportModule;
use Modules\Addons\PortalExtras\PortalExtrasModule;
use Modules\Addons\PriceUpdater\PriceUpdaterModule;
use Modules\Addons\ScrollingNotice\Models\ScrollingNotice;
use Modules\Addons\ScrollingNotice\ScrollingNoticeModule;
use Modules\Addons\ServerIpUnblock\ServerIpUnblockModule;
use Modules\Addons\SupportPin\SupportPinModule;
use PDO;

/**
 * Phase 4 - syncs real KYC, storefront, client-group, scrolling notice,
 * auto-cancel invoice, and Google Drive backup configurations/records from
 * the read-only WHMCS database (`billing_db`) into PNLCS.
 *
 * Run with: php artisan db:seed --class=Phase4SoftcentsDataSeeder
 */
class Phase4SoftcentsDataSeeder extends Seeder
{
    private const PHASE4_ADDONS = [
        ScrollingNoticeModule::class => 'scrolling_notice',
        AdvancedClientVerificationModule::NAME => 'advanced_client_verification',
        CancelInvoicesModule::NAME => 'cancel_invoices',
        PortalExtrasModule::NAME => 'portal_extras',
        PriceUpdaterModule::NAME => 'price_updater',
        InvoiceExportModule::NAME => 'invoice_export',
        DriveBackupModule::NAME => 'gdrive_backup',
        SupportPinModule::NAME => 'support_pin',
        ForceClientActiveModule::NAME => 'force_client_active',
        ServerIpUnblockModule::NAME => 'unblock_ip',
    ];

    public function run(): void
    {
        $whmcs = $this->connectBillingDb();

        $this->activateAddons();
        $this->seedBengaliLocale();
        $this->seedClientGroups($whmcs);
        $this->seedProductTrialsAndComparePrices($whmcs);
        $this->seedScrollingNotices($whmcs);
        $this->seedCancelInvoicesConfig($whmcs);
        $this->seedDriveBackupConfig($whmcs);
        $this->seedKycConfigAndRecords($whmcs);

        $this->command?->info('Phase4SoftcentsDataSeeder synced all Phase 4 settings and records from billing_db.');
    }

    private function activateAddons(): void
    {
        foreach (array_values(self::PHASE4_ADDONS) as $addon) {
            Setting::set("addon_{$addon}_active", '1', 'addons');
            Setting::set("addon_{$addon}_version", '1.0.0', 'addons');
        }

        $this->command?->info('Activated 10 Phase 4 addons.');
    }

    private function seedBengaliLocale(): void
    {
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

        $this->command?->info('Bengali (bn / বাংলা) locale registered.');
    }

    private function seedClientGroups(PDO $whmcs): void
    {
        $rows = $whmcs->query(
            'SELECT id, groupname, groupcolour, discountpercent, susptermexempt, separateinvoices FROM tblclientgroups ORDER BY id'
        )->fetchAll();

        foreach ($rows as $row) {
            $name = trim(Mojibake::repair((string) $row['groupname']));
            if ($name === '') {
                continue;
            }

            $exempt = in_array(strtolower(trim((string) $row['susptermexempt'])), ['on', '1', 'true'], true);
            $separate = in_array(strtolower(trim((string) $row['separateinvoices'])), ['on', '1', 'true'], true);
            $color = trim((string) $row['groupcolour']);
            if ($color !== '' && ! str_starts_with($color, '#')) {
                $color = '#'.$color;
            }

            ClientGroup::updateOrCreate(
                ['name' => $name],
                [
                    'color' => preg_match('/^#[0-9A-Fa-f]{3,6}$/', $color) ? $color : '#405189',
                    'discount_percent' => max(0.0, (float) ($row['discountpercent'] ?? 0)),
                    'suspend_exempt' => $exempt,
                    'terminate_exempt' => $exempt,
                    'separate_invoices' => $separate,
                ]
            );
        }

        $this->command?->info('Client groups synced: '.count($rows).'.');
    }

    private function seedProductTrialsAndComparePrices(PDO $whmcs): void
    {
        $trialsJson = (string) $whmcs->query("SELECT value FROM tblconfiguration WHERE setting = 'softcents_product_trials'")->fetchColumn();
        $compareJson = (string) $whmcs->query("SELECT value FROM tblconfiguration WHERE setting = 'softcents_compare_prices'")->fetchColumn();

        $trials = json_decode($trialsJson, true) ?: [];
        $compares = json_decode($compareJson, true) ?: [];

        $updated = 0;
        foreach (array_unique(array_merge(array_keys($trials), array_keys($compares))) as $whmcsPid) {
            $pid = (int) $whmcsPid;
            if ($pid <= 0) {
                continue;
            }

            $product = Product::where('slug', 'like', "sc-prod-{$pid}-%")->first();
            if (! $product) {
                continue;
            }

            $changes = [];
            if (isset($trials[$whmcsPid])) {
                $changes['trial_days'] = max(0, (int) $trials[$whmcsPid]);
            }
            if (isset($compares[$whmcsPid])) {
                $numeric = (float) preg_replace('/[^0-9.]/', '', (string) $compares[$whmcsPid]);
                if ($numeric > 0) {
                    $changes['compare_price'] = round($numeric, 2);
                }
            }

            if ($changes !== []) {
                $product->update($changes);
                $updated++;
            }
        }

        $this->command?->info("Product trial_days / compare_price synced onto {$updated} PNLCS product(s).");
    }

    private function seedScrollingNotices(PDO $whmcs): void
    {
        $rows = $whmcs->query('SELECT * FROM mod_scrolling_notice ORDER BY id')->fetchAll();

        foreach ($rows as $row) {
            $title = Mojibake::repair((string) ($row['title'] ?? ''));
            $content = trim(Mojibake::repair((string) ($row['content'] ?? '')));
            if ($content === '') {
                continue;
            }

            ScrollingNotice::updateOrCreate(
                ['title' => $title ?: null, 'priority' => (int) ($row['priority'] ?? 0)],
                [
                    'content' => $content,
                    'url_target' => trim((string) ($row['url_target'] ?? '')) ?: null,
                    'start_time' => ! empty($row['start_time']) && $row['start_time'] !== '0000-00-00 00:00:00' ? $row['start_time'] : null,
                    'expiry_time' => ! empty($row['expiry_time']) && $row['expiry_time'] !== '0000-00-00 00:00:00' ? $row['expiry_time'] : null,
                    'is_active' => (int) ($row['status'] ?? 1) === 1,
                    'login_status' => in_array($row['login_status'] ?? 'all', ['all', 'logged_in', 'logged_out'], true) ? $row['login_status'] : 'all',
                    'coupon_enabled' => (int) ($row['coupon_enabled'] ?? 0) === 1,
                    'coupon_code' => trim((string) ($row['coupon_code'] ?? '')) ?: null,
                    'scroll_speed' => max(1, min(20, (int) ($row['scroll_speed'] ?? 5))),
                ]
            );
        }

        $this->command?->info('Scrolling notices synced: '.count($rows).'.');
    }

    private function seedCancelInvoicesConfig(PDO $whmcs): void
    {
        $cfg = $whmcs->query('SELECT setting, value FROM mod_cancelinvoicesconfig')->fetchAll(PDO::FETCH_KEY_PAIR);

        $addon = CancelInvoicesModule::NAME;
        AddonSetting::setSetting($addon, 'enable_days', $this->whmcsBool($cfg['EnableDays'] ?? 'on'));
        AddonSetting::setSetting($addon, 'days', (string) max(1, (int) ($cfg['Days'] ?? 7)));
        AddonSetting::setSetting($addon, 'enable_all_invoices', $this->whmcsBool($cfg['EnableDaysAllInvoices'] ?? ''));
        AddonSetting::setSetting($addon, 'all_invoice_days', (string) max(1, (int) ($cfg['AllInvoiceDays'] ?? 7)));
        AddonSetting::setSetting($addon, 'enable_add_funds', $this->whmcsBool($cfg['EnableAddFunds'] ?? 'on'));
        AddonSetting::setSetting($addon, 'add_funds_days', (string) max(1, (int) ($cfg['AddFundsDays'] ?? 7)));

        $this->command?->info('Auto-Cancel Invoices settings synced.');
    }

    private function seedDriveBackupConfig(PDO $whmcs): void
    {
        $cfg = $whmcs->query('SELECT setting, value FROM mod_gdrive_backup_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        $addon = DriveBackupModule::NAME;

        foreach (['client_id', 'client_secret', 'refresh_token', 'drive_folder_id'] as $key) {
            if (! empty($cfg[$key])) {
                AddonSetting::setSetting($addon, $key, trim((string) $cfg[$key]));
            }
        }

        AddonSetting::setSetting($addon, 'max_backups', (string) max(1, (int) ($cfg['max_backups'] ?? 10)));
        AddonSetting::setSetting($addon, 'backup_db', $this->whmcsBool($cfg['backup_db'] ?? 'on'));
        AddonSetting::setSetting($addon, 'backup_files', $this->whmcsBool($cfg['backup_files'] ?? 'on'));
        AddonSetting::setSetting($addon, 'keep_local', $this->whmcsBool($cfg['keep_local'] ?? 'off'));

        $this->command?->info('Google Drive Backup OAuth2 settings synced (encrypted at rest).');
    }

    private function seedKycConfigAndRecords(PDO $whmcs): void
    {
        $cfg = $whmcs->query("SELECT setting, value FROM tbladdonmodules WHERE module = 'advanced_client_verification'")
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        $addon = AdvancedClientVerificationModule::NAME;

        // Keep hybrid mode when manual document rules are active so clients can use both Didit API and manual upload.
        $rawMode = strtolower(trim((string) ($cfg['verification_mode'] ?? 'hybrid')));
        $effectiveMode = in_array($rawMode, ['api_only', 'manual_only', 'hybrid'], true) ? 'hybrid' : 'hybrid';
        AddonSetting::setSetting($addon, 'verification_mode', $effectiveMode);

        foreach (['api_key', 'workflow_id', 'webhook_secret'] as $secretOrId) {
            if (! empty($cfg[$secretOrId])) {
                AddonSetting::setSetting($addon, $secretOrId, trim((string) $cfg[$secretOrId]));
            }
        }

        AddonSetting::setSetting($addon, 'verification_requirement', (string) ($cfg['verification_requirement'] ?: 'disabled'));
        AddonSetting::setSetting($addon, 'restricted_groups', (string) ($cfg['restricted_groups'] ?? ''));
        AddonSetting::setSetting($addon, 'require_trade_license', $this->whmcsBool($cfg['requiretradelicense'] ?? 'on'));
        AddonSetting::setSetting($addon, 'business_types', (string) ($cfg['businesstypes'] ?: 'sole_proprietorship,partnership,limited_company,pvt_ltd'));
        AddonSetting::setSetting($addon, 'max_file_size', (string) max(1, (int) ($cfg['max_file_size'] ?? 5)));
        AddonSetting::setSetting($addon, 'require_selfie', $this->whmcsBool($cfg['require_selfie'] ?? 'on'));
        AddonSetting::setSetting($addon, 'require_document_back', $this->whmcsBool($cfg['require_document_back'] ?? 'on'));
        AddonSetting::setSetting($addon, 'client_access', $this->whmcsBool($cfg['client_access'] ?? 'on'));
        AddonSetting::setSetting($addon, 'auto_approve', $this->whmcsBool($cfg['auto_approve'] ?? 'on'));
        AddonSetting::setSetting($addon, 'enable_service_suspension', $this->whmcsBool($cfg['enable_service_suspension'] ?? 'on'));
        AddonSetting::setSetting($addon, 'verification_deadline_days', (string) max(1, (int) ($cfg['verification_deadline_days'] ?? 7)));
        AddonSetting::setSetting($addon, 'enable_daily_reminders', $this->whmcsBool($cfg['enable_daily_reminders'] ?? 'on'));

        $rows = $whmcs->query(
            'SELECT v.*, LOWER(TRIM(c.email)) AS client_email
             FROM mod_advanced_client_verification v
             INNER JOIN tblclients c ON c.id = v.client_id
             ORDER BY v.id'
        )->fetchAll();

        $matched = 0;
        $clientMap = [];
        foreach ($rows as $row) {
            $email = (string) ($row['client_email'] ?? '');
            if ($email === '') {
                continue;
            }
            $client = Client::whereRaw('LOWER(email) = ?', [$email])->first();
            if (! $client) {
                continue;
            }

            $clientMap[(int) $row['client_id']] = $client->id;
            $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
            if (! in_array($status, ['verified', 'pending', 'failed', 'not_started'], true)) {
                $status = 'pending';
            }

            AcvVerification::updateOrCreate(
                ['client_id' => $client->id],
                [
                    'session_id' => trim((string) ($row['session_id'] ?? '')) ?: null,
                    'verification_url' => trim((string) ($row['verification_url'] ?? '')) ?: null,
                    'status' => $status,
                    'verification_method' => in_array($row['verification_method'] ?? 'api', ['api', 'manual'], true) ? $row['verification_method'] : 'api',
                    'document_type' => trim((string) ($row['document_type'] ?? '')) ?: null,
                    'document_number' => trim((string) ($row['document_number'] ?? '')) ?: null,
                    'business_type' => trim((string) ($row['business_type'] ?? '')) ?: null,
                    'trade_license_number' => trim((string) ($row['trade_license_number'] ?? '')) ?: null,
                    'decline_reason' => $status === 'failed' ? (trim((string) ($row['notes'] ?? '')) ?: null) : null,
                    'webhook_payload' => ($row['webhook_data'] ?? null) ?: null,
                    'verified_at' => ! empty($row['verified_at']) && $row['verified_at'] !== '0000-00-00 00:00:00' ? $row['verified_at'] : null,
                ]
            );
            $matched++;
        }

        if ($clientMap !== []) {
            $inClause = implode(',', array_map('intval', array_keys($clientMap)));
            $logs = $whmcs->query("SELECT * FROM mod_acv_activity_log WHERE client_id IN ({$inClause}) ORDER BY id")->fetchAll();
            foreach ($logs as $log) {
                $pnlcsClientId = $clientMap[(int) $log['client_id']] ?? null;
                if (! $pnlcsClientId) {
                    continue;
                }
                AcvActivityLog::firstOrCreate([
                    'client_id' => $pnlcsClientId,
                    'action' => (string) $log['action'],
                    'created_at' => $log['created_at'],
                ], [
                    'description' => Mojibake::repair((string) ($log['description'] ?? '')),
                    'ip_address' => ($log['ip_address'] ?? null) ?: null,
                    'admin_name' => ($log['admin_name'] ?? null) ?: null,
                ]);
            }
        }

        $this->command?->info("KYC settings synced + {$matched} verification record(s) matched to existing PNLCS clients.");
    }

    private function whmcsBool(mixed $value): string
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'on', 'true', 'yes'], true) ? '1' : '0';
    }

    private function connectBillingDb(): PDO
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
        $pdo->exec('SET SESSION TRANSACTION READ ONLY');

        return $pdo;
    }
}

<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Modules\Addons\ItalkBalance\Models\ItalkBalanceEndpoint;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeAccount;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeProvider;
use Modules\Addons\RechargeRequests\Models\RechargeRequest;
use PDO;

/**
 * Phase 2 - the telecom addons' data, taken from the production WHMCS database.
 *
 * Read-only from billing_db: the balance endpoints the production URL manager
 * holds, the real reseller providers (with their credentials), the client PIN
 * accounts, and the pending/completed recharge requests customers actually
 * raised. Nothing is invented, and nothing is written back to billing_db.
 *
 * Run with: php artisan db:seed --class=SoftcentsTelecomDataSeeder
 */
class SoftcentsTelecomDataSeeder extends Seeder
{
    public function run(): void
    {
        $whmcs = $this->connectBillingDb();

        $this->activateAddons();
        $this->seedEndpoints($whmcs);
        $this->seedProviders($whmcs);
        $this->seedAccounts($whmcs);
        $this->seedRechargeRequests($whmcs);

        $this->command?->info('SoftcentsTelecomDataSeeder synced Phase 2 telecom data from billing_db.');
    }

    private function activateAddons(): void
    {
        foreach (['italk_balance', 'italk_recharge', 'recharge_requests', 'bdpbx_accounting'] as $addon) {
            Setting::set("addon_{$addon}_active", '1', 'addons');
            Setting::set("addon_{$addon}_version", '1.0.0', 'addons');
        }

        $this->command?->info('Activated: italk_balance, italk_recharge, recharge_requests, bdpbx_accounting.');
    }

    private function seedEndpoints(PDO $whmcs): void
    {
        $rows = $whmcs->query('SELECT label, base_url FROM mod_italk_balance_urls ORDER BY id')->fetchAll();

        foreach ($rows as $row) {
            $program = stripos((string) $row['base_url'], 'icc') !== false ? 'icc' : 'italk';

            ItalkBalanceEndpoint::updateOrCreate(
                ['label' => (string) $row['label']],
                ['base_url' => (string) $row['base_url'], 'program' => $program, 'is_active' => true]
            );
        }

        $this->command?->info('Balance endpoints: '.count($rows).'.');
    }

    private function seedProviders(PDO $whmcs): void
    {
        $rows = $whmcs->query(
            'SELECT name, base_url, base_path, integration_type, api_type, username, password, api_key, status
             FROM mod_italk_providers ORDER BY id'
        )->fetchAll();

        foreach ($rows as $row) {
            $provider = ItalkRechargeProvider::firstOrNew(['name' => (string) $row['name']]);

            $provider->fill([
                'base_url' => (string) $row['base_url'],
                'base_path' => (string) ($row['base_path'] ?: '/iconiptsp/'),
                'integration_type' => (string) ($row['integration_type'] ?: 'scraper'),
                'api_type' => (string) ($row['api_type'] ?: 'italk'),
                'username' => (string) $row['username'],
                'status' => (string) ($row['status'] ?: 'active'),
            ]);

            // Credentials are only written when billing_db has one, so an
            // operator's own later change is never overwritten with an empty.
            if ((string) $row['password'] !== '') {
                $provider->password = (string) $row['password'];
            }
            if ((string) ($row['api_key'] ?? '') !== '') {
                $provider->api_key = (string) $row['api_key'];
            }

            $provider->save();
        }

        $this->command?->info('Recharge providers: '.count($rows).'.');
    }

    private function seedAccounts(PDO $whmcs): void
    {
        $rows = $whmcs->query(
            'SELECT r.client_id, r.provider_id, r.pin_number, r.total_recharged, r.last_recharge, r.status,
                    c.email, p.name AS provider_name
             FROM mod_italk_recharge r
             JOIN tblclients c ON c.id = r.client_id
             LEFT JOIN mod_italk_providers p ON p.id = r.provider_id'
        )->fetchAll();

        $seeded = 0;

        foreach ($rows as $row) {
            $client = Client::where('email', (string) $row['email'])->first();
            $provider = $row['provider_name']
                ? ItalkRechargeProvider::where('name', (string) $row['provider_name'])->first()
                : null;

            if (! $client || ! $provider) {
                continue;
            }

            ItalkRechargeAccount::updateOrCreate(
                ['client_id' => $client->id, 'provider_id' => $provider->id],
                [
                    'pin_number' => (string) $row['pin_number'],
                    'total_recharged' => (float) $row['total_recharged'],
                    'last_recharge' => $row['last_recharge'] ?: null,
                    'status' => (string) ($row['status'] ?: 'active'),
                ]
            );
            $seeded++;
        }

        $this->command?->info("Client PIN accounts: {$seeded} matched to PNLCS clients.");
    }

    private function seedRechargeRequests(PDO $whmcs): void
    {
        $rows = $whmcs->query(
            'SELECT client_id, amount, mobile_number, invoice_id, status, requested_at, updated_at
             FROM mod_recharge_requests ORDER BY id'
        )->fetchAll();

        $seeded = 0;

        foreach ($rows as $row) {
            $legacyClient = $whmcs->prepare('SELECT email FROM tblclients WHERE id = ?');
            $legacyClient->execute([(int) $row['client_id']]);
            $email = (string) ($legacyClient->fetchColumn() ?: '');

            $client = $email !== '' ? Client::where('email', $email)->first() : null;

            if (! $client) {
                continue;
            }

            $invoice = $row['invoice_id']
                ? Invoice::where('invoice_num', 'INV-WHMCS-'.(int) $row['invoice_id'])->first()
                : null;

            RechargeRequest::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'mobile_number' => (string) $row['mobile_number'],
                    'requested_at' => $row['requested_at'],
                ],
                [
                    'amount' => (float) $row['amount'],
                    'invoice_id' => $invoice?->id,
                    'status' => in_array($row['status'], RechargeRequest::STATUSES, true) ? $row['status'] : 'Pending',
                ]
            );
            $seeded++;
        }

        $this->command?->info("Recharge requests: {$seeded} matched to PNLCS clients.");
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

<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\Currency;
use App\Models\GatewaySettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Server;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use PDO;

/**
 * Populates pnlcs_db with real data read and decrypted directly from the
 * local WHMCS billing_db (softcentsnet_billing) and WHMCS configuration.
 * Keeps no fake placeholder credentials or mock service data.
 */
class SoftcentsRealDataSeeder extends Seeder
{
    public function run(): void
    {
        $whmcsConfigPath = env('WHMCS_CONFIG_PATH', 'E:/Softcents_New/1111/billing.softcents.com/configuration.php');
        $ccEncryptionHash = $this->readWhmcsEncryptionHash($whmcsConfigPath);
        $whmcs = $this->connectBillingDb();

        // 1. Remove any prior mock/demo rows from initial setup seeder
        $this->purgeFakeSeedData();

        // 2. Company & localization settings from billing_db.tblconfiguration
        $cfg = $whmcs->query(
            "SELECT setting, value FROM tblconfiguration WHERE setting IN ('CompanyName', 'SystemURL', 'LogoURL')"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        Setting::set('CompanyName', $cfg['CompanyName'] ?? 'SoftCents Systems', 'general');
        if (! empty($cfg['SystemURL'])) {
            Setting::set('SystemURL', $cfg['SystemURL'], 'general');
        }
        if (! empty($cfg['LogoURL'])) {
            Setting::set('LogoURL', $cfg['LogoURL'], 'general');
        }
        Setting::set('DefaultLanguage', 'en', 'general');
        Setting::set('DefaultPaymentMethod', 'piprapay', 'general');
        Setting::set('customer_currency_choice', '1', 'billing');

        // 3. Currency from billing_db.tblcurrencies (BDT rate 1.00000)
        $bdtRow = $whmcs->query("SELECT code, prefix, suffix, rate FROM tblcurrencies WHERE code = 'BDT' LIMIT 1")
            ->fetch(PDO::FETCH_ASSOC);

        Currency::query()->update(['is_default' => false]);
        $bdt = Currency::updateOrCreate(
            ['code' => 'BDT'],
            [
                'prefix' => ($bdtRow['prefix'] ?? '') ?: '৳',
                'suffix' => ($bdtRow['suffix'] ?? '') ?: ' BDT',
                'format' => 1,
                'rate' => (float) ($bdtRow['rate'] ?? 1.00000),
                'is_default' => true,
            ]
        );

        // 4. Admins from billing_db.tbladmins
        $role = AdminRole::where('is_full_admin', true)->first()
            ?? AdminRole::create([
                'name' => 'Full Administrator',
                'description' => 'Full access to all areas',
                'is_full_admin' => true,
            ]);

        $adminPassword = (string) env('PNLCS_LOCAL_ADMIN_PASSWORD', 'SoftCents@Admin2026!');
        $adminRows = $whmcs->query(
            'SELECT username, firstname, lastname, email, disabled FROM tbladmins ORDER BY id ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($adminRows as $adm) {
            if ((int) ($adm['disabled'] ?? 0) === 1) {
                continue;
            }
            Admin::updateOrCreate(
                ['username' => trim((string) $adm['username'])],
                [
                    'role_id' => $role->id,
                    'email' => trim((string) $adm['email']),
                    'first_name' => trim((string) $adm['firstname']) ?: 'Admin',
                    'last_name' => trim((string) $adm['lastname']) ?: 'User',
                    'password' => Hash::make($adminPassword),
                    'language' => 'en',
                ]
            );
        }

        // 5. Payment Gateways: preserve admin-entered keys if present; never seed fake placeholder strings
        $pgRows = $whmcs->query(
            "SELECT setting, value FROM tblpaymentgateways WHERE gateway = 'piprapay'"
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        $gatewayDefaults = [
            'piprapay' => [
                'active' => ($pgRows['visible'] ?? '') === 'on' ? '1' : '0',
                'version' => 'v3',
                'display_name' => $pgRows['name'] ?? 'SoftCents System Billing',
                'return_type' => 'Invoices',
                'exchange_rate' => '120',
            ],
            'uddoktapay' => [
                'active' => '0',
                'exchange_rate' => '120',
            ],
            'uddoktapayinternational' => [
                'active' => '0',
                'exchange_rate' => '120',
            ],
            'banktransfer' => [
                'active' => '0',
            ],
        ];

        foreach ($gatewayDefaults as $gw => $settings) {
            foreach ($settings as $key => $val) {
                GatewaySettings::updateOrCreate(
                    ['gateway' => $gw, 'setting' => $key],
                    ['value' => (string) $val]
                );
            }
        }

        // 6. Servers from billing_db.tblservers (types used by Phase 1 modules)
        $serverMap = [];
        $srvStmt = $whmcs->query(
            "SELECT id, name, type, ipaddress, hostname, port, username, password, accesshash, nameserver1, nameserver2, disabled
             FROM tblservers
             WHERE type IN ('bhpanel', 'bdpbx', 'softcents_sms_panel', 'softcents_vsms')
             ORDER BY id ASC"
        );

        foreach ($srvStmt->fetchAll(PDO::FETCH_ASSOC) as $srv) {
            $decryptedPw = $srv['password'] !== ''
                ? $this->decryptWhmcsLegacy((string) $srv['password'], $ccEncryptionHash)
                : '';

            $port = (int) ($srv['port'] ?: match ($srv['type']) {
                'bhpanel' => 2304,
                default => 443,
            });

            $model = Server::updateOrCreate(
                ['name' => trim((string) $srv['name']), 'type' => trim((string) $srv['type'])],
                [
                    'hostname' => trim((string) $srv['hostname']),
                    'ip_address' => trim((string) $srv['ipaddress']),
                    'port' => $port,
                    'username' => trim((string) $srv['username']),
                    'password' => $decryptedPw,
                    'access_hash' => trim((string) $srv['accesshash']),
                    'nameserver1' => trim((string) ($srv['nameserver1'] ?? '')) ?: null,
                    'nameserver2' => trim((string) ($srv['nameserver2'] ?? '')) ?: null,
                    'active' => (int) ($srv['disabled'] ?? 0) === 0,
                    'disabled' => (int) ($srv['disabled'] ?? 0) === 1,
                ]
            );
            $serverMap[(int) $srv['id']] = $model;
        }

        // 7. Product Groups & Products from billing_db.tblproducts (Phase 1 modules)
        $productMap = [];
        $prodStmt = $whmcs->query(
                    'SELECT p.id, p.gid, p.type, p.name, p.description, p.paytype, p.servertype, p.hidden, p.showdomainoptions,
                    p.configoption1, p.configoption2, p.configoption3, p.configoption4, p.configoption5, p.configoption6,
                    pg.name AS group_name, pg.headline AS group_headline, pg.order AS group_order, pg.hidden AS group_hidden,
                    pr.monthly, pr.quarterly, pr.semiannually, pr.annually, pr.biennially, pr.triennially,
                    pr.msetupfee, pr.qsetupfee, pr.ssetupfee, pr.asetupfee, pr.bsetupfee, pr.tsetupfee
             FROM tblproducts p
             JOIN tblproductgroups pg ON pg.id = p.gid
             LEFT JOIN tblpricing pr ON pr.relid = p.id AND pr.type = \'product\' AND pr.currency = 1
             WHERE p.servertype IN (\'bdpbx\', \'bhpanel\', \'softcents_sms_panel\', \'softcents_vsms\')
               AND p.id IN (3, 4, 6, 113, 114, 115, 116, 161, 162, 171, 287, 288, 289, 310, 311)
             ORDER BY p.id ASC'
        );

        foreach ($prodStmt->fetchAll(PDO::FETCH_ASSOC) as $idx => $p) {
            $groupSlug = str('sc-grp-'.$p['gid'].'-'.$p['group_name'])->slug()->value();
            $group = ProductGroup::updateOrCreate(
                ['slug' => $groupSlug],
                [
                    'name' => trim((string) $p['group_name']),
                    'headline' => html_entity_decode(trim((string) ($p['group_headline'] ?? '')), ENT_QUOTES, 'UTF-8'),
                    'sort_order' => (int) ($p['group_order'] ?? ($idx + 1)),
                    'hidden' => (int) ($p['group_hidden'] ?? 0) === 1,
                ]
            );

            $cfgOptions = $this->mapProductConfigOptions($p);
            $prodSlug = str('sc-prod-'.$p['id'].'-'.$p['name'])->slug()->value();
            $pnlcsType = match ((string) $p['type']) {
                'hostingaccount' => 'hosting',
                'reselleraccount' => 'reseller',
                default => 'other',
            };

            $prod = Product::updateOrCreate(
                ['slug' => $prodSlug],
                [
                    'group_id' => $group->id,
                    'name' => trim((string) $p['name']),
                    'description' => trim((string) ($p['description'] ?? '')) ?: trim((string) $p['name']),
                    'type' => $pnlcsType,
                    'pay_type' => ($p['paytype'] ?? 'recurring') === 'onetime' ? 'one_time' : ($p['paytype'] ?? 'recurring'),
                    'auto_setup' => 'payment',
                    'server_type' => (string) $p['servertype'],
                    'show_domain_options' => (bool) ($p['showdomainoptions'] ?? ($p['servertype'] === 'bhpanel')),
                    'is_featured' => in_array((int) $p['id'], [3, 114, 287, 310, 311], true),
                    'allow_upgrades' => true,
                    'hidden' => (int) ($p['hidden'] ?? 0) === 1,
                    'retired' => false,
                    'sort_order' => $idx + 1,
                    'config_options' => $cfgOptions,
                ]
            );

            Pricing::updateOrCreate(
                ['type' => 'product', 'currency_id' => $bdt->id, 'rel_id' => $prod->id],
                [
                    'monthly' => (float) ($p['monthly'] ?? -1),
                    'quarterly' => (float) ($p['quarterly'] ?? -1),
                    'semiannually' => (float) ($p['semiannually'] ?? -1),
                    'annually' => (float) ($p['annually'] ?? -1),
                    'biennially' => (float) ($p['biennially'] ?? -1),
                    'triennially' => (float) ($p['triennially'] ?? -1),
                    'monthly_setup' => (float) ($p['msetupfee'] ?? 0),
                    'quarterly_setup' => (float) ($p['qsetupfee'] ?? 0),
                    'semiannually_setup' => (float) ($p['ssetupfee'] ?? 0),
                    'annually_setup' => (float) ($p['asetupfee'] ?? 0),
                    'biennially_setup' => (float) ($p['bsetupfee'] ?? 0),
                    'triennially_setup' => (float) ($p['tsetupfee'] ?? 0),
                ]
            );

            $productMap[(int) $p['id']] = $prod;
        }

        // 8. Real test Clients, Users & Services (49: BDPBX, 56: BHPanel, 178: VSMS + Bulk SMS + Unpaid Invoices)
        $clientPassword = (string) env('PNLCS_LOCAL_CLIENT_PASSWORD', 'SoftCents@Test2026!');
        $clientMap = [];
        $clientStmt = $whmcs->query(
            'SELECT id, firstname, lastname, companyname, email, phonenumber, address1, city, state, postcode, country, status
             FROM tblclients WHERE id IN (49, 56, 178) ORDER BY id ASC'
        );

        foreach ($clientStmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $email = trim((string) $c['email']);
            $firstName = trim((string) $c['firstname']);
            $lastName = trim((string) $c['lastname']);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make($clientPassword),
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'language' => 'en',
                ]
            );

            $client = Client::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'company_name' => trim((string) ($c['companyname'] ?? '')) ?: null,
                    'phone_number' => preg_replace('/[^0-9+]/', '', (string) ($c['phonenumber'] ?? '')),
                    'address1' => trim((string) ($c['address1'] ?? '')),
                    'city' => trim((string) ($c['city'] ?? '')),
                    'state' => trim((string) ($c['state'] ?? '')),
                    'postcode' => trim((string) ($c['postcode'] ?? '')),
                    'country' => trim((string) ($c['country'] ?? 'BD')) ?: 'BD',
                    'currency_id' => $bdt->id,
                    'status' => strtolower((string) ($c['status'] ?? 'active')),
                ]
            );

            $user->clients()->syncWithoutDetaching([$client->id => ['owner' => true, 'permissions' => null]]);
            $clientMap[(int) $c['id']] = $client;
        }

        $svcStmt = $whmcs->query(
            'SELECT id, userid, packageid, server, regdate, domain, firstpaymentamount, amount, billingcycle,
                    nextduedate, domainstatus, username, password, subscriptionid
             FROM tblhosting
             WHERE id IN (68, 195, 214, 223)
             ORDER BY id ASC'
        );

        foreach ($svcStmt->fetchAll(PDO::FETCH_ASSOC) as $h) {
            $client = $clientMap[(int) $h['userid']] ?? null;
            $product = $productMap[(int) $h['packageid']] ?? null;
            $server = $serverMap[(int) $h['server']] ?? null;
            if (! $client || ! $product || ! $server) {
                continue;
            }

            $decryptedSvcPw = $h['password'] !== ''
                ? $this->decryptWhmcsLegacy((string) $h['password'], $ccEncryptionHash)
                : '';

            $moduleData = match ($product->server_type) {
                'bdpbx' => [
                    'pbx_domain' => trim((string) $h['domain']),
                    'ext_count' => (int) ($product->config_options['ext_count'] ?? 5),
                ],
                'bhpanel' => [
                    'bhpanel_username' => trim((string) $h['username']),
                ],
                'softcents_sms_panel', 'softcents_vsms' => array_filter([
                    'remote_user_id' => (int) ($h['subscriptionid'] ?? 0) ?: null,
                    'portal_host' => trim((string) $h['domain']),
                ]),
                default => [],
            };

            Service::updateOrCreate(
                [
                    'client_id' => $client->id,
                    'product_id' => $product->id,
                    'domain' => trim((string) $h['domain']),
                ],
                [
                    'server_id' => $server->id,
                    'username' => trim((string) $h['username']),
                    'password' => $decryptedSvcPw,
                    'billing_cycle' => strtolower((string) ($h['billingcycle'] ?? 'monthly')),
                    'first_payment_amount' => (float) ($h['firstpaymentamount'] ?? 0),
                    'amount' => (float) ($h['amount'] ?? 0),
                    'registration_date' => $this->normalizeDate($h['regdate'] ?? null),
                    'next_due_date' => $this->normalizeDate($h['nextduedate'] ?? null),
                    'status' => strtolower((string) ($h['domainstatus'] ?? 'active')),
                    'auto_renew' => true,
                    'module_data' => $moduleData,
                ]
            );
        }

        // 9. Real unpaid invoices for VoiceSMS LiveTest (tblinvoices 718, 721, 722)
        if (isset($clientMap[178])) {
            $invStmt = $whmcs->query(
                'SELECT i.id, i.date, i.duedate, i.subtotal, i.total, i.paymentmethod, i.status
                 FROM tblinvoices i
                 WHERE i.id IN (718, 721, 722)
                 ORDER BY i.id ASC'
            );
            $itemStmt = $whmcs->prepare('SELECT type, description, amount FROM tblinvoiceitems WHERE invoiceid = ?');

            foreach ($invStmt->fetchAll(PDO::FETCH_ASSOC) as $inv) {
                $invoice = Invoice::updateOrCreate(
                    ['invoice_num' => 'INV-WHMCS-'.$inv['id']],
                    [
                        'client_id' => $clientMap[178]->id,
                        'date' => $this->normalizeDate($inv['date']),
                        'due_date' => $this->normalizeDate($inv['duedate']),
                        'subtotal' => (float) $inv['subtotal'],
                        'total' => (float) $inv['total'],
                        'status' => strtolower((string) $inv['status']) === 'paid'
                            ? InvoiceStatus::Paid->value
                            : InvoiceStatus::Unpaid->value,
                        'payment_method' => (string) ($inv['paymentmethod'] ?: 'piprapay'),
                    ]
                );

                $itemStmt->execute([(int) $inv['id']]);
                foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $it) {
                    InvoiceItem::updateOrCreate(
                        [
                            'invoice_id' => $invoice->id,
                            'description' => trim((string) $it['description']),
                        ],
                        [
                            'type' => (string) ($it['type'] ?: 'Hosting'),
                            'qty' => 1,
                            'amount' => (float) $it['amount'],
                        ]
                    );
                }
            }
        }

        @file_put_contents(storage_path('installed.lock'), date('c'));

        $this->command?->info('SoftcentsRealDataSeeder synced from billing_db (servers: '.count($serverMap).', products: '.count($productMap).', clients: '.count($clientMap).').');
    }

    private function purgeFakeSeedData(): void
    {
        Service::whereHas('client', fn ($q) => $q->where('email', 'demo@softcents.com'))->delete();
        Client::where('email', 'demo@softcents.com')->delete();
        User::where('email', 'demo@softcents.com')->delete();
        Invoice::whereIn('invoice_num', ['INV-202611-0001', 'INV-202611-718', 'INV-202611-721', 'INV-202611-722'])->delete();

        $fakeSlugs = [
            'bhpanel-starter-hosting',
            'bhpanel-business-hosting',
            'bdpbx-startup-3ext',
            'bdpbx-corporate-10ext',
            'softcents-bulk-sms-starter',
            'softcents-voice-sms-pack',
            // Superseded hand-typed slugs replaced by sc-prod-*
            'standard-pbx',
            'advance-cloud-pbx-basic',
            'advance-pbx-business',
            'advance-2gb-bdix',
            'advance-5gb-bdix',
            'bulk-sms-standard-auto',
            'voice-sms-standard',
        ];
        $fakeProductIds = Product::whereIn('slug', $fakeSlugs)->pluck('id');
        if ($fakeProductIds->isNotEmpty()) {
            Pricing::where('type', 'product')->whereIn('rel_id', $fakeProductIds)->delete();
            Service::whereIn('product_id', $fakeProductIds)->delete();
            Product::whereIn('id', $fakeProductIds)->delete();
        }

        ProductGroup::whereIn('slug', [
            'cloud-web-hosting',
            'bdpbx-cloud-pbx',
            'bulk-sms-solutions',
            'voice-sms-broadcasting',
            'cloud-pbx',
            'advance-cloud-pbx',
            'bdix-hosting',
            'bulk-sms',
            'voice-sms',
        ])->delete();

        Server::where('name', 'BDPBX Cloud Node 01')->delete();

        // Remove any previous fake placeholder gateway keys so PNLCS reports exact status
        GatewaySettings::where('value', 'like', '%REPLACE_WITH_REAL_%')
            ->orWhere('value', 'like', '%demo_piprapay%')
            ->delete();
        GatewaySettings::where('gateway', 'banktransfer')->where('setting', 'instructions')->delete();
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

    private function readWhmcsEncryptionHash(string $path): string
    {
        if (! is_file($path)) {
            throw new \RuntimeException("WHMCS configuration.php not found at {$path}");
        }

        $cc_encryption_hash = '';
        require $path;

        return (string) $cc_encryption_hash;
    }

    private function decryptWhmcsLegacy(string $encodedString, string $ccEncryptionHash): string
    {
        if ($encodedString === '' || $ccEncryptionHash === '') {
            return '';
        }

        $hashFn = static function (string $value): string {
            $hex = sha1($value);
            $out = '';
            for ($c = 0; $c < strlen($hex); $c += 2) {
                $out .= chr(hexdec($hex[$c].$hex[$c + 1]));
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

    private function mapProductConfigOptions(array $p): array
    {
        return match ((string) $p['servertype']) {
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

    private function normalizeDate(?string $date): ?string
    {
        if (! $date || str_starts_with($date, '0000-00-00')) {
            return now()->toDateString();
        }

        return substr($date, 0, 10);
    }
}

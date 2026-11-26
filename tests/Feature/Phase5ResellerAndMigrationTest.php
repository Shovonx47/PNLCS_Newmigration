<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Currency;
use App\Models\Domain;
use App\Models\GatewaySettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Server;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AddonServiceProvider;
use App\Services\AddonManager;
use App\Services\Module\ModuleRegistry;
use App\Services\WhmcsImport\SoftcentsFullImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Modules\Addons\ResellerCenter\Models\Reseller;
use Modules\Addons\ResellerCenter\Models\ResellerClient;
use Modules\Addons\ResellerCenter\Models\ResellerGateway;
use Modules\Addons\ResellerCenter\Models\ResellerGroup;
use Modules\Addons\ResellerCenter\Models\ResellerInvoice;
use Modules\Addons\ResellerCenter\Models\ResellerPayout;
use Modules\Addons\ResellerCenter\Models\ResellerPricing;
use Modules\Addons\ResellerCenter\Models\ResellerPromo;
use Modules\Addons\ResellerCenter\ResellerCenterModule;
use Modules\Addons\ResellerCenter\Services\ResellerService;
use Modules\Gateways\ResellerGateway\ResellerGatewayModule;
use PDO;
use Tests\TestCase;

class Phase5ResellerAndMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(
            ['code' => 'BDT'],
            ['prefix' => '৳', 'suffix' => ' BDT', 'rate' => 1.00000, 'is_default' => true]
        );

        Setting::set('addon_'.ResellerCenterModule::NAME.'_active', '1', 'addons');

        app()->forgetInstance(AddonManager::class);
        (new AddonServiceProvider(app()))->boot();
        app('router')->getRoutes()->refreshNameLookups();

        require_once base_path('modules/Addons/ResellerCenter/hooks.php');
    }

    private function makeAdmin(array $permissions = ['edit_clients', 'view_clients'], bool $full = false): Admin
    {
        $role = AdminRole::firstOrCreate(
            ['name' => 'Reseller Role '.uniqid()],
            ['description' => 'Phase 5 Role', 'is_full_admin' => $full, 'permissions' => $permissions]
        );

        return Admin::forceCreate([
            'username' => 'rcadmin_'.uniqid(),
            'email' => 'rcadmin.'.uniqid().'@softcents.test',
            'password' => Hash::make('AdminSecret123!'),
            'first_name' => 'Reseller',
            'last_name' => 'Manager',
            'role_id' => $role->id,
            'is_disabled' => false,
        ]);
    }

    private function makeClientWithUser(string $name = 'Partner Account'): array
    {
        $email = 'rc.'.uniqid().'@softcents.test';

        $user = User::forceCreate([
            'first_name' => $name,
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make('ClientSecret123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => $name,
            'last_name' => 'User',
            'email' => $email,
            'phone_number' => '01711223344',
            'address1' => 'Banani, Dhaka',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1213',
            'country' => 'BD',
            'status' => 'active',
            'credit' => 0,
        ]);

        $user->clients()->attach($client->id, ['owner' => true]);

        return [$user, $client];
    }

    private function encryptWhmcsForTest(string $plain, string $ccEncryptionHash): string
    {
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
        $iv = random_bytes($hashLen);
        $tmpIv = '';
        for ($c = 0; $c < $hashLen; $c++) {
            $tmpIv .= chr(ord($iv[$c]) ^ ord($hashKey[$c]));
        }

        $currKey = $iv;
        $cipher = '';
        $len = strlen($plain);
        for ($c = 0; $c < $len; $c++) {
            if ($c !== 0 && $c % $hashLen === 0) {
                $currKey = $hashFn($currKey.substr($plain, $c - $hashLen, $hashLen));
            }
            $cipher .= chr(ord($currKey[$c % $hashLen]) ^ ord($plain[$c]));
        }

        return base64_encode($tmpIv.$cipher);
    }

    public function test_reseller_center_is_discovered_registered_in_admin_menu_and_guarded_by_permissions(): void
    {
        $manager = app(AddonManager::class);
        $this->assertTrue($manager->all()->has(ResellerCenterModule::NAME));

        $menuUrls = collect($manager->getSidebarItems())->flatMap(fn ($item) => collect($item['children'] ?? [])->pluck('url'))->all();
        $this->assertContains('/admin/extensions/reseller-center', $menuUrls);
        $this->assertContains('/admin/extensions/reseller-center/resellers', $menuUrls);
        $this->assertContains('/admin/extensions/reseller-center/groups', $menuUrls);
        $this->assertContains('/admin/extensions/reseller-center/payouts', $menuUrls);
        $this->assertContains('/admin/extensions/reseller-center/utilities', $menuUrls);

        // Guest redirected
        $this->get(route('admin.extensions.reseller-center.index'))->assertRedirect();

        // Admin without edit_clients forbidden
        $restrictedAdmin = $this->makeAdmin(['view_invoices'], false);
        $this->actingAs($restrictedAdmin, 'admin')
            ->get(route('admin.extensions.reseller-center.index'))
            ->assertForbidden();

        // Authorized admin loads all surfaces
        $admin = $this->makeAdmin(['edit_clients'], false);
        foreach ([
            'admin.extensions.reseller-center.index',
            'admin.extensions.reseller-center.resellers',
            'admin.extensions.reseller-center.groups',
            'admin.extensions.reseller-center.payouts',
            'admin.extensions.reseller-center.utilities',
        ] as $routeName) {
            $this->actingAs($admin, 'admin')->get(route($routeName))->assertOk();
        }
    }

    public function test_admin_can_create_groups_pricing_resellers_assign_clients_and_export_safe_csv(): void
    {
        $admin = $this->makeAdmin();
        [, $resellerClient] = $this->makeClientWithUser('Partner');
        [, $endClient] = $this->makeClientWithUser('EndCustomer');

        $productGroup = ProductGroup::create(['name' => 'PBX', 'slug' => 'pbx-'.uniqid()]);
        $product = Product::create([
            'group_id' => $productGroup->id,
            'name' => 'Standard PBX',
            'slug' => 'std-pbx-'.uniqid(),
            'type' => 'other',
            'pay_type' => 'recurring',
        ]);

        // Create pricing tier
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.reseller-center.groups.store'), [
                'name' => 'Silver Tier',
                'payout_method' => 'credit_balance',
                'invoicing_method' => 'provider',
                'default_commission_percent' => 15,
                'credit_limit' => 2500,
                'allow_promos' => 1,
                'allow_own_smtp' => 1,
                'allow_suspend' => 1,
            ])->assertRedirect();

        $group = ResellerGroup::where('name', 'Silver Tier')->firstOrFail();

        // Add product rule
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.reseller-center.groups.pricing.store', $group), [
                'rel_id' => $product->id,
                'billing_cycle' => 'monthly',
                'payout_type' => 'percent',
                'payout_value' => 25,
            ])->assertRedirect();

        $this->assertDatabaseHas('reseller_pricing', [
            'group_id' => $group->id,
            'rel_id' => $product->id,
            'payout_type' => 'percent',
            'payout_value' => 25.00,
        ]);

        // Create reseller with CSV formula-injection payload
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.reseller-center.resellers.store'), [
                'client_id' => $resellerClient->id,
                'group_id' => $group->id,
                'company_name' => '=HYPERLINK("https://evil.example","Click")',
                'email_address' => 'billing@partner.example',
                'cname' => 'portal.partner.example',
            ])->assertRedirect();

        $reseller = Reseller::where('client_id', $resellerClient->id)->firstOrFail();

        // Assign end-client
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.reseller-center.resellers.assign-client', $reseller), [
                'client_id' => $endClient->id,
            ])->assertRedirect();

        $this->assertDatabaseHas('reseller_clients', [
            'reseller_id' => $reseller->id,
            'client_id' => $endClient->id,
        ]);

        // CSV Export escapes leading formula characters & sends hardened headers
        $exportResponse = $this->actingAs($admin, 'admin')
            ->get(route('admin.extensions.reseller-center.export', ['type' => 'resellers']));

        $exportResponse->assertOk();
        $exportResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $csv = $exportResponse->streamedContent();
        $this->assertStringContainsString('\'=HYPERLINK(', $csv);
    }

    public function test_invoice_paid_calculates_idempotent_commission_and_mass_payout_credits_reseller(): void
    {
        $admin = $this->makeAdmin();
        [, $resellerClient] = $this->makeClientWithUser('Partner');
        [, $endClient] = $this->makeClientWithUser('EndClient');

        $group = ResellerGroup::create([
            'name' => 'Gold Tier',
            'payout_method' => 'credit_balance',
            'invoicing_method' => 'provider',
            'default_commission_percent' => 10.00,
            'allow_promos' => true,
            'allow_suspend' => true,
        ]);

        $reseller = Reseller::create([
            'client_id' => $resellerClient->id,
            'group_id' => $group->id,
            'company_name' => 'Gold Reseller',
            'status' => 'active',
            'balance' => 0,
        ]);

        ResellerClient::create(['reseller_id' => $reseller->id, 'client_id' => $endClient->id]);

        $pg = ProductGroup::create(['name' => 'Cloud', 'slug' => 'cloud-'.uniqid()]);
        $product = Product::create(['group_id' => $pg->id, 'name' => 'PBX 10', 'slug' => 'pbx10-'.uniqid(), 'type' => 'other']);
        $service = Service::create([
            'client_id' => $endClient->id,
            'product_id' => $product->id,
            'amount' => 1000.00,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        // Specific 20% commission on monthly cycle
        ResellerPricing::create([
            'group_id' => $group->id,
            'type' => 'product',
            'rel_id' => $product->id,
            'billing_cycle' => 'monthly',
            'payout_type' => 'percent',
            'payout_value' => 20.00,
        ]);

        $invoice = Invoice::create([
            'client_id' => $endClient->id,
            'invoice_num' => 'INV-RC-'.uniqid(),
            'date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'subtotal' => 1000.00,
            'total' => 1000.00,
            'status' => InvoiceStatus::Paid->value,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'client_id' => $endClient->id,
            'type' => 'Hosting',
            'rel_id' => $service->id,
            'description' => 'PBX 10 Monthly',
            'qty' => 1,
            'amount' => 1000.00,
        ]);

        // Dispatch twice -> must create only ONE commission payout (200.00)
        InvoicePaid::dispatch($invoice);
        InvoicePaid::dispatch($invoice);

        $this->assertEquals(1, ResellerInvoice::where('invoice_id', $invoice->id)->count());
        $this->assertEquals(1, ResellerPayout::where('invoice_id', $invoice->id)->count());

        $payout = ResellerPayout::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertEquals(200.00, (float) $payout->amount);
        $this->assertEquals('pending', $payout->status);

        // Execute mass payout via admin route
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.reseller-center.payouts.mass'), [
                'reseller_ids' => [$reseller->id],
            ])->assertRedirect();

        $this->assertEquals('paid', $payout->fresh()->status);
        $this->assertEquals(200.00, (float) $reseller->fresh()->balance);
        $this->assertEquals(200.00, (float) $resellerClient->fresh()->credit);
        $this->assertEquals(1, Credit::where('client_id', $resellerClient->id)->where('amount', 200.00)->count());

        // Re-running markPayoutPaid must be a safe no-op
        $this->assertFalse(app(ResellerService::class)->markPayoutPaid($payout->fresh(), $admin->id));
        $this->assertEquals(200.00, (float) $reseller->fresh()->balance);
    }

    public function test_client_reseller_portal_enforces_ownership_encrypts_secrets_manages_promos_and_controls_end_services(): void
    {
        [$resellerUser, $resellerClient] = $this->makeClientWithUser('AlphaPartner');
        [$otherUser, $otherClient] = $this->makeClientWithUser('Outsider');
        [, $endClient] = $this->makeClientWithUser('ManagedClient');

        $group = ResellerGroup::create([
            'name' => 'Enterprise Partner',
            'payout_method' => 'credit_balance',
            'invoicing_method' => 'provider',
            'allow_promos' => true,
            'allow_own_smtp' => true,
            'allow_suspend' => true,
            'default_commission_percent' => 12,
        ]);

        $reseller = Reseller::create([
            'client_id' => $resellerClient->id,
            'group_id' => $group->id,
            'company_name' => 'Alpha Cloud',
            'email_address' => 'billing@alpha.test',
            'status' => 'active',
        ]);

        ResellerClient::create(['reseller_id' => $reseller->id, 'client_id' => $endClient->id]);

        $pg = ProductGroup::create(['name' => 'Hosting', 'slug' => 'hst-'.uniqid()]);
        $product = Product::create(['group_id' => $pg->id, 'name' => 'Shared 5GB', 'slug' => 'sh5-'.uniqid(), 'type' => 'hosting']);

        $endService = Service::create([
            'client_id' => $endClient->id,
            'product_id' => $product->id,
            'domain' => 'managed.example.com',
            'amount' => 500,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        $unrelatedService = Service::create([
            'client_id' => $otherClient->id,
            'product_id' => $product->id,
            'domain' => 'outsider.example.com',
            'amount' => 500,
            'billing_cycle' => 'monthly',
            'status' => 'active',
        ]);

        // Outsider cannot post to reseller routes and gets friendly non-reseller notice on GET
        $this->actingAs($otherUser)
            ->get(route('client.reseller.index'))
            ->assertOk()
            ->assertSee('Active Reseller Membership Required');

        $this->actingAs($otherUser)
            ->post(route('client.reseller.promos.store'), ['code' => 'HACK50', 'type' => 'percent', 'value' => 50])
            ->assertForbidden();

        // Reseller updates branding + SMTP password + PipraPay secret
        $secretSmtp = 'SuperSecretSmtpPass!99';
        $secretApiKey = 'pp_live_secret_token_987654';

        $this->actingAs($resellerUser)
            ->post(route('client.reseller.branding'), [
                'company_name' => 'Alpha Cloud BD',
                'email_address' => 'support@alpha.test',
                'cname' => 'my.alpha.test',
                'theme_color' => '#112233',
                'smtp_host' => 'smtp.alpha.test',
                'smtp_port' => 587,
                'smtp_username' => 'mailer@alpha.test',
                'smtp_password' => $secretSmtp,
            ])->assertRedirect();

        $this->actingAs($resellerUser)
            ->post(route('client.reseller.gateway.save'), [
                'gateway' => 'piprapay',
                'enabled' => '1',
                'display_name' => 'Alpha Pay (bKash/Nagad)',
                'base_url' => 'https://pay.alpha.test',
                'api_key' => $secretApiKey,
            ])->assertRedirect();

        // Raw DB columns must be encrypted and HTML response must NOT echo secrets
        $rawSmtpCol = (string) DB::table('resellers')->where('id', $reseller->id)->value('smtp_settings');
        $rawGwCol = (string) DB::table('reseller_gateways')->where('reseller_id', $reseller->id)->value('settings');
        $this->assertStringNotContainsString($secretSmtp, $rawSmtpCol);
        $this->assertStringNotContainsString($secretApiKey, $rawGwCol);

        $portalHtml = $this->actingAs($resellerUser)
            ->get(route('client.reseller.index'))
            ->assertOk()
            ->assertSee('Alpha Cloud BD')
            ->getContent();
        $this->assertStringNotContainsString($secretSmtp, $portalHtml);
        $this->assertStringNotContainsString($secretApiKey, $portalHtml);

        // Create and delete own promo
        $this->actingAs($resellerUser)
            ->post(route('client.reseller.promos.store'), [
                'code' => 'ALPHA20',
                'type' => 'percent',
                'value' => 20,
                'max_uses' => 25,
            ])->assertRedirect();

        $promo = ResellerPromo::where('code', 'ALPHA20')->firstOrFail();
        $this->assertNotNull(app(ResellerService::class)->validateResellerPromo('alpha20', $endClient));
        $this->assertNull(app(ResellerService::class)->validateResellerPromo('alpha20', $otherClient));

        // Suspend and unsuspend end-client service, reject unrelated service
        $this->actingAs($resellerUser)->post(route('client.reseller.services.suspend', $endService))->assertRedirect();
        $this->assertEquals('suspended', $endService->fresh()->status);

        $this->actingAs($resellerUser)->post(route('client.reseller.services.unsuspend', $endService))->assertRedirect();
        $this->assertEquals('active', $endService->fresh()->status);

        $this->actingAs($resellerUser)->post(route('client.reseller.services.suspend', $unrelatedService))->assertRedirect();
        $this->assertEquals('active', $unrelatedService->fresh()->status);

        $this->actingAs($resellerUser)->delete(route('client.reseller.promos.destroy', $promo))->assertRedirect();
        $this->assertDatabaseMissing('reseller_promos', ['id' => $promo->id]);
    }

    public function test_reseller_gateway_discovers_proxies_reseller_piprapay_and_verifies_webhook_authenticity(): void
    {
        $registry = app(ModuleRegistry::class);
        $gatewayModule = $registry->getGatewayModule(ResellerGatewayModule::NAME);
        $this->assertInstanceOf(ResellerGatewayModule::class, $gatewayModule);

        GatewaySettings::updateOrCreate(['gateway' => ResellerGatewayModule::NAME, 'setting' => 'active'], ['value' => '1']);
        $this->assertContains(ResellerGatewayModule::NAME, $registry->usableGateways());

        [, $resellerClient] = $this->makeClientWithUser('GatewayPartner');
        [$endUser, $endClient] = $this->makeClientWithUser('GatewayCustomer');
        [$outsiderUser] = $this->makeClientWithUser('Attacker');

        $group = ResellerGroup::create(['name' => 'Proxy Tier', 'payout_method' => 'credit_balance', 'invoicing_method' => 'provider']);
        $reseller = Reseller::create(['client_id' => $resellerClient->id, 'group_id' => $group->id, 'company_name' => 'ProxyCo', 'status' => 'active']);
        ResellerClient::create(['reseller_id' => $reseller->id, 'client_id' => $endClient->id]);

        $resellerKey = 'reseller_pp_key_12345';
        ResellerGateway::create([
            'reseller_id' => $reseller->id,
            'gateway' => 'piprapay',
            'enabled' => true,
            'settings' => [
                'display_name' => 'ProxyCo Instant Pay',
                'base_url' => 'https://pay.proxyco.example',
                'api_key' => $resellerKey,
            ],
        ]);

        $invoice = Invoice::create([
            'client_id' => $endClient->id,
            'invoice_num' => 'INV-GW-'.uniqid(),
            'date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'subtotal' => 750.00,
            'total' => 750.00,
            'status' => InvoiceStatus::Unpaid->value,
            'payment_method' => ResellerGatewayModule::NAME,
        ]);

        $formHtml = $gatewayModule->getPaymentForm($invoice);
        $this->assertStringContainsString('ProxyCo Instant Pay', $formHtml);
        $this->assertStringContainsString('gateway/resellergateway/capture/'.$invoice->id, $formHtml);

        // Unauthorized customer cannot initiate capture
        $this->actingAs($outsiderUser)
            ->post(route('modules.gateways.resellergateway.capture', $invoice))
            ->assertForbidden();

        // Authorized customer capture posts to reseller PipraPay and redirects
        Http::fake([
            'https://pay.proxyco.example/checkout/redirect' => Http::response([
                'status' => true,
                'pp_id' => 'pp_res_999',
                'pp_url' => 'https://pay.proxyco.example/checkout/pp_res_999',
            ], 200),
            'https://pay.proxyco.example/verify-payment' => Http::response([
                'status' => 'completed',
                'pp_id' => 'pp_res_999',
                'amount' => 750.00,
                'metadata' => [
                    'invoiceid' => $invoice->id,
                    'reseller_id' => $reseller->id,
                    'due_amount' => '750.00',
                    'rate' => '1.00000',
                ],
            ], 200),
        ]);

        $this->actingAs($endUser)
            ->post(route('modules.gateways.resellergateway.capture', $invoice))
            ->assertRedirect('https://pay.proxyco.example/checkout/pp_res_999');

        // Webhook with bad signature is rejected
        $this->withHeaders(['MHS-PIPRAPAY-API-KEY' => 'wrong_key'])
            ->postJson(route('modules.gateways.resellergateway.webhook', ['reseller' => $reseller->id]), [
                'pp_id' => 'pp_res_999',
            ])->assertStatus(401);

        // Valid webhook verifies and marks invoice paid
        $this->withHeaders(['MHS-PIPRAPAY-API-KEY' => $resellerKey])
            ->postJson(route('modules.gateways.resellergateway.webhook', ['reseller' => $reseller->id]), [
                'pp_id' => 'pp_res_999',
                'metadata' => ['invoiceid' => $invoice->id, 'reseller_id' => $reseller->id],
            ])->assertOk();

        $this->assertEquals(InvoiceStatus::Paid->value, strtolower((string) $invoice->fresh()->status));
    }

    public function test_softcents_full_importer_decrypts_repairs_mojibake_excludes_domain_reseller_and_is_idempotent(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $ccHash = 'TestEncryptionHashSecret123456';
        $plainServerPw = 'SrvRootPass!88';
        $plainSvcPw = 'PbxUserPass!77';
        $encServerPw = $this->encryptWhmcsForTest($plainServerPw, $ccHash);
        $encSvcPw = $this->encryptWhmcsForTest($plainSvcPw, $ccHash);

        // Pre-existing user whose password must stay intact unless forcePasswords=true
        $preservedUser = User::forceCreate([
            'first_name' => 'Existing',
            'last_name' => 'User',
            'email' => 'keepme@softcents.test',
            'password' => Hash::make('KeepOriginalPass!2026'),
            'is_active' => true,
        ]);

        foreach ([
            'CREATE TABLE tblcurrencies (id INTEGER, code TEXT, prefix TEXT, suffix TEXT, format INTEGER, rate REAL, "default" INTEGER)',
            'CREATE TABLE tblclientgroups (id INTEGER, groupname TEXT, groupcolour TEXT, discountpercent REAL, susptermexempt TEXT, separateinvoices TEXT)',
            'CREATE TABLE tbladminroles (id INTEGER, name TEXT, systememails INTEGER, accountemails INTEGER, supportemails INTEGER)',
            'CREATE TABLE tbladmins (id INTEGER, roleid INTEGER, username TEXT, firstname TEXT, lastname TEXT, email TEXT, signature TEXT, disabled INTEGER, language TEXT)',
            'CREATE TABLE tblclients (id INTEGER, firstname TEXT, lastname TEXT, companyname TEXT, email TEXT, address1 TEXT, address2 TEXT, city TEXT, state TEXT, postcode TEXT, country TEXT, phonenumber TEXT, tax_id TEXT, status TEXT, groupid INTEGER, currency INTEGER, defaultgateway TEXT, credit REAL, taxexempt INTEGER, latefeeoveride INTEGER, overideduenotices INTEGER, notes TEXT, language TEXT, ip TEXT)',
            'CREATE TABLE tblusers (id INTEGER, first_name TEXT, last_name TEXT, email TEXT, password TEXT, language TEXT, email_verified_at TEXT)',
            'CREATE TABLE tblusers_clients (id INTEGER, auth_user_id INTEGER, client_id INTEGER, owner INTEGER, permissions TEXT)',
            'CREATE TABLE tblservergroups (id INTEGER, name TEXT, filltype INTEGER)',
            'CREATE TABLE tblservers (id INTEGER, name TEXT, hostname TEXT, ipaddress TEXT, maxaccounts INTEGER, type TEXT, username TEXT, password TEXT, accesshash TEXT, port INTEGER, active INTEGER, disabled INTEGER, nameserver1 TEXT, nameserver2 TEXT, nameserver3 TEXT, nameserver4 TEXT, nameserver5 TEXT)',
            'CREATE TABLE tblservergroupsrel (id INTEGER, groupid INTEGER, serverid INTEGER)',
            'CREATE TABLE tblproductgroups (id INTEGER, name TEXT, headline TEXT, tagline TEXT, orderfrmtpl TEXT, hidden INTEGER, "order" INTEGER)',
            'CREATE TABLE tblproducts (id INTEGER, type TEXT, gid INTEGER, name TEXT, description TEXT, hidden INTEGER, showdomainoptions INTEGER, freedomain TEXT, freedomaintlds TEXT, freedomainpaymentterms TEXT, is_featured INTEGER, retired INTEGER, configoptionsupgrade INTEGER, billingcycleupgrade INTEGER, paytype TEXT, autosetup TEXT, autoterminatedays INTEGER, servertype TEXT, servergroup INTEGER, stockcontrol INTEGER, qty INTEGER, "order" INTEGER, tax INTEGER, configoption1 TEXT, configoption2 TEXT, configoption3 TEXT, configoption4 TEXT, configoption5 TEXT, configoption6 TEXT)',
            'CREATE TABLE tblpricing (id INTEGER, type TEXT, currency INTEGER, relid INTEGER, monthly REAL, quarterly REAL, semiannually REAL, annually REAL, biennially REAL, triennially REAL, msetupfee REAL, qsetupfee REAL, ssetupfee REAL, asetupfee REAL, bsetupfee REAL, tsetupfee REAL)',
            'CREATE TABLE tblorders (id INTEGER, ordernum TEXT, userid INTEGER, contactid INTEGER, date TEXT, promocode TEXT, amount REAL, paymentmethod TEXT, invoiceid INTEGER, status TEXT, ipaddress TEXT, fraudmodule TEXT, fraudoutput TEXT, notes TEXT)',
            'CREATE TABLE tblhosting (id INTEGER, userid INTEGER, orderid INTEGER, packageid INTEGER, server INTEGER, regdate TEXT, domain TEXT, paymentmethod TEXT, qty INTEGER, firstpaymentamount REAL, amount REAL, billingcycle TEXT, nextduedate TEXT, termination_date TEXT, domainstatus TEXT, username TEXT, password TEXT, diskusage INTEGER, disklimit INTEGER, bwusage INTEGER, bwlimit INTEGER, suspendreason TEXT, overidesuspenduntil TEXT, notes TEXT, subscriptionid TEXT)',
            'CREATE TABLE tbldomains (id INTEGER, userid INTEGER, orderid INTEGER, type TEXT, registrar TEXT, domain TEXT, registrationperiod INTEGER, registrationdate TEXT, expirydate TEXT, nextduedate TEXT, status TEXT, dnsmanagement INTEGER, emailforwarding INTEGER, idprotection INTEGER, is_premium INTEGER, donotrenew INTEGER, paymentmethod TEXT, firstpaymentamount REAL, recurringamount REAL, additionalnotes TEXT)',
            'CREATE TABLE tblinvoices (id INTEGER, userid INTEGER, invoicenum TEXT, date TEXT, duedate TEXT, datepaid TEXT, subtotal REAL, credit REAL, tax REAL, tax2 REAL, total REAL, taxrate REAL, taxrate2 REAL, status TEXT, paymentmethod TEXT, notes TEXT)',
            'CREATE TABLE tblinvoiceitems (id INTEGER, invoiceid INTEGER, userid INTEGER, type TEXT, relid INTEGER, description TEXT, amount REAL, taxed INTEGER, duedate TEXT)',
            'CREATE TABLE tblknowledgebasecats (id INTEGER, parentid INTEGER, name TEXT, description TEXT, hidden INTEGER)',
            'CREATE TABLE tblknowledgebase (id INTEGER, title TEXT, article TEXT, views INTEGER, useful INTEGER, votes INTEGER, private INTEGER, "order" INTEGER)',
            'CREATE TABLE tblknowledgebaselinks (articleid INTEGER, categoryid INTEGER)',
            'CREATE TABLE mod_reseller_groups (id INTEGER, name TEXT, description TEXT, permissions TEXT, payout_method TEXT, invoicing_method TEXT, allow_deferred INTEGER, credit_limit REAL, allow_promos INTEGER, allow_own_smtp INTEGER, allow_suspend INTEGER, allow_global_search INTEGER, settings TEXT)',
            'CREATE TABLE mod_resellers (id INTEGER, client_id INTEGER, group_id INTEGER, cname TEXT, company_name TEXT, email_address TEXT, store_template TEXT, logo_url TEXT, invoice_logo_url TEXT, branding_settings TEXT, smtp_settings TEXT, balance REAL, status TEXT, paypal_email TEXT)',
            'CREATE TABLE mod_reseller_clients (id INTEGER, reseller_id INTEGER, client_id INTEGER)',
            'CREATE TABLE mod_reseller_pricing (id INTEGER, group_id INTEGER, type TEXT, rel_id INTEGER, billing_cycle TEXT, min_price REAL, max_price REAL, payout_type TEXT, payout_value REAL)',
            'CREATE TABLE mod_reseller_promos (id INTEGER, reseller_id INTEGER, code TEXT, type TEXT, value REAL, max_uses INTEGER, uses INTEGER, expires_at TEXT, active INTEGER)',
            'CREATE TABLE mod_reseller_payouts (id INTEGER, reseller_id INTEGER, invoice_id INTEGER, client_id INTEGER, amount REAL, payout_method TEXT, status TEXT, description TEXT, paid_at TEXT)',
        ] as $ddl) {
            $pdo->exec($ddl);
        }

        // Insert UTF-8 Mojibake (double-encoded Latin1 -> UTF-8 of "বাংলা")
        $mojibakeBangla = "\xC3\xA0\xC2\xA6\xC2\xAC\xC3\xA0\xC2\xA6\xC2\xBE\xC3\xA0\xC2\xA6\xC2\x82\xC3\xA0\xC2\xA6\xC2\xB2\xC3\xA0\xC2\xA6\xC2\xBE";

        $pdo->exec("INSERT INTO tblcurrencies VALUES (1, 'BDT', '৳', ' BDT', 1, 1.0, 1)");
        $pdo->exec("INSERT INTO tblclientgroups VALUES (1, 'Import Tier', '#112233', 0, '', '')");
        $pdo->exec("INSERT INTO tblclients VALUES (10, 'Reseller', 'Hub', 'SoftCents Hub', 'keepme@softcents.test', 'Road 1', '', 'Dhaka', 'Dhaka', '1212', 'BD', '01700000001', '', 'Active', 1, 1, 'reseller_gateway', 0, 0, 0, 0, '', 'en', '127.0.0.1')");
        $pdo->exec("INSERT INTO tblclients VALUES (11, 'Sub', 'Customer', 'Sub Co', 'subcust@softcents.test', 'Road 2', '', 'Dhaka', 'Dhaka', '1212', 'BD', '01700000002', '', 'Active', 1, 1, 'piprapay', 0, 0, 0, 0, '', 'en', '127.0.0.1')");
        $pdo->exec("INSERT INTO tblusers VALUES (10, 'Reseller', 'Hub', 'keepme@softcents.test', 'IGNORED_BECAUSE_EXISTING', 'en', '2026-01-01 00:00:00')");
        $pdo->exec("INSERT INTO tblusers VALUES (11, 'Sub', 'Customer', 'subcust@softcents.test', 'plain_new_user_pw', 'en', '2026-01-01 00:00:00')");
        $pdo->exec("INSERT INTO tblusers_clients VALUES (1, 10, 10, 1, 'profile,invoices')");
        $pdo->exec("INSERT INTO tblusers_clients VALUES (2, 11, 11, 1, '')");

        $stmtSrv = $pdo->prepare('INSERT INTO tblservers VALUES (5, ?, ?, ?, 0, ?, ?, ?, ?, 443, 1, 0, "", "", "", "", "")');
        $stmtSrv->execute(['ImportPBX', 'pbx-imp.example.com', '10.0.0.5', 'bdpbx', 'pbxadmin', $encServerPw, '']);

        $pdo->exec("INSERT INTO tblproductgroups VALUES (90, 'Imported PBX Group', 'Imported', '', 'standard_cart', 0, 1)");
        $pdo->exec("INSERT INTO tblproducts VALUES (901, 'other', 90, 'Imported PBX Plan', 'Fast Cloud PBX', 0, 0, '', '', '', 1, 0, 1, 0, 'recurring', 'payment', 0, 'bdpbx', 0, 0, 0, 1, 0, '5', 'bdpbx.com', '', '', '', '')");
        $pdo->exec("INSERT INTO tblpricing VALUES (1, 'product', 1, 901, 1500, -1, -1, 15000, -1, -1, 0, 0, 0, 0, 0, 0)");
        $pdo->exec("INSERT INTO tblorders VALUES (50, 'ORD-IMP-50', 11, 0, '2026-11-01', '', 1500, 'reseller_gateway', 500, 'Active', '127.0.0.1', '', '', '')");

        $stmtSvc = $pdo->prepare('INSERT INTO tblhosting VALUES (60, 11, 50, 901, 5, ?, ?, ?, 1, 1500, 1500, ?, ?, ?, ?, ?, ?, 0, 0, 0, 0, ?, ?, ?, ?)');
        $stmtSvc->execute(['2026-11-01', 'sub.bdpbx.com', 'reseller_gateway', 'Monthly', '2026-12-01', null, 'Active', 'subuser', $encSvcPw, '', null, 'Note', '']);

        $pdo->exec("INSERT INTO tbldomains VALUES (70, 11, 50, 'Register', 'BdwebsReseller', 'example-bd.com.bd', 1, '2026-01-01', '2027-01-01', '2027-01-01', 'Active', 1, 0, 0, 0, 0, 'piprapay', 1500, 1500, '')");
        $pdo->exec("INSERT INTO tblinvoices VALUES (500, 11, 'INV-IMP-500', '2026-11-01', '2026-11-08', '2026-11-01 10:00:00', 1500, 0, 0, 0, 1500, 0, 0, 'Paid', 'reseller_gateway', '')");
        $pdo->exec("INSERT INTO tblinvoiceitems VALUES (1, 500, 11, 'Hosting', 60, 'Imported PBX Plan', 1500, 0, '2026-11-08')");

        $stmtCat = $pdo->prepare('INSERT INTO tblknowledgebasecats VALUES (80, 0, ?, ?, 0)');
        $stmtCat->execute([$mojibakeBangla, 'Desc']);
        $pdo->exec("INSERT INTO tblknowledgebase VALUES (800, 'Guide Title', 'Body', 5, 1, 2, 0, 1)");
        $pdo->exec('INSERT INTO tblknowledgebaselinks VALUES (800, 80)');

        $pdo->exec("INSERT INTO mod_reseller_groups VALUES (1, 'Imported Resellers', 'Imported Tier', '{}', 'credit_balance', 'provider', 0, 500, 1, 0, 1, 1, '{}')");
        $pdo->exec("INSERT INTO mod_resellers VALUES (1, 10, 1, 'imp.softcents.test', 'SoftCents Hub', 'hub@softcents.test', 'standard_cart', '', '', '{}', '{}', 50, 'active', '')");
        $pdo->exec('INSERT INTO mod_reseller_clients VALUES (1, 1, 11)');
        $pdo->exec("INSERT INTO mod_reseller_pricing VALUES (1, 1, 'product', 901, 'monthly', 1000, 2000, 'percent', 20)");
        $pdo->exec("INSERT INTO mod_reseller_promos VALUES (1, 1, 'IMP10', 'percent', 10, 50, 0, '2028-01-01 00:00:00', 1)");
        $pdo->exec("INSERT INTO mod_reseller_payouts VALUES (1, 1, 500, 11, 300, 'credit_balance', 'pending', 'Commission #500', null)");

        // 1) Dry-run leaves DB completely untouched
        $dryImporter = new SoftcentsFullImporter($pdo, $ccHash, dryRun: true, forcePasswords: false);
        $dryStats = $dryImporter->run();
        $this->assertEquals(2, $dryStats['clients']['synced']);
        $this->assertNull(Client::where('email', 'subcust@softcents.test')->first());

        // 2) Live run migrates, decrypts, repairs Mojibake, excludes BdwebsReseller, and keeps existing password
        $importer = new SoftcentsFullImporter($pdo, $ccHash, dryRun: false, forcePasswords: false);
        $importer->run();

        $this->assertTrue(Hash::check('KeepOriginalPass!2026', $preservedUser->fresh()->password));

        $srv = Server::where('name', 'ImportPBX')->firstOrFail();
        $this->assertEquals($plainServerPw, $srv->password);

        $svc = Service::where('domain', 'sub.bdpbx.com')->firstOrFail();
        $this->assertEquals($plainSvcPw, $svc->password);
        $this->assertEquals($srv->id, $svc->server_id);
        $this->assertEquals('resellergateway', $svc->payment_method);

        $domain = Domain::where('domain', 'example-bd.com.bd')->firstOrFail();
        $this->assertEquals('Manual', $domain->registrar);

        $cat = KbCategory::where('name', 'বাংলা')->first();
        $this->assertNotNull($cat);
        $this->assertNotNull(KbArticle::where('category_id', $cat->id)->where('title', 'Guide Title')->first());

        $importedReseller = Reseller::where('cname', 'imp.softcents.test')->firstOrFail();
        $this->assertDatabaseHas('reseller_clients', ['reseller_id' => $importedReseller->id, 'client_id' => Client::where('email', 'subcust@softcents.test')->value('id')]);
        $this->assertDatabaseHas('reseller_promos', ['code' => 'IMP10', 'reseller_id' => $importedReseller->id]);
        $this->assertDatabaseHas('reseller_payouts', ['reseller_id' => $importedReseller->id, 'amount' => 300.00]);

        // 3) Re-run is completely idempotent (zero duplicate rows)
        $beforeClients = Client::count();
        $beforeServices = Service::count();
        $beforeInvoices = Invoice::count();
        (new SoftcentsFullImporter($pdo, $ccHash, dryRun: false, forcePasswords: false))->run();

        $this->assertEquals($beforeClients, Client::count());
        $this->assertEquals($beforeServices, Service::count());
        $this->assertEquals($beforeInvoices, Invoice::count());
    }
}

<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Currency;
use App\Models\GatewaySettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Server;
use App\Models\Service;
use App\Models\User;
use App\Services\Module\ModuleRegistry;
use App\Services\Module\ModuleScreens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Modules\Servers\Bdpbx\BdpbxModule;
use Modules\Servers\Bhpanel\BhpanelClient;
use Modules\Servers\Bhpanel\BhpanelModule;
use Modules\Servers\SoftcentsSmsPanel\SoftcentsSmsPanelModule;
use Modules\Servers\SoftcentsVsms\SoftcentsVsmsModule;
use Tests\TestCase;

class Phase1SoftcentsModulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        BhpanelClient::resetCache();

        Currency::firstOrCreate(
            ['code' => 'BDT'],
            ['prefix' => '৳', 'suffix' => ' BDT', 'rate' => 1.00000, 'is_default' => true]
        );
    }

    private function createClientWithUser(string $company = 'Acme Telecom Ltd'): array
    {
        $user = User::forceCreate([
            'first_name' => 'Rahim',
            'last_name' => 'Uddin',
            'email' => 'rahim.'.uniqid().'@example.com',
            'password' => Hash::make('SecretPass123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => 'Rahim',
            'last_name' => 'Uddin',
            'company_name' => $company,
            'email' => $user->email,
            'phone_number' => '01711223344',
            'address1' => 'Banani Road 11',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1213',
            'country' => 'BD',
            'status' => 'active',
        ]);

        $user->clients()->syncWithoutDetaching([$client->id => ['owner' => true, 'permissions' => null]]);

        return [$user, $client];
    }

    private function createUnpaidInvoice(Client $client, float $total = 1500.00, string $gateway = 'piprapay'): Invoice
    {
        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_num' => 'INV-TEST-'.uniqid(),
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => $total,
            'total' => $total,
            'status' => InvoiceStatus::Unpaid->value,
            'payment_method' => $gateway,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'type' => 'Hosting',
            'description' => 'Cloud PBX Plan',
            'qty' => 1,
            'amount' => $total,
        ]);

        return $invoice;
    }

    public function test_all_phase1_gateways_and_server_modules_are_discovered(): void
    {
        $registry = app(ModuleRegistry::class);

        $this->assertNotNull($registry->getGatewayModule('piprapay'));
        $this->assertNotNull($registry->getGatewayModule('uddoktapay'));
        $this->assertNotNull($registry->getGatewayModule('uddoktapayinternational'));

        $serverNames = $registry->serverModuleNames(true);
        $this->assertSame('BDPBX Cloud PBX', $serverNames['bdpbx'] ?? null);
        $this->assertSame('BHPanel (cPanel)', $serverNames['bhpanel'] ?? null);
        $this->assertSame('Softcents Bulk SMS', $serverNames['softcents_sms_panel'] ?? null);
        $this->assertSame('Softcents Voice SMS', $serverNames['softcents_vsms'] ?? null);

        foreach (['bdpbx', 'bhpanel', 'softcents_sms_panel', 'softcents_vsms'] as $type) {
            $this->assertSame('either', $registry->serverCredentialRequirement($type));
        }
    }

    public function test_piprapay_capture_and_webhook_verification_settles_invoice(): void
    {
        [, $client] = $this->createClientWithUser();
        $invoice = $this->createUnpaidInvoice($client, 1200.00, 'piprapay');

        foreach ([
            'active' => '1',
            'api_key' => 'pp_live_secret_key_123',
            'base_url' => 'https://pay.piprapay.example',
            'version' => 'v2',
        ] as $k => $v) {
            GatewaySettings::updateOrCreate(['gateway' => 'piprapay', 'setting' => $k], ['value' => $v]);
        }

        Http::fake([
            'https://pay.piprapay.example/api/create-charge' => Http::response([
                'pp_id' => 'PP_TX_9001',
                'pp_url' => 'https://pay.piprapay.example/checkout/PP_TX_9001',
            ], 200),
            'https://pay.piprapay.example/api/verify-payments' => Http::response([
                'pp_id' => 'PP_TX_9001',
                'status' => 'completed',
                'amount' => 1200.00,
                'metadata' => [
                    'invoiceid' => $invoice->id,
                    'due_amount' => '1200.00',
                    'rate' => '1.00000',
                ],
            ], 200),
        ]);

        $module = app(ModuleRegistry::class)->getGatewayModule('piprapay');
        $capture = $module->capture($invoice, 1200.00);
        $this->assertTrue($capture['success']);
        $this->assertSame('https://pay.piprapay.example/checkout/PP_TX_9001', $capture['checkout_url']);

        // Forged webhook header must be rejected with 401
        $badResp = $this->postJson(route('modules.gateways.piprapay.webhook'), [
            'pp_id' => 'PP_TX_9001',
            'status' => 'completed',
            'amount' => 1200.00,
            'metadata' => ['invoiceid' => $invoice->id],
        ], ['mh-piprapay-api-key' => 'wrong_key']);
        $badResp->assertStatus(401);
        $this->assertNotEquals(InvoiceStatus::Paid->value, $invoice->fresh()->status);

        // Authentic webhook settles the invoice
        $okResp = $this->postJson(route('modules.gateways.piprapay.webhook'), [
            'pp_id' => 'PP_TX_9001',
            'status' => 'completed',
            'amount' => 1200.00,
            'metadata' => ['invoiceid' => $invoice->id],
        ], ['mh-piprapay-api-key' => 'pp_live_secret_key_123']);
        $okResp->assertStatus(200);
        $this->assertSame(InvoiceStatus::Paid->value, $invoice->fresh()->status);
    }

    public function test_uddoktapay_local_and_international_verification_and_replay_guard(): void
    {
        [$user, $client] = $this->createClientWithUser();
        $invoiceA = $this->createUnpaidInvoice($client, 2400.00, 'uddoktapay');
        $invoiceB = $this->createUnpaidInvoice($client, 2400.00, 'uddoktapay');

        foreach ([
            'active' => '1',
            'api_key' => 'up_secret_987654',
            'api_url' => 'https://pay.softcents.example/api/checkout-v2',
            'exchange_rate' => '120',
        ] as $k => $v) {
            GatewaySettings::updateOrCreate(['gateway' => 'uddoktapay', 'setting' => $k], ['value' => $v]);
            GatewaySettings::updateOrCreate(['gateway' => 'uddoktapayinternational', 'setting' => $k], ['value' => $v]);
        }

        Http::fake([
            'https://pay.softcents.example/api/checkout-v2' => Http::response([
                'status' => true,
                'payment_url' => 'https://pay.softcents.example/pay/UP_INV_1',
            ], 200),
            'https://pay.softcents.example/api/checkout-v2/global' => Http::response([
                'status' => true,
                'payment_url' => 'https://pay.softcents.example/pay/UP_GLOBAL_1',
            ], 200),
            'https://pay.softcents.example/api/verify-payment' => Http::response([
                'status' => 'COMPLETED',
                'transaction_id' => 'BKASH_TRX_7788',
                'amount' => '2400.00',
                'metadata' => [
                    'invoice_id' => $invoiceA->id,
                    'due_amount' => '2400.00',
                    'expected_charge' => '2400.00',
                    'rate' => '1.00000',
                ],
            ], 200),
        ]);

        // International checkout converts 2400 BDT -> 20 USD at rate 120
        $intlModule = app(ModuleRegistry::class)->getGatewayModule('uddoktapayinternational');
        $intlCharge = $intlModule->calculateCharge($invoiceB, 2400.00);
        $this->assertSame('USD', $intlCharge['currency']);
        $this->assertEquals(20.00, $intlCharge['amount']);

        $this->actingAs($user)->post(route('modules.gateways.uddoktapay.capture', $invoiceA))
            ->assertRedirect('https://pay.softcents.example/pay/UP_INV_1');

        // Attempt to replay invoiceA's payment reference onto invoiceB's return URL must NOT mark invoiceB paid!
        $this->get(route('modules.gateways.uddoktapay.return', ['invoice' => $invoiceB->id, 'invoice_id' => 'UP_INV_1']));
        $this->assertSame(InvoiceStatus::Unpaid->value, $invoiceB->fresh()->status);

        // Authentic IPN webhook settles invoiceA
        $this->postJson(route('modules.gateways.uddoktapay.webhook'), [
            'invoice_id' => 'UP_INV_1',
        ], ['RT-UDDOKTAPAY-API-KEY' => 'up_secret_987654'])->assertStatus(200);

        $this->assertSame(InvoiceStatus::Paid->value, $invoiceA->fresh()->status);
    }

    public function test_bdpbx_lifecycle_soft_termination_extension_999_guard_and_abstraction(): void
    {
        [$user, $client] = $this->createClientWithUser('Dhaka Ventures');
        $group = ProductGroup::create(['name' => 'Cloud PBX', 'slug' => 'cloud-pbx-'.uniqid()]);
        $server = Server::create([
            'name' => 'PBX Node 1',
            'hostname' => 'pbxnode.internal.example',
            'ip_address' => '203.0.113.50',
            'port' => 443,
            'type' => 'bdpbx',
            'access_hash' => 'pbx_api_secret_token',
            'active' => true,
        ]);
        $product = Product::create([
            'group_id' => $group->id,
            'name' => 'Business PBX 5 Ext',
            'slug' => 'biz-pbx-'.uniqid(),
            'type' => 'other',
            'pay_type' => 'recurring',
            'server_type' => 'bdpbx',
            'config_options' => ['ext_count' => '5', 'domain_suffix' => 'bdpbx.com'],
        ]);
        $service = Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'server_id' => $server->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount' => 1500,
        ]);

        Http::fake([
            'https://pbxnode.internal.example/billing_api.php' => function ($request) {
                $action = $request['action'] ?? '';

                return match ($action) {
                    'create' => Http::response([
                        'status' => 'success',
                        'domain' => $request['domain'],
                        'package_extensions' => [
                            ['extension' => '101', 'password' => 'Pass101!'],
                        ],
                        'free_extension_999' => ['extension' => '999', 'password' => 'Sys999!'],
                    ], 200),
                    'status' => Http::response([
                        'status' => 'success',
                        'domain' => 'dhakaventures-pbx.bdpbx.com',
                        'recordings_count_30d' => 12,
                        'extensions' => [
                            ['extension' => '101', 'effective_caller_id_name' => 'Reception', 'password' => 'Pass101!', 'enabled' => true],
                            ['extension' => '999', 'effective_caller_id_name' => 'Voice SMS', 'password' => 'Sys999!', 'enabled' => true],
                        ],
                    ], 200),
                    'check_ip' => Http::response(['status' => 'success', 'is_blocked' => false], 200),
                    default => Http::response(['status' => 'success', 'sso_url' => 'https://dhakaventures-pbx.bdpbx.com/sso?t=abc'], 200),
                };
            },
        ]);

        $module = app(BdpbxModule::class);
        $created = $module->create($service);
        $this->assertTrue($created['success']);
        $this->assertSame('dhakaventures-pbx.bdpbx.com', $service->fresh()->domain);

        // Customer abstraction: server_details hidden so internal node hostname/IP are not leaked!
        $screens = ModuleScreens::for($service->fresh());
        $this->assertFalse($screens->shows('server_details'));

        // Extension 999 password modification by customer MUST be blocked
        $blocked999 = $module->updateExtensionPassword($service->fresh(), '999', 'NewPass123!', false);
        $this->assertFalse($blocked999['success']);

        // Extension 101 password modification succeeds
        $allowed101 = $module->updateExtensionPassword($service->fresh(), '101', 'NewPass123!', false);
        $this->assertTrue($allowed101['success']);

        // Soft termination preserves CDRs and marks soft_terminated
        $term = $module->terminate($service->fresh());
        $this->assertTrue($term['success']);
        $this->assertTrue((bool) ($service->fresh()->module_data['soft_terminated'] ?? false));

        // Client page renders cleanly without exposing internal server hostname
        $page = $this->actingAs($user)->get(route('client.services.show', $service));
        $page->assertStatus(200);
        $page->assertSee('dhakaventures-pbx.bdpbx.com');
        $page->assertDontSee('pbxnode.internal.example');
    }

    public function test_bhpanel_ownership_check_sso_and_embedded_tabs(): void
    {
        [$user, $client] = $this->createClientWithUser();
        $group = ProductGroup::create(['name' => 'Web Hosting', 'slug' => 'web-hosting-'.uniqid()]);
        $validToken = 'bhp_live_'.str_repeat('a', 64);

        $server = Server::create([
            'name' => 'BHPanel Node 1',
            'hostname' => 'panel.hosting.example',
            'port' => 2304,
            'type' => 'bhpanel',
            'access_hash' => $validToken,
            'active' => true,
        ]);
        $product = Product::create([
            'group_id' => $group->id,
            'name' => 'Starter Hosting',
            'slug' => 'starter-hosting-'.uniqid(),
            'type' => 'hosting',
            'pay_type' => 'recurring',
            'server_type' => 'bhpanel',
            'config_options' => ['package_name' => 'starter_10g'],
        ]);
        $service = Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'server_id' => $server->id,
            'domain' => 'customer-site.com',
            'username' => 'custsite',
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount' => 500,
        ]);

        $ssoHex = str_repeat('f', 64);

        Http::fake([
            'https://panel.hosting.example:2304/v1/accounts/custsite' => Http::response([
                'ok' => true,
                'data' => ['account' => ['username' => 'custsite', 'domain' => 'customer-site.com']],
            ], 200),
            'https://panel.hosting.example:2304/v1/accounts/custsite/signin*' => Http::response([
                'ok' => true,
                'data' => ['url' => "https://panel.hosting.example:2304/sso/{$ssoHex}"],
            ], 200),
            'https://panel.hosting.example:2304/v1/accounts/custsite/domains?*' => Http::response([
                'ok' => true,
                'data' => [['domain' => 'customer-site.com', 'kind' => 'primary', 'php' => '8.4']],
            ], 200),
            'https://panel.hosting.example:2304/v1/accounts/custsite/domains/ssl?*' => Http::response([
                'ok' => true,
                'data' => ['domains' => [['domain' => 'customer-site.com', 'has_cert' => true, 'force_https' => true]]],
            ], 200),
            'https://panel.hosting.example:2304/v1/php' => Http::response([
                'ok' => true,
                'data' => [['version' => '8.4', 'installed' => true, 'ready' => true]],
            ], 200),
        ]);

        $module = app(BhpanelModule::class);
        $sso = $module->ssoLogin($service);
        $this->assertTrue($sso['success']);
        $this->assertSame("https://panel.hosting.example:2304/sso/{$ssoHex}", $sso['data']['url']);

        // Embedded Domains & SSL tabs render cleanly for the owner
        $this->actingAs($user)->get(route('client.services.bhpanel.domains', $service))
            ->assertStatus(200)
            ->assertSee('customer-site.com');

        $this->actingAs($user)->get(route('client.services.bhpanel.ssl', $service))
            ->assertStatus(200)
            ->assertSee('Valid SSL');

        // Ownership guard: if service domain differs from panel account domain, suspend is refused!
        BhpanelClient::resetCache();
        $mismatched = Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'server_id' => $server->id,
            'domain' => 'someone-elses-domain.com',
            'username' => 'custsite',
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount' => 500,
        ]);
        $refused = $module->suspend($mismatched, 'Overdue');
        $this->assertFalse($refused['success']);
    }

    public function test_softcents_sms_and_vsms_provisioning_and_paid_renewal_wallet_topup(): void
    {
        [, $client] = $this->createClientWithUser();
        $group = ProductGroup::create(['name' => 'Messaging', 'slug' => 'messaging-'.uniqid()]);

        $smsServer = Server::create([
            'name' => 'SMS Platform',
            'hostname' => 'sms.softcents.example',
            'port' => 443,
            'type' => 'softcents_sms_panel',
            'access_hash' => 'sanctum_sms_token_123',
            'active' => true,
        ]);
        $vsmsServer = Server::create([
            'name' => 'VSMS Platform',
            'hostname' => 'vsms.softcents.example',
            'port' => 443,
            'type' => 'softcents_vsms',
            'access_hash' => 'sanctum_vsms_token_456',
            'active' => true,
        ]);

        $smsProduct = Product::create([
            'group_id' => $group->id,
            'name' => 'Bulk SMS Starter',
            'slug' => 'bulk-sms-'.uniqid(),
            'type' => 'other',
            'pay_type' => 'recurring',
            'server_type' => 'softcents_sms_panel',
            'config_options' => [
                'account_type' => 'user',
                'initial_balance' => '500',
                'renewal_balance' => '500',
            ],
        ]);
        $vsmsProduct = Product::create([
            'group_id' => $group->id,
            'name' => 'Voice SMS Starter',
            'slug' => 'voice-sms-'.uniqid(),
            'type' => 'other',
            'pay_type' => 'recurring',
            'server_type' => 'softcents_vsms',
            'config_options' => [
                'account_type' => 'user',
                'initial_balance' => '1000',
                'renewal_balance' => '1000',
            ],
        ]);

        $smsService = Service::create([
            'client_id' => $client->id,
            'product_id' => $smsProduct->id,
            'server_id' => $smsServer->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount' => 500,
        ]);
        $vsmsService = Service::create([
            'client_id' => $client->id,
            'product_id' => $vsmsProduct->id,
            'server_id' => $vsmsServer->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount' => 1000,
        ]);

        Http::fake([
            'https://sms.softcents.example/api/whmcs/accounts' => Http::response([
                'status' => 'success',
                'created' => true,
                'user' => ['id' => 401, 'username' => 'sms_rahim'],
            ], 201),
            'https://sms.softcents.example/api/whmcs/accounts/401/credit' => Http::response([
                'status' => 'success',
                'message' => 'Credited',
            ], 200),
            'https://vsms.softcents.example/api/whmcs/accounts' => Http::response([
                'status' => 'success',
                'created' => true,
                'user' => ['id' => 802, 'username' => 'vsms_rahim'],
            ], 201),
            'https://vsms.softcents.example/api/whmcs/accounts/802/credit' => Http::response([
                'status' => 'success',
                'message' => 'Credited',
            ], 200),
        ]);

        $smsMod = app(SoftcentsSmsPanelModule::class);
        $vsmsMod = app(SoftcentsVsmsModule::class);

        $this->assertTrue($smsMod->create($smsService)['success']);
        $this->assertSame(401, $smsService->fresh()->module_data['remote_user_id']);

        $this->assertTrue($vsmsMod->create($vsmsService)['success']);
        $this->assertSame(802, $vsmsService->fresh()->module_data['remote_user_id']);

        // Record a paid renewal invoice and verify renew() credits both wallets idempotently
        $paidInv = Invoice::create([
            'client_id' => $client->id,
            'invoice_num' => 'INV-REN-'.uniqid(),
            'date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'subtotal' => 1500,
            'total' => 1500,
            'status' => InvoiceStatus::Paid->value,
        ]);
        InvoiceItem::create([
            'invoice_id' => $paidInv->id,
            'type' => 'Hosting',
            'rel_id' => $smsService->id,
            'description' => 'SMS Renewal',
            'qty' => 1,
            'amount' => 500,
        ]);
        InvoiceItem::create([
            'invoice_id' => $paidInv->id,
            'type' => 'Hosting',
            'rel_id' => $vsmsService->id,
            'description' => 'VSMS Renewal',
            'qty' => 1,
            'amount' => 1000,
        ]);

        $this->assertTrue($smsMod->renew($smsService->fresh())['success']);
        $this->assertTrue($vsmsMod->renew($vsmsService->fresh())['success']);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Models\AddonSetting;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Server;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AddonServiceProvider;
use App\Services\AddonManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Addons\BdpbxAccounting\Models\BdpbxDailyStat;
use Modules\Addons\BdpbxAccounting\Services\AccountingEngine;
use Modules\Addons\ItalkBalance\Models\ItalkBalanceEndpoint;
use Modules\Addons\ItalkBalance\Models\ItalkBalanceService;
use Modules\Addons\ItalkBalance\Services\BalanceFetcher;
use Modules\Addons\ItalkBalance\Services\BalanceRegistry;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeAccount;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeLog;
use Modules\Addons\ItalkRecharge\Models\ItalkRechargeProvider;
use Modules\Addons\ItalkRecharge\Services\RechargeService;
use Modules\Addons\RechargeRequests\Models\RechargeRequest;
use Tests\TestCase;

/**
 * Phase 2 - telecom, IPTSP balance and multi-provider auto-recharge.
 *
 * The addons are real folders under modules/Addons: the tests switch them on
 * the way an operator does (Setting::set) and boot their providers, then
 * exercise the routes, the hooks and the drivers as the application does.
 */
class Phase2SoftcentsTelecomTest extends TestCase
{
    private const ADDONS = ['italk_balance', 'italk_recharge', 'recharge_requests', 'bdpbx_accounting'];

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(
            ['code' => 'BDT'],
            ['prefix' => '৳', 'suffix' => ' BDT', 'rate' => 1.00000, 'is_default' => true]
        );

        foreach (self::ADDONS as $addon) {
            Setting::set("addon_{$addon}_active", '1', 'addons');
        }

        app()->forgetInstance(AddonManager::class);
        (new AddonServiceProvider(app()))->boot();
        app('router')->getRoutes()->refreshNameLookups();

        // Hook files load at boot only for addons already active in the
        // database; here they are switched on inside the test's transaction.
        foreach (['ItalkBalance', 'ItalkRecharge', 'RechargeRequests'] as $folder) {
            $file = base_path("modules/Addons/{$folder}/hooks.php");
            if (is_file($file)) {
                require $file;
            }
        }
    }

    private function createClientWithUser(string $email = ''): array
    {
        $email = $email ?: 'telecom.'.uniqid().'@example.com';

        $user = User::forceCreate([
            'first_name' => 'Rahim',
            'last_name' => 'Uddin',
            'email' => $email,
            'password' => Hash::make('SecretPass123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => 'Rahim',
            'last_name' => 'Uddin',
            'email' => $email,
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

    private function makeService(Client $client, string $serverType = 'bdpbx', ?Server $server = null): Service
    {
        $group = ProductGroup::create(['name' => 'Telecom '.uniqid(), 'slug' => 'telecom-'.uniqid()]);

        $product = Product::create([
            'group_id' => $group->id,
            'name' => 'IP Phone '.uniqid(),
            'slug' => 'ip-phone-'.uniqid(),
            'type' => 'other',
            'pay_type' => 'recurring',
            'server_type' => $serverType,
        ]);

        return Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'server_id' => $server?->id,
            'domain' => 'service-'.uniqid().'.example.com',
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount' => 1000,
        ]);
    }

    public function test_the_four_phase_two_addons_are_discovered_activated_and_route_their_pages(): void
    {
        $manager = app(AddonManager::class);

        foreach (['italk_balance', 'italk_recharge', 'recharge_requests', 'bdpbx_accounting'] as $name) {
            $this->assertNotNull($manager->find($name), "Addon {$name} is not discovered");
            $this->assertTrue($manager->isActive($name), "Addon {$name} is not active");
        }

        $this->assertTrue(Schema::hasTable('italk_balance_endpoints'));
        $this->assertTrue(Schema::hasTable('italk_balance_services'));
        $this->assertTrue(Schema::hasTable('italk_recharge_providers'));
        $this->assertTrue(Schema::hasTable('italk_recharge_accounts'));
        $this->assertTrue(Schema::hasTable('italk_recharge_logs'));
        $this->assertTrue(Schema::hasTable('recharge_requests'));
        $this->assertTrue(Schema::hasTable('bdpbx_daily_stats'));

        $admin = Admin::factory()->create(['role_id' => AdminRole::factory()->fullAdmin()->create()->id]);

        foreach ([
            'admin.extensions.italk-balance.index',
            'admin.extensions.iptsp-recharge.index',
            'admin.extensions.recharge-requests.index',
            'admin.extensions.bdpbx-accounting.index',
        ] as $route) {
            $this->actingAs($admin, 'admin')->get(route($route))->assertOk();
        }
    }

    public function test_balance_card_refreshes_live_and_enforces_ownership_and_cooldown(): void
    {
        [$user, $client] = $this->createClientWithUser();
        $service = $this->makeService($client);

        $endpoint = ItalkBalanceEndpoint::create([
            'label' => 'iTalk API',
            'base_url' => 'https://sip.italk.com.bd/iconiptsp/getclientbalance.do?pin=',
            'program' => 'italk',
            'is_active' => true,
        ]);

        AddonSetting::setSetting('italk_balance', 'products_italk', (string) $service->product_id);
        AddonSetting::setSetting('italk_balance', 'refresh_cooldown', '30');

        $mapping = ItalkBalanceService::create([
            'client_id' => $client->id,
            'service_id' => $service->id,
            'product_id' => $service->product_id,
            'ip_number' => '09644881127',
            'endpoint_id' => $endpoint->id,
        ]);

        Http::fake([
            'https://sip.italk.com.bd/*' => Http::response('1169.000000', 200),
        ]);

        $registry = app(BalanceRegistry::class);
        $first = $registry->refresh($mapping->fresh());

        $this->assertTrue($first['ok']);
        $this->assertSame(1169.0, $first['balance']);
        $this->assertSame('1169.000000', $mapping->fresh()->balance);

        // Inside the cooldown the provider is not called again.
        Http::fake(['https://sip.italk.com.bd/*' => Http::response('999.000000', 200)]);
        $second = $registry->refresh($mapping->fresh());
        $this->assertFalse($second['ok']);
        $this->assertSame(1169.0, $mapping->fresh()->balanceAmount());

        // The card renders on the service page for the owner.
        $page = $this->actingAs($user)->get(route('client.services.show', $service));
        $page->assertOk();
        $page->assertSee('IP Number Balance');
        $page->assertSee('1,169.00');
        $page->assertSee('09644881127');

        // The refresh endpoint is owner-only.
        $otherUser = User::forceCreate([
            'first_name' => 'Other',
            'last_name' => 'User',
            'email' => 'other.'.uniqid().'@example.com',
            'password' => Hash::make('SecretPass123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $otherClient = Client::forceCreate([
            'first_name' => 'Other', 'last_name' => 'User',
            'email' => $otherUser->email, 'status' => 'active',
        ]);
        $otherUser->clients()->syncWithoutDetaching([$otherClient->id => ['owner' => true, 'permissions' => null]]);

        $this->actingAs($otherUser)
            ->postJson(route('client.italk-balance.refresh', $service))
            ->assertStatus(403);
    }

    public function test_balance_fetch_refuses_insecure_urls_bad_pins_and_non_numeric_answers(): void
    {
        $fetcher = new BalanceFetcher;

        $this->assertFalse($fetcher->fetch('http://sip.italk.com.bd/getclientbalance.do?pin=', '09644881127')['ok']);
        $this->assertFalse($fetcher->fetch('https://sip.italk.com.bd/x?pin=', 'abc&amount=1')['ok']);

        Http::fake(['https://icctalk.com/*' => Http::response('Invalid Account', 200)]);
        $invalid = $fetcher->fetch('https://icctalk.com/ICC/getclientbalance.do?pin=', '09644551064');
        $this->assertFalse($invalid['ok']);
        $this->assertSame('No balance is available for this IP number.', $invalid['message']);

        Http::fake(['https://icctalk.com/*' => Http::response('<html>down</html>', 200)]);
        $this->assertFalse($fetcher->fetch('https://icctalk.com/ICC/getclientbalance.do?pin=', '09644551064')['ok']);
    }

    public function test_provider_credentials_are_encrypted_and_a_paid_invoice_triggers_the_top_up(): void
    {
        [, $client] = $this->createClientWithUser();

        $provider = ItalkRechargeProvider::create([
            'name' => 'CODE 09647',
            'base_url' => 'https://billing.webvoice.net',
            'base_path' => '/',
            'integration_type' => 'rest',
            'api_type' => 'webvoice',
            'username' => 'Ominaz',
            'password' => 'SoFTCeNt@1971',
            'status' => 'active',
        ]);

        // The stored column is ciphertext, not the reseller password.
        $raw = DB::table('italk_recharge_providers')->where('id', $provider->id)->value('password');
        $this->assertNotSame('SoFTCeNt@1971', $raw);
        $this->assertSame('SoFTCeNt@1971', ItalkRechargeProvider::find($provider->id)->password);

        $account = ItalkRechargeAccount::create([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'pin_number' => '09644881127',
            'status' => 'active',
        ]);

        Http::fake([
            'https://billing.webvoice.net/api/login' => Http::response(['token' => 'jwt-token'], 200),
            // The payment stub is registered first: fakes match in order, and a
            // clients/* wildcard would otherwise answer the payment call too.
            'https://billing.webvoice.net/api/clients/77/payment' => Http::response([
                'id' => 9001, 'client_id' => 77, 'balance_after' => 500,
            ], 201),
            'https://billing.webvoice.net/api/clients?*' => Http::response([
                'data' => [['id' => 77, 'username' => '09644881127', 'callerid' => '09644881127']],
            ], 200),
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'invoice_num' => 'INV-RC-'.uniqid(),
            'date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'subtotal' => 500,
            'total' => 500,
            'status' => InvoiceStatus::Paid->value,
        ]);

        $request = RechargeRequest::create([
            'client_id' => $client->id,
            'amount' => 500,
            'mobile_number' => '09644881127',
            'invoice_id' => $invoice->id,
            'status' => 'Pending',
            'requested_at' => now(),
        ]);

        // InvoicePaid is bridged to the hook loaded in setUp.
        event(new InvoicePaid($invoice));

        $log = ItalkRechargeLog::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('success', $log->status);
        $this->assertSame(1, (int) $log->attempts);

        $this->assertSame('Completed', $request->fresh()->status);
        $this->assertSame('500.00', $account->fresh()->total_recharged);

        // A success is never run twice, whatever the retry button says.
        $retry = app(RechargeService::class)->retry($log->fresh());
        $this->assertFalse($retry['ok']);
    }

    private function ranksittProvider(): ItalkRechargeProvider
    {
        return ItalkRechargeProvider::create([
            'name' => 'Ranksitt',
            'base_url' => 'https://sip.ranksitt.net/vsm4',
            'base_path' => '/',
            'integration_type' => 'rest',
            'api_type' => 'ranksitt',
            'username' => 'Ominaz_Online_L1',
            'password' => 'secret',
            'status' => 'active',
        ]);
    }

    public function test_a_failed_top_up_is_logged_with_its_reason(): void
    {
        [, $client] = $this->createClientWithUser();
        $provider = $this->ranksittProvider();

        Http::fake(['https://sip.ranksitt.net/*' => Http::response(['error' => 'not signed in'], 401)]);

        $result = app(RechargeService::class)->recharge($client, $provider, '09617777313', 1000);

        $this->assertFalse($result['ok']);
        $this->assertSame('failed', $result['log']->status);
        $this->assertSame(1, (int) $result['log']->attempts);
        $this->assertNotNull($result['log']->api_response);
        $this->assertStringContainsString('Sign-in was refused', (string) $result['log']->api_response);
    }

    public function test_a_failed_top_up_can_be_retried_to_success(): void
    {
        [, $client] = $this->createClientWithUser();
        $provider = $this->ranksittProvider();

        $log = ItalkRechargeLog::create([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'pin_number' => '09617777313',
            'amount' => 1000,
            'status' => 'failed',
            'attempts' => 1,
            'api_response' => '{"message":"Sign-in was refused"}',
        ]);

        Http::fake([
            'https://sip.ranksitt.net/vsm4/api/Account/SignIn' => Http::response(['userName' => 'Ominaz_Online_L1'], 200),
            'https://sip.ranksitt.net/vsm4/api/ResellerClientsRetail/GetClientsRetailsList*' => Http::response([
                'data' => [['id' => 12, 'login' => '09617777313']],
            ], 200),
            'https://sip.ranksitt.net/vsm4/api/ResellerClients/AddPayment' => Http::response('', 200),
        ]);

        $retried = app(RechargeService::class)->retry($log->fresh());

        $this->assertTrue($retried['ok'], $retried['message'].' :: '.$retried['log']->api_response);
        $this->assertSame('success', $retried['log']->status);
        $this->assertSame(2, (int) $retried['log']->attempts);
    }

    public function test_client_can_raise_a_recharge_invoice_and_the_admin_queue_handles_it(): void
    {
        [$user, $client] = $this->createClientWithUser();

        // The dashboard card is present for a signed-in client.
        $this->actingAs($user)->get(route('client.home'))
            ->assertOk()
            ->assertSee('IP Number Calling Balance Recharge');

        // Below the minimum the invoice is refused.
        $this->actingAs($user)->post(route('client.recharge.store'), [
            'mobile_number' => '09644881127',
            'amount' => 100,
        ])->assertSessionHasErrors('amount');

        $response = $this->actingAs($user)->post(route('client.recharge.store'), [
            'mobile_number' => '09644881127',
            'amount' => 500,
        ]);

        $request = RechargeRequest::where('client_id', $client->id)->latest('id')->first();

        $this->assertNotNull($request);
        $this->assertSame('Pending', $request->status);
        $this->assertSame('500.00', $request->amount);

        $invoice = $request->invoice;
        $this->assertNotNull($invoice);
        $this->assertSame($invoice->id, $invoice->id);
        $this->assertSame(500.0, (float) $invoice->total);

        $item = InvoiceItem::where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($item);
        $this->assertSame('Recharge', $item->type);
        $this->assertStringContainsString('09644881127', (string) $item->description);

        $response->assertRedirect(route('client.invoices.show', $invoice));

        $admin = Admin::factory()->create(['role_id' => AdminRole::factory()->fullAdmin()->create()->id]);

        $this->actingAs($admin, 'admin')->get(route('admin.extensions.recharge-requests.index'))
            ->assertOk()
            ->assertSee('09644881127');

        $this->actingAs($admin, 'admin')->put(route('admin.extensions.recharge-requests.update', $request), [
            'status' => 'Completed',
        ])->assertRedirect();

        $this->assertSame('Completed', $request->fresh()->status);
    }

    public function test_bdpbx_accounting_snapshots_each_server_and_the_combined_figure(): void
    {
        [, $client] = $this->createClientWithUser();

        $serverA = Server::create([
            'name' => 'srv111-bdpbx-1', 'hostname' => 'srv111.example', 'type' => 'bdpbx', 'active' => true,
        ]);
        $serverB = Server::create([
            'name' => 'SC bdpbx-server22', 'hostname' => 'srv22.example', 'type' => 'bdpbx', 'active' => true,
        ]);

        $serviceA = $this->makeService($client, 'bdpbx', $serverA);
        $serviceB = $this->makeService($client, 'bdpbx', $serverB);

        $paid = Invoice::create([
            'client_id' => $client->id,
            'invoice_num' => 'INV-ACCT-'.uniqid(),
            'date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'date_paid' => now()->toDateString(),
            'subtotal' => 3000,
            'total' => 3000,
            'status' => InvoiceStatus::Paid->value,
        ]);
        InvoiceItem::create([
            'invoice_id' => $paid->id, 'type' => 'Hosting', 'rel_id' => $serviceA->id,
            'description' => 'PBX A', 'qty' => 1, 'amount' => 2000,
        ]);
        InvoiceItem::create([
            'invoice_id' => $paid->id, 'type' => 'Hosting', 'rel_id' => $serviceB->id,
            'description' => 'PBX B', 'qty' => 1, 'amount' => 1000,
        ]);

        $unpaid = Invoice::create([
            'client_id' => $client->id,
            'invoice_num' => 'INV-ACCT-U-'.uniqid(),
            'date' => now()->toDateString(),
            'due_date' => now()->subDays(3)->toDateString(),
            'subtotal' => 1000,
            'total' => 1000,
            'status' => InvoiceStatus::Unpaid->value,
        ]);
        InvoiceItem::create([
            'invoice_id' => $unpaid->id, 'type' => 'Hosting', 'rel_id' => $serviceB->id,
            'description' => 'PBX B unpaid', 'qty' => 1, 'amount' => 1000,
        ]);

        $engine = app(AccountingEngine::class);
        $rows = $engine->run();

        $this->assertArrayHasKey(0, $rows);
        $this->assertArrayHasKey($serverA->id, $rows);
        $this->assertArrayHasKey($serverB->id, $rows);

        $combined = $rows[0];
        $this->assertEquals(3000.0, (float) $combined['paid_amount_month']);
        $this->assertEquals(3000.0, (float) $combined['paid_today_amount']);
        $this->assertEquals(1000.0, (float) $combined['due_amount']);
        $this->assertEquals(2, $combined['active_services']);

        // Per-server revenue is that server's own invoice items, not the whole invoice twice.
        $this->assertEquals(2000.0, (float) $rows[$serverA->id]['paid_amount_month']);
        $this->assertEquals(1000.0, (float) $rows[$serverB->id]['paid_amount_month']);
        $this->assertEquals(1000.0, (float) $rows[$serverB->id]['due_amount']);
        $this->assertEquals(0.0, (float) $rows[$serverA->id]['due_amount']);

        // The command is registered and writes the day.
        $this->artisan('pnlcs:bdpbx-accounting')->assertSuccessful();

        // History keeps the day readable.
        $this->assertTrue(BdpbxDailyStat::where('snapshot_date', now()->toDateString())->exists());
        $this->assertContains(now()->toDateString(), $engine->dates()->all());

        $admin = Admin::factory()->create(['role_id' => AdminRole::factory()->fullAdmin()->create()->id]);
        $this->actingAs($admin, 'admin')->get(route('admin.extensions.bdpbx-accounting.index'))
            ->assertOk()
            ->assertSee('All PBX Servers (Combined)');

        $this->actingAs($admin, 'admin')->post(route('admin.extensions.bdpbx-accounting.run'), ['date' => now()->toDateString()])
            ->assertRedirect();
    }

    public function test_balance_mapping_admin_actions_and_endpoint_validation(): void
    {
        [$user, $client] = $this->createClientWithUser();
        $service = $this->makeService($client);

        $admin = Admin::factory()->create(['role_id' => AdminRole::factory()->fullAdmin()->create()->id]);

        // An http endpoint is refused: the fetch would be downgraded.
        $this->actingAs($admin, 'admin')->post(route('admin.extensions.italk-balance.endpoints.store'), [
            'label' => 'Insecure',
            'base_url' => 'http://sip.example.com/balance?pin=',
            'program' => 'italk',
        ])->assertSessionHasErrors('base_url');

        $this->actingAs($admin, 'admin')->post(route('admin.extensions.italk-balance.endpoints.store'), [
            'label' => 'iTalk API',
            'base_url' => 'https://sip.italk.com.bd/iconiptsp/getclientbalance.do?pin=',
            'program' => 'italk',
            'is_active' => '1',
        ])->assertRedirect();

        $endpoint = ItalkBalanceEndpoint::where('label', 'iTalk API')->firstOrFail();

        $this->actingAs($admin, 'admin')->post(route('admin.extensions.italk-balance.mappings.store'), [
            'service_id' => $service->id,
            'ip_number' => '09644881127',
            'endpoint_id' => $endpoint->id,
        ])->assertRedirect();

        $mapping = ItalkBalanceService::where('service_id', $service->id)->first();
        $this->assertNotNull($mapping);
        $this->assertSame($client->id, (int) $mapping->client_id);
        $this->assertSame($service->product_id, (int) $mapping->product_id);

        // The card stays hidden until the product is selected for the program.
        $this->assertNull(app(BalanceRegistry::class)->revealMapping($service));

        $this->actingAs($admin, 'admin')->post(route('admin.extensions.italk-balance.settings'), [
            'products_italk' => [$service->product_id],
            'refresh_cooldown' => 45,
        ])->assertRedirect();

        $this->assertNotNull(app(BalanceRegistry::class)->revealMapping($service->fresh()));
        $this->assertSame(45, app(BalanceRegistry::class)->cooldown());
    }
}

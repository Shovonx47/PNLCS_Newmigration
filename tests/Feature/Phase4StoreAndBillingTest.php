<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\ServiceStatus;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\ClientGroup;
use App\Models\Credit;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Language;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AddonServiceProvider;
use App\Services\AddonManager;
use App\Services\InvoiceGenerationService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Addons\CancelInvoices\Models\CancelInvoiceExclusion;
use Modules\Addons\PortalExtras\Models\ServiceTransferRequest;
use Modules\Addons\ScrollingNotice\Models\ScrollingNotice;
use Tests\TestCase;

class Phase4StoreAndBillingTest extends TestCase
{
    private const ADDONS = ['scrolling_notice', 'cancel_invoices', 'portal_extras'];

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

        foreach (['ScrollingNotice', 'PortalExtras'] as $folder) {
            $file = base_path("modules/Addons/{$folder}/hooks.php");
            if (is_file($file)) {
                require $file;
            }
        }
    }

    private function makeAdmin(): Admin
    {
        $role = AdminRole::firstOrCreate(
            ['name' => 'Full Admin '.uniqid()],
            ['description' => 'Full admin', 'is_full_admin' => true, 'permissions' => []]
        );

        return Admin::forceCreate([
            'username' => 'admin_'.uniqid(),
            'email' => 'admin.'.uniqid().'@softcents.test',
            'password' => Hash::make('AdminSecret123!'),
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'role_id' => $role->id,
            'is_disabled' => false,
        ]);
    }

    private function makeClientWithUser(?ClientGroup $group = null): array
    {
        $email = 'client.'.uniqid().'@softcents.test';

        $user = User::forceCreate([
            'first_name' => 'Tanvir',
            'last_name' => 'Hasan',
            'email' => $email,
            'password' => Hash::make('ClientSecret123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => 'Tanvir',
            'last_name' => 'Hasan',
            'email' => $email,
            'phone_number' => '01711000000',
            'address1' => 'Uttara Sector 7',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1230',
            'country' => 'BD',
            'status' => 'active',
            'group_id' => $group?->id,
        ]);

        $user->clients()->syncWithoutDetaching([$client->id => ['owner' => true, 'permissions' => null]]);

        return [$user, $client];
    }

    public function test_product_trial_days_and_compare_price_work_across_admin_store_and_orders(): void
    {
        $admin = $this->makeAdmin();
        $currency = Currency::getDefault();
        $group = ProductGroup::create(['name' => 'Voice Packs '.uniqid(), 'slug' => 'voice-'.uniqid()]);
        $product = Product::create([
            'group_id' => $group->id,
            'name' => 'Voice Trial Plan',
            'slug' => 'voice-trial-'.uniqid(),
            'type' => 'other',
            'pay_type' => 'recurring',
            'auto_setup' => 'payment',
        ]);

        Pricing::create([
            'type' => 'product',
            'currency_id' => $currency->id,
            'rel_id' => $product->id,
            'monthly' => 500,
            'quarterly' => -1,
            'semiannually' => -1,
            'annually' => -1,
            'biennially' => -1,
            'triennially' => -1,
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $product), [
                'name' => 'Voice Trial Plan',
                'group_id' => $group->id,
                'type' => 'other',
                'pay_type' => 'recurring',
                'auto_setup' => 'payment',
                'trial_days' => 7,
                'compare_price' => 1000,
            ])
            ->assertRedirect();

        $product->refresh();
        $this->assertSame(7, $product->trial_days);
        $this->assertEquals(1000.00, (float) $product->compare_price);

        auth()->shouldUse('web');
        $storeHtml = $this->get(route('client.store'))->assertOk()->getContent();
        $this->assertStringContainsString('line-through', $storeHtml);
        $this->assertStringContainsString('7-day free trial', $storeHtml);

        [, $client] = $this->makeClientWithUser();
        $order = app(OrderService::class)->processOrder($client, [[
            'type' => 'service',
            'product_id' => $product->id,
            'domain' => '',
            'amount' => 500,
            'setup_fee' => 0,
            'billing_cycle' => 'monthly',
        ]], 'banktransfer');

        $service = Service::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(now()->addDays(7)->toDateString(), $service->next_due_date->toDateString());

        // Simulate expired unpaid trial -> pnlcs:auto-suspend suspends it.
        $service->update([
            'status' => ServiceStatus::Active->value,
            'next_due_date' => now()->subDay()->toDateString(),
        ]);

        Artisan::call('pnlcs:auto-suspend');
        $this->assertSame(ServiceStatus::Suspended->value, strtolower((string) $service->fresh()->status));
    }

    public function test_scrolling_notice_renders_safely_and_blocks_unsafe_schemes(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.scrolling-notices.store'), [
                'title' => 'Unsafe Link',
                'content' => 'Test unsafe scheme',
                'url_target' => 'javascript:alert(1)',
                'login_status' => 'all',
            ])
            ->assertSessionHasErrors('url_target');

        ScrollingNotice::create([
            'title' => 'IP Offer',
            'content' => 'স্পেশাল আইপি নাম্বার অফার চলছে',
            'priority' => 20,
            'url_target' => '/index.php?rp=/store/ip-phone-namber',
            'is_active' => true,
            'login_status' => 'all',
            'coupon_enabled' => true,
            'coupon_code' => 'SOFTCENTS50',
            'scroll_speed' => 5,
        ]);

        auth()->shouldUse('web');
        $res = $this->get(route('client.store'))->assertOk();
        $res->assertSee('স্পেশাল আইপি নাম্বার অফার চলছে');
        $res->assertSee('SOFTCENTS50');
        $res->assertSee('/client/store');
    }

    public function test_bengali_locale_and_client_group_separate_invoices_work(): void
    {
        Language::updateOrCreate(
            ['code' => 'bn'],
            ['name' => 'Bengali', 'native_name' => 'বাংলা', 'flag_code' => 'bd', 'direction' => 'ltr', 'is_active' => true]
        );
        $this->assertTrue(Language::active()->where('code', 'bn')->exists());

        Setting::set('InvoiceRenewalsSeparately', '0', 'billing');
        $group = ClientGroup::create([
            'name' => 'Manual Bill '.uniqid(),
            'color' => '#030805',
            'discount_percent' => 0,
            'suspend_exempt' => true,
            'terminate_exempt' => true,
            'separate_invoices' => true,
        ]);

        [, $client] = $this->makeClientWithUser($group);
        $pg = ProductGroup::create(['name' => 'PBX '.uniqid(), 'slug' => 'pbx-'.uniqid()]);
        $product = Product::create(['group_id' => $pg->id, 'name' => 'PBX Plan', 'slug' => 'pbx-p-'.uniqid(), 'type' => 'other', 'pay_type' => 'recurring']);

        foreach (['s1.bdpbx.com', 's2.bdpbx.com'] as $dom) {
            Service::create([
                'client_id' => $client->id,
                'product_id' => $product->id,
                'domain' => $dom,
                'amount' => 750,
                'billing_cycle' => 'Monthly',
                'next_due_date' => now()->addDays(2)->toDateString(),
                'registration_date' => now()->subMonth()->toDateString(),
                'status' => 'active',
                'auto_renew' => true,
            ]);
        }

        $summary = app(InvoiceGenerationService::class)->generateDueInvoices();
        $clientInvoices = Invoice::where('client_id', $client->id)->get();
        $this->assertCount(2, $clientInvoices);
        $this->assertGreaterThanOrEqual(2, $summary['generated']);
    }

    public function test_auto_cancel_unpaid_invoices_cancels_stale_and_add_funds_while_honoring_exclusions(): void
    {
        [, $clientA] = $this->makeClientWithUser();
        [, $clientExcluded] = $this->makeClientWithUser();

        CancelInvoiceExclusion::create(['type' => 'client', 'rel_id' => $clientExcluded->id, 'note' => 'VIP']);

        $staleServiceInv = Invoice::create([
            'invoice_num' => 'INV-STALE-'.uniqid(),
            'client_id' => $clientA->id,
            'date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
            'subtotal' => 500,
            'total' => 500,
            'status' => InvoiceStatus::Overdue->value,
        ]);
        InvoiceItem::create([
            'invoice_id' => $staleServiceInv->id,
            'client_id' => $clientA->id,
            'type' => 'Hosting',
            'rel_id' => 99999,
            'description' => 'Hosting Renewal',
            'amount' => 500,
        ]);

        $staleAddFundsInv = Invoice::create([
            'invoice_num' => 'INV-AF-'.uniqid(),
            'client_id' => $clientA->id,
            'date' => now()->subDays(12)->toDateString(),
            'due_date' => now()->subDays(9)->toDateString(),
            'subtotal' => 1000,
            'total' => 1000,
            'status' => InvoiceStatus::Unpaid->value,
        ]);
        InvoiceItem::create([
            'invoice_id' => $staleAddFundsInv->id,
            'client_id' => $clientA->id,
            'type' => 'AddFunds',
            'rel_id' => 0,
            'description' => 'Add Funds',
            'amount' => 1000,
        ]);

        $excludedInv = Invoice::create([
            'invoice_num' => 'INV-EX-'.uniqid(),
            'client_id' => $clientExcluded->id,
            'date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->subDays(12)->toDateString(),
            'subtotal' => 800,
            'total' => 800,
            'status' => InvoiceStatus::Overdue->value,
        ]);
        InvoiceItem::create([
            'invoice_id' => $excludedInv->id,
            'client_id' => $clientExcluded->id,
            'type' => 'Hosting',
            'rel_id' => 88888,
            'description' => 'Protected Service',
            'amount' => 800,
        ]);

        Artisan::call('pnlcs:cancel-unpaid-invoices');

        $this->assertSame(InvoiceStatus::Cancelled->value, strtolower((string) $staleServiceInv->fresh()->status));
        $this->assertSame(InvoiceStatus::Cancelled->value, strtolower((string) $staleAddFundsInv->fresh()->status));
        $this->assertSame(InvoiceStatus::Overdue->value, strtolower((string) $excludedInv->fresh()->status));
    }

    public function test_portal_stats_self_suspend_terminate_and_ownership_transfer_workflow(): void
    {
        $admin = $this->makeAdmin();
        [$userOwner, $owner] = $this->makeClientWithUser();
        [$userRecipient, $recipient] = $this->makeClientWithUser();

        Credit::create([
            'client_id' => $owner->id,
            'date' => now()->toDateString(),
            'description' => 'Welcome bonus credit',
            'amount' => 250,
        ]);
        $owner->update(['credit' => 250]);

        $pg = ProductGroup::create(['name' => 'Cloud '.uniqid(), 'slug' => 'cloud-'.uniqid()]);
        $product = Product::create(['group_id' => $pg->id, 'name' => 'Managed Node', 'slug' => 'node-'.uniqid(), 'type' => 'other', 'pay_type' => 'recurring']);

        $service = Service::create([
            'client_id' => $owner->id,
            'product_id' => $product->id,
            'domain' => 'app.example.com',
            'amount' => 600,
            'billing_cycle' => 'Monthly',
            'next_due_date' => now()->addMonth()->toDateString(),
            'registration_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Admin top stats & Client dashboard panels render cleanly
        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Renewals Today:');

        $this->actingAs($userOwner, 'web')
            ->withSession(['active_client_id' => $owner->id])
            ->get(route('client.home'))
            ->assertOk()
            ->assertSee('Recent Credit Activity')
            ->assertSee('Welcome bonus credit');

        // Another client cannot suspend owner's service
        $this->actingAs($userRecipient, 'web')
            ->withSession(['active_client_id' => $recipient->id])
            ->post(route('client.services.self-suspend', $service), ['confirm_suspend' => '1'])
            ->assertForbidden();

        // Owner requests transfer while active (emails dispatched cleanly)
        Mail::fake();
        $this->actingAs($userOwner, 'web')
            ->withSession(['active_client_id' => $owner->id])
            ->post(route('client.services.transfer-request', $service), [
                'to_name' => 'Recipient User',
                'to_email' => $recipient->email,
                'to_phone' => '01800000000',
                'reason' => 'Company merger',
            ])
            ->assertRedirect();

        $transfer = ServiceTransferRequest::where('service_id', $service->id)->firstOrFail();
        $this->assertSame('pending', $transfer->status);

        // Admin approves transfer -> service moves to recipient
        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.service-transfers.approve', $transfer))
            ->assertRedirect();

        $this->assertSame($recipient->id, (int) $service->fresh()->client_id);

        // New owner self-suspends, sees WhatsApp reactivation CTA, and then self-terminates
        $this->actingAs($userRecipient, 'web')
            ->withSession(['active_client_id' => $recipient->id])
            ->post(route('client.services.self-suspend', $service), ['confirm_suspend' => '1'])
            ->assertRedirect();
        $this->assertSame('suspended', strtolower((string) $service->fresh()->status));

        $this->actingAs($userRecipient, 'web')
            ->withSession(['active_client_id' => $recipient->id])
            ->get(route('client.services.show', $service))
            ->assertOk()
            ->assertSee('WhatsApp Support')
            ->assertSee('https://wa.me/8801329646688');

        $this->actingAs($userRecipient, 'web')
            ->withSession(['active_client_id' => $recipient->id])
            ->post(route('client.services.self-terminate', $service), ['confirm_terminate' => '1'])
            ->assertRedirect();
        $this->assertSame('terminated', strtolower((string) $service->fresh()->status));
    }
}

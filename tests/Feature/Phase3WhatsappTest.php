<?php

namespace Tests\Feature;

use App\Events\InvoiceCreated;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AddonServiceProvider;
use App\Services\AddonManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Modules\Addons\WhatsappPro\Models\WaLog;
use Modules\Addons\WhatsappPro\Models\WaQueueItem;
use Modules\Addons\WhatsappPro\Models\WaSettings;
use Modules\Addons\WhatsappPro\Models\WaTemplate;
use Modules\Addons\WhatsappPro\Services\Recipients;
use Modules\Addons\WhatsappPro\Services\WaDispatcher;
use Modules\Addons\WhatsappPro\WhatsappProModule;
use Tests\TestCase;

/**
 * Phase 3 - WhatsApp.
 *
 * The gateway is faked, but the queue is real: a hook must only write a row,
 * the command must do the network call, and a failure must be retried the
 * number of times the operator configured.
 */
class Phase3WhatsappTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(
            ['code' => 'BDT'],
            ['prefix' => '৳', 'suffix' => ' BDT', 'rate' => 1.00000, 'is_default' => true]
        );

        Setting::set('addon_whatsapp_pro_active', '1', 'addons');

        app()->forgetInstance(AddonManager::class);
        (new AddonServiceProvider(app()))->boot();
        app('router')->getRoutes()->refreshNameLookups();

        $hooks = base_path('modules/Addons/WhatsappPro/hooks.php');
        if (is_file($hooks)) {
            require $hooks;
        }

        app(WhatsappProModule::class)->seedTemplates();

        WaSettings::current()->fill([
            'api_key' => 'test-key',
            'sender' => '8801713252753',
            'api_domain' => 'https://wpg.softcents.com',
            'footer' => 'Softcents Systems',
            'admin_numbers' => '01711223300',
            'max_retry' => 2,
            'enabled' => true,
            'client_portal' => true,
        ])->save();
    }

    /** Flipped by a test to make the gateway start or stop accepting. */
    private bool $sendOk = true;

    private function fakeGateway(): void
    {
        // One closure, not a stub map: Http::fake() merges stubs and the first
        // match wins, so a second map would never replace the first.
        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, 'check-number')) {
                return Http::response(['status' => true]);
            }

            if (str_contains($url, 'send-media')) {
                return Http::response(['status' => true]);
            }

            return $this->sendOk
                ? Http::response(['status' => true])
                : Http::response(['status' => false, 'message' => 'Number not on WhatsApp'], 200);
        });
    }

    private function createClientWithUser(string $phone = '01711223344'): array
    {
        $email = 'wa.'.uniqid().'@example.com';

        $user = User::forceCreate([
            'first_name' => 'Nusrat',
            'last_name' => 'Jahan',
            'email' => $email,
            'password' => Hash::make('SecretPass123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => 'Nusrat',
            'last_name' => 'Jahan',
            'email' => $email,
            'phone_number' => $phone,
            'address1' => 'Dhanmondi 27',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1209',
            'country' => 'BD',
            'status' => 'active',
        ]);

        $user->clients()->syncWithoutDetaching([$client->id => ['owner' => true, 'permissions' => null]]);

        return [$user, $client];
    }

    private function makeInvoice(Client $client): Invoice
    {
        return Invoice::forceCreate([
            'client_id' => $client->id,
            'invoice_num' => 'INV-W'.random_int(10000, 99999),
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(4)->toDateString(),
            'subtotal' => 950,
            'tax' => 0,
            'total' => 950,
            'status' => 'unpaid',
            'type' => 'vat',
        ]);
    }

    public function test_the_addon_is_activated_with_the_operators_templates_and_admin_page(): void
    {
        $this->assertNotNull(app(AddonManager::class)->find('whatsapp_pro'));
        $this->assertTrue(app(AddonManager::class)->isActive('whatsapp_pro'));

        $this->assertSame(35, WaTemplate::count());

        $this->fakeGateway();
        $admin = Admin::factory()->create(['role_id' => AdminRole::factory()->fullAdmin()->create()->id]);

        // The page must not call the gateway while it renders: the check waits
        // on the operator's phone and can take half a minute.
        $this->actingAs($admin, 'admin')
            ->get(route('admin.extensions.whatsapp.index'))
            ->assertOk()
            ->assertSee('WhatsApp Pro')
            ->assertSee('Not checked yet')
            ->assertSee('Check now');

        Http::assertNothingSent();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.extensions.whatsapp.connection'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.extensions.whatsapp.index'))
            ->assertOk()
            ->assertSee('Connected');
    }

    public function test_a_hook_only_queues_and_the_command_sends(): void
    {
        $this->fakeGateway();
        [, $client] = $this->createClientWithUser();
        $invoice = $this->makeInvoice($client);

        event(new InvoiceCreated($invoice));

        // Nothing went to the gateway during the event.
        Http::assertNothingSent();

        $item = WaQueueItem::where('template_name', 'InvoiceCreated')->first();
        $this->assertNotNull($item);
        $this->assertSame('pending', $item->status);
        $this->assertSame('8801711223344', $item->number);
        $this->assertStringContainsString('/pay/'.$invoice->id.'?sig=', (string) $item->message);

        $this->artisan('pnlcs:whatsapp-queue')->assertExitCode(0);

        $item->refresh();
        $this->assertSame('sent', $item->status);

        $log = WaLog::where('template_name', 'InvoiceCreated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('Sent', $log->status);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/send-message')
            && $request['number'] === '8801711223344'
            && $request['footer'] === 'Softcents Systems');
    }

    public function test_a_failing_gateway_is_retried_and_then_gives_up(): void
    {
        $this->sendOk = false;
        $this->fakeGateway();
        [, $client] = $this->createClientWithUser();
        $invoice = $this->makeInvoice($client);

        event(new InvoiceCreated($invoice));

        // max_retry is 2: two runs, then the item is failed, not pending.
        $this->artisan('pnlcs:whatsapp-queue')->assertExitCode(0);
        $this->assertSame('pending', WaQueueItem::first()->status);

        $this->artisan('pnlcs:whatsapp-queue')->assertExitCode(0);

        $item = WaQueueItem::first();
        $this->assertSame('failed', $item->status);
        $this->assertSame(2, (int) $item->attempts);
        $this->assertSame('Number not on WhatsApp', $item->error);

        $this->assertSame(2, WaLog::where('status', 'Failed')->count());

        // The admin can put it back on the queue, and this time it works.
        $this->sendOk = true;
        app(WaDispatcher::class)->retry(WaLog::latest('id')->first());
        $this->artisan('pnlcs:whatsapp-queue')->assertExitCode(0);
        $this->assertSame('sent', WaQueueItem::latest('id')->first()->status);
    }

    public function test_a_bulk_send_targets_the_unpaid_and_renders_each_name(): void
    {
        $this->fakeGateway();
        [, $client] = $this->createClientWithUser('01711223344');
        $this->makeInvoice($client);
        $this->createClientWithUser('01719998877'); // no invoice: not in the audience

        $admin = Admin::factory()->create(['role_id' => AdminRole::factory()->fullAdmin()->create()->id]);

        $this->actingAs($admin, 'admin')->post(route('admin.extensions.whatsapp.bulk'), [
            'target' => 'unpaid',
            'message' => 'Hello {firstname}, invoice {invoiceid} is ready.',
        ])->assertRedirect();

        $this->assertSame(1, WaQueueItem::count());

        $item = WaQueueItem::first();
        $this->assertSame('8801711223344', $item->number);
        $this->assertStringContainsString('Hello Nusrat', (string) $item->message);
        $this->assertStringNotContainsString('{invoiceid}', (string) $item->message);
    }

    public function test_a_pasted_list_is_normalised_and_deduplicated(): void
    {
        $rows = Recipients::fromCsv("number,name\n01711223344,One\n8801711223344,One again\n+880 1811-223344,Two\nnonsense,");

        $this->assertSame([
            ['client_id' => null, 'number' => '8801711223344'],
            ['client_id' => null, 'number' => '8801811223344'],
        ], $rows);
    }

    public function test_a_customer_sees_only_their_own_messages(): void
    {
        [$user, $client] = $this->createClientWithUser('01711223344');
        [, $other] = $this->createClientWithUser('01719990000');

        WaLog::create(['client_id' => $client->id, 'number' => '8801711223344', 'message' => 'Yours', 'status' => 'Sent', 'template_name' => 'InvoiceCreated']);
        WaLog::create(['client_id' => $other->id, 'number' => '8801719990000', 'message' => 'Not yours', 'status' => 'Sent', 'template_name' => 'InvoiceCreated']);

        $this->actingAs($user)
            ->get(route('client.whatsapp.index'))
            ->assertOk()
            ->assertSee('Yours')
            ->assertDontSee('Not yours');
    }
}

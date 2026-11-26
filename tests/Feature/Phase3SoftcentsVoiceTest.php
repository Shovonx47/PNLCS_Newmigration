<?php

namespace Tests\Feature;

use App\Events\InvoiceCreated;
use App\Events\ServiceSuspended;
use App\Models\Admin;
use App\Models\AdminRole;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AddonServiceProvider;
use App\Services\AddonManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Modules\Addons\SoftcentsVoice\Models\VoiceEvent;
use Modules\Addons\SoftcentsVoice\Models\VoiceGroupOverride;
use Modules\Addons\SoftcentsVoice\Models\VoiceLog;
use Modules\Addons\SoftcentsVoice\Models\VoiceRetry;
use Modules\Addons\SoftcentsVoice\Models\VoiceSettings;
use Modules\Addons\SoftcentsVoice\Services\VoiceDispatcher;
use Modules\Addons\SoftcentsVoice\SoftcentsVoiceModule;
use Tests\TestCase;

/**
 * Phase 3 - voice.
 *
 * The gateway is faked. The window, the pause switch, the group override and
 * the retry schedule are real, and they are what decides whether a customer's
 * phone rings at all.
 */
class Phase3SoftcentsVoiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(
            ['code' => 'BDT'],
            ['prefix' => '৳', 'suffix' => ' BDT', 'rate' => 1.00000, 'is_default' => true]
        );

        Setting::set('addon_softcents_voice_active', '1', 'addons');

        app()->forgetInstance(AddonManager::class);
        (new AddonServiceProvider(app()))->boot();
        app('router')->getRoutes()->refreshNameLookups();

        $hooks = base_path('modules/Addons/SoftcentsVoice/hooks.php');
        if (is_file($hooks)) {
            require $hooks;
        }

        app(SoftcentsVoiceModule::class)->activate();

        $this->settings()->fill([
            'api_token' => '15|test-token',
            'api_base_url' => 'https://vsms.softcents.com/api',
            'caller_id' => '376',
            'calling_start' => '09:00',
            'calling_end' => '21:00',
            'timezone' => 'Asia/Dhaka',
            'master_paused' => false,
            'retry_enabled' => true,
            'retry_interval_minutes' => 15,
            'max_attempts' => 3,
        ])->save();

        VoiceEvent::where('event_key', 'invoice_created')->update([
            'audio_id' => '427', 'caller_id' => '', 'is_enabled' => true,
        ]);
    }

    private function settings(): VoiceSettings
    {
        return VoiceSettings::current();
    }

    /** Flipped by a test to make the gateway start or stop accepting calls. */
    private bool $callOk = true;

    private function fakeGateway(): void
    {
        // One closure: Http::fake() merges stubs and the first match wins, so
        // a second registration would never replace this one.
        Http::fake(function ($request) {
            $url = $request->url();
            $callOk = $this->callOk;

            if (str_contains($url, 'account/balance')) {
                return Http::response(['data' => ['balance' => 5200]]);
            }

            if (str_contains($url, 'audio')) {
                return Http::response(['data' => [['id' => 427, 'title' => 'Invoice notice']]]);
            }

            if (str_contains($url, 'calls')) {
                return $callOk
                    ? Http::response(['data' => ['id' => 'call-123']])
                    : Http::response(['message' => 'Audio not approved'], 422);
            }

            return Http::response(['data' => []]);
        });
    }

    private function createClientWithUser(): array
    {
        $email = 'voice.'.uniqid().'@example.com';

        $user = User::forceCreate([
            'first_name' => 'Tanvir',
            'last_name' => 'Hasan',
            'email' => $email,
            'password' => Hash::make('SecretPass123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => 'Tanvir',
            'last_name' => 'Hasan',
            'email' => $email,
            'phone_number' => '01711223344',
            'address1' => 'Mirpur',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1216',
            'country' => 'BD',
            'status' => 'active',
        ]);

        $user->clients()->syncWithoutDetaching([$client->id => ['owner' => true, 'permissions' => null]]);

        return [$user, $client];
    }

    private function makeInvoice(Client $client, string $status = 'unpaid', int $dueInDays = 5): Invoice
    {
        return Invoice::forceCreate([
            'client_id' => $client->id,
            'invoice_num' => 'INV-V'.random_int(1000, 9999),
            'date' => now()->toDateString(),
            'due_date' => now()->addDays($dueInDays)->toDateString(),
            'subtotal' => 650,
            'tax' => 0,
            'total' => 650,
            'status' => $status,
            'type' => 'vat',
        ]);
    }

    private function at(string $dhakaTime): void
    {
        Carbon::setTestNow(Carbon::parse($dhakaTime, 'Asia/Dhaka'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_addon_installs_24_events_and_the_admin_screen(): void
    {
        $this->assertTrue(app(AddonManager::class)->isActive('softcents_voice'));
        $this->assertSame(24, VoiceEvent::count());

        $this->fakeGateway();
        $this->at('2026-11-25 12:00');

        $admin = Admin::factory()->create(['role_id' => AdminRole::factory()->fullAdmin()->create()->id]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.extensions.voice.index'))
            ->assertOk()
            ->assertSee('Softcents Voice')
            ->assertSee('5200');
    }

    public function test_a_paused_master_switch_places_no_call(): void
    {
        $this->fakeGateway();
        $this->at('2026-11-25 12:00');
        [, $client] = $this->createClientWithUser();

        $this->settings()->forceFill(['master_paused' => true])->save();

        event(new InvoiceCreated($this->makeInvoice($client)));

        Http::assertNothingSent();
        $this->assertDatabaseHas('voice_logs', ['status' => 'skipped', 'reason' => 'Voice dispatch is paused.']);
    }

    public function test_an_enabled_event_inside_the_window_calls_the_right_number(): void
    {
        $this->fakeGateway();
        $this->at('2026-11-25 14:00');
        [, $client] = $this->createClientWithUser();
        $invoice = $this->makeInvoice($client);

        event(new InvoiceCreated($invoice));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/calls')
            && $request['phone_number'] === '+8801711223344'
            && (string) $request['audio_id'] === '427'
            && (int) $request['caller_id'] === 376);

        $this->assertDatabaseHas('voice_logs', [
            'invoice_id' => $invoice->id,
            'status' => 'sent',
            'call_id' => 'call-123',
        ]);
    }

    public function test_outside_the_window_the_call_is_deferred_to_the_next_opening(): void
    {
        $this->fakeGateway();
        $this->at('2026-11-25 03:00');
        [, $client] = $this->createClientWithUser();

        event(new InvoiceCreated($this->makeInvoice($client)));

        Http::assertNothingSent();
        $this->assertDatabaseHas('voice_logs', ['status' => 'skipped', 'reason' => 'Outside the calling window.']);

        $retry = VoiceRetry::first();
        $this->assertNotNull($retry);
        $this->assertSame('09:00', $retry->next_attempt_at->copy()->setTimezone('Asia/Dhaka')->format('H:i'));
    }

    public function test_a_refused_call_is_retried_by_the_scheduled_command(): void
    {
        $this->at('2026-11-25 11:00');
        [, $client] = $this->createClientWithUser();
        $invoice = $this->makeInvoice($client);

        $this->callOk = false;
        $this->fakeGateway();
        event(new InvoiceCreated($invoice));

        $retry = VoiceRetry::first();
        $this->assertNotNull($retry);
        $this->assertSame(1, (int) $retry->attempt);
        $this->assertDatabaseHas('voice_logs', ['status' => 'failed', 'reason' => 'Audio not approved']);

        // Still inside the interval: nothing happens.
        $this->artisan('pnlcs:voice-retries')->assertExitCode(0);
        $this->assertSame(1, VoiceLog::where('status', 'failed')->count());

        // Fifteen minutes later the gateway accepts it.
        $this->callOk = true;
        $this->at('2026-11-25 11:16');

        $this->artisan('pnlcs:voice-retries')->assertExitCode(0);

        $this->assertSame('sent', VoiceRetry::first()->status);
        $this->assertDatabaseHas('voice_logs', ['status' => 'sent', 'attempt' => 2]);
    }

    public function test_a_product_group_uses_its_own_recording(): void
    {
        $this->fakeGateway();
        $this->at('2026-11-25 15:00');
        [, $client] = $this->createClientWithUser();

        $group = ProductGroup::create(['name' => 'Voice Hosting '.uniqid(), 'slug' => 'voice-'.uniqid()]);
        $product = Product::create([
            'group_id' => $group->id,
            'name' => 'Voice Plan '.uniqid(),
            'slug' => 'voice-plan-'.uniqid(),
            'type' => 'hosting',
            'pay_type' => 'recurring',
        ]);
        $service = Service::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'domain' => 'voice-'.uniqid().'.example.com',
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'amount' => 500,
        ]);

        VoiceGroupOverride::create([
            'group_id' => $group->id,
            'event_key' => 'service_suspended',
            'audio_id' => '999',
            'caller_id' => '369',
            'is_enabled' => true,
        ]);

        VoiceEvent::where('event_key', 'service_suspended')->update(['audio_id' => '427', 'is_enabled' => true]);

        event(new ServiceSuspended($service, 'Overdue'));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/calls')
            && (string) $request['audio_id'] === '999'
            && (int) $request['caller_id'] === 369);
    }

    public function test_reminder_calls_fire_once_per_invoice_and_stage(): void
    {
        $this->fakeGateway();
        $this->at('2026-11-25 11:00');
        [, $client] = $this->createClientWithUser();

        VoiceEvent::where('event_key', 'first_payment_reminder')->update(['audio_id' => '427', 'is_enabled' => true]);

        // Two days overdue: the first reminder.
        $invoice = $this->makeInvoice($client, 'overdue', -2);

        $this->artisan('pnlcs:voice-reminders')->assertExitCode(0);
        $this->artisan('pnlcs:voice-reminders')->assertExitCode(0);

        $this->assertSame(1, VoiceLog::where('invoice_id', $invoice->id)
            ->where('event_key', 'first_payment_reminder')->count());
    }

    public function test_the_window_helper_opens_at_nine_dhaka(): void
    {
        $dispatcher = app(VoiceDispatcher::class);

        $this->at('2026-11-25 08:59');
        $this->assertFalse($dispatcher->windowOpen());

        $this->at('2026-11-25 09:00');
        $this->assertTrue($dispatcher->windowOpen());

        $this->at('2026-11-25 21:01');
        $this->assertFalse($dispatcher->windowOpen());
    }
}

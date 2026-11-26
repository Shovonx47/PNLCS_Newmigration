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
use Modules\Addons\SoftcentsSms\Models\SmsLog;
use Modules\Addons\SoftcentsSms\Models\SmsSetting;
use Modules\Addons\SoftcentsSms\Models\SmsTemplate;
use Modules\Addons\SoftcentsSms\Services\OtpService;
use Modules\Addons\SoftcentsSms\SoftcentsSmsModule;
use Tests\TestCase;

/**
 * Phase 3 - Softcents SMS.
 *
 * The gateway is faked (no real message is paid for in a test), but everything
 * on this side of it is real: the addon is discovered and activated the way an
 * operator does it, the templates are the operator's own 29, and the OTP code
 * is read back out of the request the gateway would have received.
 */
class Phase3SoftcentsSmsTest extends TestCase
{
    private static string $lastOtpCode = '';

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(
            ['code' => 'BDT'],
            ['prefix' => '৳', 'suffix' => ' BDT', 'rate' => 1.00000, 'is_default' => true]
        );

        Setting::set('addon_softcents_sms_active', '1', 'addons');

        app()->forgetInstance(AddonManager::class);
        (new AddonServiceProvider(app()))->boot();
        app('router')->getRoutes()->refreshNameLookups();

        // require, not require_once: each test gets a fresh HookManager, so a
        // file loaded only by the first test leaves the rest without hooks.
        $hooks = base_path('modules/Addons/SoftcentsSms/hooks.php');
        if (is_file($hooks)) {
            require $hooks;
        }

        app(SoftcentsSmsModule::class)->seedTemplates();

        SmsSetting::current()->fill([
            'client_id' => '000016',
            'client_secret' => 'test-secret',
            'sender_id' => '8809617777121',
            'otp_enabled' => true,
            'otp_length' => 6,
            'otp_expiry_minutes' => 5,
            'otp_resend_cooldown' => 0,
            'otp_max_per_hour' => 10,
            'otp_template' => 'Your {companyname} code is {otp}. Valid {minutes} minutes.',
        ])->save();

        $this->assertTrue(SmsSetting::current()->configured(), 'SMS settings should persist for the test');
    }

    private function fakeGateway(bool $sendOk = true): void
    {
        Http::fake(function ($request) use ($sendOk) {
            $url = $request->url();

            if (str_contains($url, 'check-balance')) {
                return Http::response(['balance' => '4821.50']);
            }

            $contents = urldecode((string) ($request['contents'] ?? ''));
            if (preg_match('/\b(\d{6})\b/', $contents, $m)) {
                self::$lastOtpCode = $m[1];
            }

            return $sendOk
                ? Http::response(['status' => '200', 'message' => 'Sent'])
                : Http::response(['status' => '500', 'message' => 'Insufficient balance'], 500);
        });
    }

    private function createClientWithUser(string $phone = '01711223344'): array
    {
        $email = 'sms.'.uniqid().'@example.com';

        $user = User::forceCreate([
            'first_name' => 'Karim',
            'last_name' => 'Ahmed',
            'email' => $email,
            'password' => Hash::make('SecretPass123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => 'Karim',
            'last_name' => 'Ahmed',
            'email' => $email,
            'phone_number' => $phone,
            'address1' => 'Gulshan 1',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1212',
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
            'invoice_num' => 'INV-S'.random_int(10000, 99999),
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'subtotal' => 1200,
            'tax' => 0,
            'total' => 1200,
            'status' => 'unpaid',
            'type' => 'vat',
        ]);
    }

    public function test_the_addon_is_discovered_activated_and_installs_the_operators_templates(): void
    {
        $manager = app(AddonManager::class);

        $this->assertNotNull($manager->find('softcents_sms'));
        $this->assertTrue($manager->isActive('softcents_sms'));

        // 29 templates came from the legacy install, not from a placeholder.
        $this->assertSame(29, SmsTemplate::count());
        $this->assertTrue(SmsTemplate::where('name', 'InvoiceCreated')->exists());
        $this->assertTrue(SmsTemplate::where('name', 'InvoicePaid')->exists());
        $this->assertTrue(SmsTemplate::where('name', 'TicketUserReplyAdmin')->exists());

        $admin = Admin::factory()->create(['role_id' => AdminRole::factory()->fullAdmin()->create()->id]);

        $this->fakeGateway();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.extensions.softcents-sms.index'))
            ->assertOk()
            ->assertSee('Softcents SMS')
            ->assertSee('4821.50');
    }

    public function test_registration_otp_is_sent_verified_and_required(): void
    {
        $this->fakeGateway();

        // Without a verified number the registration is refused.
        $this->post(route('client.register.submit'), $this->registrationPayload('01711223345'))
            ->assertSessionHasErrors('phone_number');

        $this->assertDatabaseMissing('users', ['email' => 'otp.check@example.com']);

        // Ask for a code: it leaves for the gateway and the code is readable.
        $this->postJson(route('client.otp.send'), ['phone' => '01711223345', 'prefix' => ''])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertMatchesRegularExpression('/^\d{6}$/', self::$lastOtpCode);

        // A wrong code changes nothing.
        $this->postJson(route('client.otp.verify'), ['phone' => '01711223345', 'otp' => '000000'])
            ->assertOk()
            ->assertJson(['status' => 'error']);

        $this->postJson(route('client.otp.verify'), ['phone' => '01711223345', 'otp' => self::$lastOtpCode])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertSame('8801711223345', session(OtpService::SESSION_PHONE));

        // A different number is still refused even though this session is verified.
        $other = $this->registrationPayload('01719999999');
        $this->post(route('client.register.submit'), $other)->assertSessionHasErrors('phone_number');

        // The verified one goes through.
        $this->post(route('client.register.submit'), $this->registrationPayload('01711223345'))
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'otp.check@example.com']);

        // And the code is spent.
        $this->assertSame('', (string) session(OtpService::SESSION_PHONE));
    }

    public function test_an_invoice_created_event_sends_the_template_with_a_signed_pay_link(): void
    {
        $this->fakeGateway();
        [, $client] = $this->createClientWithUser('01711223344');
        $invoice = $this->makeInvoice($client);

        event(new InvoiceCreated($invoice));

        $log = SmsLog::where('template_name', 'InvoiceCreated')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
        $this->assertSame('8801711223344', $log->to_number);
        $this->assertStringContainsString('/pay/'.$invoice->id.'?sig=', (string) $log->message);
        $this->assertStringContainsString($invoice->invoice_num, (string) $log->message);
        $this->assertStringContainsString('1,200.00', (string) $log->message);
    }

    public function test_a_refused_send_is_logged_with_its_reason_and_does_not_throw(): void
    {
        $this->fakeGateway(sendOk: false);
        [, $client] = $this->createClientWithUser('01711223344');
        $invoice = $this->makeInvoice($client);

        // A gateway outage must not break the event that fired it.
        event(new InvoiceCreated($invoice));

        $log = SmsLog::where('template_name', 'InvoiceCreated')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('failed', $log->status);
        $this->assertSame('Insufficient balance', $log->error);
    }

    public function test_a_switched_off_template_sends_nothing(): void
    {
        $this->fakeGateway();
        [, $client] = $this->createClientWithUser();

        SmsTemplate::where('name', 'InvoiceCreated')->update(['active' => false]);

        event(new InvoiceCreated($this->makeInvoice($client)));

        $this->assertDatabaseMissing('softcents_sms_logs', ['template_name' => 'InvoiceCreated']);
    }

    private function registrationPayload(string $phone): array
    {
        return [
            'first_name' => 'Otp',
            'last_name' => 'Check',
            'email' => 'otp.check@example.com',
            'password' => 'SecretPass123!',
            'password_confirmation' => 'SecretPass123!',
            'address1' => 'Road 1',
            'city' => 'Dhaka',
            'postcode' => '1200',
            'country' => 'BD',
            'phone_number' => $phone,
            'phone_prefix' => '+880',
            'tos' => '1',
        ];
    }
}

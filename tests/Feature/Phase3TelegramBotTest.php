<?php

namespace Tests\Feature;

use App\Events\InvoiceCreated;
use App\Models\AddonSetting;
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
use Illuminate\Testing\TestResponse;
use Modules\Addons\TelegramBot\Models\TelegramClient;
use Modules\Addons\TelegramBot\Models\TelegramMessage;
use Tests\TestCase;

/**
 * Phase 3 - the client Telegram bot.
 *
 * The Bot API is faked. The parts that matter are real: the connect token is
 * one-use, the webhook refuses an update without the secret, and an alert only
 * reaches a connected, unmuted customer.
 */
class Phase3TelegramBotTest extends TestCase
{
    private const SECRET = 'sc_test_hook_secret';

    protected function setUp(): void
    {
        parent::setUp();

        Currency::firstOrCreate(
            ['code' => 'BDT'],
            ['prefix' => '৳', 'suffix' => ' BDT', 'rate' => 1.00000, 'is_default' => true]
        );

        Setting::set('addon_telegram_bot_active', '1', 'addons');

        app()->forgetInstance(AddonManager::class);
        (new AddonServiceProvider(app()))->boot();
        app('router')->getRoutes()->refreshNameLookups();

        $hooks = base_path('modules/Addons/TelegramBot/hooks.php');
        if (is_file($hooks)) {
            require $hooks;
        }

        AddonSetting::setSetting('telegram_bot', 'bot_token', '123456:test-token');
        AddonSetting::setSetting('telegram_bot', 'bot_username', 'SCWhmcs_bot');
        AddonSetting::setSetting('telegram_bot', 'webhook_secret', self::SECRET);
        AddonSetting::setSetting('telegram_bot', 'enabled', '1');
        AddonSetting::setSetting('telegram_bot', 'notify_invoices', '1');
        AddonSetting::setSetting('telegram_bot', 'notify_services', '1');
        AddonSetting::setSetting('telegram_bot', 'notify_tickets', '1');
    }

    private function fakeTelegram(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
    }

    private function createClientWithUser(): array
    {
        $email = 'tg.'.uniqid().'@example.com';

        $user = User::forceCreate([
            'first_name' => 'Sadia',
            'last_name' => 'Islam',
            'email' => $email,
            'password' => Hash::make('SecretPass123!'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $client = Client::forceCreate([
            'first_name' => 'Sadia',
            'last_name' => 'Islam',
            'email' => $email,
            'phone_number' => '01711223344',
            'address1' => 'Uttara',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postcode' => '1230',
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
            'invoice_num' => 'INV-TG'.random_int(1000, 9999),
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(6)->toDateString(),
            'subtotal' => 750,
            'tax' => 0,
            'total' => 750,
            'status' => 'unpaid',
            'type' => 'vat',
        ]);
    }

    private function update(string $text, string $chatId = '5550001'): TestResponse
    {
        return $this->withHeader('X-Telegram-Bot-Api-Secret-Token', self::SECRET)
            ->postJson(route('telegram.webhook'), [
                'update_id' => random_int(1, 99999),
                'message' => [
                    'message_id' => 1,
                    'chat' => ['id' => $chatId, 'type' => 'private'],
                    'from' => ['id' => $chatId, 'username' => 'sadia', 'first_name' => 'Sadia'],
                    'text' => $text,
                ],
            ]);
    }

    public function test_the_addon_is_active_and_the_admin_screen_works(): void
    {
        $this->assertNotNull(app(AddonManager::class)->find('telegram_bot'));
        $this->assertTrue(app(AddonManager::class)->isActive('telegram_bot'));

        $admin = Admin::factory()->create(['role_id' => AdminRole::factory()->fullAdmin()->create()->id]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.extensions.telegram.index'))
            ->assertOk()
            ->assertSee('Telegram Client Bot')
            ->assertSee('SCWhmcs_bot');
    }

    public function test_connect_token_is_one_use_and_binds_the_chat(): void
    {
        $this->fakeTelegram();
        [$user, $client] = $this->createClientWithUser();

        // The dashboard's connect button redirects to t.me with a token.
        $response = $this->actingAs($user)->post(route('client.telegram.connect'));
        $response->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://t.me/SCWhmcs_bot?start=', (string) $location);

        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);
        $token = $query['start'];
        $this->assertNotSame('', $token);

        // Bogus token: nothing binds, the customer is told to go back.
        $this->update('/start not-a-real-token')->assertOk();
        $this->assertNull(TelegramClient::where('client_id', $client->id)->first()->chat_id);

        // The real one binds it.
        $this->update('/start '.$token)->assertOk();

        $row = TelegramClient::where('client_id', $client->id)->first();
        $this->assertSame('5550001', $row->chat_id);
        $this->assertNotNull($row->connected_at);
        $this->assertNotSame($token, $row->link_token); // spent

        // Replaying the spent token does nothing.
        $this->update('/start '.$token, '9999999')->assertOk();
        $this->assertSame('5550001', TelegramClient::where('client_id', $client->id)->first()->chat_id);
    }

    public function test_the_webhook_refuses_an_update_without_the_secret(): void
    {
        $this->postJson(route('telegram.webhook'), [
            'message' => ['chat' => ['id' => '1'], 'text' => '/start abc'],
        ])->assertForbidden();

        $this->assertSame(0, TelegramClient::whereNotNull('chat_id')->count());
    }

    public function test_invoices_command_answers_with_signed_links_and_mute_is_honoured(): void
    {
        $this->fakeTelegram();
        [$user, $client] = $this->createClientWithUser();
        $invoice = $this->makeInvoice($client);

        $this->actingAs($user)->post(route('client.telegram.connect'));
        $row = TelegramClient::where('client_id', $client->id)->first();
        $this->update('/start '.$row->link_token);

        $this->update('/invoices')->assertOk();

        Http::assertSent(function ($request) use ($invoice) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains((string) ($request['text'] ?? ''), '/pay/'.$invoice->id.'?sig=');
        });

        // /mute stops alerts, /unmute brings them back.
        $this->update('/mute');
        $this->assertFalse((bool) TelegramClient::where('client_id', $client->id)->first()->notify_enabled);

        $this->update('/unmute');
        $this->assertTrue((bool) TelegramClient::where('client_id', $client->id)->first()->notify_enabled);
    }

    public function test_an_invoice_alert_reaches_only_a_connected_unmuted_customer(): void
    {
        $this->fakeTelegram();
        [$user, $client] = $this->createClientWithUser();
        $invoice = $this->makeInvoice($client);

        // Not connected yet: the hook sends nothing.
        event(new InvoiceCreated($invoice));
        Http::assertNothingSent();

        // Connect it.
        $this->actingAs($user)->post(route('client.telegram.connect'));
        $row = TelegramClient::where('client_id', $client->id)->first();
        $this->update('/start '.$row->link_token);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);

        event(new InvoiceCreated($invoice));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === '5550001'
            && str_contains((string) ($request['text'] ?? ''), '/pay/'.$invoice->id.'?sig='));

        $this->assertSame(1, TelegramMessage::where('event', 'InvoiceCreated')->where('status', 'sent')->count());

        // Muted: nothing more goes out.
        TelegramClient::where('client_id', $client->id)->update(['notify_enabled' => false]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        event(new InvoiceCreated($this->makeInvoice($client)));

        Http::assertNothingSent();
    }
}

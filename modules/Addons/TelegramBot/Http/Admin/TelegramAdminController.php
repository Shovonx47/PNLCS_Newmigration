<?php

namespace Modules\Addons\TelegramBot\Http\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddonSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Addons\TelegramBot\Models\TelegramClient;
use Modules\Addons\TelegramBot\Models\TelegramMessage;
use Modules\Addons\TelegramBot\Services\TelegramBot;
use Modules\Addons\TelegramBot\Services\TelegramLink;

/**
 * Extensions -> Telegram Bot.
 *
 * The token and the switches, who is connected, and what was sent. The legacy
 * addon managed this from a WHMCS addon page with a table named after the
 * module; here the same information sits beside the admin notifier it extends.
 */
class TelegramAdminController extends Controller
{
    public function index()
    {
        $bot = app(TelegramBot::class);

        return view('telegrambot::admin.index', [
            'token' => AddonSetting::getSetting('telegram_bot', 'bot_token', ''),
            'username' => $bot->username(),
            'secret' => $bot->secret(),
            'enabled' => $bot->enabled(),
            'notifyInvoices' => AddonSetting::getSetting('telegram_bot', 'notify_invoices', '0') === '1',
            'notifyServices' => AddonSetting::getSetting('telegram_bot', 'notify_services', '0') === '1',
            'notifyTickets' => AddonSetting::getSetting('telegram_bot', 'notify_tickets', '0') === '1',
            'connected' => TelegramClient::whereNotNull('chat_id')->count(),
            'rows' => TelegramClient::with('client')->orderByDesc('id')->limit(100)->get(),
            'messages' => TelegramMessage::latest('id')->limit(50)->get(),
            'webhookUrl' => url('/telegram/webhook'),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bot_username' => ['nullable', 'string', 'max:100'],
            'webhook_secret' => ['nullable', 'string', 'max:100'],
        ]);

        if (trim((string) $request->input('bot_token')) !== '') {
            AddonSetting::setSetting('telegram_bot', 'bot_token', trim((string) $request->input('bot_token')));
        }

        AddonSetting::setSetting('telegram_bot', 'bot_username', trim((string) ($validated['bot_username'] ?? '')));
        AddonSetting::setSetting('telegram_bot', 'webhook_secret', trim((string) ($validated['webhook_secret'] ?? '')));
        AddonSetting::setSetting('telegram_bot', 'enabled', $request->boolean('enabled') ? '1' : '0');
        AddonSetting::setSetting('telegram_bot', 'notify_invoices', $request->boolean('notify_invoices') ? '1' : '0');
        AddonSetting::setSetting('telegram_bot', 'notify_services', $request->boolean('notify_services') ? '1' : '0');
        AddonSetting::setSetting('telegram_bot', 'notify_tickets', $request->boolean('notify_tickets') ? '1' : '0');

        return back()->with('success', 'Telegram settings saved.');
    }

    public function registerWebhook(): RedirectResponse
    {
        $result = app(TelegramBot::class)->registerWebhook(url('/telegram/webhook'));

        return back()->with($result['ok'] ? 'success' : 'error', 'Telegram webhook: '.$result['message']);
    }

    public function test(TelegramClient $telegramClient): RedirectResponse
    {
        $result = app(TelegramBot::class)->send(
            (string) $telegramClient->chat_id,
            '<b>Test message</b>'."\nSent from the Softcents admin panel."
        );

        TelegramMessage::create([
            'client_id' => $telegramClient->client_id,
            'chat_id' => $telegramClient->chat_id,
            'direction' => 'out',
            'event' => 'AdminTest',
            'body' => 'Test message',
            'status' => $result['ok'] ? 'sent' : 'failed',
            'error' => $result['ok'] ? null : mb_substr($result['error'], 0, 480),
        ]);

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Test sent.' : 'Telegram refused: '.$result['error']);
    }

    public function disconnect(TelegramClient $telegramClient): RedirectResponse
    {
        $client = $telegramClient->client;

        if ($client) {
            app(TelegramLink::class)->disconnect($client);
        }

        return back()->with('success', 'Chat disconnected.');
    }
}

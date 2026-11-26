<?php

namespace Modules\Addons\TelegramBot\Http;

use App\Http\Controllers\Concerns\ResolvesClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Addons\TelegramBot\Models\TelegramClient;
use Modules\Addons\TelegramBot\Models\TelegramMessage;
use Modules\Addons\TelegramBot\Services\TelegramBot;
use Modules\Addons\TelegramBot\Services\TelegramLink;

/**
 * The customer's side: connect, disconnect, send a test.
 *
 * The connect button does not print a token on the page - it redirects straight
 * to t.me, so the token only ever exists in the address bar of Telegram's own
 * site.
 */
class ClientTelegramController extends Controller
{
    use ResolvesClient;

    public function index()
    {
        $client = $this->currentClient();
        abort_if($client === null, 403);

        $row = app(TelegramLink::class)->forClient($client);
        $bot = app(TelegramBot::class);

        return view('telegrambot::client.index', [
            'row' => $row,
            'bot' => $bot,
            'messages' => TelegramMessage::where('client_id', $client->id)
                ->where('direction', 'out')
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function connect(): RedirectResponse
    {
        $client = $this->currentClient();
        abort_if($client === null, 403);

        $bot = app(TelegramBot::class);

        if (! $bot->enabled() || $bot->username() === '') {
            return back()->with('error', 'Telegram is not available right now.');
        }

        return redirect()->away(app(TelegramLink::class)->connectUrl($client));
    }

    public function disconnect(): RedirectResponse
    {
        $client = $this->currentClient();
        abort_if($client === null, 403);

        app(TelegramLink::class)->disconnect($client);

        return back()->with('success', 'Telegram disconnected.');
    }

    public function test(): RedirectResponse
    {
        $client = $this->currentClient();
        abort_if($client === null, 403);

        $row = TelegramClient::where('client_id', $client->id)->first();

        if (! $row || ! $row->connected()) {
            return back()->with('error', 'Connect Telegram first.');
        }

        $result = app(TelegramBot::class)->send(
            (string) $row->chat_id,
            '<b>Test message</b>'."\nThis is your Softcents account bot. Alerts will arrive here."
        );

        TelegramMessage::create([
            'client_id' => $client->id,
            'chat_id' => $row->chat_id,
            'direction' => 'out',
            'event' => 'Test',
            'body' => 'Test message',
            'status' => $result['ok'] ? 'sent' : 'failed',
            'error' => $result['ok'] ? null : mb_substr($result['error'], 0, 480),
        ]);

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Test message sent.' : 'Telegram refused the message: '.$result['error']);
    }

    public function toggle(): RedirectResponse
    {
        $client = $this->currentClient();
        abort_if($client === null, 403);

        $row = TelegramClient::where('client_id', $client->id)->firstOrFail();
        $row->forceFill(['notify_enabled' => ! $row->notify_enabled])->save();

        return back()->with('success', $row->notify_enabled ? 'Alerts are on.' : 'Alerts are muted.');
    }
}

<?php

namespace Modules\Addons\TelegramBot\Services;

use App\Models\Client;
use Illuminate\Support\Str;
use Modules\Addons\TelegramBot\Models\TelegramClient;

/**
 * How a chat becomes this customer's chat.
 *
 * A token is made for the customer in the dashboard, and the bot only accepts
 * it once, from the Telegram account that sends /start with it. The legacy
 * addon kept the token after connecting and matched on it forever; here the
 * token is replaced on connect, so a leaked old link cannot re-point a
 * customer's alerts at somebody else's chat.
 */
class TelegramLink
{
    public function forClient(Client $client): TelegramClient
    {
        $row = TelegramClient::firstOrCreate(
            ['client_id' => $client->id],
            ['link_token' => $this->newToken(), 'notify_enabled' => true]
        );

        if ((string) $row->link_token === '') {
            $row->forceFill(['link_token' => $this->newToken()])->save();
        }

        return $row;
    }

    public function connectUrl(Client $client): string
    {
        return app(TelegramBot::class)->deepLink($this->forClient($client)->link_token);
    }

    /**
     * @return array{ok: bool, message: string, client: ?TelegramClient}
     */
    public function bind(string $token, string $chatId, ?string $username, ?string $name): array
    {
        $row = TelegramClient::where('link_token', $token)->first();

        if (! $row) {
            return ['ok' => false, 'message' => 'This connect link is not valid. Open your dashboard and try again.', 'client' => null];
        }

        // One chat belongs to one customer: a second account connecting the
        // same Telegram account takes it away from the first.
        TelegramClient::where('chat_id', $chatId)->where('id', '!=', $row->id)
            ->update(['chat_id' => null, 'connected_at' => null]);

        $row->forceFill([
            'chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_name' => $name,
            // A fresh token, so this link cannot be used twice.
            'link_token' => $this->newToken(),
            'connected_at' => now(),
            'last_seen_at' => now(),
        ])->save();

        return ['ok' => true, 'message' => 'Connected.', 'client' => $row];
    }

    public function disconnect(Client $client): void
    {
        TelegramClient::where('client_id', $client->id)->update([
            'chat_id' => null,
            'connected_at' => null,
            'link_token' => $this->newToken(),
        ]);
    }

    private function newToken(): string
    {
        return Str::random(32);
    }
}

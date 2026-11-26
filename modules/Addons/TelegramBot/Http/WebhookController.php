<?php

namespace Modules\Addons\TelegramBot\Http;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Service;
use App\Support\DirectPayLink;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Addons\TelegramBot\Models\TelegramClient;
use Modules\Addons\TelegramBot\Models\TelegramMessage;
use Modules\Addons\TelegramBot\Services\TelegramBot;
use Modules\Addons\TelegramBot\Services\TelegramLink;

/**
 * POST /telegram/webhook
 *
 * Telegram's updates. The secret in the header is the one setWebhook was given,
 * so a stranger who finds the address cannot post commands as a customer. Every
 * answer is a plain reply; nothing here trusts the message for anything but its
 * chat id and its text.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBot $bot, TelegramLink $link): Response
    {
        $secret = $bot->secret();

        if ($secret !== '' && ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response('Forbidden', 403);
        }

        $message = $request->input('message') ?? $request->input('edited_message');
        $text = trim((string) ($message['text'] ?? ''));
        $chatId = (string) ($message['chat']['id'] ?? '');

        if ($chatId === '' || $text === '') {
            // A sticker, a photo, a callback: acknowledged, not answered.
            return response('ok');
        }

        $row = TelegramClient::where('chat_id', $chatId)->first();
        $row?->forceFill(['last_seen_at' => now()])->save();

        TelegramMessage::create([
            'client_id' => $row?->client_id,
            'chat_id' => $chatId,
            'direction' => 'in',
            'event' => 'message',
            'body' => $text,
            'status' => 'received',
        ]);

        [$command, $argument] = array_pad(preg_split('/\s+/', $text, 2) ?: [], 2, '');
        $command = strtolower((string) preg_replace('/@\w+$/', '', (string) $command));

        $reply = match ($command) {
            '/start' => $this->start($bot, $link, (string) $argument, $chatId, $message, $row),
            '/status' => $this->status($row),
            '/invoices' => $this->invoices($row),
            '/mute' => $this->mute($row, false),
            '/unmute' => $this->mute($row, true),
            default => $this->help(),
        };

        $bot->send($chatId, $reply);

        return response('ok');
    }

    private function start(TelegramBot $bot, TelegramLink $link, string $token, string $chatId, array $message, ?TelegramClient $row): string
    {
        if ($token === '') {
            return $row && $row->connected()
                ? 'You are connected. Use /invoices, /status, /mute or /unmute.'
                : 'Open your dashboard and press "Connect Telegram" to link this chat.';
        }

        $result = $link->bind(
            $token,
            $chatId,
            $message['from']['username'] ?? null,
            trim(($message['from']['first_name'] ?? '').' '.($message['from']['last_name'] ?? ''))
        );

        return $result['ok']
            ? "Connected. You will get invoice and service updates here.\n\n/invoices — open invoices\n/status — your account\n/mute — stop these alerts"
            : e($result['message']);
    }

    private function status(?TelegramClient $row): string
    {
        if (! $row || ! $row->connected()) {
            return $this->help();
        }

        $client = $row->client;
        if (! $client) {
            return 'That account no longer exists.';
        }

        $open = Invoice::where('client_id', $client->id)
            ->whereIn('status', ['unpaid', 'overdue', 'partially_paid'])->count();
        $active = Service::where('client_id', $client->id)->where('status', 'active')->count();

        return '<b>'.e(trim($client->first_name.' '.$client->last_name))."</b>\n"
            ."Open invoices: {$open}\n"
            ."Active services: {$active}\n"
            .'Alerts: '.($row->notify_enabled ? 'on' : 'muted');
    }

    private function invoices(?TelegramClient $row): string
    {
        if (! $row || ! $row->connected() || ! $row->client) {
            return $this->help();
        }

        $invoices = Invoice::where('client_id', $row->client_id)
            ->whereIn('status', ['unpaid', 'overdue', 'partially_paid'])
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        if ($invoices->isEmpty()) {
            return 'You have no open invoices.';
        }

        $lines = ['<b>Open invoices</b>'];
        foreach ($invoices as $invoice) {
            $lines[] = '#'.e((string) ($invoice->invoice_num ?: $invoice->id))
                .' — '.number_format((float) $invoice->total, 2)
                .' — <a href="'.e(DirectPayLink::url($invoice)).'">pay</a>';
        }

        return implode("\n", $lines);
    }

    private function mute(?TelegramClient $row, bool $notify): string
    {
        if (! $row || ! $row->connected()) {
            return $this->help();
        }

        $row->forceFill(['notify_enabled' => $notify])->save();

        return $notify ? 'Alerts are on again.' : 'Alerts are muted. Send /unmute to turn them back on.';
    }

    private function help(): string
    {
        return "This bot sends your Softcents invoice and service updates.\n\n"
            ."Connect it from your dashboard: Client Area → Connect Telegram.\n\n"
            .'/invoices, /status, /mute, /unmute';
    }
}

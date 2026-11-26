<?php

namespace Modules\Addons\TelegramBot\Services;

use App\Models\AddonSetting;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Ticket;
use App\Support\DirectPayLink;
use App\Support\NotificationVariables;
use Modules\Addons\TelegramBot\Models\TelegramClient;
use Modules\Addons\TelegramBot\Models\TelegramMessage;

/**
 * What the customer's bot says to them.
 *
 * Three short alerts the legacy addon sent over WhatsApp and SMS too, here in
 * Telegram and with the one thing a chat is good at: a signed payment link that
 * opens the invoice in one tap. The switches are checked per kind, so an
 * operator can keep invoice alerts and turn off ticket ones.
 */
class TelegramAlerts
{
    public function enabledFor(string $kind): bool
    {
        $bot = app(TelegramBot::class);

        if (! $bot->enabled()) {
            return false;
        }

        return AddonSetting::getSetting('telegram_bot', 'notify_'.$kind, '0') === '1';
    }

    /**
     * @param  array<string, mixed>  $vars
     */
    public function sendForClient(?Client $client, string $kind, string $html, string $event = ''): ?TelegramMessage
    {
        if (! $client || ! $this->enabledFor($kind)) {
            return null;
        }

        $row = TelegramClient::where('client_id', $client->id)->first();

        if (! $row || ! $row->connected() || ! $row->notify_enabled) {
            return null;
        }

        $result = app(TelegramBot::class)->send((string) $row->chat_id, $html);

        return TelegramMessage::create([
            'client_id' => $client->id,
            'chat_id' => $row->chat_id,
            'direction' => 'out',
            'event' => $event ?: $kind,
            'body' => $html,
            'status' => $result['ok'] ? 'sent' : 'failed',
            'error' => $result['ok'] ? null : mb_substr($result['error'], 0, 480),
        ]);
    }

    /** The invoice alerts, in one shape: what, how much, and a pay button. */
    public function invoice(Client $client, Invoice $invoice, string $event): ?TelegramMessage
    {
        $variables = NotificationVariables::build(['client' => $client, 'invoice' => $invoice]);
        $pay = DirectPayLink::url($invoice);

        $html = match ($event) {
            'InvoiceCreated' => '<b>New invoice #'.e((string) $variables['invoiceid'])."</b>\n"
                .'Amount: '.e($this->money($invoice))."\n"
                .'Due: '.e((string) $variables['duedate'])."\n\n"
                .'<a href="'.e($pay).'">Open and pay</a>',
            'InvoicePaid' => "<b>Payment received</b>\n"
                .'Invoice #'.e((string) $variables['invoiceid']).' — '.e($this->money($invoice))."\n"
                ."Thank you. Your receipt:\n".'<a href="'.e($pay).'">View invoice</a>',
            default => "<b>Invoice update</b>\n".'<a href="'.e($pay).'">View invoice</a>',
        };

        return $this->sendForClient($client, 'invoices', $html, $event);
    }

    public function service(Client $client, Service $service, string $event): ?TelegramMessage
    {
        $domain = e((string) ($service->domain ?: $service->product->name ?? 'your service'));

        $html = match ($event) {
            'ServiceSuspended' => "<b>Service suspended</b>\n{$domain}\nPay the open invoice to restore it.",
            'ServiceActivated' => "<b>Service active</b>\n{$domain} is ready to use.",
            'ServiceUnsuspended' => "<b>Service restored</b>\n{$domain} is active again.",
            'ServiceTerminated' => "<b>Service terminated</b>\n{$domain} has been removed.",
            default => "<b>Service update</b>\n{$domain}",
        };

        return $this->sendForClient($client, 'services', $html, $event);
    }

    public function ticket(Client $client, Ticket $ticket, bool $staffReply): ?TelegramMessage
    {
        if (! $staffReply) {
            return null;
        }

        $html = "<b>Support replied</b>\nTicket #".e((string) ($ticket->tid ?: $ticket->id))
            .' — '.e((string) $ticket->title);

        return $this->sendForClient($client, 'tickets', $html, 'TicketReplied');
    }

    private function money(Invoice $invoice): string
    {
        $currency = $invoice->client?->currency_id
            ? Currency::find($invoice->client->currency_id)
            : Currency::where('is_default', true)->first();

        return ($currency?->prefix ?? '').number_format((float) $invoice->total, 2).($currency?->code ? ' '.$currency->code : '');
    }
}

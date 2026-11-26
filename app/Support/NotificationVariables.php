<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Support\Facades\Route;

/**
 * The {placeholders} the legacy templates were written with.
 *
 * The SMS, WhatsApp and Telegram addons all send the same operator-written
 * copy, and that copy says {firstname}, {invoiceid}, {amount}, {invoiceurl}
 * and so on. Building those values in one place is what keeps a paid-invoice
 * SMS and a paid-invoice WhatsApp saying the same truthful thing - above all
 * the URL, which is the signed payment link rather than a login page.
 */
final class NotificationVariables
{
    /**
     * @param  array<string, mixed>  $vars  the hook's parameters
     * @return array<string, string>
     */
    public static function build(array $vars): array
    {
        $invoice = $vars['invoice'] ?? null;
        $service = $vars['service'] ?? null;
        $ticket = $vars['ticket'] ?? null;
        $order = $vars['order'] ?? null;
        $domain = $vars['domain'] ?? null;
        $user = $vars['user'] ?? null;

        $client = self::clientFrom($vars, $invoice, $service, $ticket, $order, $user);

        $out = [
            'firstname' => (string) ($client?->first_name ?? ''),
            'lastname' => (string) ($client?->last_name ?? ''),
            'fullname' => trim((string) ($client?->first_name.' '.$client?->last_name)),
            'clientname' => trim((string) ($client?->first_name.' '.$client?->last_name)),
            'email' => (string) ($client?->email ?? $user?->email ?? ''),
            'companyname' => company_name(),
            'date' => now()->format(date_fmt()),
            'time' => now()->format('H:i'),
        ];

        if ($client) {
            $currency = $client->currency_id ? Currency::find($client->currency_id) : Currency::where('is_default', true)->first();
            $out['currency'] = (string) ($currency?->code ?? '');
            $out['currencysymbol'] = (string) ($currency?->prefix ?? '');
            $out['clienturl'] = self::route('client.home');
        }

        if ($invoice instanceof Invoice) {
            $out['invoiceid'] = (string) ($invoice->invoice_num ?: $invoice->id);
            $out['invoice_id'] = (string) $invoice->id;
            $out['amount'] = number_format((float) $invoice->total, 2);
            $out['total'] = $out['amount'];
            $out['duedate'] = $invoice->due_date?->format(date_fmt()) ?? '';
            $out['status'] = (string) $invoice->status;
            $out['paiddate'] = $invoice->date_paid?->format(date_fmt()) ?? now()->format(date_fmt());
            $out['paymentmethod'] = (string) ($invoice->payment_method ?? '');
            $out['invoiceurl'] = DirectPayLink::url($invoice);
            $out['invoice_link'] = $out['invoiceurl'];
            $out['overduedays'] = $invoice->due_date
                ? (string) max(0, (int) $invoice->due_date->startOfDay()->diffInDays(now()->startOfDay(), false))
                : '0';
        }

        if ($service instanceof Service) {
            $out['domain'] = (string) ($service->domain ?? '');
            $out['username'] = (string) ($service->username ?? '');
            $out['password'] = (string) ($service->password ?? '');
            $out['serverip'] = (string) ($service->server->hostname ?? '');
            $out['product'] = (string) ($service->product->name ?? '');
            $out['serviceurl'] = self::route('client.services.show', $service);
            $out['reason'] = (string) ($vars['reason'] ?? '');
        }

        if ($ticket instanceof Ticket) {
            $out['ticketid'] = (string) ($ticket->tid ?: $ticket->id);
            $out['subject'] = (string) ($ticket->title ?? '');
            $out['priority'] = (string) ($ticket->priority ?? '');
            $out['ticketstatus'] = (string) ($ticket->status ?? '');
            $out['ticketurl'] = self::route('client.tickets.show', $ticket);
            $out['reply'] = (string) ($vars['replyMessage'] ?? '');
        }

        if (is_object($order)) {
            $out['orderid'] = (string) ($order->id ?? '');
            $out['ordernumber'] = (string) ($order->order_num ?? $order->id ?? '');
        }

        if (is_object($domain)) {
            $out['domain'] = (string) ($domain->domain ?? $out['domain'] ?? '');
            $out['expirydate'] = isset($domain->expiry_date) && $domain->expiry_date
                ? $domain->expiry_date->format(date_fmt())
                : '';
            $out['days'] = isset($domain->expiry_date) && $domain->expiry_date
                ? (string) max(0, (int) now()->startOfDay()->diffInDays($domain->expiry_date->startOfDay(), false))
                : '0';
        }

        if ($user) {
            $out['ip'] = (string) ($vars['ipAddress'] ?? request()->ip());
        }

        return $out;
    }

    /**
     * Replace {name} for every name that has a value. A placeholder with no
     * value is left alone so a missing field is visible rather than silently
     * blank - the legacy addons did the same.
     *
     * @param  array<string, string>  $variables
     */
    public static function render(string $template, array $variables): string
    {
        foreach ($variables as $name => $value) {
            if ($value === '') {
                continue;
            }

            $template = str_replace('{'.$name.'}', $value, $template);
        }

        return $template;
    }

    private static function clientFrom(array $vars, mixed $invoice, mixed $service, mixed $ticket, mixed $order, mixed $user): ?Client
    {
        foreach ([$vars['client'] ?? null, $invoice, $service, $ticket, $order] as $candidate) {
            if ($candidate instanceof Client) {
                return $candidate;
            }

            $client = is_object($candidate) ? ($candidate->client ?? null) : null;

            if ($client instanceof Client) {
                return $client;
            }
        }

        if (is_object($user)) {
            return $user->clients()->first();
        }

        return null;
    }

    private static function route(string $name, mixed $parameter = null): string
    {
        if (! Route::has($name)) {
            return '';
        }

        try {
            return $parameter === null ? route($name) : route($name, $parameter);
        } catch (\Throwable) {
            return '';
        }
    }
}

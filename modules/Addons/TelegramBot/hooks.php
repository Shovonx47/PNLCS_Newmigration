<?php

use Illuminate\Support\Facades\View;
use Modules\Addons\TelegramBot\Services\TelegramAlerts;
use Modules\Addons\TelegramBot\Services\TelegramBot;

/*
 * Phase 3 - Telegram client alerts.
 *
 * The same events the admin notifier already covers, but addressed to the
 * customer and carrying the signed invoice link, which is the whole point of
 * a chat alert: one tap to pay.
 */

$alerts = fn () => app(TelegramAlerts::class);

add_hook('InvoiceCreated', 15, function (array $vars) use ($alerts) {
    $invoice = $vars['invoice'] ?? null;
    $client = $invoice?->client;

    return $client ? $alerts()->invoice($client, $invoice, 'InvoiceCreated') : null;
});

add_hook('InvoicePaid', 15, function (array $vars) use ($alerts) {
    $invoice = $vars['invoice'] ?? null;
    $client = $invoice?->client;

    return $client ? $alerts()->invoice($client, $invoice, 'InvoicePaid') : null;
});

foreach (['ServiceActivated', 'ServiceSuspended', 'ServiceUnsuspended', 'ServiceTerminated'] as $point) {
    add_hook($point, 15, function (array $vars) use ($alerts, $point) {
        $service = $vars['service'] ?? null;
        $client = $service?->client;

        return $client ? $alerts()->service($client, $service, $point) : null;
    });
}

add_hook('TicketReplied', 15, function (array $vars) use ($alerts) {
    $ticket = $vars['ticket'] ?? null;
    $client = $ticket?->client;

    if (! $client) {
        return null;
    }

    return $alerts()->ticket($client, $ticket, ! empty($vars['isStaffReply']));
});

/*
 * The card on the dashboard: connect, or the connected state and a way in.
 */
add_hook('ClientAreaHomePagePanels', 15, function () {
    if (! auth()->check()) {
        return null;
    }

    $bot = app(TelegramBot::class);

    if (! $bot->enabled()) {
        return null;
    }

    try {
        return View::make('telegrambot::card', ['bot' => $bot])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});

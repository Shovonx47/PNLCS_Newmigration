<?php

use Illuminate\Support\Facades\View;
use Modules\Addons\WhatsappPro\Models\WaSettings;
use Modules\Addons\WhatsappPro\Services\WaDispatcher;

/*
 * Phase 3 - WhatsApp.
 *
 * Every hook here only writes a queue row. The gateway is called by
 * pnlcs:whatsapp-queue a minute later, which is what the legacy addon's v6.4
 * rewrite was for: a customer's checkout must not wait on WhatsApp.
 *
 * Template names are the operator's own, so the copy and the switches they
 * already have keep working.
 */

$queue = function (string $template, array $vars) {
    try {
        return app(WaDispatcher::class)->queueTemplate($template, $vars);
    } catch (Throwable $e) {
        report($e);

        return null;
    }
};

add_hook('InvoiceCreated', 10, fn (array $vars) => $queue('InvoiceCreated', $vars));

// Payment reminders – fired by pnlcs:payment-reminders for each stage.
// 'InvoicePaymentReminder' covers pre-due stages; First/Second/Third cover
// the three overdue tiers. The $vars array always carries 'invoice', 'client',
// 'days' (negative = overdue), and 'type'.
add_hook('InvoicePaymentReminder', 10, fn (array $vars) => $queue('InvoicePaymentReminder', $vars));
add_hook('InvoicePaymentReminderFirst', 10, fn (array $vars) => $queue('InvoicePaymentReminderFirst', $vars));
add_hook('InvoicePaymentReminderSecond', 10, fn (array $vars) => $queue('InvoicePaymentReminderSecond', $vars));
add_hook('InvoicePaymentReminderThird', 10, fn (array $vars) => $queue('InvoicePaymentReminderThird', $vars));
add_hook('InvoicePaid', 10, fn (array $vars) => $queue('InvoicePaid', $vars));
add_hook('InvoiceCancelled', 10, fn (array $vars) => $queue('InvoiceCancelled', $vars));
add_hook('AcceptOrder', 10, fn (array $vars) => $queue('AcceptOrder', $vars));
add_hook('ClientAdd', 10, function (array $vars) use ($queue) {
    $queue('ClientAdd', $vars);

    return $queue('ClientAddAdmin', $vars);
});
add_hook('ClientLoggedIn', 10, fn (array $vars) => $queue('ClientLoginNotify', $vars));

add_hook('ServiceActivated', 10, fn (array $vars) => $queue('AfterModuleCreate', $vars));
add_hook('ServiceSuspended', 10, fn (array $vars) => $queue('AfterModuleSuspend', $vars));
add_hook('ServiceUnsuspended', 10, fn (array $vars) => $queue('AfterModuleUnsuspend', $vars));
add_hook('ServiceTerminated', 10, fn (array $vars) => $queue('AfterModuleTerminate', $vars));
add_hook('AfterModuleChangePassword', 10, fn (array $vars) => $queue('AfterModuleChangePassword', $vars));
add_hook('AfterModuleChangePackage', 10, fn (array $vars) => $queue('AfterModuleChangePackage', $vars));

add_hook('DomainRegistered', 10, function (array $vars) use ($queue) {
    $queue('AfterRegistrarRegistration', $vars);

    return $queue('AfterRegistrarRegistrationAdmin', $vars);
});
add_hook('DomainRenewed', 10, fn (array $vars) => $queue('AfterRegistrarRenewal', $vars));

add_hook('TicketOpen', 10, function (array $vars) use ($queue) {
    if (! empty($vars['isAdmin'])) {
        return null;
    }

    $queue('TicketOpenAdmin', $vars);

    return $queue('TicketOpen', $vars);
});

add_hook('TicketReplied', 10, function (array $vars) use ($queue) {
    return ! empty($vars['isStaffReply'])
        ? $queue('TicketAdminReply', $vars)
        : $queue('TicketUserReplyAdmin', $vars);
});

add_hook('TicketClose', 10, fn (array $vars) => $queue('TicketClose', $vars));
add_hook('RefundIssued', 10, fn (array $vars) => $queue('InvoiceRefunded', $vars));

/*
 * The customer's way in: a card on the dashboard while the portal is enabled.
 * The history page is the addon's own route.
 */
add_hook('ClientAreaHomePagePanels', 12, function () {
    $settings = WaSettings::current();

    if (! $settings->client_portal || ! auth()->check()) {
        return null;
    }

    try {
        return View::make('whatsapppro::card')->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});

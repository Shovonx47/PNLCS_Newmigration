<?php

use App\Support\BdPhone;
use Illuminate\Support\Facades\View;
use Modules\Addons\SoftcentsSms\Models\SmsSetting;
use Modules\Addons\SoftcentsSms\Services\OtpService;
use Modules\Addons\SoftcentsSms\Services\SmsDispatcher;

/*
 * Phase 3 - Softcents SMS.
 *
 * The legacy addon had one file per event under hooks/, each repeating the
 * same query and the same str_replace table. The template names are unchanged,
 * so the operator's copy and switches keep working; what changed is that the
 * values come from one builder and a failure to send can never break the
 * event that fired it (an order must not fail because the SMS gateway is down).
 */

$send = function (string $template, array $vars, bool $clientOnly = false) {
    try {
        $dispatcher = app(SmsDispatcher::class);
        if (! $dispatcher->enabled()) {
            return null;
        }

        $client = $vars['client'] ?? null;

        if (! $client && isset($vars['order'])) {
            $client = $vars['order']->client ?? null;
        }

        return $dispatcher->sendTemplate($template, $vars, $client);
    } catch (Throwable $e) {
        report($e);

        return null;
    }
};

add_hook('InvoiceCreated', 10, fn (array $vars) => $send('InvoiceCreated', $vars));

// Payment reminders – fired by pnlcs:payment-reminders for each stage.
// 'InvoicePaymentReminder' covers pre-due stages; First/Second/Third cover
// the three overdue tiers. The $vars array always carries 'invoice', 'client',
// 'days' (negative = overdue), and 'type'.
add_hook('InvoicePaymentReminder', 10, fn (array $vars) => $send('InvoicePaymentReminder', $vars));
add_hook('InvoicePaymentReminderFirst', 10, fn (array $vars) => $send('InvoicePaymentReminderFirst', $vars));
add_hook('InvoicePaymentReminderSecond', 10, fn (array $vars) => $send('InvoicePaymentReminderSecond', $vars));
add_hook('InvoicePaymentReminderThird', 10, fn (array $vars) => $send('InvoicePaymentReminderThird', $vars));
add_hook('InvoicePaid', 10, fn (array $vars) => $send('InvoicePaid', $vars));
add_hook('InvoiceCancelled', 10, fn (array $vars) => $send('InvoiceCancelled', $vars));
add_hook('AcceptOrder', 10, fn (array $vars) => $send('AcceptOrder', $vars));
add_hook('ClientAdd', 10, fn (array $vars) => $send('ClientAdd', $vars));
add_hook('UserLogin', 10, fn (array $vars) => $send('UserLogin', $vars));

add_hook('ServiceActivated', 10, fn (array $vars) => $send('AfterModuleCreate', $vars));
add_hook('ServiceSuspended', 10, fn (array $vars) => $send('AfterModuleSuspend', $vars));
add_hook('ServiceUnsuspended', 10, fn (array $vars) => $send('AfterModuleUnsuspend', $vars));
add_hook('ServiceTerminated', 10, fn (array $vars) => $send('AfterModuleTerminate', $vars));
add_hook('AfterModuleChangePassword', 10, fn (array $vars) => $send('AfterModuleChangePassword', $vars));
add_hook('AfterModuleChangePackage', 10, fn (array $vars) => $send('AfterModuleChangePackage', $vars));

add_hook('DomainRegistered', 10, fn (array $vars) => $send('AfterRegistrarRegistration', $vars));
add_hook('DomainRenewed', 10, fn (array $vars) => $send('AfterRegistrarRenewal', $vars));

// A new ticket is the operator's to answer; a staff reply is the customer's to
// read. The legacy addon had one file for each and sent to a different party.
add_hook('TicketOpen', 10, function (array $vars) use ($send) {
    return ! empty($vars['isAdmin'])
        ? null
        : $send('TicketOpenAdmin', $vars);
});

add_hook('TicketReplied', 10, function (array $vars) use ($send) {
    return ! empty($vars['isStaffReply'])
        ? $send('TicketAdminReply', $vars)
        : $send('TicketUserReplyAdmin', $vars);
});

add_hook('TicketClose', 10, fn (array $vars) => $send('TicketClose', $vars));

/*
 * The registration gate.
 *
 * The legacy addon refused registration from a ClientDetailsValidation hook
 * when the submitted number had no verified OTP in the session. That is the
 * same check, against the number as this application stores it.
 */
add_hook('ClientDetailsValidation', 10, function (array $vars) {
    $settings = SmsSetting::current();

    if (! $settings->otp_enabled || auth()->check()) {
        return null;
    }

    $data = $vars['data'] ?? [];
    $phone = BdPhone::gateway($data['phone_number'] ?? '', $data['phone_prefix'] ?? '');

    if ($phone === '') {
        return ['Please enter your mobile number and verify it with the SMS code. (অনুগ্রহ করে মোবাইল নম্বর দিন এবং SMS OTP দিয়ে ভেরিফাই করুন।)'];
    }

    if (app(OtpService::class)->verifiedPhone() !== $phone) {
        return ['Please verify your mobile number with the SMS code before creating an account. (অ্যাকাউন্ট তৈরি করার আগে মোবাইল নম্বরটি SMS OTP দিয়ে ভেরিফাই করুন।)'];
    }

    return null;
});

// The code is spent once the account exists, so it cannot open a second one.
add_hook('ClientAdd', 99, function (array $vars) {
    try {
        $client = $vars['client'] ?? null;
        $phone = $client ? BdPhone::gateway($client->phone_number, $client->phone_prefix) : '';

        if ($phone !== '') {
            app(OtpService::class)->consume($phone);
        }
    } catch (Throwable $e) {
        report($e);
    }

    return null;
});

/*
 * The verification box on the registration page. Rendered through the footer
 * output hook the auth layout already fires, and only on the registration page:
 * a script on every page would be a wasted 6KB on the login form.
 */
add_hook('ClientAreaFooterOutput', 20, function (array $vars) {
    $request = request();

    if (! $request->routeIs('client.register') || auth()->check()) {
        return null;
    }

    $settings = SmsSetting::current();

    if (! $settings->otp_enabled) {
        return null;
    }

    try {
        return View::make('softcentssms::otp', [
            'length' => max(4, min(8, (int) $settings->otp_length)),
            'cooldown' => max(0, (int) $settings->otp_resend_cooldown),
            'verifiedPhone' => app(OtpService::class)->verifiedPhone(),
            'sendUrl' => route('client.otp.send'),
            'verifyUrl' => route('client.otp.verify'),
        ])->render();
    } catch (Throwable $e) {
        report($e);

        return null;
    }
});

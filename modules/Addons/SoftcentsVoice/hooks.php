<?php

use Modules\Addons\SoftcentsVoice\Services\VoiceDispatcher;

/*
 * Phase 3 - voice calls.
 *
 * Only events that have a matching moment in this application are wired: an
 * event that nothing fires would be a switch that does nothing, which is worse
 * than no switch. The reminder calls that have no event come from
 * pnlcs:voice-reminders.
 */

$call = function (string $eventKey, array $vars) {
    try {
        return app(VoiceDispatcher::class)->dispatch($eventKey, $vars);
    } catch (Throwable $e) {
        report($e);

        return null;
    }
};

add_hook('InvoiceCreated', 20, fn (array $vars) => $call('invoice_created', $vars));
add_hook('InvoicePaid', 20, fn (array $vars) => $call('payment_confirmation', $vars));
add_hook('ServiceSuspended', 20, fn (array $vars) => $call('service_suspended', $vars));
add_hook('ServiceUnsuspended', 20, fn (array $vars) => $call('service_reactivated', $vars));
add_hook('ServiceTerminated', 20, fn (array $vars) => $call('service_terminated', $vars));
add_hook('RefundIssued', 20, fn (array $vars) => $call('refund_confirmation', $vars));

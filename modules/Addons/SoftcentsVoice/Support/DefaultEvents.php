<?php

namespace Modules\Addons\SoftcentsVoice\Support;

/**
 * The 24 events the legacy voice addon installed, with the categories the
 * operator's screen groups them by. Switched off until an operator chooses a
 * recording: a silent event must not ring anybody.
 */
final class DefaultEvents
{
    public static function all(): array
    {
        return [
            ['event_key' => 'invoice_created', 'category' => 'billing', 'name' => 'Invoice Created', 'description' => 'A new invoice was issued.'],
            ['event_key' => 'invoice_due_reminder', 'category' => 'billing', 'name' => 'Invoice Due Reminder', 'description' => 'The invoice is due in a few days.'],
            ['event_key' => 'first_payment_reminder', 'category' => 'billing', 'name' => 'First Payment Reminder', 'description' => 'First reminder after the due date.'],
            ['event_key' => 'second_payment_reminder', 'category' => 'billing', 'name' => 'Second Payment Reminder', 'description' => 'Second reminder, a few days later.'],
            ['event_key' => 'final_payment_reminder', 'category' => 'billing', 'name' => 'Final Payment Reminder', 'description' => 'Last warning before suspension.'],
            ['event_key' => 'payment_confirmation', 'category' => 'billing', 'name' => 'Payment Confirmation', 'description' => 'The invoice was paid.'],
            ['event_key' => 'payment_failed', 'category' => 'billing', 'name' => 'Payment Failed', 'description' => 'A card or gateway payment failed.'],
            ['event_key' => 'payment_pending', 'category' => 'billing', 'name' => 'Payment Pending', 'description' => 'The payment is waiting on the gateway.'],
            ['event_key' => 'service_suspension_warning', 'category' => 'service', 'name' => 'Service Suspension Warning', 'description' => 'The service will be suspended soon.'],
            ['event_key' => 'service_suspended', 'category' => 'service', 'name' => 'Service Suspended', 'description' => 'The service was suspended.'],
            ['event_key' => 'service_reactivated', 'category' => 'service', 'name' => 'Service Reactivated', 'description' => 'The service was unsuspended.'],
            ['event_key' => 'service_termination_warning', 'category' => 'service', 'name' => 'Service Termination Warning', 'description' => 'The service will be terminated soon.'],
            ['event_key' => 'service_terminated', 'category' => 'service', 'name' => 'Service Terminated', 'description' => 'The service was terminated.'],
            ['event_key' => 'low_balance_alert', 'category' => 'finance', 'name' => 'Low Balance Alert', 'description' => 'The account balance is low.'],
            ['event_key' => 'refund_confirmation', 'category' => 'finance', 'name' => 'Refund Confirmation', 'description' => 'A refund was issued.'],
            ['event_key' => 'credit_added', 'category' => 'finance', 'name' => 'Credit Added', 'description' => 'Credit was added to the account.'],
            ['event_key' => 'credit_deducted', 'category' => 'finance', 'name' => 'Credit Deducted', 'description' => 'Credit was taken from the account.'],
            ['event_key' => 'system_maintenance', 'category' => 'system', 'name' => 'System Maintenance Alert', 'description' => 'Planned maintenance notice.'],
            ['event_key' => 'service_outage', 'category' => 'system', 'name' => 'Service Outage Alert', 'description' => 'An outage is being worked on.'],
            ['event_key' => 'service_restored', 'category' => 'system', 'name' => 'Service Restored', 'description' => 'The outage is over.'],
            ['event_key' => 'security_alert', 'category' => 'security', 'name' => 'Security Alert', 'description' => 'A security notice for the account.'],
            ['event_key' => 'password_changed', 'category' => 'security', 'name' => 'Password Changed Alert', 'description' => 'The password was changed.'],
            ['event_key' => 'account_suspended', 'category' => 'security', 'name' => 'Account Suspended', 'description' => 'The account was suspended.'],
            ['event_key' => 'account_reactivated', 'category' => 'security', 'name' => 'Account Reactivated', 'description' => 'The account was reactivated.'],
        ];
    }
}

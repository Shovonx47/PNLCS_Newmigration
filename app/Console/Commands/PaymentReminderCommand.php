<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Mail\PaymentReminderMail;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentReminderCommand extends Command
{
    protected $signature = 'pnlcs:payment-reminders';

    protected $description = 'Remind customers about invoices coming due and invoices already past due';

    /**
     * The points at which a customer hears from us, furthest away first.
     *
     * Reminders used to be sent by matching the due date to an exact day, so a
     * run that missed a day skipped that reminder for good — and nothing at all
     * went out once an invoice was more than a week overdue. Each invoice now
     * carries the last stage it was told about, so a late run still catches up
     * and nothing is sent twice.
     *
     * @var array<int, array{key: string, days: int, before: bool}>
     */
    private const STAGES = [
        ['key' => 'due7', 'days' => 7, 'before' => true],
        ['key' => 'due3', 'days' => 3, 'before' => true],
        ['key' => 'due1', 'days' => 1, 'before' => true],
        ['key' => 'late1', 'days' => 1, 'before' => false],
        ['key' => 'late3', 'days' => 3, 'before' => false],
        ['key' => 'late7', 'days' => 7, 'before' => false],
        ['key' => 'late30', 'days' => 30, 'before' => false],
    ];

    /**
     * Map each stage key to the hook name and the 'type' string passed in $vars.
     *
     * InvoicePaymentReminder  → pre-due reminders (due7 / due3 / due1)
     * InvoicePaymentReminderFirst  → 1 day overdue  (late1)
     * InvoicePaymentReminderSecond → 3 days overdue (late3)
     * InvoicePaymentReminderThird  → 7 days overdue (late7)
     * late30 fires no named hook of its own (treat as a final / silent stage).
     */
    private const STAGE_HOOKS = [
        'due7'   => ['hook' => 'InvoicePaymentReminder',       'type' => 'reminder'],
        'due3'   => ['hook' => 'InvoicePaymentReminder',       'type' => 'reminder'],
        'due1'   => ['hook' => 'InvoicePaymentReminder',       'type' => 'reminder'],
        'late1'  => ['hook' => 'InvoicePaymentReminderFirst',  'type' => 'firstoverdue'],
        'late3'  => ['hook' => 'InvoicePaymentReminderSecond', 'type' => 'secondoverdue'],
        'late7'  => ['hook' => 'InvoicePaymentReminderThird',  'type' => 'thirdoverdue'],
        'late30' => ['hook' => null,                           'type' => 'final'],
    ];

    public function handle(): int
    {
        $invoices = Invoice::with('client')
            ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Overdue->value])
            // The invoices a payment invoice lists are reminded of on their own.
            ->where('type', '!=', \App\Services\MassPaymentService::TYPE)
            ->whereNotNull('due_date')
            ->get();

        $sent = 0;
        $today = now()->startOfDay();

        foreach ($invoices as $invoice) {
            $stage = $this->stageFor($invoice, $today);

            if (! $stage || $stage['key'] === $invoice->reminder_stage) {
                continue;
            }

            if (! $invoice->client?->billingEmail()) {
                continue;
            }

            $days = $stage['before'] ? $stage['days'] : -$stage['days'];

            try {
                Mail::to($invoice->client->billingEmail())->queue(new PaymentReminderMail($invoice, $days));
                $invoice->update(['reminder_stage' => $stage['key'], 'reminder_sent_at' => now()]);

                // Fire the addon hook so SMS / WhatsApp modules can react.
                $hookMeta = self::STAGE_HOOKS[$stage['key']] ?? null;
                if ($hookMeta && $hookMeta['hook'] !== null) {
                    $hookVars = [
                        'invoice' => $invoice,
                        'client'  => $invoice->client,
                        'days'    => $days,
                        'type'    => $hookMeta['type'],
                    ];
                    run_hook($hookMeta['hook'], $hookVars);
                }

                $sent++;
            } catch (\Throwable $e) {
                Log::error("Payment reminder failed for invoice #{$invoice->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} payment reminder(s).");

        return Command::SUCCESS;
    }

    /**
     * The furthest stage this invoice has reached, or null if it is too early.
     *
     * @return array{key: string, days: int, before: bool}|null
     */
    private function stageFor(Invoice $invoice, Carbon $today): ?array
    {
        $due = Carbon::parse($invoice->due_date)->startOfDay();
        $daysUntil = (int) $today->diffInDays($due, false);

        $reached = null;

        foreach (self::STAGES as $stage) {
            $hit = $stage['before']
                ? ($daysUntil >= 0 && $daysUntil <= $stage['days'])
                : ($daysUntil <= -$stage['days']);

            if ($hit) {
                $reached = $stage;
            }
        }

        return $reached;
    }
}

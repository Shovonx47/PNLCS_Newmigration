<?php

namespace Modules\Addons\SoftcentsVoice\Console;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Modules\Addons\SoftcentsVoice\Models\VoiceLog;
use Modules\Addons\SoftcentsVoice\Services\VoiceDispatcher;

/**
 * The reminder calls, which no event can trigger because nothing "happens"
 * when an invoice simply gets older.
 *
 * One call per invoice per stage: an operator who switches on the second
 * reminder must not have it repeat every morning.
 */
class ReminderCallsCommand extends Command
{
    protected $signature = 'pnlcs:voice-reminders {--limit=200}';

    protected $description = 'Place due-date and overdue reminder calls for open invoices';

    public function handle(VoiceDispatcher $dispatcher): int
    {
        $invoices = Invoice::with('client')
            ->whereIn('status', ['unpaid', 'overdue', 'partially_paid'])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays(3)->toDateString())
            ->orderBy('due_date')
            ->limit((int) $this->option('limit'))
            ->get();

        $placed = 0;

        foreach ($invoices as $invoice) {
            $days = (int) $invoice->due_date->startOfDay()->diffInDays(now()->startOfDay(), false);
            $event = match (true) {
                $days <= 0 => 'invoice_due_reminder',
                $days <= 3 => 'first_payment_reminder',
                $days <= 7 => 'second_payment_reminder',
                default => 'final_payment_reminder',
            };

            // This stage has been called for this invoice already.
            if (VoiceLog::where('invoice_id', $invoice->id)->where('event_key', $event)->exists()) {
                continue;
            }

            $log = $dispatcher->dispatch($event, ['invoice' => $invoice, 'client' => $invoice->client], $invoice->client);

            if ($log && $log->status !== 'skipped') {
                $placed++;
            }
        }

        $this->info("Voice reminders: {$placed} call(s) placed or queued.");

        return self::SUCCESS;
    }
}

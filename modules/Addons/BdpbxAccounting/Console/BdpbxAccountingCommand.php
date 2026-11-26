<?php

namespace Modules\Addons\BdpbxAccounting\Console;

use Illuminate\Console\Command;
use Modules\Addons\BdpbxAccounting\Services\AccountingEngine;

/**
 * `php artisan pnlcs:bdpbx-accounting` - write today's (or a given day's) PBX
 * accounting snapshot. Scheduled daily from the addon's service provider, and
 * runnable by hand from the accounting screen.
 */
class BdpbxAccountingCommand extends Command
{
    protected $signature = 'pnlcs:bdpbx-accounting {--date= : The day to snapshot (YYYY-MM-DD), defaults to today}';

    protected $description = 'Recalculate BDPBX per-server and combined accounting for a day';

    public function handle(AccountingEngine $engine): int
    {
        $date = $engine->normaliseDate($this->option('date'));
        $rows = $engine->run($date);

        if ($rows === []) {
            $this->warn("No BDPBX products are configured, so there is nothing to account for on {$date}.");

            return self::SUCCESS;
        }

        $this->info("BDPBX accounting for {$date}:");
        $this->table(
            ['Server', 'Services', 'Active MRR', 'Paid this month', 'Paid today', 'Due'],
            array_map(fn (array $row) => [
                $row['server_name'],
                $row['active_services'].' / '.$row['total_services'],
                number_format((float) $row['active_mrr'], 2),
                number_format((float) $row['paid_amount_month'], 2),
                number_format((float) $row['paid_today_amount'], 2),
                number_format((float) $row['due_amount'], 2),
            ], $rows)
        );

        return self::SUCCESS;
    }
}

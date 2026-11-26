<?php

namespace Modules\Addons\CancelInvoices\Console;

use Illuminate\Console\Command;
use Modules\Addons\CancelInvoices\CancelInvoicesModule;
use Modules\Addons\CancelInvoices\Services\CancelInvoicesService;

class CancelUnpaidInvoicesCommand extends Command
{
    protected $signature = 'pnlcs:cancel-unpaid-invoices {--dry-run : Report how many invoices would be cancelled without changing them}';

    protected $description = 'Cancel unpaid renewal and Add-Funds invoices older than configured thresholds';

    public function handle(CancelInvoicesService $service): int
    {
        if (! CancelInvoicesModule::isEnabled()) {
            $this->info('Auto-Cancel Invoices addon is inactive; skipping.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $res = $service->run($dryRun);
        $verb = $dryRun ? 'Would cancel' : 'Cancelled';

        $this->info("{$verb} {$res['cancelled_regular']} regular invoice(s) and {$res['cancelled_add_funds']} Add-Funds invoice(s) ({$res['skipped_excluded']} excluded).");

        return self::SUCCESS;
    }
}

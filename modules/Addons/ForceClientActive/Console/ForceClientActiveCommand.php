<?php

namespace Modules\Addons\ForceClientActive\Console;

use Illuminate\Console\Command;
use Modules\Addons\ForceClientActive\ForceClientActiveModule;
use Modules\Addons\ForceClientActive\Services\ForceClientActiveService;

class ForceClientActiveCommand extends Command
{
    protected $signature = 'pnlcs:force-client-active';

    protected $description = 'Restore protected inactive/closed client accounts back to Active status';

    public function handle(ForceClientActiveService $service): int
    {
        if (! ForceClientActiveModule::isEnabled()) {
            $this->info('Force Client Active addon is inactive; skipping.');

            return self::SUCCESS;
        }

        $count = $service->run();
        $this->info("Restored {$count} client account(s) to active status.");

        return self::SUCCESS;
    }
}

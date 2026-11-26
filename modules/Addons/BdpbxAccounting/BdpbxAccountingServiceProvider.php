<?php

namespace Modules\Addons\BdpbxAccounting;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\BdpbxAccounting\Console\BdpbxAccountingCommand;
use Modules\Addons\BdpbxAccounting\Http\Admin\AccountingController;

class BdpbxAccountingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'bdpbxaccounting');

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin/extensions/bdpbx-accounting')->name('admin.extensions.bdpbx-accounting.')
            ->group(function () {
                Route::get('/', [AccountingController::class, 'index'])->name('index');
                Route::post('run', [AccountingController::class, 'run'])->name('run')->middleware('throttle:10,1');
            });

        if ($this->app->runningInConsole()) {
            $this->commands([BdpbxAccountingCommand::class]);
        }

        // After invoice generation (06:00), overdue marking (06:30) and
        // auto-charge (06:45), so the day's figures are read once the morning
        // billing has settled rather than mid-run.
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('pnlcs:bdpbx-accounting')->dailyAt('06:50')->withoutOverlapping();
        });
    }
}

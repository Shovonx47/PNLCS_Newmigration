<?php

namespace Modules\Addons\CancelInvoices;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\CancelInvoices\Console\CancelUnpaidInvoicesCommand;
use Modules\Addons\CancelInvoices\Http\Admin\CancelInvoicesAdminController;

class CancelInvoicesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'cancelinvoices');

        if ($this->app->runningInConsole()) {
            $this->commands([CancelUnpaidInvoicesCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('pnlcs:cancel-unpaid-invoices')->dailyAt('06:15')->withoutOverlapping(30);
        });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_invoices'])
            ->prefix('admin/extensions/cancel-invoices')
            ->name('admin.extensions.cancel-invoices.')
            ->group(function () {
                Route::get('/', [CancelInvoicesAdminController::class, 'index'])->name('index');
                Route::post('settings', [CancelInvoicesAdminController::class, 'updateSettings'])->name('settings');
                Route::post('exclusions', [CancelInvoicesAdminController::class, 'storeExclusion'])->name('exclusions.store');
                Route::delete('exclusions/{exclusion}', [CancelInvoicesAdminController::class, 'destroyExclusion'])->name('exclusions.destroy');
                Route::post('run', [CancelInvoicesAdminController::class, 'runNow'])->name('run');
            });
    }
}

<?php

namespace Modules\Addons\ForceClientActive;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\ForceClientActive\Console\ForceClientActiveCommand;
use Modules\Addons\ForceClientActive\Http\Admin\ForceClientActiveAdminController;

class ForceClientActiveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'forceclientactive');

        if ($this->app->runningInConsole()) {
            $this->commands([ForceClientActiveCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('pnlcs:force-client-active')->dailyAt('05:30')->withoutOverlapping(10);
        });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:edit_clients'])
            ->prefix('admin/extensions/force-client-active')
            ->name('admin.extensions.force-client-active.')
            ->group(function () {
                Route::get('/', [ForceClientActiveAdminController::class, 'index'])->name('index');
                Route::post('mode', [ForceClientActiveAdminController::class, 'updateMode'])->name('mode');
                Route::post('/', [ForceClientActiveAdminController::class, 'store'])->name('store');
                Route::delete('{entry}', [ForceClientActiveAdminController::class, 'destroy'])->name('destroy');
            });
    }
}

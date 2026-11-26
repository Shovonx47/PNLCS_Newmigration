<?php

namespace Modules\Addons\DriveBackup;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\DriveBackup\Console\RunDriveBackupCommand;
use Modules\Addons\DriveBackup\Http\Admin\DriveBackupAdminController;

class DriveBackupServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'gdrivebackup');

        if ($this->app->runningInConsole()) {
            $this->commands([RunDriveBackupCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('pnlcs:gdrive-backup')->dailyAt('03:30')->withoutOverlapping(60);
        });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_settings'])
            ->prefix('admin/extensions/gdrive-backup')
            ->name('admin.extensions.gdrive-backup.')
            ->group(function () {
                Route::get('/', [DriveBackupAdminController::class, 'index'])->name('index');
                Route::post('settings', [DriveBackupAdminController::class, 'updateSettings'])->name('settings');
                Route::post('oauth-exchange', [DriveBackupAdminController::class, 'exchangeOAuthCode'])->name('oauth-exchange');
                Route::post('run', [DriveBackupAdminController::class, 'runNow'])->name('run');
            });
    }
}

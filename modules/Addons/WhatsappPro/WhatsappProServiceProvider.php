<?php

namespace Modules\Addons\WhatsappPro;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\WhatsappPro\Console\ProcessQueueCommand;
use Modules\Addons\WhatsappPro\Http\Admin\WaAdminController;
use Modules\Addons\WhatsappPro\Http\WhatsAppController;

class WhatsappProServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ProcessQueueCommand::class]);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'whatsapppro');

        Route::middleware(['web', 'banned.ip', 'client.permission', 'auth', '2fa'])
            ->prefix('client')->name('client.')
            ->group(function () {
                Route::get('whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.index');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin/extensions/whatsapp-pro')->name('admin.extensions.whatsapp.')
            ->group(function () {
                Route::get('/', [WaAdminController::class, 'index'])->name('index');
                Route::post('settings', [WaAdminController::class, 'updateSettings'])->name('settings');
                Route::post('check-connection', [WaAdminController::class, 'checkConnection'])->name('connection');
                Route::post('templates/{template}', [WaAdminController::class, 'updateTemplate'])->name('templates.update');
                Route::post('send', [WaAdminController::class, 'send'])->name('send');
                Route::post('bulk', [WaAdminController::class, 'bulk'])->name('bulk');
                Route::post('logs/{log}/retry', [WaAdminController::class, 'retry'])->name('logs.retry');
            });

        // The queue is the point of this addon: one minute is the promise the
        // legacy addon made to the operator, and it is kept here.
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('pnlcs:whatsapp-queue')->everyMinute()->withoutOverlapping();
        });
    }
}

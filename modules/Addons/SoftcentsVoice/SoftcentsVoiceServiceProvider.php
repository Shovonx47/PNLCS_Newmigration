<?php

namespace Modules\Addons\SoftcentsVoice;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\SoftcentsVoice\Console\ProcessRetriesCommand;
use Modules\Addons\SoftcentsVoice\Console\ReminderCallsCommand;
use Modules\Addons\SoftcentsVoice\Http\Admin\VoiceAdminController;

class SoftcentsVoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ProcessRetriesCommand::class, ReminderCallsCommand::class]);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'softcentsvoice');

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin/extensions/softcents-voice')->name('admin.extensions.voice.')
            ->group(function () {
                Route::get('/', [VoiceAdminController::class, 'index'])->name('index');
                Route::post('settings', [VoiceAdminController::class, 'updateSettings'])->name('settings');
                Route::post('events/{event}', [VoiceAdminController::class, 'updateEvent'])->name('events.update');
                Route::post('overrides', [VoiceAdminController::class, 'storeOverride'])->name('overrides.store');
                Route::delete('overrides/{override}', [VoiceAdminController::class, 'destroyOverride'])->name('overrides.destroy');
                Route::post('test', [VoiceAdminController::class, 'test'])->name('test');
            });

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Retries run often because a 15-minute interval is the promise.
            $schedule->command('pnlcs:voice-retries')->everyFiveMinutes()->withoutOverlapping();
            // One reminder pass a morning, inside the calling window.
            $schedule->command('pnlcs:voice-reminders')->dailyAt('10:00')->withoutOverlapping();
        });
    }
}

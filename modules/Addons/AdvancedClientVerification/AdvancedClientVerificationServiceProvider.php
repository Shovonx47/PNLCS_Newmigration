<?php

namespace Modules\Addons\AdvancedClientVerification;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\AdvancedClientVerification\Console\EnforceKycCommand;
use Modules\Addons\AdvancedClientVerification\Http\Admin\AdminVerificationController;
use Modules\Addons\AdvancedClientVerification\Http\Client\ClientVerificationController;
use Modules\Addons\AdvancedClientVerification\Http\DiditWebhookController;

class AdvancedClientVerificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'acv');

        if ($this->app->runningInConsole()) {
            $this->commands([EnforceKycCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('pnlcs:acv-enforce')->dailyAt('07:15')->withoutOverlapping(30);
        });

        // Didit cannot present a browser session CSRF token; DiditWebhookController
        // authenticates every call via constant-time HMAC-SHA256 signature verification.
        Route::middleware(['web', 'throttle:60,1'])
            ->withoutMiddleware([PreventRequestForgery::class])
            ->post('kyc/didit/webhook', DiditWebhookController::class)
            ->name('kyc.didit.webhook');

        Route::middleware(['web', 'banned.ip', 'client.permission', 'auth', '2fa'])
            ->prefix('client/verification')
            ->name('client.kyc.')
            ->group(function () {
                Route::get('/', [ClientVerificationController::class, 'index'])->name('index');
                Route::post('session', [ClientVerificationController::class, 'startSession'])
                    ->middleware('throttle:10,1')->name('session');
                Route::post('refresh', [ClientVerificationController::class, 'refreshSession'])
                    ->middleware('throttle:15,1')->name('refresh');
                Route::post('manual', [ClientVerificationController::class, 'submitManual'])
                    ->middleware('throttle:10,1')->name('manual');
                Route::get('documents/{document}', [ClientVerificationController::class, 'downloadDocument'])
                    ->name('documents.download');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:edit_clients'])
            ->prefix('admin/extensions/client-verification')
            ->name('admin.extensions.kyc.')
            ->group(function () {
                Route::get('/', [AdminVerificationController::class, 'index'])->name('index');
                Route::post('settings', [AdminVerificationController::class, 'updateSettings'])->name('settings');
                Route::get('export', [AdminVerificationController::class, 'exportCsv'])->name('export');
                Route::get('documents/{document}', [AdminVerificationController::class, 'downloadDocument'])->name('documents.download');
                Route::get('{verification}', [AdminVerificationController::class, 'show'])->name('show');
                Route::post('{verification}/approve', [AdminVerificationController::class, 'approve'])->name('approve');
                Route::post('{verification}/decline', [AdminVerificationController::class, 'decline'])->name('decline');
                Route::post('{verification}/notes', [AdminVerificationController::class, 'addNote'])->name('notes');
            });
    }
}

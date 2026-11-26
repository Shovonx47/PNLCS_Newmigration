<?php

namespace Modules\Gateways\UddoktaPay;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class UddoktaPayServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', '2fa'])
            ->post('gateway/uddoktapay/capture/{invoice}', [UddoktaPayController::class, 'captureLocal'])
            ->middleware('throttle:20,1')
            ->name('modules.gateways.uddoktapay.capture');

        Route::middleware('web')
            ->withoutMiddleware([
                PreventRequestForgery::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SetLocale::class,
            ])
            ->group(function () {
                Route::match(['get', 'post'], 'gateway/uddoktapay/return/{invoice}', [UddoktaPayController::class, 'returnLocal'])
                    ->middleware('throttle:30,1')
                    ->name('modules.gateways.uddoktapay.return');

                Route::post('gateway/uddoktapay/webhook', [UddoktaPayController::class, 'webhookLocal'])
                    ->middleware('throttle:60,1')
                    ->name('modules.gateways.uddoktapay.webhook');
            });
    }
}

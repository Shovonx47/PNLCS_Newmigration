<?php

namespace Modules\Gateways\UddoktaPayInternational;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Gateways\UddoktaPay\UddoktaPayController;

class UddoktaPayInternationalServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', '2fa'])
            ->post('gateway/uddoktapayinternational/capture/{invoice}', [UddoktaPayController::class, 'captureInternational'])
            ->middleware('throttle:20,1')
            ->name('modules.gateways.uddoktapayinternational.capture');

        Route::middleware('web')
            ->withoutMiddleware([
                PreventRequestForgery::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SetLocale::class,
            ])
            ->group(function () {
                Route::match(['get', 'post'], 'gateway/uddoktapayinternational/return/{invoice}', [UddoktaPayController::class, 'returnInternational'])
                    ->middleware('throttle:30,1')
                    ->name('modules.gateways.uddoktapayinternational.return');

                Route::post('gateway/uddoktapayinternational/webhook', [UddoktaPayController::class, 'webhookInternational'])
                    ->middleware('throttle:60,1')
                    ->name('modules.gateways.uddoktapayinternational.webhook');
            });
    }
}

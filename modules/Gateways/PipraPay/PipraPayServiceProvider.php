<?php

namespace Modules\Gateways\PipraPay;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PipraPayServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', '2fa'])
            ->post('gateway/piprapay/capture/{invoice}', [PipraPayController::class, 'capture'])
            ->middleware('throttle:20,1')
            ->name('modules.gateways.piprapay.capture');

        Route::middleware('web')
            ->withoutMiddleware([
                PreventRequestForgery::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SetLocale::class,
            ])
            ->group(function () {
                Route::match(['get', 'post'], 'gateway/piprapay/return/{invoice}', [PipraPayController::class, 'handleReturn'])
                    ->middleware('throttle:30,1')
                    ->name('modules.gateways.piprapay.return');

                Route::post('gateway/piprapay/webhook', [PipraPayController::class, 'webhook'])
                    ->middleware('throttle:60,1')
                    ->name('modules.gateways.piprapay.webhook');
            });
    }
}

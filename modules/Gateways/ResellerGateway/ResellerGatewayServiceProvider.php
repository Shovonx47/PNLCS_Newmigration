<?php

namespace Modules\Gateways\ResellerGateway;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ResellerGatewayServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth', '2fa'])
            ->post('gateway/resellergateway/capture/{invoice}', [ResellerGatewayController::class, 'capture'])
            ->middleware('throttle:20,1')
            ->name('modules.gateways.resellergateway.capture');

        Route::middleware('web')
            ->withoutMiddleware([
                PreventRequestForgery::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                SetLocale::class,
            ])
            ->group(function () {
                Route::match(['get', 'post'], 'gateway/resellergateway/return/{invoice}', [ResellerGatewayController::class, 'handleReturn'])
                    ->middleware('throttle:30,1')
                    ->name('modules.gateways.resellergateway.return');

                Route::post('gateway/resellergateway/webhook', [ResellerGatewayController::class, 'webhook'])
                    ->middleware('throttle:60,1')
                    ->name('modules.gateways.resellergateway.webhook');
            });
    }
}

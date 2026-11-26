<?php

namespace Modules\Addons\SupportPin;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\SupportPin\Http\SupportPinController;

class SupportPinServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'supportpin');

        Route::middleware(['web', 'banned.ip', 'client.permission', 'auth', '2fa'])
            ->prefix('client/support-pin')
            ->name('client.support-pin.')
            ->group(function () {
                Route::post('regenerate', [SupportPinController::class, 'regenerate'])
                    ->middleware('throttle:10,1')->name('regenerate');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:view_clients'])
            ->prefix('admin/extensions/support-pin')
            ->name('admin.extensions.support-pin.')
            ->group(function () {
                Route::get('/', [SupportPinController::class, 'adminIndex'])->name('index');
                Route::post('verify', [SupportPinController::class, 'adminVerify'])
                    ->middleware('throttle:5,1')->name('verify');
            });
    }
}

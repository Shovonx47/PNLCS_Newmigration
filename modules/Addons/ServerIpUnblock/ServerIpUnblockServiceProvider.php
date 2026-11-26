<?php

namespace Modules\Addons\ServerIpUnblock;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\ServerIpUnblock\Http\IpUnblockController;

class ServerIpUnblockServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'unblockip');

        Route::middleware(['web', 'banned.ip', 'client.permission', 'auth', '2fa'])
            ->prefix('client/unblock-ip')
            ->name('client.unblock-ip.')
            ->group(function () {
                Route::get('/', [IpUnblockController::class, 'clientIndex'])->name('index');
                Route::post('/', [IpUnblockController::class, 'clientSubmit'])
                    ->middleware('throttle:5,1')->name('submit');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin/extensions/unblock-ip')
            ->name('admin.extensions.unblock-ip.')
            ->group(function () {
                Route::get('/', [IpUnblockController::class, 'adminIndex'])->name('index');
                Route::post('unblock', [IpUnblockController::class, 'adminUnblock'])
                    ->middleware('throttle:10,1')->name('unblock');
            });
    }
}

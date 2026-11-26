<?php

namespace Modules\Servers\Bdpbx;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BdpbxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'bdpbx');

        Route::middleware(['web', 'auth', '2fa'])
            ->prefix('client/services/{service}/bdpbx')
            ->name('client.services.bdpbx.')
            ->group(function () {
                Route::post('extension-password', [BdpbxController::class, 'updateExtensionPassword'])
                    ->middleware('throttle:15,1')
                    ->name('extension-password');
                Route::post('unblock-ip', [BdpbxController::class, 'unblockIp'])
                    ->middleware('throttle:15,1')
                    ->name('unblock-ip');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin')
            ->name('admin.bdpbx.')
            ->group(function () {
                Route::post('services/{service}/bdpbx/extensions', [BdpbxController::class, 'adminUpdateExtensionPasswords'])
                    ->name('extensions.update');
                Route::post('services/{service}/bdpbx/unblock-ip', [BdpbxController::class, 'adminUnblockIp'])
                    ->name('unblock-ip');
                Route::get('services/{service}/bdpbx/sso', [BdpbxController::class, 'adminCustomerSso'])
                    ->name('customer-sso');
                Route::get('servers/{server}/bdpbx/super-sso', [BdpbxController::class, 'adminSuperSso'])
                    ->middleware('admin.permission:manage_servers')
                    ->name('super-sso');
            });
    }
}

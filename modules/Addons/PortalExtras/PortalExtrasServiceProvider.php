<?php

namespace Modules\Addons\PortalExtras;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\PortalExtras\Http\Admin\ServiceTransferAdminController;
use Modules\Addons\PortalExtras\Http\PortalServiceActionController;

class PortalExtrasServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'portalextras');

        Route::middleware(['web', 'banned.ip', 'client.permission', 'auth', '2fa'])
            ->prefix('client/services/{service}')
            ->name('client.services.')
            ->group(function () {
                Route::post('self-suspend', [PortalServiceActionController::class, 'selfSuspend'])
                    ->middleware('throttle:5,1')->name('self-suspend');
                Route::post('self-terminate', [PortalServiceActionController::class, 'selfTerminate'])
                    ->middleware('throttle:5,1')->name('self-terminate');
                Route::post('transfer-request', [PortalServiceActionController::class, 'requestTransfer'])
                    ->middleware('throttle:5,1')->name('transfer-request');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin/extensions/service-transfers')
            ->name('admin.extensions.service-transfers.')
            ->group(function () {
                Route::get('/', [ServiceTransferAdminController::class, 'index'])->name('index');
                Route::post('{transfer}/approve', [ServiceTransferAdminController::class, 'approve'])->name('approve');
                Route::post('{transfer}/reject', [ServiceTransferAdminController::class, 'reject'])->name('reject');
            });
    }
}

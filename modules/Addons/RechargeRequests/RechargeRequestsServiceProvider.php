<?php

namespace Modules\Addons\RechargeRequests;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\RechargeRequests\Http\Admin\RechargeRequestAdminController;
use Modules\Addons\RechargeRequests\Http\Client\RechargeRequestController;

class RechargeRequestsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'rechargerequests');

        Route::middleware(['web', 'banned.ip', 'client.permission', 'auth', '2fa'])
            ->prefix('client')->name('client.')
            ->group(function () {
                Route::get('recharge', [RechargeRequestController::class, 'show'])->name('recharge');
                Route::post('recharge', [RechargeRequestController::class, 'store'])
                    ->middleware('throttle:10,1')->name('recharge.store');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin/extensions/recharge-requests')->name('admin.extensions.recharge-requests.')
            ->group(function () {
                Route::get('/', [RechargeRequestAdminController::class, 'index'])->name('index');
                Route::put('{rechargeRequest}', [RechargeRequestAdminController::class, 'update'])->name('update');
            });
    }
}

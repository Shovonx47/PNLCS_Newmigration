<?php

namespace Modules\Addons\ItalkRecharge;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\ItalkRecharge\Http\Admin\RechargeAdminController;

class ItalkRechargeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'italkrecharge');

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin/extensions/iptsp-recharge')->name('admin.extensions.iptsp-recharge.')
            ->group(function () {
                Route::get('/', [RechargeAdminController::class, 'index'])->name('index');
                Route::post('providers', [RechargeAdminController::class, 'storeProvider'])->name('providers.store');
                Route::put('providers/{provider}', [RechargeAdminController::class, 'updateProvider'])->name('providers.update');
                Route::delete('providers/{provider}', [RechargeAdminController::class, 'destroyProvider'])->name('providers.destroy');
                Route::post('accounts', [RechargeAdminController::class, 'storeAccount'])->name('accounts.store');
                Route::delete('accounts/{account}', [RechargeAdminController::class, 'destroyAccount'])->name('accounts.destroy');
                Route::post('recharge', [RechargeAdminController::class, 'rechargeNow'])->name('recharge')->middleware('throttle:20,1');
                Route::post('logs/{log}/retry', [RechargeAdminController::class, 'retryLog'])->name('logs.retry')->middleware('throttle:10,1');
                Route::post('logs/{log}/resolve', [RechargeAdminController::class, 'resolveLog'])->name('logs.resolve');
            });
    }
}

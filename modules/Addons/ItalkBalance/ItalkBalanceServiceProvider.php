<?php

namespace Modules\Addons\ItalkBalance;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\ItalkBalance\Http\Admin\BalanceAdminController;
use Modules\Addons\ItalkBalance\Http\BalanceRefreshController;

class ItalkBalanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'italkbalance');

        // Client area: the refresh button behind the panel's own guards.
        Route::middleware(['web', 'banned.ip', 'client.permission', 'auth', '2fa'])
            ->prefix('client')->name('client.')
            ->group(function () {
                Route::post('services/{service}/italk-balance/refresh', [BalanceRefreshController::class, 'refresh'])
                    ->middleware('throttle:10,1')
                    ->name('italk-balance.refresh');
            });

        // Admin: the mapping screen, behind manage_services.
        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin/extensions/italk-balance')->name('admin.extensions.italk-balance.')
            ->group(function () {
                Route::get('/', [BalanceAdminController::class, 'index'])->name('index');
                Route::post('settings', [BalanceAdminController::class, 'updateSettings'])->name('settings');
                Route::post('endpoints', [BalanceAdminController::class, 'storeEndpoint'])->name('endpoints.store');
                Route::put('endpoints/{endpoint}', [BalanceAdminController::class, 'updateEndpoint'])->name('endpoints.update');
                Route::delete('endpoints/{endpoint}', [BalanceAdminController::class, 'destroyEndpoint'])->name('endpoints.destroy');
                Route::post('mappings', [BalanceAdminController::class, 'storeMapping'])->name('mappings.store');
                Route::delete('mappings/{mapping}', [BalanceAdminController::class, 'destroyMapping'])->name('mappings.destroy');
                Route::post('mappings/{mapping}/refresh', [BalanceAdminController::class, 'refreshMapping'])->name('mappings.refresh');
            });
    }
}

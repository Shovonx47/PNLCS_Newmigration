<?php

namespace Modules\Addons\PriceUpdater;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\PriceUpdater\Http\Admin\PriceUpdaterAdminController;

class PriceUpdaterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'priceupdater');

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_products'])
            ->prefix('admin/extensions/price-updater')
            ->name('admin.extensions.price-updater.')
            ->group(function () {
                Route::get('/', [PriceUpdaterAdminController::class, 'index'])->name('index');
                Route::post('products', [PriceUpdaterAdminController::class, 'handleProduct'])->name('products');
                Route::post('domains', [PriceUpdaterAdminController::class, 'handleDomain'])->name('domains');
            });
    }
}

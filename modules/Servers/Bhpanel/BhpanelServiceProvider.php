<?php

namespace Modules\Servers\Bhpanel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BhpanelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'bhpanel');

        Route::middleware(['web', 'auth', '2fa'])
            ->prefix('client/services/{service}/bhpanel')
            ->name('client.services.bhpanel.')
            ->group(function () {
                Route::post('signin', [BhpanelController::class, 'signin'])
                    ->middleware('throttle:20,1')
                    ->name('signin');

                Route::match(['get', 'post'], 'wordpress', [BhpanelController::class, 'wordpress'])
                    ->middleware('throttle:40,1')
                    ->name('wordpress');

                Route::match(['get', 'post'], 'domains', [BhpanelController::class, 'domains'])
                    ->middleware('throttle:40,1')
                    ->name('domains');

                Route::match(['get', 'post'], 'email', [BhpanelController::class, 'email'])
                    ->middleware('throttle:40,1')
                    ->name('email');

                Route::match(['get', 'post'], 'databases', [BhpanelController::class, 'databases'])
                    ->middleware('throttle:40,1')
                    ->name('databases');

                Route::match(['get', 'post'], 'ssl', [BhpanelController::class, 'ssl'])
                    ->middleware('throttle:40,1')
                    ->name('ssl');

                Route::match(['get', 'post'], 'security', [BhpanelController::class, 'security'])
                    ->middleware('throttle:30,1')
                    ->name('security');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa'])
            ->prefix('admin')
            ->name('admin.bhpanel.')
            ->group(function () {
                Route::get('servers/{server}/bhpanel/sso', [BhpanelController::class, 'adminServerSso'])
                    ->middleware('admin.permission:manage_servers')
                    ->name('server-sso');
                Route::get('services/{service}/bhpanel/sso', [BhpanelController::class, 'adminServiceSso'])
                    ->middleware('admin.permission:manage_services')
                    ->name('service-sso');
            });
    }
}

<?php

namespace Modules\Addons\ResellerCenter;

use App\Events\InvoicePaid;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\ResellerCenter\Http\Admin\ResellerAdminController;
use Modules\Addons\ResellerCenter\Http\Client\ResellerPortalController;
use Modules\Addons\ResellerCenter\Services\ResellerService;

class ResellerCenterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'resellercenter');

        Event::listen(InvoicePaid::class, function (InvoicePaid $event) {
            try {
                app(ResellerService::class)->processPaidInvoice($event->invoice);
            } catch (\Throwable $e) {
                report($e);
            }
        });

        Route::middleware(['web', 'banned.ip', 'client.permission', 'auth', '2fa'])
            ->prefix('client/reseller')
            ->name('client.reseller.')
            ->group(function () {
                Route::get('/', [ResellerPortalController::class, 'index'])->name('index');
                Route::post('branding', [ResellerPortalController::class, 'updateBranding'])
                    ->middleware('throttle:15,1')->name('branding');
                Route::post('promos', [ResellerPortalController::class, 'storePromo'])
                    ->middleware('throttle:15,1')->name('promos.store');
                Route::delete('promos/{promo}', [ResellerPortalController::class, 'destroyPromo'])
                    ->name('promos.destroy');
                Route::post('gateway', [ResellerPortalController::class, 'saveGateway'])
                    ->middleware('throttle:15,1')->name('gateway.save');
                Route::post('services/{service}/suspend', [ResellerPortalController::class, 'suspendService'])
                    ->middleware('throttle:10,1')->name('services.suspend');
                Route::post('services/{service}/unsuspend', [ResellerPortalController::class, 'unsuspendService'])
                    ->middleware('throttle:10,1')->name('services.unsuspend');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:edit_clients'])
            ->prefix('admin/extensions/reseller-center')
            ->name('admin.extensions.reseller-center.')
            ->group(function () {
                Route::get('/', [ResellerAdminController::class, 'dashboard'])->name('index');
                Route::get('resellers', [ResellerAdminController::class, 'resellers'])->name('resellers');
                Route::post('resellers', [ResellerAdminController::class, 'storeReseller'])->name('resellers.store');
                Route::get('resellers/{reseller}', [ResellerAdminController::class, 'showReseller'])->name('resellers.show');
                Route::put('resellers/{reseller}', [ResellerAdminController::class, 'updateReseller'])->name('resellers.update');
                Route::delete('resellers/{reseller}', [ResellerAdminController::class, 'destroyReseller'])->name('resellers.destroy');
                Route::post('resellers/{reseller}/clients', [ResellerAdminController::class, 'assignClient'])->name('resellers.assign-client');
                Route::delete('resellers/{reseller}/clients/{link}', [ResellerAdminController::class, 'unassignClient'])->name('resellers.unassign-client');

                Route::get('groups', [ResellerAdminController::class, 'groups'])->name('groups');
                Route::post('groups', [ResellerAdminController::class, 'storeGroup'])->name('groups.store');
                Route::put('groups/{group}', [ResellerAdminController::class, 'updateGroup'])->name('groups.update');
                Route::delete('groups/{group}', [ResellerAdminController::class, 'destroyGroup'])->name('groups.destroy');
                Route::post('groups/{group}/pricing', [ResellerAdminController::class, 'storePricing'])->name('groups.pricing.store');
                Route::delete('pricing/{pricing}', [ResellerAdminController::class, 'destroyPricing'])->name('pricing.destroy');

                Route::get('payouts', [ResellerAdminController::class, 'payouts'])->name('payouts');
                Route::post('payouts/{payout}/mark-paid', [ResellerAdminController::class, 'markPayoutPaid'])
                    ->middleware('throttle:20,1')->name('payouts.mark-paid');
                Route::post('payouts/mass', [ResellerAdminController::class, 'massPayout'])
                    ->middleware('throttle:10,1')->name('payouts.mass');

                Route::get('utilities', [ResellerAdminController::class, 'utilities'])->name('utilities');
                Route::get('export', [ResellerAdminController::class, 'exportCsv'])->name('export');
                Route::post('logs/clear', [ResellerAdminController::class, 'clearLogs'])->name('logs.clear');
            });
    }
}

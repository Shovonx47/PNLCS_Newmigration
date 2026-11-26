<?php

namespace Modules\Addons\InvoiceExport;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\InvoiceExport\Http\Admin\InvoiceExportController;

class InvoiceExportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'invoiceexport');

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:view_invoices'])
            ->prefix('admin/extensions/invoice-export')
            ->name('admin.extensions.invoice-export.')
            ->group(function () {
                Route::get('/', [InvoiceExportController::class, 'index'])->name('index');
                Route::post('download', [InvoiceExportController::class, 'download'])->name('download');
            });
    }
}

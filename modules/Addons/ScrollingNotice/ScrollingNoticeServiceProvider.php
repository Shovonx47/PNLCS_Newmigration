<?php

namespace Modules\Addons\ScrollingNotice;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\ScrollingNotice\Http\Admin\ScrollingNoticeAdminController;

class ScrollingNoticeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'scrollingnotice');

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_announcements'])
            ->prefix('admin/extensions/scrolling-notices')
            ->name('admin.extensions.scrolling-notices.')
            ->group(function () {
                Route::get('/', [ScrollingNoticeAdminController::class, 'index'])->name('index');
                Route::post('/', [ScrollingNoticeAdminController::class, 'store'])->name('store');
                Route::put('{notice}', [ScrollingNoticeAdminController::class, 'update'])->name('update');
                Route::post('{notice}/toggle', [ScrollingNoticeAdminController::class, 'toggle'])->name('toggle');
                Route::delete('{notice}', [ScrollingNoticeAdminController::class, 'destroy'])->name('destroy');
            });
    }
}

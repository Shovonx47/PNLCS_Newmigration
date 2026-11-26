<?php

namespace Modules\Addons\SoftcentsSms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\SoftcentsSms\Http\Admin\SmsAdminController;
use Modules\Addons\SoftcentsSms\Http\OtpController;
use Modules\Addons\SoftcentsSms\Services\SoftcentsSmsSender;

class SoftcentsSmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Phone verification and anything else asking the container for an SMS
        // sender uses this account, unless an operator has already bound one.
        $this->app->bindIf(SmsSender::class, SoftcentsSmsSender::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'softcentssms');

        // Registration OTP: guests only, and each call is a real SMS - so the
        // route itself is throttled as well as the service.
        Route::middleware(['web', 'banned.ip'])
            ->prefix('otp')->name('client.otp.')
            ->group(function () {
                Route::post('send', [OtpController::class, 'send'])->middleware('throttle:6,1')->name('send');
                Route::post('verify', [OtpController::class, 'verify'])->middleware('throttle:12,1')->name('verify');
                Route::get('status', [OtpController::class, 'status'])->name('status');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin/extensions/softcents-sms')->name('admin.extensions.softcents-sms.')
            ->group(function () {
                Route::get('/', [SmsAdminController::class, 'index'])->name('index');
                Route::post('settings', [SmsAdminController::class, 'updateSettings'])->name('settings');
                Route::post('templates/{template}', [SmsAdminController::class, 'updateTemplate'])->name('templates.update');
                Route::post('test', [SmsAdminController::class, 'test'])->name('test');
            });
    }
}

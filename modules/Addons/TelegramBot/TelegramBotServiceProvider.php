<?php

namespace Modules\Addons\TelegramBot;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Addons\TelegramBot\Console\SetWebhookCommand;
use Modules\Addons\TelegramBot\Http\Admin\TelegramAdminController;
use Modules\Addons\TelegramBot\Http\ClientTelegramController;
use Modules\Addons\TelegramBot\Http\WebhookController;

class TelegramBotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SetWebhookCommand::class]);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/views', 'telegrambot');

        // Telegram cannot carry a CSRF token; the update is authenticated by
        // the secret header instead (WebhookController).
        Route::withoutMiddleware([PreventRequestForgery::class])
            ->post('telegram/webhook', WebhookController::class)
            ->name('telegram.webhook');

        Route::middleware(['web', 'banned.ip', 'client.permission', 'auth', '2fa'])
            ->prefix('client/telegram')->name('client.telegram.')
            ->group(function () {
                Route::get('/', [ClientTelegramController::class, 'index'])->name('index');
                Route::post('connect', [ClientTelegramController::class, 'connect'])->middleware('throttle:10,1')->name('connect');
                Route::post('disconnect', [ClientTelegramController::class, 'disconnect'])->name('disconnect');
                Route::post('test', [ClientTelegramController::class, 'test'])->middleware('throttle:5,1')->name('test');
                Route::post('toggle', [ClientTelegramController::class, 'toggle'])->name('toggle');
            });

        Route::middleware(['web', 'admin.auth', 'admin.2fa', 'admin.permission:manage_services'])
            ->prefix('admin/extensions/telegram-bot')->name('admin.extensions.telegram.')
            ->group(function () {
                Route::get('/', [TelegramAdminController::class, 'index'])->name('index');
                Route::post('settings', [TelegramAdminController::class, 'updateSettings'])->name('settings');
                Route::post('webhook', [TelegramAdminController::class, 'registerWebhook'])->name('webhook');
                Route::post('clients/{telegramClient}/test', [TelegramAdminController::class, 'test'])->name('clients.test');
                Route::post('clients/{telegramClient}/disconnect', [TelegramAdminController::class, 'disconnect'])->name('clients.disconnect');
            });
    }
}

<?php

namespace Modules\Addons\TelegramBot;

use App\Contracts\AddonModuleInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 - the client Telegram bot.
 *
 * Extends the panel's admin Telegram notifier with a customer-facing bot: a
 * one-tap connect from the dashboard, /invoices and /status commands, mute and
 * test controls, and invoice alerts carrying a signed payment link.
 */
class TelegramBotModule implements AddonModuleInterface
{
    public function getName(): string
    {
        return 'telegram_bot';
    }

    public function getDisplayName(): string
    {
        return 'Telegram Client Bot';
    }

    public function getDescription(): string
    {
        return 'Let customers connect their Telegram and receive invoice, service and support alerts with one-tap payment links, plus /invoices and /status commands.';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getAuthor(): string
    {
        return 'SoftCents Systems';
    }

    public function activate(): array
    {
        if (! Schema::hasTable('telegram_clients') || ! Schema::hasTable('telegram_messages')) {
            return ['success' => false, 'message' => 'Telegram tables are missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'Telegram bot activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Telegram bot deactivated. Connections kept.'];
    }

    public function upgrade(string $fromVersion): array
    {
        return ['success' => true, 'message' => 'Upgraded from '.$fromVersion];
    }

    public function output(Request $request): string
    {
        return '';
    }

    public function config(): array
    {
        return [];
    }

    public function sidebar(): array
    {
        return [[
            'label' => 'Telegram Bot',
            'icon' => 'send',
            'url' => '/admin/extensions/telegram-bot',
        ]];
    }

    public static function isEnabled(): bool
    {
        return DB::table('settings')
            ->where('setting', 'addon_telegram_bot_active')
            ->where('value', '1')
            ->exists();
    }
}

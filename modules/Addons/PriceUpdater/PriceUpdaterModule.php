<?php

namespace Modules\Addons\PriceUpdater;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PriceUpdaterModule implements AddonModuleInterface
{
    public const NAME = 'price_updater';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Bulk Product & TLD Price Updater';
    }

    public function getDescription(): string
    {
        return 'Preview and apply percentage or fixed-amount bulk price updates across products, active recurring services, and domain TLD extensions with audit logging.';
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
        if (! Schema::hasTable('price_updater_logs')) {
            return ['success' => false, 'message' => 'The price_updater_logs table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'Bulk Price Updater activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Bulk Price Updater deactivated.'];
    }

    public function config(): array
    {
        return [];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Bulk Price Updater',
                'icon' => 'sliders',
                'url' => '/admin/extensions/price-updater',
            ],
        ];
    }

    public function upgrade(string $fromVersion): array
    {
        return ['success' => true, 'message' => "Upgraded from {$fromVersion}"];
    }

    public function output(Request $request): string
    {
        return '';
    }

    public static function isEnabled(): bool
    {
        try {
            return Setting::get('addon_'.self::NAME.'_active', '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }
}

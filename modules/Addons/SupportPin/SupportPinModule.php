<?php

namespace Modules\Addons\SupportPin;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SupportPinModule implements AddonModuleInterface
{
    public const NAME = 'support_pin';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Client Support PIN';
    }

    public function getDescription(): string
    {
        return 'Rotating 24-hour 6-digit customer Support PIN on the client dashboard with rate-limited staff verification lookup and audit logging.';
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
        if (! Schema::hasTable('support_pins')) {
            return ['success' => false, 'message' => 'The support_pins table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'Client Support PIN activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Client Support PIN deactivated.'];
    }

    public function config(): array
    {
        return [];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Support PIN Lookup',
                'icon' => 'key',
                'url' => '/admin/extensions/support-pin',
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

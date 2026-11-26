<?php

namespace Modules\Addons\ServerIpUnblock;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ServerIpUnblockModule implements AddonModuleInterface
{
    public const NAME = 'unblock_ip';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Client Server IP Unblocker';
    }

    public function getDescription(): string
    {
        return 'Self-service firewall IP unblocker for active client services with pre-filled visitor IP, rate limiting, and staff audit logs.';
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
        if (! Schema::hasTable('ip_unblock_logs')) {
            return ['success' => false, 'message' => 'The ip_unblock_logs table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'Client Server IP Unblocker activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Client Server IP Unblocker deactivated.'];
    }

    public function config(): array
    {
        return [];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'IP Unblock Logs',
                'icon' => 'shield',
                'url' => '/admin/extensions/unblock-ip',
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

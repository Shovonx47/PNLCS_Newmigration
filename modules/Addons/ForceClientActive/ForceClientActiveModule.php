<?php

namespace Modules\Addons\ForceClientActive;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ForceClientActiveModule implements AddonModuleInterface
{
    public const NAME = 'force_client_active';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Force Client Active';
    }

    public function getDescription(): string
    {
        return 'Keeps protected client accounts in Active status so portal login and payment access are never blocked by automatic inactivity closure.';
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
        if (! Schema::hasTable('forced_active_clients')) {
            return ['success' => false, 'message' => 'The forced_active_clients table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'Force Client Active activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Force Client Active deactivated.'];
    }

    public function config(): array
    {
        return [
            ['name' => 'restore_inactive_clients', 'label' => 'Automatically Restore Inactive Clients (leaves Closed untouched unless protected)', 'type' => 'yesno', 'default' => '1'],
            ['name' => 'enforce_all_clients', 'label' => 'Keep All Clients Active Globally (including Closed)', 'type' => 'yesno', 'default' => '0'],
        ];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Force Client Active',
                'icon' => 'user-check',
                'url' => '/admin/extensions/force-client-active',
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

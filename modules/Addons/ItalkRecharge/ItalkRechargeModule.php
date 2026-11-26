<?php

namespace Modules\Addons\ItalkRecharge;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 - multi-provider IPTSP auto-recharge.
 *
 * Providers (iTalk/ICC scraper, Ranksitt, WebVoice) with encrypted
 * credentials, a client PIN per provider, and an audit log that a paid
 * invoice drives automatically. Credentials never leave the server: the admin
 * screen shows whether one is stored, never its value.
 */
class ItalkRechargeModule implements AddonModuleInterface
{
    public function getName(): string
    {
        return 'italk_recharge';
    }

    public function getDisplayName(): string
    {
        return 'IPTSP Multi-Provider Recharge';
    }

    public function getDescription(): string
    {
        return 'Automated BDT top-ups for iTalk, ICC Talk, Ranksitt and WebVoice accounts. Encrypted provider credentials, per-client PIN mapping, paid-invoice trigger, retry and audit log.';
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
        foreach (['italk_recharge_providers', 'italk_recharge_accounts', 'italk_recharge_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                return ['success' => false, 'message' => 'Recharge tables are missing. Run `php artisan migrate` first.'];
            }
        }

        return ['success' => true, 'message' => 'IPTSP Multi-Provider Recharge activated. Providers are configured under Extensions -> IPTSP Recharge.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'IPTSP Recharge deactivated. Providers, PIN accounts and the audit log are kept.'];
    }

    public function config(): array
    {
        return [
            [
                'name' => 'auto_recharge',
                'label' => 'Top up automatically when a top-up invoice is paid',
                'type' => 'checkbox',
                'default' => '1',
            ],
        ];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'IPTSP Recharge',
                'icon' => 'credit-card',
                'url' => '/admin/extensions/iptsp-recharge',
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
            return Setting::get('addon_italk_recharge_active', '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }

    /** Whether a paid invoice may start a top-up; on unless switched off. */
    public static function autoRechargeEnabled(): bool
    {
        try {
            return Setting::get('addon_italk_recharge_auto_recharge', '1') !== '0';
        } catch (\Throwable) {
            return true;
        }
    }
}

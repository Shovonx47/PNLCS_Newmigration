<?php

namespace Modules\Addons\ItalkBalance;

use App\Contracts\AddonModuleInterface;
use App\Models\AddonSetting;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 - IPTSP live balance for iTalk and ICC Talk.
 *
 * Ported from the legacy `italk_balance` / `icc_balance` WHMCS addons: an
 * operator maps a customer's service to the IP number (PIN) it holds, picks
 * which balance endpoint that number belongs to, and the customer's service
 * page shows the live BDT balance with a rate-limited refresh.
 *
 * What is deliberately different from the legacy code:
 *  - the balance call is a single HTTPS request with certificate verification
 *    on; the legacy addon disabled TLS verification on both the fetch and the
 *    SSO calls it made elsewhere;
 *  - the refresh is an authenticated POST with CSRF and an ownership check,
 *    not a `clientarea.php?italk_ajax=1` GET that trusted a client-supplied id;
 *  - the cached balance is only replaced by a numeric answer, so a provider
 *    that starts returning an error page cannot blank what the customer saw.
 */
class ItalkBalanceModule implements AddonModuleInterface
{
    public function getName(): string
    {
        return 'italk_balance';
    }

    public function getDisplayName(): string
    {
        return 'iTalk / ICC Live Balance';
    }

    public function getDescription(): string
    {
        return 'Per-service IP number (PIN) mapping with a live BDT balance card on the client service page and a rate-limited refresh. Supports multiple balance endpoints for iTalk and ICC Talk.';
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
        if (! Schema::hasTable('italk_balance_endpoints') || ! Schema::hasTable('italk_balance_services')) {
            return ['success' => false, 'message' => 'Balance tables are missing. Run `php artisan migrate` first.'];
        }

        // The two endpoints the production WHMCS install actually uses. Seeded
        // once so a fresh activation has something to map against; an operator
        // may add or remove endpoints afterwards.
        if (DB::table('italk_balance_endpoints')->count() === 0) {
            DB::table('italk_balance_endpoints')->insert([
                [
                    'label' => 'iTalk API',
                    'base_url' => 'https://sip.italk.com.bd/iconiptsp/getclientbalance.do?pin=',
                    'program' => 'italk',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'label' => 'icc Talk',
                    'base_url' => 'https://icctalk.com/ICC/getclientbalance.do?pin=',
                    'program' => 'icc',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        AddonSetting::setSetting('italk_balance', 'refresh_cooldown', AddonSetting::getSetting('italk_balance', 'refresh_cooldown', '30'));

        return ['success' => true, 'message' => 'iTalk / ICC Balance activated. Map services under Extensions -> iTalk / ICC Balance.'];
    }

    public function deactivate(): array
    {
        // Data is kept: deactivating hides the card and its pages, it does not
        // throw away the mapping an operator entered.
        return ['success' => true, 'message' => 'iTalk / ICC Balance deactivated. Mappings and cached balances are kept.'];
    }

    public function config(): array
    {
        return [
            [
                'name' => 'refresh_cooldown',
                'label' => 'Refresh cooldown (seconds)',
                'type' => 'text',
                'default' => '30',
            ],
        ];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'iTalk / ICC Balance',
                'icon' => 'phone',
                'url' => '/admin/extensions/italk-balance',
            ],
        ];
    }

    public function upgrade(string $fromVersion): array
    {
        return ['success' => true, 'message' => "Upgraded from {$fromVersion}"];
    }

    public function output(Request $request): string
    {
        // The real page is served by the addon's service provider route, which
        // has the theme layout, flash messages and the panel's own tables.
        return '';
    }

    /** Whether the addon is switched on; hooks and pages are only loaded then. */
    public static function isEnabled(): bool
    {
        try {
            return Setting::get('addon_italk_balance_active', '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }
}

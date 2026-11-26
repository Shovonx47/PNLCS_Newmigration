<?php

namespace Modules\Addons\ResellerCenter;

use App\Contracts\AddonModuleInterface;
use Illuminate\Http\Request;
use Modules\Addons\ResellerCenter\Models\ResellerGroup;

class ResellerCenterModule implements AddonModuleInterface
{
    public const NAME = 'reseller_center';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Resellers Center';
    }

    public function getDescription(): string
    {
        return 'Sub-reseller tiers, white-label portal, custom commission rules, reseller promo codes, and payout management.';
    }

    public function getVersion(): string
    {
        return '2.0.0';
    }

    public function getAuthor(): string
    {
        return 'SoftCents';
    }

    public function activate(): array
    {
        if (ResellerGroup::count() === 0) {
            ResellerGroup::create([
                'name' => 'Standard Resellers',
                'description' => 'Default reseller pricing tier',
                'permissions' => [
                    'can_access_clients' => 1,
                    'can_manage_orders' => 1,
                    'can_view_billing' => 1,
                    'can_use_promos' => 1,
                    'can_suspend' => 1,
                ],
                'payout_method' => 'credit_balance',
                'invoicing_method' => 'provider',
                'allow_deferred' => false,
                'credit_limit' => 1000.00,
                'allow_promos' => true,
                'allow_own_smtp' => false,
                'allow_suspend' => true,
                'allow_global_search' => true,
                'default_commission_percent' => 10.00,
                'settings' => [],
            ]);
        }

        return ['success' => true, 'message' => 'Resellers Center activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Resellers Center deactivated. Reseller records preserved.'];
    }

    public function upgrade(string $fromVersion): array
    {
        return ['success' => true];
    }

    public function output(Request $request): string
    {
        return '<div class="card"><div class="card-body">'
            .'<p>Manage reseller tiers, end-client mappings, commission payouts, and white-label settings from the dedicated dashboard.</p>'
            .'<a href="'.e(route('admin.extensions.reseller-center.index')).'" class="btn btn-primary btn-sm">Open Resellers Center</a>'
            .'</div></div>';
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Resellers Center',
                'icon' => 'store',
                'url' => '/admin/extensions/reseller-center',
                'children' => [
                    ['label' => 'Dashboard', 'url' => '/admin/extensions/reseller-center'],
                    ['label' => 'Resellers', 'url' => '/admin/extensions/reseller-center/resellers'],
                    ['label' => 'Pricing Groups', 'url' => '/admin/extensions/reseller-center/groups'],
                    ['label' => 'Payouts', 'url' => '/admin/extensions/reseller-center/payouts'],
                    ['label' => 'Utilities & Logs', 'url' => '/admin/extensions/reseller-center/utilities'],
                ],
            ],
        ];
    }

    public function config(): array
    {
        return [
            'invoicing_method' => [
                'FriendlyName' => 'Invoicing Method',
                'Type' => 'dropdown',
                'Options' => ['provider' => 'Provider Issues Invoices', 'reseller' => 'Reseller Issues Invoices'],
                'Default' => 'provider',
                'Description' => 'Default invoice issuer mode for new reseller tiers.',
            ],
            'payout_method' => [
                'FriendlyName' => 'Default Payout Method',
                'Type' => 'dropdown',
                'Options' => ['credit_balance' => 'Credit Balance', 'paypal' => 'PayPal Payouts', 'manual' => 'Manual Settlement'],
                'Default' => 'credit_balance',
            ],
            'allow_promos' => [
                'FriendlyName' => 'Allow Reseller Promos',
                'Type' => 'yesno',
                'Default' => '1',
            ],
            'allow_own_smtp' => [
                'FriendlyName' => 'Allow Own SMTP',
                'Type' => 'yesno',
                'Default' => '0',
            ],
            'allow_suspend' => [
                'FriendlyName' => 'Allow End-Client Suspend',
                'Type' => 'yesno',
                'Default' => '1',
            ],
            'paypal_client_id' => [
                'FriendlyName' => 'PayPal Client ID',
                'Type' => 'text',
                'Description' => 'Optional PayPal REST client ID for automated reseller payouts.',
            ],
            'paypal_secret' => [
                'FriendlyName' => 'PayPal Secret',
                'Type' => 'password',
                'secret' => true,
                'Description' => 'Stored encrypted; leave blank to keep current value.',
            ],
            'api_token' => [
                'FriendlyName' => 'Internal API Token',
                'Type' => 'password',
                'secret' => true,
                'Description' => 'Stored encrypted for reseller integration verification.',
            ],
        ];
    }
}

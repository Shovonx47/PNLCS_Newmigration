<?php

namespace Modules\Addons\RechargeRequests;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 - the customer's own IP number balance top-up.
 *
 * A card on the client dashboard and a page of its own raise a real PNLCS
 * invoice for the amount, and the operator gets a queue of who asked for what.
 * The Bengali wrong-number warning is carried over verbatim from production:
 * a top-up sent to a mistyped number is not refundable, and the customer is
 * told so before they pay.
 */
class RechargeRequestsModule implements AddonModuleInterface
{
    public function getName(): string
    {
        return 'recharge_requests';
    }

    public function getDisplayName(): string
    {
        return 'IP Number Recharge Requests';
    }

    public function getDescription(): string
    {
        return 'Client-facing IP number calling balance recharge: dashboard card and page, instant invoice generation, and an admin queue with Pending / Completed / Cancelled states.';
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
        if (! Schema::hasTable('recharge_requests')) {
            return ['success' => false, 'message' => 'The recharge_requests table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'IP Number Recharge Requests activated. Customers can raise top-up invoices from their dashboard.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'IP Number Recharge Requests deactivated. Existing requests and invoices are kept.'];
    }

    public function config(): array
    {
        return [
            [
                'name' => 'minimum_amount',
                'label' => 'Minimum top-up (BDT)',
                'type' => 'text',
                'default' => '500',
            ],
        ];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Recharge Requests',
                'icon' => 'inbox',
                'url' => '/admin/extensions/recharge-requests',
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
            return Setting::get('addon_recharge_requests_active', '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }
}

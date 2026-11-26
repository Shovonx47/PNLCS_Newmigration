<?php

namespace Modules\Addons\CancelInvoices;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CancelInvoicesModule implements AddonModuleInterface
{
    public const NAME = 'cancel_invoices';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Auto-Cancel Unpaid Invoices';
    }

    public function getDescription(): string
    {
        return 'Automatically cancels stale unpaid service/renewal and Add-Funds invoices after a configurable number of days, with client, product, and service exclusions.';
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
        if (! Schema::hasTable('cancel_invoice_exclusions')) {
            return ['success' => false, 'message' => 'The cancel_invoice_exclusions table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'Auto-Cancel Unpaid Invoices activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Auto-Cancel Unpaid Invoices deactivated.'];
    }

    public function config(): array
    {
        return [
            ['name' => 'enable_days', 'label' => 'Auto-Cancel Overdue Service/Domain Invoices', 'type' => 'yesno', 'default' => '1'],
            ['name' => 'days', 'label' => 'Days Past Due Before Cancel', 'type' => 'text', 'default' => '7'],
            ['name' => 'enable_all_invoices', 'label' => 'Also Cancel Custom / Manual Unpaid Invoices', 'type' => 'yesno', 'default' => '0'],
            ['name' => 'all_invoice_days', 'label' => 'Days Past Due for Custom Invoices', 'type' => 'text', 'default' => '7'],
            ['name' => 'enable_add_funds', 'label' => 'Auto-Cancel Unpaid Add-Funds Invoices', 'type' => 'yesno', 'default' => '1'],
            ['name' => 'add_funds_days', 'label' => 'Add-Funds Invoice Expiry (Days)', 'type' => 'text', 'default' => '7'],
        ];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Auto-Cancel Invoices',
                'icon' => 'file-x',
                'url' => '/admin/extensions/cancel-invoices',
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

<?php

namespace Modules\Addons\PortalExtras;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PortalExtrasModule implements AddonModuleInterface
{
    public const NAME = 'portal_extras';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'SoftCents Live Stats & Service Controls';
    }

    public function getDescription(): string
    {
        return 'Admin and client live stats bars, client dashboard credit ledger & account summary cards, self-service suspend/terminate controls, and service ownership transfer workflow.';
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
        if (! Schema::hasTable('service_transfer_requests')) {
            return ['success' => false, 'message' => 'The service_transfer_requests table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'Portal Extras activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Portal Extras deactivated.'];
    }

    public function config(): array
    {
        return [
            ['name' => 'enable_admin_top_stats', 'label' => 'Show Admin Top Live Stats Bar', 'type' => 'yesno', 'default' => '1'],
            ['name' => 'enable_client_top_stats', 'label' => 'Show Client Top Summary Strip', 'type' => 'yesno', 'default' => '1'],
            ['name' => 'enable_self_suspend', 'label' => 'Allow Client Self-Service Suspend / Terminate', 'type' => 'yesno', 'default' => '1'],
            ['name' => 'enable_service_transfer', 'label' => 'Allow Service Ownership Transfer Requests', 'type' => 'yesno', 'default' => '1'],
            ['name' => 'whatsapp_support_number', 'label' => 'WhatsApp Reactivation Number (digits only, e.g. 8801329646688)', 'type' => 'text', 'default' => '8801329646688'],
        ];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Service Transfers',
                'icon' => 'repeat',
                'url' => '/admin/extensions/service-transfers',
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

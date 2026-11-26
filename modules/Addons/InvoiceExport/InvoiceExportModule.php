<?php

namespace Modules\Addons\InvoiceExport;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;

class InvoiceExportModule implements AddonModuleInterface
{
    public const NAME = 'invoice_export';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Mass Invoice PDF & CSV Export';
    }

    public function getDescription(): string
    {
        return 'Export filtered invoices as a single ZIP archive containing rendered PDF documents and a formula-safe accounting index CSV.';
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
        return ['success' => true, 'message' => 'Mass Invoice PDF Export activated.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Mass Invoice PDF Export deactivated.'];
    }

    public function config(): array
    {
        return [];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'Invoice PDF Export',
                'icon' => 'archive',
                'url' => '/admin/extensions/invoice-export',
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

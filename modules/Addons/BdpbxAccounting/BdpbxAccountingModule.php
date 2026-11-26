<?php

namespace Modules\Addons\BdpbxAccounting;

use App\Contracts\AddonModuleInterface;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 - BDPBX multi-server daily accounting.
 *
 * A daily snapshot command and an admin dashboard showing, per PBX server and
 * combined: active services and MRR, revenue collected today and this month,
 * and what is unpaid, with a historical date picker. The SuperAdmin PBX SSO
 * already exists in the Bdpbx server module and is linked from the dashboard.
 */
class BdpbxAccountingModule implements AddonModuleInterface
{
    public function getName(): string
    {
        return 'bdpbx_accounting';
    }

    public function getDisplayName(): string
    {
        return 'BDPBX Daily Accounting';
    }

    public function getDescription(): string
    {
        return 'Daily per-server and combined Cloud PBX accounting: active MRR, paid today and this month, unpaid services and invoices, historical snapshots.';
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
        if (! Schema::hasTable('bdpbx_daily_stats')) {
            return ['success' => false, 'message' => 'The bdpbx_daily_stats table is missing. Run `php artisan migrate` first.'];
        }

        return ['success' => true, 'message' => 'BDPBX Daily Accounting activated. A snapshot is written daily at 06:50; run `php artisan pnlcs:bdpbx-accounting` for any other day.'];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'BDPBX Daily Accounting deactivated. Recorded snapshots are kept.'];
    }

    public function config(): array
    {
        return [];
    }

    public function sidebar(): array
    {
        return [
            [
                'label' => 'BDPBX Accounting',
                'icon' => 'chart-bar',
                'url' => '/admin/extensions/bdpbx-accounting',
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
            return Setting::get('addon_bdpbx_accounting_active', '0') === '1';
        } catch (\Throwable) {
            return false;
        }
    }
}

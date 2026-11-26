<?php

namespace Modules\Addons\WhatsappPro;

use App\Contracts\AddonModuleInterface;
use App\Support\Mojibake;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Addons\WhatsappPro\Models\WaSettings;
use Modules\Addons\WhatsappPro\Models\WaTemplate;
use Modules\Addons\WhatsappPro\Support\DefaultTemplates;

/**
 * Phase 3 - WhatsApp.
 *
 * Ported from modules/addons/bdhost_wa (4,457 lines): the 35 lifecycle
 * templates, the queue the legacy addon introduced so a slow gateway could not
 * stall an order, bulk sends by segment and CSV, and the customer's own message
 * history. The gateway contract is unchanged, so the existing account works.
 */
class WhatsappProModule implements AddonModuleInterface
{
    public function getName(): string
    {
        return 'whatsapp_pro';
    }

    public function getDisplayName(): string
    {
        return 'WhatsApp Pro';
    }

    public function getDescription(): string
    {
        return 'Automatic WhatsApp on 30+ billing, service and ticket events, queued so nothing waits on the gateway, with bulk sends, segments, CSV import and per-customer history.';
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
        if (! Schema::hasTable('whatsapp_templates') || ! Schema::hasTable('whatsapp_queue')) {
            return ['success' => false, 'message' => 'WhatsApp tables are missing. Run `php artisan migrate` first.'];
        }

        WaSettings::current();
        $created = $this->seedTemplates();

        return ['success' => true, 'message' => "WhatsApp activated. {$created} templates installed."];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'WhatsApp deactivated. Templates, queue and logs kept.'];
    }

    public function upgrade(string $fromVersion): array
    {
        return ['success' => true, 'message' => 'Upgraded from '.$fromVersion];
    }

    public function output(Request $request): string
    {
        return '';
    }

    public function config(): array
    {
        return [];
    }

    public function sidebar(): array
    {
        return [[
            'label' => 'WhatsApp Pro',
            'icon' => 'whatsapp',
            'url' => '/admin/extensions/whatsapp-pro',
        ]];
    }

    public function seedTemplates(): int
    {
        $created = 0;

        foreach (DefaultTemplates::all() as $row) {
            if (! WaTemplate::where('name', $row['name'])->exists()) {
                // The legacy table stored these double-encoded; repair before
                // the emoji reaches a customer's phone.
                $row['template'] = Mojibake::repair($row['template'] ?? '');
                $row['description'] = Mojibake::repair($row['description'] ?? '');

                WaTemplate::create($row + ['active' => true]);
                $created++;
            }
        }

        return $created;
    }

    public static function isEnabled(): bool
    {
        return DB::table('settings')
            ->where('setting', 'addon_whatsapp_pro_active')
            ->where('value', '1')
            ->exists();
    }
}

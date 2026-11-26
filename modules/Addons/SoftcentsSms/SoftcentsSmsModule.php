<?php

namespace Modules\Addons\SoftcentsSms;

use App\Contracts\AddonModuleInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Addons\SoftcentsSms\Models\SmsSetting;
use Modules\Addons\SoftcentsSms\Models\SmsTemplate;
use Modules\Addons\SoftcentsSms\Support\DefaultTemplates;

/**
 * Phase 3 - Softcents SMS.
 *
 * The operator's 29 templates, the registration SMS OTP gate, and a delivery
 * log. Ported from modules/addons/SOFTCENTS_SMS, where every event had its own
 * hook file with the merge fields written out by hand; here the fields come
 * from App\Support\NotificationVariables and the templates drive the rest.
 */
class SoftcentsSmsModule implements AddonModuleInterface
{
    public function getName(): string
    {
        return 'softcents_sms';
    }

    public function getDisplayName(): string
    {
        return 'Softcents SMS';
    }

    public function getDescription(): string
    {
        return 'Event-driven SMS with the operator\'s templates, live gateway balance, delivery log, and an SMS OTP gate on customer registration.';
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
        if (! Schema::hasTable('softcents_sms_templates') || ! Schema::hasTable('softcents_sms_settings')) {
            return ['success' => false, 'message' => 'SMS tables are missing. Run `php artisan migrate` first.'];
        }

        SmsSetting::current();
        $created = $this->seedTemplates();

        return [
            'success' => true,
            'message' => "SMS activated. {$created} templates installed.",
        ];
    }

    public function deactivate(): array
    {
        // Nothing is dropped: the templates are the operator's own copy and the
        // log is the only record of what was sent. The legacy addon's
        // deactivate ran uninstall.sql and destroyed both.
        return ['success' => true, 'message' => 'SMS deactivated. Templates and logs kept.'];
    }

    public function upgrade(string $fromVersion): array
    {
        return ['success' => true, 'message' => 'Upgraded from '.$fromVersion];
    }

    public function output(Request $request): string
    {
        return '';
    }

    /** The real screen is the addon's own route; nothing is configured here. */
    public function config(): array
    {
        return [];
    }

    public function sidebar(): array
    {
        return [[
            'label' => 'Softcents SMS',
            'icon' => 'comment',
            'url' => '/admin/extensions/softcents-sms',
        ]];
    }

    /** Install any template the operator does not have yet. */
    public function seedTemplates(): int
    {
        $created = 0;

        foreach (DefaultTemplates::all() as $row) {
            $exists = SmsTemplate::where('name', $row['name'])->exists();

            if (! $exists) {
                SmsTemplate::create($row + ['active' => true]);
                $created++;
            }
        }

        return $created;
    }

    public static function isEnabled(): bool
    {
        return DB::table('settings')
            ->where('setting', 'addon_softcents_sms_active')
            ->where('value', '1')
            ->exists();
    }
}

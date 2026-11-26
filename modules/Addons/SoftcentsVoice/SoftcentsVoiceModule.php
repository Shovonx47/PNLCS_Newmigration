<?php

namespace Modules\Addons\SoftcentsVoice;

use App\Contracts\AddonModuleInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Addons\SoftcentsVoice\Models\VoiceEvent;
use Modules\Addons\SoftcentsVoice\Models\VoiceSettings;
use Modules\Addons\SoftcentsVoice\Support\DefaultEvents;

/**
 * Phase 3 - Softcents Voice.
 *
 * Ported from modules/addons/softcents_voice (2,387 lines): 24 events, a
 * recording and caller id per product group, a calling window, and a retry
 * queue. The gateway contract and the operator's audio ids are unchanged.
 */
class SoftcentsVoiceModule implements AddonModuleInterface
{
    public function getName(): string
    {
        return 'softcents_voice';
    }

    public function getDisplayName(): string
    {
        return 'Softcents Voice';
    }

    public function getDescription(): string
    {
        return 'Automated outbound voice calls on 24 billing, service and security events, with per-group recordings, a 09:00-21:00 calling window and automatic retries.';
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
        if (! Schema::hasTable('voice_events') || ! Schema::hasTable('voice_settings')) {
            return ['success' => false, 'message' => 'Voice tables are missing. Run `php artisan migrate` first.'];
        }

        VoiceSettings::current();
        $created = 0;

        foreach (DefaultEvents::all() as $row) {
            if (! VoiceEvent::where('event_key', $row['event_key'])->exists()) {
                VoiceEvent::create($row);
                $created++;
            }
        }

        return ['success' => true, 'message' => "Voice activated. {$created} events installed (all switched off)."];
    }

    public function deactivate(): array
    {
        return ['success' => true, 'message' => 'Voice deactivated. Events, recordings and logs kept.'];
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
            'label' => 'Softcents Voice',
            'icon' => 'phone-volume',
            'url' => '/admin/extensions/softcents-voice',
        ]];
    }

    public static function isEnabled(): bool
    {
        return DB::table('settings')
            ->where('setting', 'addon_softcents_voice_active')
            ->where('value', '1')
            ->exists();
    }
}

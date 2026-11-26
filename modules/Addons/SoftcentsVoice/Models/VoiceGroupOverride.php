<?php

namespace Modules\Addons\SoftcentsVoice\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceGroupOverride extends Model
{
    protected $table = 'voice_group_overrides';

    protected $fillable = ['group_id', 'event_key', 'audio_id', 'caller_id', 'is_enabled'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }
}

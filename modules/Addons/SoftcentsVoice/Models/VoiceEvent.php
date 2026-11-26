<?php

namespace Modules\Addons\SoftcentsVoice\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceEvent extends Model
{
    protected $table = 'voice_events';

    protected $fillable = ['event_key', 'category', 'name', 'description', 'audio_id', 'caller_id', 'is_enabled'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }
}

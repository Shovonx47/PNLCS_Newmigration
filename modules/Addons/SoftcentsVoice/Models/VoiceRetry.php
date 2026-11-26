<?php

namespace Modules\Addons\SoftcentsVoice\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceRetry extends Model
{
    protected $table = 'voice_retries';

    protected $fillable = [
        'client_id', 'phone', 'event_key', 'group_id', 'audio_id', 'caller_id',
        'initial_call_id', 'attempt', 'max_attempts', 'interval_minutes', 'status',
        'last_status_reason', 'next_attempt_at', 'completed_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'next_attempt_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}

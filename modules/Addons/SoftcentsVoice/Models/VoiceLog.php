<?php

namespace Modules\Addons\SoftcentsVoice\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceLog extends Model
{
    protected $table = 'voice_logs';

    protected $fillable = [
        'client_id', 'invoice_id', 'phone', 'event_key', 'audio_id', 'caller_id',
        'call_id', 'status', 'reason', 'attempt',
    ];
}

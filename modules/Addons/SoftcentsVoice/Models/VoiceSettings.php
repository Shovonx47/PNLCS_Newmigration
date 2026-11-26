<?php

namespace Modules\Addons\SoftcentsVoice\Models;

use Illuminate\Database\Eloquent\Model;

class VoiceSettings extends Model
{
    protected $table = 'voice_settings';

    protected $fillable = [
        'api_token', 'api_base_url', 'caller_id', 'calling_start', 'calling_end',
        'timezone', 'master_paused', 'retry_enabled', 'retry_interval_minutes', 'max_attempts',
    ];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'master_paused' => 'boolean',
            'retry_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $row = static::query()->orderBy('id')->first();

        return $row ?: static::create([
            'api_token' => '',
            'api_base_url' => 'https://vsms.softcents.com/api',
            'caller_id' => '376',
            'calling_start' => '09:00',
            'calling_end' => '21:00',
            'timezone' => 'Asia/Dhaka',
            'master_paused' => true,
            'retry_enabled' => true,
            'retry_interval_minutes' => 15,
            'max_attempts' => 3,
        ]);
    }

    public function configured(): bool
    {
        return trim((string) $this->api_token) !== '';
    }
}

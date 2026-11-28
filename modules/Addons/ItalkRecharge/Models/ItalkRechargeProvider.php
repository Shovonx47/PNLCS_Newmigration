<?php

namespace Modules\Addons\ItalkRecharge\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A top-up provider: where to reach it, which driver talks to it, and the
 * reseller credentials. Password and API key are encrypted at rest (the WHMCS
 * tables kept both in plaintext).
 */
class ItalkRechargeProvider extends Model
{
    protected $table = 'italk_recharge_providers';

    protected $fillable = [
        'name', 'base_url', 'base_path', 'integration_type', 'api_type',
        'username', 'password', 'api_key', 'logo_url', 'status', 'sort_order',
    ];

    protected $hidden = ['password', 'api_key'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'api_key' => 'encrypted',
        ];
    }

    public const API_TYPES = [
        'italk' => 'iTalk / ICC scraper',
        'ranksitt' => 'Ranksitt API',
        'webvoice' => 'WebVoice API',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

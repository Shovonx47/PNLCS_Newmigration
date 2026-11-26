<?php

namespace Modules\Addons\ResellerCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerGateway extends Model
{
    protected $table = 'reseller_gateways';

    protected $fillable = [
        'reseller_id',
        'gateway',
        'settings',
        'enabled',
    ];

    protected $hidden = ['settings'];

    protected function casts(): array
    {
        return [
            'settings' => 'encrypted:array',
            'enabled' => 'boolean',
        ];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }
}

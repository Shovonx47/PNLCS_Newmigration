<?php

namespace Modules\Addons\ResellerCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerPromo extends Model
{
    protected $table = 'reseller_promos';

    protected $fillable = [
        'reseller_id',
        'code',
        'type',
        'value',
        'max_uses',
        'uses',
        'expires_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'expires_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    public function isCurrentlyUsable(): bool
    {
        if (! $this->active) {
            return false;
        }
        if ($this->max_uses > 0 && $this->uses >= $this->max_uses) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}

<?php

namespace Modules\Addons\PriceUpdater\Models;

use Illuminate\Database\Eloquent\Model;

class PriceUpdaterLog extends Model
{
    protected $table = 'price_updater_logs';

    protected $fillable = [
        'admin_id',
        'target_type',
        'mode',
        'delta',
        'filters',
        'affected_count',
    ];

    protected function casts(): array
    {
        return [
            'delta' => 'decimal:2',
            'filters' => 'array',
            'affected_count' => 'integer',
        ];
    }
}

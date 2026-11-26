<?php

namespace Modules\Addons\ResellerCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerLog extends Model
{
    protected $table = 'reseller_logs';

    protected $fillable = [
        'reseller_id',
        'admin_id',
        'action',
        'description',
        'ip_address',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }
}

<?php

namespace Modules\Addons\ItalkRecharge\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Which PIN a client holds at which provider, and what has been topped up. */
class ItalkRechargeAccount extends Model
{
    protected $table = 'italk_recharge_accounts';

    protected $fillable = [
        'client_id', 'provider_id', 'pin_number', 'total_recharged',
        'last_recharge', 'status',
    ];

    protected $casts = [
        'total_recharged' => 'decimal:2',
        'last_recharge' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ItalkRechargeProvider::class, 'provider_id');
    }
}

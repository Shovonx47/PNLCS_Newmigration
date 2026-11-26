<?php

namespace Modules\Addons\RechargeRequests\Models;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A customer's request to top up one IP number, and the invoice it raised. */
class RechargeRequest extends Model
{
    protected $table = 'recharge_requests';

    protected $fillable = [
        'client_id', 'amount', 'mobile_number', 'invoice_id', 'status', 'requested_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
    ];

    public const STATUSES = ['Pending', 'Completed', 'Cancelled'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}

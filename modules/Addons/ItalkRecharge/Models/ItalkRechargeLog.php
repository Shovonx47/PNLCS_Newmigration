<?php

namespace Modules\Addons\ItalkRecharge\Models;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One top-up attempt: written before the provider is called, updated after. */
class ItalkRechargeLog extends Model
{
    protected $table = 'italk_recharge_logs';

    protected $fillable = [
        'client_id', 'provider_id', 'account_id', 'invoice_id', 'pin_number',
        'amount', 'status', 'attempts', 'last_attempt_at', 'api_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'last_attempt_at' => 'datetime',
    ];

    public const STATUSES = ['pending', 'success', 'failed', 'skipped'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ItalkRechargeProvider::class, 'provider_id');
    }
}

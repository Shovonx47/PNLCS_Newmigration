<?php

namespace Modules\Addons\ItalkBalance\Models;

use App\Models\Client;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One customer service <-> one IP number (PIN) <-> one balance endpoint.
 * The balance columns are a cache of the provider's last numeric answer.
 */
class ItalkBalanceService extends Model
{
    protected $table = 'italk_balance_services';

    protected $fillable = [
        'client_id', 'service_id', 'product_id', 'ip_number',
        'balance', 'endpoint_id', 'last_checked_at', 'last_error',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ItalkBalanceEndpoint::class, 'endpoint_id');
    }

    /** The cached balance as money, or null when it has never been fetched. */
    public function balanceAmount(): ?float
    {
        return is_numeric($this->balance) ? (float) $this->balance : null;
    }
}

<?php

namespace Modules\Addons\ResellerCenter\Models;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerPayout extends Model
{
    protected $table = 'reseller_payouts';

    protected $fillable = [
        'reseller_id',
        'invoice_id',
        'client_id',
        'amount',
        'payout_method',
        'status',
        'description',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}

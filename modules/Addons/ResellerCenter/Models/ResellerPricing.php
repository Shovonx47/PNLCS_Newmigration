<?php

namespace Modules\Addons\ResellerCenter\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerPricing extends Model
{
    protected $table = 'reseller_pricing';

    protected $fillable = [
        'group_id',
        'type',
        'rel_id',
        'billing_cycle',
        'min_price',
        'max_price',
        'payout_type',
        'payout_value',
    ];

    protected function casts(): array
    {
        return [
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'payout_value' => 'decimal:2',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ResellerGroup::class, 'group_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'rel_id');
    }
}

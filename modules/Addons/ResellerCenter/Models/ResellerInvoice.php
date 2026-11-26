<?php

namespace Modules\Addons\ResellerCenter\Models;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerInvoice extends Model
{
    protected $table = 'reseller_invoices';

    protected $fillable = [
        'reseller_id',
        'invoice_id',
        'profit',
        'status',
    ];

    protected function casts(): array
    {
        return ['profit' => 'decimal:2'];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}

<?php

namespace Modules\Addons\CancelInvoices\Models;

use Illuminate\Database\Eloquent\Model;

class CancelInvoiceExclusion extends Model
{
    protected $table = 'cancel_invoice_exclusions';

    protected $fillable = ['type', 'rel_id', 'note'];
}

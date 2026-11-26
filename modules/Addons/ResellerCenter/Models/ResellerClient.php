<?php

namespace Modules\Addons\ResellerCenter\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerClient extends Model
{
    protected $table = 'reseller_clients';

    protected $fillable = ['reseller_id', 'client_id'];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}

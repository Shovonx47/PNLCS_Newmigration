<?php

namespace Modules\Addons\SupportPin\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportPinLog extends Model
{
    protected $table = 'support_pin_logs';

    protected $fillable = ['client_id', 'admin_id', 'result', 'ip_address'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}

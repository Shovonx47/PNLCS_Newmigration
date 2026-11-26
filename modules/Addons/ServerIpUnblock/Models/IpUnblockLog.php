<?php

namespace Modules\Addons\ServerIpUnblock\Models;

use App\Models\Client;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpUnblockLog extends Model
{
    protected $table = 'ip_unblock_logs';

    protected $fillable = [
        'client_id',
        'service_id',
        'ip_address',
        'success',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}

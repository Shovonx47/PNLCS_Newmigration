<?php

namespace Modules\Addons\PortalExtras\Models;

use App\Models\Client;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceTransferRequest extends Model
{
    protected $table = 'service_transfer_requests';

    protected $fillable = [
        'service_id',
        'from_client_id',
        'to_client_id',
        'to_name',
        'to_email',
        'to_phone',
        'reason',
        'status',
        'processed_by_admin_id',
        'processed_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function fromClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'from_client_id');
    }

    public function toClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'to_client_id');
    }
}

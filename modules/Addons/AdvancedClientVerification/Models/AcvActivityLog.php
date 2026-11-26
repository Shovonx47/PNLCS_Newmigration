<?php

namespace Modules\Addons\AdvancedClientVerification\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcvActivityLog extends Model
{
    protected $table = 'acv_activity_logs';

    protected $fillable = [
        'client_id',
        'action',
        'description',
        'ip_address',
        'admin_id',
        'admin_name',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public static function record(
        int $clientId,
        string $action,
        ?string $description = null,
        ?int $adminId = null,
        ?string $adminName = null,
        ?string $ipAddress = null
    ): self {
        return static::create([
            'client_id' => $clientId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress ?? request()?->ip(),
            'admin_id' => $adminId,
            'admin_name' => $adminName,
        ]);
    }
}

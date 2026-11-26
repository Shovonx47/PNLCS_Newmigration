<?php

namespace Modules\Addons\AdvancedClientVerification\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcvVerification extends Model
{
    protected $table = 'acv_verifications';

    protected $fillable = [
        'client_id',
        'session_id',
        'verification_url',
        'status',
        'verification_method',
        'document_type',
        'document_number',
        'business_type',
        'trade_license_number',
        'decline_reason',
        'webhook_payload',
        'acv_suspended_service_ids',
        'verified_at',
        'verified_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'acv_suspended_service_ids' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AcvDocument::class, 'verification_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(AcvAdminNote::class, 'verification_id')->latest('id');
    }
}

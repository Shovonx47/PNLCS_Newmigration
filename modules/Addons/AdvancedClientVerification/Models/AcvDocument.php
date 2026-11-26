<?php

namespace Modules\Addons\AdvancedClientVerification\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcvDocument extends Model
{
    protected $table = 'acv_documents';

    protected $fillable = [
        'verification_id',
        'client_id',
        'file_type',
        'original_name',
        'storage_path',
        'file_size',
        'mime_type',
        'status',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(AcvVerification::class, 'verification_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}

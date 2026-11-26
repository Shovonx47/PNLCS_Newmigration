<?php

namespace Modules\Addons\WhatsappPro\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;

class WaLog extends Model
{
    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'client_id', 'number', 'message', 'status', 'type', 'media_url',
        'source', 'template_name', 'retry_count', 'error', 'last_retry_at',
    ];

    protected function casts(): array
    {
        return ['last_retry_at' => 'datetime'];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

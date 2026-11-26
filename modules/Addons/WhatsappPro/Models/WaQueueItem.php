<?php

namespace Modules\Addons\WhatsappPro\Models;

use Illuminate\Database\Eloquent\Model;

class WaQueueItem extends Model
{
    protected $table = 'whatsapp_queue';

    protected $fillable = [
        'client_id', 'number', 'message', 'type', 'media_url', 'media_type',
        'view_once', 'template_name', 'status', 'attempts', 'error', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'view_once' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}

<?php

namespace Modules\Addons\WhatsappPro\Models;

use Illuminate\Database\Eloquent\Model;

class WaTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    protected $fillable = ['name', 'type', 'template', 'variables', 'description', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}

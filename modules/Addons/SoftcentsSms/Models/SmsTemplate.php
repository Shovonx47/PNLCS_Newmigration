<?php

namespace Modules\Addons\SoftcentsSms\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $table = 'softcents_sms_templates';

    protected $fillable = ['name', 'type', 'template', 'variables', 'description', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}

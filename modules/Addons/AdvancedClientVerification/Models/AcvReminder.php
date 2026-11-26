<?php

namespace Modules\Addons\AdvancedClientVerification\Models;

use Illuminate\Database\Eloquent\Model;

class AcvReminder extends Model
{
    protected $table = 'acv_reminders';

    protected $fillable = [
        'client_id',
        'sent_on',
    ];

    protected function casts(): array
    {
        return [
            'sent_on' => 'date',
        ];
    }
}

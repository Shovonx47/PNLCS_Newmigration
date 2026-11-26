<?php

namespace Modules\Addons\AdvancedClientVerification\Models;

use Illuminate\Database\Eloquent\Model;

class AcvAdminNote extends Model
{
    protected $table = 'acv_admin_notes';

    protected $fillable = [
        'verification_id',
        'admin_id',
        'admin_name',
        'note',
    ];
}

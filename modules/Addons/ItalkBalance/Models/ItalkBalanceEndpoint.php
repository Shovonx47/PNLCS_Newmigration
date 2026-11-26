<?php

namespace Modules\Addons\ItalkBalance\Models;

use Illuminate\Database\Eloquent\Model;

class ItalkBalanceEndpoint extends Model
{
    protected $table = 'italk_balance_endpoints';

    protected $fillable = ['label', 'base_url', 'program', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public const PROGRAMS = [
        'italk' => 'iTalk',
        'icc' => 'ICC Talk',
    ];
}

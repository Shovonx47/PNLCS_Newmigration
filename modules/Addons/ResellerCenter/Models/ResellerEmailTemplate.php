<?php

namespace Modules\Addons\ResellerCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerEmailTemplate extends Model
{
    protected $table = 'reseller_email_templates';

    protected $fillable = [
        'reseller_id',
        'template_name',
        'subject',
        'body',
        'enabled',
    ];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }
}

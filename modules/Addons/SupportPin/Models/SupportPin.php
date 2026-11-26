<?php

namespace Modules\Addons\SupportPin\Models;

use App\Casts\EncryptedValue;
use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportPin extends Model
{
    protected $table = 'support_pins';

    protected $fillable = ['client_id', 'pin_hash', 'pin_encrypted', 'expires_at'];

    protected function casts(): array
    {
        return [
            'pin_encrypted' => EncryptedValue::class,
            'expires_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}

<?php

namespace Modules\Addons\ForceClientActive\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForcedActiveLog extends Model
{
    protected $table = 'forced_active_logs';

    protected $fillable = ['client_id', 'previous_status', 'restored_at'];

    protected function casts(): array
    {
        return ['restored_at' => 'datetime'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}

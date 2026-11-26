<?php

namespace Modules\Addons\ForceClientActive\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForcedActiveClient extends Model
{
    protected $table = 'forced_active_clients';

    protected $fillable = ['client_id', 'added_by_admin_id', 'note'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}

<?php

namespace Modules\Addons\SoftcentsSms\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $table = 'softcents_sms_logs';

    protected $fillable = ['client_id', 'to_number', 'template_name', 'message', 'status', 'error', 'http_code'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

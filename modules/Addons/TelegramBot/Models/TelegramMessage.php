<?php

namespace Modules\Addons\TelegramBot\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    protected $table = 'telegram_messages';

    protected $fillable = ['client_id', 'chat_id', 'direction', 'event', 'body', 'status', 'error'];
}

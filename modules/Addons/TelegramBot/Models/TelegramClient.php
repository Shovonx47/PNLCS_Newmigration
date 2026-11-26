<?php

namespace Modules\Addons\TelegramBot\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;

class TelegramClient extends Model
{
    protected $table = 'telegram_clients';

    protected $fillable = [
        'client_id', 'chat_id', 'telegram_username', 'telegram_name',
        'link_token', 'notify_enabled', 'connected_at', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'notify_enabled' => 'boolean',
            'connected_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function connected(): bool
    {
        return (string) $this->chat_id !== '';
    }
}

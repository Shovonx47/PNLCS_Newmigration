@php
    $client = auth()->user()?->clients()->first();
    $row = $client ? \Modules\Addons\TelegramBot\Models\TelegramClient::where('client_id', $client->id)->first() : null;
    $connected = $row && $row->connected();
@endphp

@if($client)
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:18px 20px;margin-top:16px;box-shadow:0 1px 3px rgba(0,0,0,.06);">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <div style="font-size:15px;font-weight:700;color:#0f172a;">
                <span style="color:#229ed9;">&#9992;</span> Telegram
            </div>
            <div style="font-size:12.5px;color:#64748b;margin-top:3px;">
                @if($connected)
                    Connected{{ $row->telegram_username ? ' as @'.$row->telegram_username : '' }} — invoice and service alerts arrive in your chat.
                @else
                    Connect your Telegram to get invoice links and service notices in one tap.
                @endif
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('client.telegram.index') }}" class="btn btn-default btn-sm">{{ $connected ? 'Manage' : 'Connect' }}</a>
        </div>
    </div>
</div>
@endif

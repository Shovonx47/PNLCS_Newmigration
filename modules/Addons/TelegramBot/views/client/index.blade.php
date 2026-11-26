@extends('client.layouts.app')
@section('title', 'Telegram')

@section('content')
<div class="pn-page-header">
    <div>
        <h1 class="pn-page-title">Telegram</h1>
        <p class="pn-page-subtitle">Invoice, service and support alerts in your Telegram chat.</p>
    </div>
</div>

<div class="pn-card" style="margin-bottom:16px;">
    <div class="pn-card-header">
        <span class="pn-card-title">Connection</span>
    </div>
    <div class="pn-card-body" style="padding:18px;">
        @if($row->connected())
            <p style="font-size:14px;color:var(--text);margin:0 0 14px;">
                <strong style="color:#065f46;">&#10003; Connected</strong>
                @if($row->telegram_username) as <strong>@{{ $row->telegram_username }}</strong> @endif
                since {{ $row->connected_at?->format('d M Y') }}.
                Alerts are {{ $row->notify_enabled ? 'on' : 'muted' }}.
            </p>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <form method="POST" action="{{ route('client.telegram.test') }}">@csrf
                    <button class="btn btn-outline btn-sm" type="submit">Send test message</button>
                </form>
                <form method="POST" action="{{ route('client.telegram.toggle') }}">@csrf
                    <button class="btn btn-outline btn-sm" type="submit">{{ $row->notify_enabled ? 'Mute alerts' : 'Unmute alerts' }}</button>
                </form>
                <form method="POST" action="{{ route('client.telegram.disconnect') }}">@csrf
                    <button class="btn btn-danger btn-sm" type="submit">Disconnect</button>
                </form>
            </div>
        @else
            <p style="font-size:14px;color:var(--text);margin:0 0 14px;">
                Open Telegram, press Start, and this chat starts receiving your invoice and service messages.
            </p>
            <form method="POST" action="{{ route('client.telegram.connect') }}">@csrf
                <button class="btn btn-primary btn-sm" type="submit">Connect Telegram</button>
            </form>
        @endif
    </div>
</div>

<div class="pn-card">
    <div class="pn-card-header">
        <span class="pn-card-title">Recent messages</span>
    </div>
    <div class="pn-card-body-flush">
        @forelse($messages as $message)
        <div style="padding:12px 18px;border-bottom:1px solid var(--border);">
            <div style="font-size:11px;color:var(--muted);margin-bottom:4px;">
                {{ $message->created_at?->format('d M Y H:i') }} &middot; {{ $message->event }}
                @if($message->status !== 'sent') &middot; <span style="color:#b45309;">{{ $message->status }}</span> @endif
            </div>
            <div style="font-size:13.5px;color:var(--text);">{{ $message->event === 'Test' || $message->event === 'AdminTest' ? 'Test message' : \Illuminate\Support\Str::limit(strip_tags((string) $message->body), 160) }}</div>
        </div>
        @empty
        <div style="text-align:center;padding:44px 20px;color:var(--muted);">
            <i class="ri-telegram-line" style="font-size:30px;display:block;margin-bottom:8px;opacity:.5;"></i>
            <div style="font-size:13.5px;">No messages yet.</div>
        </div>
        @endforelse
    </div>
</div>
@endsection

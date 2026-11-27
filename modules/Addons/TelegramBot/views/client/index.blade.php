@extends('client.layouts.twentyone')
@section('title', 'Telegram Notifications')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fab fa-telegram-plane fa-fw"></i>&nbsp;{{ 'Telegram Connection' }}</h5>
    </div>
    <div class="card-body">
        @if($row->connected())
            <p class="mb-3">
                <span class="label label-success mr-1">&#10003; {{ 'Connected' }}</span>
                @if($row->telegram_username) {{ 'as' }} <strong>{{ '@' . $row->telegram_username }}</strong> @endif
                {{ 'since ' . ($row->connected_at?->format('d M Y') ?? '') . '. Alerts are ' . ($row->notify_enabled ? 'on' : 'muted') . '.' }}
            </p>
            <div class="d-flex flex-wrap">
                <form method="POST" action="{{ route('client.telegram.test') }}" class="mr-2 mb-2">@csrf
                    <button class="btn btn-default btn-sm" type="submit">{{ 'Send test message' }}</button>
                </form>
                <form method="POST" action="{{ route('client.telegram.toggle') }}" class="mr-2 mb-2">@csrf
                    <button class="btn btn-default btn-sm" type="submit">{{ $row->notify_enabled ? 'Mute alerts' : 'Unmute alerts' }}</button>
                </form>
                <form method="POST" action="{{ route('client.telegram.disconnect') }}" class="mb-2">@csrf
                    <button class="btn btn-danger btn-sm" type="submit">{{ 'Disconnect' }}</button>
                </form>
            </div>
        @else
            <p class="text-muted">{{ 'Open Telegram, press Start, and this chat starts receiving your invoice and service messages.' }}</p>
            <form method="POST" action="{{ route('client.telegram.connect') }}">@csrf
                <button class="btn btn-primary" type="submit"><i class="fab fa-telegram-plane"></i> {{ 'Connect Telegram' }}</button>
            </form>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history fa-fw"></i>&nbsp;{{ 'Recent Messages' }}</h5>
    </div>
    @if($messages->isEmpty())
        <div class="card-body text-center text-muted py-4">
            <i class="fab fa-telegram-plane fa-2x mb-2 d-block"></i>
            {{ 'No messages yet.' }}
        </div>
    @else
        <div class="list-group list-group-flush">
            @foreach($messages as $message)
                <div class="list-group-item">
                    <div class="small text-muted mb-1">
                        {{ $message->created_at?->format('d M Y H:i') }} &middot; {{ $message->event }}
                        @if($message->status !== 'sent') &middot; <span class="text-warning">{{ $message->status }}</span> @endif
                    </div>
                    <div>{{ $message->event === 'Test' || $message->event === 'AdminTest' ? 'Test message' : \Illuminate\Support\Str::limit(strip_tags((string) $message->body), 160) }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@extends('admin.layouts.app')
@section('title', 'Telegram Bot')

@section('content')

<div class="page-header">
    <h1>Telegram Client Bot</h1>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:15px;">
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Bot</div>
        <div style="font-size:16px;font-weight:700;color:{{ $enabled ? '#065f46' : '#991b1b' }};">
            {{ $enabled ? 'Enabled' : 'Off' }}
        </div>
        <div style="font-size:11px;color:#6b7280;">{{ $username ? '@'.$username : 'No username set' }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Connected customers</div>
        <div style="font-size:22px;font-weight:700;">{{ $connected }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Webhook</div>
        <div style="font-size:11px;color:#374151;word-break:break-all;">{{ $webhookUrl }}</div>
    </div></div>
</div>

<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Bot settings</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.telegram.settings') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;">
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Bot token</label>
                    <input type="password" name="bot_token" class="form-control" autocomplete="new-password"
                           placeholder="{{ $token ? '•••••••• (saved — leave blank to keep)' : '123456:ABC...' }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Bot username</label>
                    <input type="text" name="bot_username" class="form-control" value="{{ $username }}" placeholder="SCWhmcs_bot">
                    <div style="font-size:11px;color:#6b7280;margin-top:3px;">Without the @. Used to build the connect link.</div>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Webhook secret</label>
                    <input type="text" name="webhook_secret" class="form-control" value="{{ $secret }}">
                    <div style="font-size:11px;color:#6b7280;margin-top:3px;">Telegram sends it back in a header; the webhook refuses updates without it.</div>
                </div>
            </div>
            <div style="display:flex;gap:18px;flex-wrap:wrap;margin:4px 0 12px;">
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="enabled" value="1" {{ $enabled ? 'checked' : '' }}> Bot enabled
                </label>
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="notify_invoices" value="1" {{ $notifyInvoices ? 'checked' : '' }}> Invoice alerts
                </label>
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="notify_services" value="1" {{ $notifyServices ? 'checked' : '' }}> Service alerts
                </label>
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="notify_tickets" value="1" {{ $notifyTickets ? 'checked' : '' }}> Support reply alerts
                </label>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">Save settings</button>
            </div>
        </form>
        <form method="POST" action="{{ route('admin.extensions.telegram.webhook') }}" style="margin-top:10px;">@csrf
            <button class="btn btn-default btn-sm" type="submit">Register webhook with Telegram</button>
            <span style="font-size:12px;color:#6b7280;">Or run <code>php artisan pnlcs:telegram-webhook</code>.</span>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Connected customers</strong></div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
        <table class="table table-striped" style="margin:0;font-size:12px;">
            <thead><tr><th>Client</th><th>Telegram</th><th>Alerts</th><th>Connected</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td>#{{ $row->client_id }} {{ $row->client?->first_name }} {{ $row->client?->last_name }}</td>
                    <td>{{ $row->telegram_username ? '@'.$row->telegram_username : ($row->telegram_name ?: '—') }}</td>
                    <td>{{ $row->notify_enabled ? 'on' : 'muted' }}</td>
                    <td>{{ $row->connected() ? $row->connected_at?->format('d M Y') : 'not connected' }}</td>
                    <td style="white-space:nowrap;">
                        @if($row->connected())
                        <form method="POST" action="{{ route('admin.extensions.telegram.clients.test', $row) }}" style="display:inline;">@csrf
                            <button class="btn btn-default btn-xs" type="submit">Test</button>
                        </form>
                        <form method="POST" action="{{ route('admin.extensions.telegram.clients.disconnect', $row) }}" style="display:inline;">@csrf
                            <button class="btn btn-danger btn-xs" type="submit">Disconnect</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:14px;color:#9ca3af;">No customers have connected yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Recent messages</strong></div>
    <div class="card-body" style="padding:0;max-height:420px;overflow:auto;">
        <table class="table table-striped" style="margin:0;font-size:12px;">
            <thead><tr><th>When</th><th>Dir</th><th>Event</th><th>Status</th><th>Text</th></tr></thead>
            <tbody>
                @forelse($messages as $message)
                <tr>
                    <td style="white-space:nowrap;">{{ $message->created_at?->format('d M H:i') }}</td>
                    <td>{{ $message->direction }}</td>
                    <td>{{ $message->event }}</td>
                    <td style="color:{{ $message->status === 'failed' ? '#991b1b' : '#065f46' }};">{{ $message->status }}</td>
                    <td style="max-width:520px;color:#6b7280;">{{ $message->error ?: \Illuminate\Support\Str::limit(strip_tags((string) $message->body), 100) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:14px;color:#9ca3af;">Nothing yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

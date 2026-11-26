@extends('admin.layouts.app')
@section('title', 'Support PIN Verification')

@section('content')
<div class="page-header">
    <h1>Client Support PIN Verification</h1>
</div>

@if(! empty($matchedClient))
<div class="card" style="margin-bottom:16px;border-color:#4ade80;">
    <div class="card-header"><strong>Verified Client Match</strong></div>
    <div class="card-body">
        <p style="margin:0;font-size:14px;">
            <strong><a href="{{ route('admin.clients.show', $matchedClient['id']) }}">{{ $matchedClient['name'] }}</a></strong>
            ({{ $matchedClient['email'] }})
            @if(! empty($matchedClient['company'])) &mdash; {{ $matchedClient['company'] }} @endif
        </p>
    </div>
</div>
@endif

<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><strong>Verify Customer PIN</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.support-pin.verify') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label class="form-label">6-Digit Support PIN *</label>
                <input type="text" name="pin" maxlength="6" pattern="[0-9]{6}" required class="form-control" placeholder="123456" style="width:160px;letter-spacing:2px;font-weight:700;">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Client ID (optional)</label>
                <input type="number" name="client_id" min="1" class="form-control" placeholder="Any client" style="width:150px;">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Verify PIN</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Verification Audit Log</strong></div>
    <div class="card-body">
        @if($logs->isEmpty())
            <div style="color:#888;font-size:13px;">No verification attempts logged yet.</div>
        @else
            <table class="data-table">
                <thead><tr><th>When</th><th>Client</th><th>Result</th><th>IP Address</th></tr></thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="font-size:12px;color:#666;">{{ $log->created_at?->format('d M Y, H:i:s') }}</td>
                        <td>@if($log->client)<a href="{{ route('admin.clients.show', $log->client) }}">{{ trim($log->client->first_name.' '.$log->client->last_name) }}</a>@else {{ $log->client_id ? '#'.$log->client_id : '—' }} @endif</td>
                        <td><span class="badge {{ $log->result === 'verified' ? 'badge-active' : 'badge-cancelled' }}">{{ $log->result }}</span></td>
                        <td><code>{{ $log->ip_address ?: '—' }}</code></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

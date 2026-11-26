@extends('admin.layouts.app')
@section('title', 'Server IP Unblock Logs')

@section('content')
<div class="page-header">
    <h1>Server IP Unblock &amp; Audit Log</h1>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><strong>Direct Staff IP Unblock</strong></div>
    <div class="card-body">
        @if(($capableServices ?? collect())->isEmpty())
            <div style="color:#888;font-size:13px;">No active firewall-capable services are currently provisioned.</div>
        @else
            <form method="POST" action="{{ route('admin.extensions.unblock-ip.unblock') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                @csrf
                <div class="form-group" style="margin:0;min-width:260px;flex:1;">
                    <label class="form-label">Active Service *</label>
                    <select name="service_id" required class="form-control">
                        @foreach($capableServices as $svc)
                            <option value="{{ $svc->id }}">
                                #{{ $svc->id }} &mdash; {{ $svc->product?->name }} ({{ $svc->domain ?: 'Client #'.$svc->client_id }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;min-width:200px;">
                    <label class="form-label">IP Address (IPv4 / IPv6) *</label>
                    <input type="text" name="ip_address" required class="form-control" placeholder="e.g. 103.120.44.55">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Check &amp; Unblock IP</button>
            </form>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($logs->isEmpty())
            <div style="text-align:center;padding:30px;color:#888;">No IP unblock requests recorded yet.</div>
        @else
            <table class="data-table">
                <thead><tr><th>When</th><th>Client</th><th>Service</th><th>IP Address</th><th>Result</th><th>Message</th></tr></thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="font-size:12px;color:#666;">{{ $log->created_at?->format('d M Y, H:i:s') }}</td>
                        <td>@if($log->client)<a href="{{ route('admin.clients.show', $log->client) }}">{{ trim($log->client->first_name.' '.$log->client->last_name) }}</a>@else #{{ $log->client_id }} @endif</td>
                        <td>@if($log->service)<a href="{{ route('admin.services.show', $log->service) }}">#{{ $log->service_id }} {{ $log->service->product?->name }}</a>@else #{{ $log->service_id }} @endif</td>
                        <td><code>{{ $log->ip_address }}</code></td>
                        <td><span class="badge {{ $log->success ? 'badge-active' : 'badge-cancelled' }}">{{ $log->success ? 'Success' : 'Failed' }}</span></td>
                        <td style="font-size:12px;">{{ $log->message }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:14px;">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection

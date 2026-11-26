@extends('client.layouts.app')
@section('title', 'Unblock IP Address')

@section('content')
<div class="pn-page-header">
    <div>
        <h1 class="pn-page-title">Unblock IP Address</h1>
        <p class="pn-page-subtitle">Restore connection access if your network IP was temporarily blocked after failed login attempts.</p>
    </div>
    <a href="{{ route('client.home') }}" class="btn btn-outline">&larr; Dashboard</a>
</div>

<div class="pn-card" style="padding:20px 24px;margin-bottom:20px;">
    @if($services->isEmpty())
        <div style="color:var(--muted);font-size:13.5px;">None of your active services currently require or support self-service IP unblocking.</div>
    @else
        <form method="POST" action="{{ route('client.unblock-ip.submit') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div class="form-group" style="margin:0;flex:1;min-width:220px;">
                <label class="form-label">Select Active Service</label>
                <select name="service_id" class="form-control" required>
                    @foreach($services as $svc)
                        <option value="{{ $svc->id }}">{{ $svc->product?->name }}{{ $svc->domain ? ' — '.$svc->domain : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;min-width:200px;">
                <label class="form-label">IP Address to Unblock</label>
                <input type="text" name="ip_address" value="{{ old('ip_address', $visitorIp) }}" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Check &amp; Unblock IP</button>
        </form>
    @endif
</div>

@if($recentLogs->isNotEmpty())
<div class="pn-card" style="padding:20px 24px;">
    <div class="pn-section-title" style="margin:0 0 12px;">Recent Unblock Requests</div>
    <table class="pn-table">
        <thead><tr><th>Date</th><th>Service</th><th>IP Address</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($recentLogs as $log)
            <tr>
                <td style="font-size:12.5px;color:var(--muted);">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                <td>{{ $log->service?->product?->name ?? 'Service #'.$log->service_id }}</td>
                <td><code>{{ $log->ip_address }}</code></td>
                <td><span class="badge {{ $log->success ? 'badge-active' : 'badge-cancelled' }}">{{ $log->success ? 'Completed' : 'Unavailable' }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection

@extends('admin.layouts.app')
@section('title', 'Utilities & Logs — Resellers Center')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Reseller Utilities &amp; Logs</h1>
    <form method="POST"
          action="{{ route('admin.extensions.reseller-center.logs.clear') }}"
          onsubmit="return pnConfirm(event, @js('Clear all reseller activity logs?'));">
        @csrf
        <button type="submit" class="btn btn-danger btn-sm">Clear Activity Logs</button>
    </form>
</div>

@include('resellercenter::admin._nav')

<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><strong>Database Schema &amp; Record Counts</strong></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;">
            @foreach($tableStatus as $table => $count)
            <div style="border:1px solid #e5e7eb;border-radius:6px;padding:10px 12px;">
                <div style="font-family:ui-monospace,Menlo,monospace;font-size:12px;color:#555;">{{ $table }}</div>
                <div style="font-size:18px;font-weight:700;margin-top:4px;">
                    {{ $count === null ? 'Missing' : number_format($count).' rows' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Recent Activity Audit Trail</strong></div>
    <div class="card-body">
        @if($logs->isEmpty())
            <div style="color:#888;font-size:13px;">No audit log entries recorded yet.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Reseller</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="white-space:nowrap;">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->reseller?->company_name ?: ($log->reseller_id ? '#'.$log->reseller_id : 'System') }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>{{ $log->description }}</td>
                        <td><code>{{ $log->ip_address ?: '—' }}</code></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

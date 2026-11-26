@extends('admin.layouts.app')
@section('title', 'Force Client Active')

@section('content')
<div class="page-header">
    <h1>Force Client Active</h1>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><strong>Global Enforcement Mode</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.force-client-active.mode') }}" style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
            @csrf
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin:0;">
                <input type="checkbox" name="restore_inactive_clients" value="1" @checked($restoreInactive ?? true)>
                Automatically restore <strong>Inactive</strong> accounts (leaves Closed accounts untouched unless protected)
            </label>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin:0;">
                <input type="checkbox" name="enforce_all_clients" value="1" @checked($enforceAll)>
                Keep <strong>all</strong> accounts Active globally (including Closed)
            </label>
            <button type="submit" class="btn btn-primary btn-sm">Save &amp; Run Now</button>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><strong>Protected Clients ({{ $flagged->count() }})</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.force-client-active.store') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label class="form-label">Client ID *</label>
                <input type="number" name="client_id" min="1" required class="form-control" style="width:140px;">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:200px;">
                <label class="form-label">Note</label>
                <input type="text" name="note" class="form-control" placeholder="e.g. Enterprise PBX partner">
            </div>
            <button type="submit" class="btn btn-default btn-sm">Add Protected Client</button>
        </form>

        @if($flagged->isNotEmpty())
            <table class="data-table">
                <thead><tr><th>Client</th><th>Note</th><th style="text-align:right;">Remove</th></tr></thead>
                <tbody>
                    @foreach($flagged as $row)
                    <tr>
                        <td>@if($row->client)<a href="{{ route('admin.clients.show', $row->client) }}">#{{ $row->client_id }} {{ trim($row->client->first_name.' '.$row->client->last_name) }}</a>@else #{{ $row->client_id }} @endif</td>
                        <td>{{ $row->note ?: '—' }}</td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('admin.extensions.force-client-active.destroy', $row) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Recent Status Restorations</strong></div>
    <div class="card-body">
        @if($logs->isEmpty())
            <div style="color:#888;font-size:13px;">No accounts needed restoration yet.</div>
        @else
            <table class="data-table">
                <thead><tr><th>Restored At</th><th>Client</th><th>Previous Status</th></tr></thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="font-size:12px;color:#666;">{{ $log->restored_at?->format('d M Y, H:i') }}</td>
                        <td>@if($log->client)<a href="{{ route('admin.clients.show', $log->client) }}">{{ trim($log->client->first_name.' '.$log->client->last_name) }}</a>@else #{{ $log->client_id }} @endif</td>
                        <td><code>{{ $log->previous_status }}</code></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

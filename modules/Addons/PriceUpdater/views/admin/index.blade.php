@extends('admin.layouts.app')
@section('title', 'Bulk Price Updater')

@section('content')
<div class="page-header">
    <h1>Bulk Product &amp; TLD Price Updater</h1>
</div>

@if(! empty($previewResult))
<div class="card" style="margin-bottom:16px;border-color:#93c5fd;">
    <div class="card-header"><strong>Dry-Run Preview ({{ $previewResult['kind'] }})</strong> &mdash; {{ $previewResult['summary'] }}</div>
    <div class="card-body">
        @if(empty($previewResult['rows']))
            <div style="color:#666;font-size:13px;">No matching rows would change with these parameters.</div>
        @else
            <table class="data-table">
                <thead><tr><th>Item</th><th>Current Price</th><th>New Price</th></tr></thead>
                <tbody>
                    @foreach($previewResult['rows'] as $r)
                    <tr>
                        <td>{{ $r['target'] }}</td>
                        <td>{{ number_format($r['old'], 2) }}</td>
                        <td><strong>{{ number_format($r['new'], 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:16px;margin-bottom:16px;">
    <div class="card">
        <div class="card-header"><strong>Product &amp; Active Service Pricing</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.extensions.price-updater.products') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div class="form-group">
                        <label class="form-label">Adjustment Mode</label>
                        <select name="mode" class="form-control">
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (+/-)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adjustment Value</label>
                        <input type="number" step="0.01" name="delta" required class="form-control" placeholder="e.g. 10 or -50">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Product Group</label>
                        <select name="group_id" class="form-control">
                            <option value="">All Groups</option>
                            @foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Currency</label>
                        <select name="currency_id" class="form-control">
                            <option value="">All Currencies</option>
                            @foreach($currencies as $c)<option value="{{ $c->id }}">{{ $c->code }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-top:8px;">
                    <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;">
                        <input type="checkbox" name="cascade_services" value="1">
                        Also update recurring <code>amount</code> on active/suspended services
                    </label>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" name="action_mode" value="preview" class="btn btn-default btn-sm">Preview Dry-Run</button>
                    <button type="submit" name="action_mode" value="apply" class="btn btn-primary btn-sm">Apply Product Update</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Domain TLD Pricing</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.extensions.price-updater.domains') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div class="form-group">
                        <label class="form-label">Adjustment Mode</label>
                        <select name="mode" class="form-control">
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (+/-)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adjustment Value</label>
                        <input type="number" step="0.01" name="delta" required class="form-control" placeholder="e.g. 5 or 100">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Extension Filter (optional)</label>
                        <input type="text" name="extension" class="form-control" placeholder="Leave blank for all TLDs, or e.g. .com">
                    </div>
                </div>
                <div style="display:flex;gap:12px;margin-top:8px;font-size:12.5px;">
                    <label><input type="checkbox" name="fields[]" value="register_price" checked> Register</label>
                    <label><input type="checkbox" name="fields[]" value="renew_price" checked> Renew</label>
                    <label><input type="checkbox" name="fields[]" value="transfer_price" checked> Transfer</label>
                </div>
                <div class="form-group" style="margin-top:8px;">
                    <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;">
                        <input type="checkbox" name="cascade_domains" value="1">
                        Also update <code>recurring_amount</code> on active domains when Renew price changes
                    </label>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" name="action_mode" value="preview" class="btn btn-default btn-sm">Preview Dry-Run</button>
                    <button type="submit" name="action_mode" value="apply" class="btn btn-primary btn-sm">Apply TLD Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Recent Price Update Audit Log</strong></div>
    <div class="card-body">
        @if($logs->isEmpty())
            <div style="color:#888;font-size:13px;">No bulk price updates have been applied yet.</div>
        @else
            <table class="data-table">
                <thead><tr><th>When</th><th>Target</th><th>Mode</th><th>Delta</th><th>Affected</th></tr></thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="font-size:12px;color:#666;">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                        <td>{{ ucfirst($log->target_type) }}</td>
                        <td>{{ ucfirst($log->mode) }}</td>
                        <td>{{ $log->delta }}</td>
                        <td>{{ $log->affected_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

@extends('admin.layouts.app')
@section('title', 'Auto-Cancel Unpaid Invoices')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Auto-Cancel Unpaid Invoices</h1>
    <form method="POST" action="{{ route('admin.extensions.cancel-invoices.run') }}" onsubmit="return pnConfirm(event, @js('Run unpaid invoice auto-cancellation sweep right now?'))">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm">Run Sweep Now</button>
    </form>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><strong>Cancellation Rules</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.cancel-invoices.settings') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;">
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:6px;font-weight:600;margin-bottom:6px;">
                        <input type="checkbox" name="enable_days" value="1" @checked(in_array(strtolower((string)($settings['enable_days'] ?? '0')), ['1','on','true'], true))>
                        Service &amp; Domain Invoices
                    </label>
                    <label class="form-label">Days Past Due</label>
                    <input type="number" name="days" min="1" max="365" value="{{ $settings['days'] ?? 7 }}" class="form-control">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:6px;font-weight:600;margin-bottom:6px;">
                        <input type="checkbox" name="enable_add_funds" value="1" @checked(in_array(strtolower((string)($settings['enable_add_funds'] ?? '0')), ['1','on','true'], true))>
                        Add-Funds Top-Up Invoices
                    </label>
                    <label class="form-label">Days Past Due</label>
                    <input type="number" name="add_funds_days" min="1" max="365" value="{{ $settings['add_funds_days'] ?? 7 }}" class="form-control">
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:6px;font-weight:600;margin-bottom:6px;">
                        <input type="checkbox" name="enable_all_invoices" value="1" @checked(in_array(strtolower((string)($settings['enable_all_invoices'] ?? '0')), ['1','on','true'], true))>
                        Custom / Other Invoices
                    </label>
                    <label class="form-label">Days Past Due</label>
                    <input type="number" name="all_invoice_days" min="1" max="365" value="{{ $settings['all_invoice_days'] ?? 7 }}" class="form-control">
                </div>
            </div>
            <div style="margin-top:12px;">
                <button type="submit" class="btn btn-primary btn-sm">Save Rules</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Exclusions ({{ $exclusions->count() }})</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.cancel-invoices.exclusions.store') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label class="form-label">Exclusion Type</label>
                <select name="type" class="form-control">
                    <option value="client">Client ID</option>
                    <option value="product">Product ID</option>
                    <option value="service">Service ID</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Record ID</label>
                <input type="number" name="rel_id" min="1" required class="form-control" style="width:130px;">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:200px;">
                <label class="form-label">Reason / Note</label>
                <input type="text" name="note" class="form-control" placeholder="Optional note">
            </div>
            <button type="submit" class="btn btn-default btn-sm">Add Exclusion</button>
        </form>

        @if($exclusions->isEmpty())
            <div style="color:#888;font-size:13px;">No exclusions configured.</div>
        @else
            <table class="data-table">
                <thead><tr><th>Type</th><th>ID</th><th>Note</th><th style="text-align:right;">Remove</th></tr></thead>
                <tbody>
                    @foreach($exclusions as $ex)
                    <tr>
                        <td><code>{{ $ex->type }}</code></td>
                        <td>#{{ $ex->rel_id }}</td>
                        <td>{{ $ex->note ?: '—' }}</td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('admin.extensions.cancel-invoices.exclusions.destroy', $ex) }}" style="display:inline;">
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
@endsection

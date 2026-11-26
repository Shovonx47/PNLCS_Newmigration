@extends('admin.layouts.app')
@section('title', 'BDPBX Accounting')

@section('content')

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>BDPBX Accounting</h1>
    <form method="GET" action="{{ route('admin.extensions.bdpbx-accounting.index') }}" style="display:flex;gap:8px;align-items:center;">
        <input type="date" name="date" value="{{ $date }}" class="form-control" style="width:auto;">
        <button type="submit" class="btn btn-default btn-sm">Show day</button>
        <button type="submit" form="acct-run" class="btn btn-primary btn-sm">Run now</button>
    </form>
</div>

<form id="acct-run" method="POST" action="{{ route('admin.extensions.bdpbx-accounting.run') }}">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">
</form>

<p style="color:#666;font-size:13px;margin-bottom:15px;">
    Snapshot for <strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong>.
    The daily command writes it at 06:50; "Run now" recalculates the day in place.
    Combined revenue counts each paid invoice once, per-server revenue is that server's own invoice items.
</p>

@if(! $combined)
<div class="card">
    <div class="card-body" style="text-align:center;padding:40px;color:#999;">
        No snapshot has been recorded for this day yet.
        <div style="margin-top:12px;">
            <button type="submit" form="acct-run" class="btn btn-primary btn-sm">Run accounting for {{ $date }}</button>
        </div>
    </div>
</div>
@else

{{-- Combined --}}
<div class="card" style="margin-bottom:15px;border-left:4px solid var(--primary, #2563eb);">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <strong>All PBX Servers (Combined)</strong>
        <span style="font-size:12px;color:#666;">Last run {{ $combined->last_run_at?->format('d M Y, H:i') ?? '—' }}</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;">
            @foreach([
                ['Active MRR', '৳'.number_format((float) $combined->active_mrr, 2), $combined->active_services.' active · '.$combined->total_services.' total'],
                ['Paid this month', '৳'.number_format((float) $combined->paid_amount_month, 2), $combined->paid_invoices_month.' invoices · '.$combined->paid_clients_month.' clients'],
                ['Paid today', '৳'.number_format((float) $combined->paid_today_amount, 2), 'collected on '.$date],
                ['Unpaid', '৳'.number_format((float) $combined->due_amount, 2), $combined->due_invoices.' invoices · '.$combined->due_clients.' clients'],
            ] as $box)
            <div style="border:1px solid #e5e7eb;border-radius:8px;padding:12px 14px;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#666;">{{ $box[0] }}</div>
                <div style="font-size:20px;font-weight:800;margin-top:6px;">{{ $box[1] }}</div>
                <div style="font-size:11.5px;color:#888;margin-top:4px;">{{ $box[2] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Per server --}}
<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Per server</strong></div>
    <div class="card-body">
        @if($perServer->isEmpty())
        <div style="color:#999;font-size:13px;">No server has PBX services on this day.</div>
        @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Server</th>
                    <th>Services</th>
                    <th>Active MRR</th>
                    <th>Paid this month</th>
                    <th>Paid today</th>
                    <th>Unpaid</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($perServer as $row)
                <tr>
                    <td>
                        <strong>{{ $row->server_name }}</strong>
                        @if($row->server_hostname)
                        <div style="font-size:11.5px;color:#888;">{{ $row->server_hostname }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $row->active_services }} active / {{ $row->total_services }}
                        <div style="font-size:11.5px;color:#888;">
                            {{ $row->suspended_services }} suspended · {{ $row->pending_services }} pending · {{ $row->cancelled_services }} cancelled
                        </div>
                    </td>
                    <td>৳{{ number_format((float) $row->active_mrr, 2) }}</td>
                    <td>৳{{ number_format((float) $row->paid_amount_month, 2) }}</td>
                    <td>৳{{ number_format((float) $row->paid_today_amount, 2) }}</td>
                    <td>৳{{ number_format((float) $row->due_amount, 2) }} <span style="color:#888;font-size:11.5px;">({{ $row->due_invoices }})</span></td>
                    <td style="text-align:right;">
                        @if($ssoRoute && $servers->has($row->server_id))
                        <a href="{{ route('admin.bdpbx.super-sso', $servers->get($row->server_id)) }}" target="_blank" rel="noopener" class="btn btn-default btn-xs">PBX SSO</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- Unpaid drill-down --}}
@if(! empty($combined->due_details))
<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Unpaid invoices (combined)</strong></div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr><th>Invoice</th><th>Client</th><th>Amount</th><th>Due date</th></tr>
            </thead>
            <tbody>
                @foreach(array_slice($combined->due_details, 0, 25) as $due)
                <tr>
                    <td>#{{ $due['invoice_id'] }}</td>
                    <td>#{{ $due['client_id'] }}</td>
                    <td>৳{{ number_format((float) $due['amount'], 2) }}</td>
                    <td>{{ $due['due_date'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if(count($combined->due_details) > 25)
        <p style="font-size:12px;color:#888;margin:8px 0 0;">Showing 25 of {{ count($combined->due_details) }}.</p>
        @endif
    </div>
</div>
@endif

@endif

{{-- History --}}
<div class="card">
    <div class="card-header"><strong>Recorded days</strong></div>
    <div class="card-body" style="display:flex;gap:8px;flex-wrap:wrap;">
        @forelse($history as $day)
        <a href="{{ route('admin.extensions.bdpbx-accounting.index', ['date' => $day]) }}"
           class="btn {{ $day === $date ? 'btn-primary' : 'btn-default' }} btn-sm">
            {{ \Carbon\Carbon::parse($day)->format('d M Y') }}
        </a>
        @empty
        <span style="color:#999;font-size:13px;">Nothing recorded yet.</span>
        @endforelse
    </div>
</div>

@endsection

@extends('admin.layouts.app')
@section('title', 'Payouts — Resellers Center')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Reseller Payouts</h1>
    <div style="font-weight:700;color:#b45309;">Pending Queue: {{ money_fmt($totalPending) }}</div>
</div>

@include('resellercenter::admin._nav')

@if($resellersWithPending->isNotEmpty())
<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><strong>Mass Settlement Queue</strong></div>
    <div class="card-body">
        <form method="POST"
              action="{{ route('admin.extensions.reseller-center.payouts.mass') }}"
              onsubmit="return pnConfirm(event, @js('Process all pending payouts for the selected resellers?'));">
            @csrf
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
                @foreach($resellersWithPending as $reseller)
                <label style="border:1px solid #e5e7eb;border-radius:6px;padding:8px 12px;display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="reseller_ids[]" value="{{ $reseller->id }}" checked>
                    <span>
                        <strong>{{ $reseller->company_name ?: trim(($reseller->client?->first_name ?? '').' '.($reseller->client?->last_name ?? '')) }}</strong>
                        &middot; {{ money_fmt((float) ($reseller->pending_sum ?? 0)) }}
                    </span>
                </label>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Pay Selected Resellers</button>
        </form>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header"><strong>Commission Payout Ledger ({{ $payouts->total() }})</strong></div>
    <div class="card-body">
        @if($payouts->isEmpty())
            <div style="color:#888;font-size:13px;">No payout entries recorded yet.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Reseller</th>
                        <th>End Client</th>
                        <th>Invoice</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Paid At</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payouts as $payout)
                    <tr>
                        <td>#{{ $payout->id }}</td>
                        <td>
                            @if($payout->reseller)
                                <a href="{{ route('admin.extensions.reseller-center.resellers.show', $payout->reseller) }}">
                                    {{ $payout->reseller->company_name ?: trim(($payout->reseller->client?->first_name ?? '').' '.($payout->reseller->client?->last_name ?? '')) }}
                                </a>
                            @else
                                Reseller #{{ $payout->reseller_id }}
                            @endif
                        </td>
                        <td>
                            @if($payout->client)
                                {{ trim($payout->client->first_name.' '.$payout->client->last_name) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if($payout->invoice)
                                <a href="{{ route('admin.invoices.show', $payout->invoice) }}">
                                    {{ $payout->invoice->invoice_num ?: '#'.$payout->invoice_id }}
                                </a>
                            @elseif($payout->invoice_id)
                                #{{ $payout->invoice_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $payout->payout_method }}</td>
                        <td><strong>{{ money_fmt((float) $payout->amount) }}</strong></td>
                        <td>
                            <span class="badge {{ $payout->status === 'paid' ? 'badge-active' : ($payout->status === 'pending' ? 'badge-pending' : 'badge-cancelled') }}">
                                {{ ucfirst($payout->status) }}
                            </span>
                        </td>
                        <td>{{ $payout->paid_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        <td style="text-align:right;">
                            @if($payout->status === 'pending')
                                <form method="POST"
                                      action="{{ route('admin.extensions.reseller-center.payouts.mark-paid', $payout) }}"
                                      onsubmit="return pnConfirm(event, @js('Mark payout #'.$payout->id.' as paid now?'));">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">Mark Paid</button>
                                </form>
                            @else
                                <span style="color:#888;font-size:12px;">Settled</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:12px;">{{ $payouts->links() }}</div>
        @endif
    </div>
</div>
@endsection

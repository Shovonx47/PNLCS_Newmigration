@extends('admin.layouts.app')
@section('title', 'Payment Disputes')
@section('content')

<h1>{{ 'Payment Disputes' }}</h1>

@php
    $tabs = [
        'all' => 'All Records',
        'open' => 'Disputed',
        'refunded' => 'Refunds / Chargebacks',
    ];
@endphp

<ul class="admin-tabs" role="tablist" style="margin-bottom:14px;">
    @foreach($tabs as $key => $label)
        <li class="{{ ($filter ?? 'all') === $key ? 'active' : '' }}">
            <a href="{{ route('admin.billing.disputes', ['filter' => $key]) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ 'Transaction ID' }}</th>
                <th>{{ 'Client' }}</th>
                <th>{{ 'Invoice #' }}</th>
                <th>{{ 'Gateway' }}</th>
                <th>{{ 'Description' }}</th>
                <th style="text-align:right;">{{ 'Amount Out' }}</th>
                <th>{{ 'Date' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($disputes as $tx)
                <tr>
                    <td style="font-family:monospace;">{{ $tx->trans_id ?: ('#' . $tx->id) }}</td>
                    <td>
                        @if($tx->client)
                            <a href="{{ route('admin.clients.show', $tx->client) }}">{{ $tx->client->full_name }}</a>
                        @else
                            {{ '—' }}
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($tx->invoice)
                            <a href="{{ route('admin.invoices.show', $tx->invoice) }}">#{{ $tx->invoice->invoice_num ?? $tx->invoice->id }}</a>
                        @else
                            {{ '—' }}
                        @endif
                    </td>
                    <td style="text-align:center;">{{ payment_method_label((string) $tx->gateway) }}</td>
                    <td>{{ $tx->description }}</td>
                    <td style="text-align:right;font-weight:600;color:#c43c35;">{{ money_fmt($tx->amount_out > 0 ? $tx->amount_out : $tx->amount_in) }}</td>
                    <td style="text-align:center;">{{ $tx->date ? \Carbon\Carbon::parse($tx->date)->format(date_fmt()) : ($tx->created_at?->format(date_fmt()) ?? '—') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:#777;padding:24px;">{{ 'No payment disputes or chargebacks recorded.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">{{ $disputes->withQueryString()->links() }}</div>

@endsection

@extends('admin.layouts.app')
@section('title', 'Offline CC Processing')
@section('content')

<h1>{{ 'Offline CC Processing' }}</h1>

<form method="GET" action="{{ route('admin.billing.offline-cc') }}" style="margin-bottom:14px;">
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="18%" class="fieldlabel">{{ 'Client / Invoice #' }}</td>
            <td class="fieldarea">
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ 'Search by Invoice # or Client...' }}" class="form-control input-300">
                    <button type="submit" class="btn btn-primary btn-sm">{{ 'Filter' }}</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('admin.billing.offline-cc') }}" class="btn btn-default btn-sm">{{ 'Reset' }}</a>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</form>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ 'Invoice #' }}</th>
                <th>{{ 'Client Name' }}</th>
                <th>{{ 'Invoice Date' }}</th>
                <th>{{ 'Due Date' }}</th>
                <th style="text-align:right;">{{ 'Total' }}</th>
                <th>{{ 'Payment Method' }}</th>
                <th>{{ 'Stored Card' }}</th>
                <th width="150">{{ 'Actions' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $inv)
                <tr>
                    <td><a href="{{ route('admin.invoices.show', $inv) }}" style="font-weight:600;">#{{ $inv->invoice_num ?? $inv->id }}</a></td>
                    <td>
                        @if($inv->client)
                            <a href="{{ route('admin.clients.show', $inv->client) }}">{{ $inv->client->full_name }}</a>
                        @else
                            {{ 'N/A' }}
                        @endif
                    </td>
                    <td style="text-align:center;">{{ $inv->date ? \Carbon\Carbon::parse($inv->date)->format(date_fmt()) : '—' }}</td>
                    <td style="text-align:center;">{{ $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format(date_fmt()) : '—' }}</td>
                    <td style="text-align:right;font-weight:600;">{{ money_fmt($inv->total) }}</td>
                    <td style="text-align:center;">{{ payment_method_label((string) $inv->payment_method) }}</td>
                    <td style="text-align:center;">
                        @if($inv->client_id && isset($storedCardClients[$inv->client_id]))
                            <span class="whmcs-status active">{{ 'Card on File' }}</span>
                        @else
                            <span style="color:#888;font-size:12px;">{{ 'Manual / Offline' }}</span>
                        @endif
                    </td>
                    <td style="text-align:center;white-space:nowrap;">
                        <a href="{{ route('admin.invoices.show', $inv) }}" class="btn btn-primary btn-xs">{{ 'Open & Process' }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;color:#777;padding:22px;">{{ 'No unpaid offline or card-processing invoices found.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">{{ $invoices->withQueryString()->links() }}</div>

@endsection

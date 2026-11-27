@extends('admin.layouts.app')
@section('title', 'Service Addons')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
    <h1 style="margin:0;border:0;padding:0;">{{ 'Service Addons' }}</h1>
    <a href="{{ route('admin.config.addons') }}" class="btn btn-default btn-sm"><i class="fas fa-cog"></i> {{ 'Configure Product Addons' }}</a>
</div>

<form method="GET" action="{{ route('admin.services.addons') }}" style="margin-bottom:14px;">
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="15%" class="fieldlabel">{{ 'Search' }}</td>
            <td width="35%" class="fieldarea">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ 'Addon, domain, or client...' }}" class="form-control input-250">
            </td>
            <td width="15%" class="fieldlabel">{{ 'Status' }}</td>
            <td width="35%" class="fieldarea">
                <select name="status" class="form-control select-inline">
                    <option value="">{{ '- Any -' }}</option>
                    @foreach(['active', 'pending', 'suspended', 'terminated', 'cancelled'] as $st)
                        <option value="{{ $st }}" @selected(strtolower((string) request('status', '')) === $st)>{{ status_label($st) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-left:8px;">{{ 'Search' }}</button>
            </td>
        </tr>
    </table>
</form>

<div style="font-size:13px;color:#333;margin-bottom:6px;">
    {{ $addons->total() }} {{ 'Records Found' }}
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th style="width:55px;">{{ 'ID' }}</th>
                <th class="text-left">{{ 'Addon' }}</th>
                <th class="text-left">{{ 'Product/Service' }}</th>
                <th class="text-left">{{ 'Client Name' }}</th>
                <th>{{ 'Billing Cycle' }}</th>
                <th>{{ 'Price' }}</th>
                <th>{{ 'Next Due Date' }}</th>
                <th>{{ 'Status' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($addons as $sa)
                @php
                    $cur = $sa->client?->currency;
                    $priceText = $cur
                        ? (($cur->prefix ?? '') . number_format((float) $sa->amount, 2) . ($cur->suffix ?? ''))
                        : money_fmt((float) $sa->amount);
                @endphp
                <tr>
                    <td style="text-align:center;">{{ $sa->id }}</td>
                    <td><strong>{{ $sa->label() }}</strong></td>
                    <td>
                        @if($sa->service)
                            <a href="{{ route('admin.services.show', $sa->service) }}">
                                {{ $sa->service->product?->name ?? 'Service #' . $sa->service->id }}
                            </a>
                            @if($sa->service->domain)
                                <span style="color:#666;">({{ $sa->service->domain }})</span>
                            @endif
                        @else
                            {{ '—' }}
                        @endif
                    </td>
                    <td>
                        @if($sa->client)
                            <a href="{{ route('admin.clients.show', $sa->client) }}">{{ $sa->client->full_name }}</a>
                        @else
                            {{ '—' }}
                        @endif
                    </td>
                    <td style="text-align:center;">{{ cycle_label($sa->billing_cycle) }}</td>
                    <td style="text-align:center;">{{ $priceText }}</td>
                    <td style="text-align:center;">{{ $sa->next_due_date?->format(date_fmt()) ?? '—' }}</td>
                    <td style="text-align:center;">
                        <span class="whmcs-status {{ strtolower((string) $sa->status) }}">{{ status_label($sa->status) }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;color:#777;padding:22px;">{{ 'No client service addons found.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">{{ $addons->withQueryString()->links() }}</div>

@endsection

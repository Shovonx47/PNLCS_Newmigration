@extends('admin.layouts.app')
@section('title', 'Products/Services')
@section('content')
@php
    $filterOpen = request()->hasAny(['q', 'search', 'status', 'product_id', 'billing_cycle']);
    $hideInactive = request()->boolean('hide_inactive', false);
@endphp

<h1>{{ 'Products/Services' }}</h1>

<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="active">
        <a href="#tabServiceFilter" onclick="var p=document.getElementById('svc-filter-pane');if(p){p.style.display=p.style.display==='none'?'block':'none';}return false;">
            {{ 'Search/Filter' }}
        </a>
    </li>
</ul>
<div class="tab-content admin-tabs" id="svc-filter-pane" style="display:{{ $filterOpen ? 'block' : 'none' }};margin-bottom:12px;">
    <div class="tab-pane active">
        <form method="GET" action="{{ route('admin.services.index') }}">
            @if(request()->filled('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ 'Product/Service' }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="product_id" class="form-control select-inline">
                            <option value="">{{ '- Any -' }}</option>
                            @foreach($productsList ?? [] as $prod)
                                <option value="{{ $prod->id }}" @selected((string) request('product_id') === (string) $prod->id)>{{ $prod->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td width="15%" class="fieldlabel">{{ 'Domain / IP / Client' }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="q" value="{{ request('q', request('search')) }}" class="form-control input-250" placeholder="{{ 'Domain, username, IP or ID' }}">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ 'Billing Cycle' }}</td>
                    <td class="fieldarea">
                        <select name="billing_cycle" class="form-control select-inline">
                            <option value="">{{ '- Any -' }}</option>
                            @foreach(['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially', 'onetime', 'free'] as $cyc)
                                <option value="{{ $cyc }}" @selected(request('billing_cycle') === $cyc)>{{ cycle_label($cyc) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="fieldlabel">{{ 'Status' }}</td>
                    <td class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            <option value="">{{ '- Any -' }}</option>
                            @foreach(['active', 'pending', 'suspended', 'terminated', 'cancelled', 'fraud'] as $val)
                                <option value="{{ $val }}" @selected(strtolower((string) request('status', '')) === $val)>{{ status_label($val) }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> {{ 'Search' }}</button>
                <a href="{{ route('admin.services.index', array_filter(['type' => request('type')])) }}" class="btn btn-default btn-sm">{{ 'Reset' }}</a>
            </div>
        </form>
    </div>
</div>

<div class="whmcs-record-bar" style="margin:10px 0 6px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
    <div style="font-size:13px;color:#333;">
        {{ $services->total() }} {{ 'Records Found' }}@if($services->total() > 0), {{ 'Showing' }} {{ $services->firstItem() }} {{ 'to' }} {{ $services->lastItem() }}@endif
    </div>
    <div style="display:flex;align-items:center;gap:8px;font-size:12px;">
        <span>{{ 'Jump to Page:' }}</span>
        <a href="{{ route('admin.services.index', array_merge(request()->except('page'), ['hide_inactive' => $hideInactive ? 0 : 1])) }}"
           style="display:inline-flex;align-items:center;border:1px solid #ccc;border-radius:3px;overflow:hidden;text-decoration:none;font-size:11.5px;">
            <span style="padding:2px 7px;background:{{ $hideInactive ? '#337ab7' : '#e5e7eb' }};color:{{ $hideInactive ? '#fff' : '#555' }};font-weight:700;">{{ $hideInactive ? 'ON' : 'OFF' }}</span>
            <span style="padding:2px 8px;background:#fff;color:#333;">{{ 'Hide Inactive Clients (' . ($inactiveClientsCount ?? 0) . ')' }}</span>
        </a>
        @if($services->lastPage() > 1)
            <select onchange="if(this.value) window.location.href=this.value" style="padding:2px 6px;border:1px solid #ccc;border-radius:3px;font-size:12px;">
                @for($pg = 1; $pg <= $services->lastPage(); $pg++)
                    <option value="{{ $services->url($pg) }}" @selected($pg === $services->currentPage())>{{ $pg }}</option>
                @endfor
            </select>
        @else
            <select style="padding:2px 6px;border:1px solid #ccc;border-radius:3px;font-size:12px;"><option>1</option></select>
        @endif
    </div>
</div>

<div class="tablebg" x-data="{ expandedId: null }">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th style="width:26px;"><input type="checkbox" onclick="document.querySelectorAll('.svc-chk').forEach(c => c.checked = this.checked)"></th>
                <th style="width:55px;">{{ 'ID' }}</th>
                <th class="text-left">{{ 'Product/Service' }}</th>
                <th class="text-left">{{ 'Domain' }}</th>
                <th class="text-left">{{ 'Client Name' }}</th>
                <th>{{ 'Price' }}</th>
                <th>{{ 'Billing Cycle' }}</th>
                <th>{{ 'Next Due Date' }}</th>
                <th>{{ 'Status' }}</th>
                <th style="width:28px;"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($services as $service)
                @php
                    $statusSlug = strtolower((string) ($service->status ?? 'cancelled'));
                    $cur = $service->client?->currency;
                    $priceFormatted = $cur
                        ? (($cur->prefix ?? '') . number_format((float) $service->amount, 2) . ($cur->suffix ?? ''))
                        : money_fmt((float) $service->amount);
                @endphp
                <tr>
                    <td style="text-align:center;"><input type="checkbox" class="svc-chk" value="{{ $service->id }}"></td>
                    <td style="text-align:center;"><a href="{{ route('admin.services.show', $service) }}">{{ $service->id }}</a></td>
                    <td>
                        <a href="{{ route('admin.services.show', $service) }}" style="color:#333;">
                            {{ $service->product?->name ?? $service->whmcs_product_name ?? 'Service' }}
                        </a>
                    </td>
                    <td>
                        @if($service->domain)
                            <a href="{{ route('admin.services.show', $service) }}">{{ $service->domain }}</a>
                            <a href="http://{{ $service->domain }}" target="_blank" rel="noopener" style="color:#cc0000;font-size:10px;margin-left:3px;text-decoration:none;" title="{{ 'Visit domain' }}">www</a>
                        @else
                            <span style="color:#666;">({{ 'No Domain' }})</span>
                        @endif
                    </td>
                    <td>
                        @if($service->client)
                            <a href="{{ route('admin.clients.show', $service->client_id) }}">{{ $service->client->full_name }}</a>
                        @else
                            {{ 'N/A' }}
                        @endif
                    </td>
                    <td style="text-align:center;">{{ $priceFormatted }}</td>
                    <td style="text-align:center;">{{ cycle_label($service->billing_cycle) }}</td>
                    <td style="text-align:center;">{{ $service->next_due_date?->format(date_fmt()) ?? '-' }}</td>
                    <td style="text-align:center;">
                        <span class="whmcs-status {{ $statusSlug }}">{{ status_label($service->status ?? '') }}</span>
                    </td>
                    <td style="text-align:center;">
                        <button type="button"
                                @click="expandedId = (expandedId === {{ $service->id }} ? null : {{ $service->id }})"
                                style="border:0;background:none;color:#1a4d80;font-weight:700;font-size:15px;cursor:pointer;line-height:1;"
                                title="{{ 'Quick Details' }}">+</button>
                    </td>
                </tr>
                <tr x-show="expandedId === {{ $service->id }}" x-cloak>
                    <td colspan="10" style="background:#f8fafc;padding:8px 14px;font-size:12px;">
                        <strong>{{ 'Username:' }}</strong> {{ $service->username ?: '—' }}
                        &nbsp;|&nbsp;
                        <strong>{{ 'Registration Date:' }}</strong> {{ $service->registration_date ? \Carbon\Carbon::parse($service->registration_date)->format(date_fmt()) : ($service->created_at?->format(date_fmt()) ?? '—') }}
                        &nbsp;|&nbsp;
                        <strong>{{ 'Payment Method:' }}</strong> {{ $service->payment_method ? payment_method_label((string) $service->payment_method) : '—' }}
                        &nbsp;|&nbsp;
                        <a href="{{ route('admin.services.show', $service) }}" style="font-weight:600;">{{ 'Open Full Service Record' }} &rarr;</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align:center;color:#777;padding:22px;">{{ __('admin.services.no_services') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">
    {{ $services->withQueryString()->links() }}
</div>
@endsection

@extends('admin.layouts.app')
@section('title', __('admin.orders.order_hash') . $order->order_num)
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
    <h1 style="margin:0;">{{ __('admin.search.order_title', ['num' => $order->order_num]) }}</h1>
    <div class="context-btn-container" style="margin:0;">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.orders.back') }}</a>
    </div>
</div>

@if(strtolower($order->status) === 'fraud' && $order->fraud_output)
<div class="infobox" style="border-left:4px solid #c43c35;background:#f2dede;color:#a94442;margin-bottom:15px;">
    <strong>{{ __('admin.orders.fraud_info') }}</strong><br>
    <span>{{ $order->fraud_output }}</span>
</div>
@endif

<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td width="15%" class="fieldlabel">{{ __('admin.orders.date') }}</td>
        <td width="35%" class="fieldarea">{{ $order->date?->format(date_fmt()) ?? '—' }}</td>
        <td width="15%" class="fieldlabel">{{ __('admin.orders.payment_method_label') }}</td>
        <td width="35%" class="fieldarea">{{ $order->payment_method ? payment_method_label((string) $order->payment_method) : '—' }}</td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.orders.order_number') }}</td>
        <td class="fieldarea"><strong style="font-family:monospace;">#{{ $order->order_num }}</strong> ({{ __('ID') }}: {{ $order->id }})</td>
        <td class="fieldlabel">{{ __('admin.orders.amount') }}</td>
        <td class="fieldarea"><strong>{{ money_fmt($order->amount) }}</strong></td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.orders.client_info') }}</td>
        <td class="fieldarea">
            @if($order->client)
                <a href="{{ route('admin.clients.show', $order->client) }}" style="font-weight:600;">{{ $order->client?->display_name ?? __('Deleted Client') }}</a>
                @if($order->client?->company_name)
                    <span>({{ $order->client->company_name }})</span>
                @endif
                @if($order->client?->email)
                    <div style="font-size:12px;color:#666;">{{ $order->client->email }}</div>
                @endif
            @else
                {{ __('Deleted Client') }}
            @endif
        </td>
        <td class="fieldlabel">{{ __('common.table.invoice_num') }}</td>
        <td class="fieldarea">
            @if($order->invoice)
                <a href="{{ route('admin.invoices.show', $order->invoice) }}">{{ __('admin.orders.view_invoice') }} #{{ $order->invoice->invoice_num }}</a>
            @else
                {{ __('No Invoice Due') }}
            @endif
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.orders.promo_code_label') }}</td>
        <td class="fieldarea">
            @if($order->promo_code)
                <span style="background:#fcf8e3;color:#8a6d3b;padding:2px 6px;border-radius:3px;font-family:monospace;font-size:12px;">{{ $order->promo_code }}</span>
            @else
                {{ '—' }}
            @endif
        </td>
        <td class="fieldlabel">{{ __('common.table.status') }}</td>
        <td class="fieldarea">
            <span class="whmcs-status {{ strtolower($order->status) }} badge-{{ strtolower($order->status) }}">{{ status_label($order->status) }}</span>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.orders.ip_address') }}</td>
        <td class="fieldarea" style="font-family:monospace;">{{ $order->ip_address ?: '—' }}</td>
        <td class="fieldlabel">{{ __('admin.orders.notes') }}</td>
        <td class="fieldarea" style="white-space:pre-wrap;">{{ $order->notes ?: '—' }}</td>
    </tr>
</table>

<h2 style="margin:18px 0 8px;">{{ __('Order Items') }}</h2>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.type') }}</th>
                <th>{{ __('common.table.description') }}</th>
                <th>{{ __('common.table.domain') }}</th>
                <th>{{ __('admin.orders.billing') }}</th>
                <th style="text-align:right;">{{ __('common.table.amount') }}</th>
                <th>{{ __('common.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $hasAnyOrderItems = $order->services->count() > 0 || $order->domains->count() > 0 || $order->addons->count() > 0;
            @endphp

            @foreach($order->services as $svc)
            <tr>
                <td>{{ __('admin.orders.services_header') }}</td>
                <td><a href="{{ route('admin.services.show', $svc) }}">{{ $svc->product?->name ?? __('N/A') }}</a></td>
                <td style="font-family:monospace;">
                    @if(strtolower($svc->status) === 'pending' && empty($svc->username))
                    <form method="POST" action="{{ route('admin.orders.service-domain', [$order, $svc]) }}" style="display:inline-flex;gap:4px;align-items:center;margin:0;">
                        @csrf @method('PUT')
                        <input type="text" name="domain" value="{{ $svc->domain }}" class="form-control" style="height:26px;font-size:12px;padding:2px 6px;max-width:180px;" placeholder="{{ 'domain.com' }}">
                        <button type="submit" class="btn btn-default btn-xs">{{ __('common.actions.save') }}</button>
                    </form>
                    @else
                        {{ $svc->domain ?? '—' }}
                    @endif
                </td>
                <td>{{ cycle_label($svc->billing_cycle) }}</td>
                <td style="text-align:right;font-family:monospace;">{{ money_fmt($svc->amount) }}</td>
                <td><span class="badge-{{ strtolower($svc->status) }} whmcs-status {{ strtolower($svc->status) }}">{{ status_label($svc->status) }}</span></td>
            </tr>
            @endforeach

            @foreach($order->domains as $dom)
            <tr>
                <td>{{ __('admin.orders.domains_header') }} ({{ $dom->type }})</td>
                <td>{{ __('common.table.domain') }}</td>
                <td style="font-family:monospace;font-weight:600;">{{ $dom->domain }}</td>
                <td>{{ __('admin.orders.expiry') }}: {{ $dom->expiry_date?->format(date_fmt()) ?? '—' }}</td>
                <td style="text-align:right;">{{ '—' }}</td>
                <td><span class="badge-{{ strtolower($dom->status) }} whmcs-status {{ strtolower($dom->status) }}">{{ status_label($dom->status) }}</span></td>
            </tr>
            @endforeach

            @foreach($order->addons as $addon)
            <tr>
                <td>{{ __('admin.orders.addons_header') }}</td>
                <td>
                    {{ $addon->label() }}
                    @if($addon->addon?->description)
                        <br><small style="color:#777;">{{ \Illuminate\Support\Str::limit($addon->addon->description, 80) }}</small>
                    @endif
                </td>
                <td>
                    @if($addon->service)
                        <a href="{{ route('admin.services.show', $addon->service) }}">{{ $addon->service->domain ?: '#' . $addon->service->id }}</a>
                    @else
                        {{ '—' }}
                    @endif
                </td>
                <td>
                    {{ cycle_label($addon->billing_cycle) }}
                    @if($addon->next_due_date)
                        <div style="font-size:11px;color:#777;">{{ __('client.services.next_due_date') }}: {{ $addon->next_due_date->format(date_fmt()) }}</div>
                    @endif
                </td>
                <td style="text-align:right;font-family:monospace;">{{ number_format((float) $addon->amount, 2) }}</td>
                <td><span class="badge-{{ strtolower($addon->status) }} whmcs-status {{ strtolower($addon->status) }}">{{ status_label($addon->status) }}</span></td>
            </tr>
            @endforeach

            @unless($hasAnyOrderItems)
            <tr>
                <td colspan="6" style="text-align:center;padding:18px;color:#888;">{{ __('No Order Items') }}</td>
            </tr>
            @endunless
        </tbody>
        <tfoot>
            <tr style="background:#f5f5f5;font-weight:700;">
                <td colspan="4" style="text-align:right;padding:6px 10px;">{{ __('common.table.total') }}</td>
                <td style="text-align:right;padding:6px 10px;font-family:monospace;">{{ money_fmt($order->amount) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="btn-container" style="margin-top:16px;">
    @if(strtolower($order->status) === 'pending')
    <form method="POST" action="{{ route('admin.orders.accept', $order) }}" style="display:inline-block;margin:0 3px;">
        @csrf
        <button type="submit" class="btn btn-success">{{ __('admin.orders.accept_order') }}</button>
    </form>
    @endif

    @if(! in_array(strtolower($order->status), ['cancelled', 'fraud']))
    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" onsubmit="return pnConfirm(event, @js(__('admin.orders.confirm_cancel')))" style="display:inline-block;margin:0 3px;">
        @csrf
        <button type="submit" class="btn btn-default">{{ __('admin.orders.cancel_order') }}</button>
    </form>
    @endif

    @if(strtolower($order->status) !== 'fraud')
    <form method="POST" action="{{ route('admin.orders.fraud', $order) }}" onsubmit="return pnConfirm(event, @js(__('admin.orders.confirm_fraud')))" style="display:inline-block;margin:0 3px;">
        @csrf
        <button type="submit" class="btn btn-warning">{{ __('common.actions.mark_fraud') }}</button>
    </form>
    @endif

    @if(in_array(strtolower($order->status), ['cancelled', 'fraud']))
    <form method="POST" action="{{ route('admin.orders.reopen', $order) }}" style="display:inline-block;margin:0 3px;">
        @csrf
        <button type="submit" class="btn btn-default">{{ __('admin.orders.set_pending') }}</button>
    </form>
    @endif

    @if(in_array(strtolower($order->status), ['cancelled', 'fraud', 'pending']))
    <form method="POST" action="{{ route('admin.orders.delete', $order) }}" onsubmit="return pnConfirm(event, @js(__('admin.orders.confirm_delete')))" style="display:inline-block;margin:0 3px;">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger">{{ __('admin.orders.delete_order') }}</button>
    </form>
    @endif
</div>

@endsection

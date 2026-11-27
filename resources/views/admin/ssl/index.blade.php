@extends('admin.layouts.app')
@section('title', __('admin.ssl_orders'))
@section('content')
<h1>{{ __('admin.ssl.title') }}</h1>

<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="active"><a href="#tabSslFilter" role="tab" data-toggle="tab">{{ 'Search/Filter' }}</a></li>
</ul>
<div class="tab-content admin-tabs" style="margin-bottom:15px;">
    <div class="tab-pane active" id="tabSslFilter">
        <form method="GET" action="{{ route('admin.ssl.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.actions.search') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="search" class="form-control input-300" placeholder="{{ __('admin.ssl.search_placeholder') }}" value="{{ request('search') }}">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            <option value="">{{ __('common.misc.all_statuses') }}</option>
                            @foreach(['Awaiting Configuration', 'Configuration Submitted', 'Awaiting Issuance', 'Completed', 'Cancelled', 'Revoked', 'Expired'] as $s)
                                <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default"><i class="fas fa-search"></i> {{ __('common.actions.filter') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="context-btn-container" style="text-align:right;margin-bottom:10px;">
    <form method="POST" action="{{ route('admin.ssl.import') }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.ssl.import_confirm')))">
        @csrf
        <button type="submit" class="btn btn-default btn-sm"><i class="fas fa-download"></i> {{ __('admin.ssl.import_remote') }}</button>
    </form>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.id') }}</th>
                <th>{{ __('common.table.domain') }}</th>
                <th>{{ __('common.table.type') }}</th>
                <th>{{ __('common.table.client') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th>{{ __('admin.ssl.order_date') }}</th>
                <th>{{ __('admin.ssl.expires') }}</th>
                <th>{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td><a href="{{ route('admin.ssl.show', $order) }}">{{ $order->id }}</a></td>
                <td>
                    <strong>{{ $order->domain ?: '—' }}</strong>
                    @if($order->cert_type)
                        <br><small class="text-muted">{{ $order->cert_type }}</small>
                    @endif
                </td>
                <td>{{ $order->module ?: '—' }}</td>
                <td>
                    @if($order->client)
                        <a href="{{ route('admin.clients.show', $order->client_id) }}">
                            {{ $order->client->first_name }} {{ $order->client->last_name }}
                        </a>
                    @else
                        {{ '—' }}
                    @endif
                </td>
                <td><span class="whmcs-status badge {{ $order->getStatusBadgeClass() }}">{{ status_label($order->status) }}</span></td>
                <td>{{ $order->order_date?->format(date_fmt()) ?: $order->created_at->format(date_fmt()) }}</td>
                <td>
                    @if($order->crt_expires)
                        {{ $order->crt_expires->format(date_fmt()) }}
                        @if($order->daysUntilExpiry() !== null && $order->daysUntilExpiry() <= 30 && $order->daysUntilExpiry() > 0)
                            <br><small style="color:#d68100;">{{ $order->daysUntilExpiry() . ' ' . __('admin.ssl.days') }}</small>
                        @elseif($order->daysUntilExpiry() !== null && $order->daysUntilExpiry() <= 0)
                            <br><small style="color:#c43c35;">{{ __('admin.ssl.expired') }}</small>
                        @endif
                    @else
                        {{ '—' }}
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.ssl.show', $order) }}" class="btn btn-default btn-xs">{{ __('common.actions.view') }}</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">{{ __('admin.ssl.no_orders') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">{{ $orders->links() }}</div>
@endsection

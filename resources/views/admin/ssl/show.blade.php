@extends('admin.layouts.app')
@section('title', __('admin.ssl.title_num', ['num' => $order->id]))
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
    <h1 style="margin:0;">{{ __('admin.ssl.title_num', ['num' => $order->id]) }}</h1>
    <a href="{{ route('admin.ssl.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.ssl.back_to_list') }}</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<h2>{{ __('admin.ssl.certificate_details') }}</h2>
<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td width="20%" class="fieldlabel">{{ __('admin.ssl.domain') }}</td>
        <td width="30%" class="fieldarea"><strong>{{ $order->domain ?: '—' }}</strong></td>
        <td width="20%" class="fieldlabel">{{ __('admin.ssl.status') }}</td>
        <td width="30%" class="fieldarea">
            <span class="whmcs-status badge {{ $order->getStatusBadgeClass() }}">{{ status_label($order->status) }}</span>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.ssl.type') }}</td>
        <td class="fieldarea">{{ $order->cert_type ?: '—' }}</td>
        <td class="fieldlabel">{{ __('admin.ssl.module') }}</td>
        <td class="fieldarea">{{ $order->module ?: '—' }}</td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.ssl.remote_id') }}</td>
        <td class="fieldarea">{{ $order->remote_id ?: '—' }}</td>
        <td class="fieldlabel">{{ __('admin.ssl.validation') }}</td>
        <td class="fieldarea">{{ $order->validation_method ?: '—' }}</td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.ssl.client') }}</td>
        <td class="fieldarea">
            @if($order->client)
                <a href="{{ route('admin.clients.show', $order->client_id) }}"><strong>{{ $order->client->first_name }} {{ $order->client->last_name }}</strong></a>
                <span class="text-muted">({{ $order->client->email }})</span>
                <a href="{{ route('admin.clients.show', $order->client_id) }}" class="btn btn-default btn-xs" style="margin-left:6px;">{{ __('admin.ssl.view_client') }}</a>
            @else
                <span class="text-muted">{{ __('admin.ssl.no_client') }}</span>
            @endif
        </td>
        <td class="fieldlabel">{{ __('admin.ssl.related_service') }}</td>
        <td class="fieldarea">
            @if($order->service)
                <a href="{{ route('admin.services.show', $order->service_id) }}"><strong>{{ $order->service->product?->name ?? ('Service #' . $order->service_id) }}</strong></a>
                <span class="text-muted">({{ __('admin.ssl.service_status', ['status' => $order->service->status]) }})</span>
                <a href="{{ route('admin.services.show', $order->service_id) }}" class="btn btn-default btn-xs" style="margin-left:6px;">{{ __('admin.ssl.view_service') }}</a>
            @else
                {{ '—' }}
            @endif
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.ssl.order_date') }}</td>
        <td class="fieldarea">{{ $order->order_date?->timezone(display_tz())->format(datetime_fmt()) ?: $order->created_at->timezone(display_tz())->format(datetime_fmt()) }}</td>
        <td class="fieldlabel">{{ __('admin.ssl.completion_date') }}</td>
        <td class="fieldarea">{{ $order->completion_date?->timezone(display_tz())->format(datetime_fmt()) ?: '—' }}</td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.ssl.expires') }}</td>
        <td class="fieldarea">
            @if($order->crt_expires)
                {{ $order->crt_expires->format(date_fmt()) }}
                @if($order->daysUntilExpiry() !== null)
                    <small style="color:{{ $order->daysUntilExpiry() <= 7 ? '#c43c35' : ($order->daysUntilExpiry() <= 30 ? '#d68100' : '#2e7d32') }};">
                        ({{ $order->daysUntilExpiry() . ' ' . __('admin.ssl.days') }})
                    </small>
                @endif
            @else
                {{ '—' }}
            @endif
        </td>
        <td class="fieldlabel">{{ __('admin.ssl.timeline') }}</td>
        <td class="fieldarea">
            <div><small class="text-muted">{{ __('admin.ssl.created') }}{{ ': ' }}</small>{{ $order->created_at->timezone(display_tz())->format(datetime_fmt()) }}</div>
            @if($order->order_date)
                <div><small class="text-muted">{{ __('admin.ssl.submitted_ca') }}{{ ': ' }}</small>{{ $order->order_date->timezone(display_tz())->format(datetime_fmt()) }}</div>
            @endif
            @if($order->completion_date)
                <div><small class="text-muted">{{ __('admin.ssl.completed') }}{{ ': ' }}</small>{{ $order->completion_date->timezone(display_tz())->format(datetime_fmt()) }}</div>
            @endif
            @if($order->last_polled_at)
                <div><small class="text-muted">{{ __('admin.ssl.last_polled') }}{{ ': ' }}</small>{{ $order->last_polled_at->timezone(display_tz())->format(datetime_fmt()) }}</div>
            @endif
        </td>
    </tr>
    @if($order->domains)
    <tr>
        <td class="fieldlabel">{{ __('admin.ssl.san_domains') }}</td>
        <td class="fieldarea" colspan="3">{{ $order->domains }}</td>
    </tr>
    @endif
    @if($order->approver_email)
    <tr>
        <td class="fieldlabel">{{ __('admin.ssl.approver_email') }}</td>
        <td class="fieldarea" colspan="3">{{ $order->approver_email }}</td>
    </tr>
    @endif
    <tr>
        <td class="fieldlabel">{{ __('admin.ssl.actions') }}</td>
        <td class="fieldarea" colspan="3">
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                @if(in_array($order->status, ['Awaiting Configuration', 'Awaiting Issuance', 'Configuration Submitted']))
                    <form method="POST" action="{{ route('admin.ssl.action', $order) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="action" value="poll">
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.ssl.poll_status') }}</button>
                    </form>
                @endif

                @if(in_array($order->status, ['Awaiting Issuance', 'Configuration Submitted']))
                    <form method="POST" action="{{ route('admin.ssl.action', $order) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="action" value="resend">
                        <button type="submit" class="btn btn-warning btn-sm">{{ __('admin.ssl.resend_validation') }}</button>
                    </form>
                @endif

                @if($order->isCompleted())
                    @if(auth('admin')->user()?->hasPermission('manage_services'))
                        <a href="{{ route('admin.ssl.download', $order) }}" class="btn btn-success btn-sm">{{ __('admin.ssl.download_cert') }}</a>
                    @endif
                    <form method="POST" action="{{ route('admin.ssl.action', $order) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="action" value="renew">
                        <button type="submit" class="btn btn-success btn-sm" onclick="return pnConfirm(event, @js(__('admin.ssl.confirm_renew')))">{{ __('admin.ssl.renew') }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.ssl.action', $order) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="action" value="reissue">
                        <input type="hidden" name="csr" value="{{ $order->csr }}">
                        <button type="submit" class="btn btn-info btn-sm" onclick="return pnConfirm(event, @js(__('admin.ssl.confirm_reissue')))">{{ __('admin.ssl.reissue') }}</button>
                    </form>
                @endif

                @if(!in_array($order->status, ['Cancelled', 'Revoked', 'Expired']))
                    <form method="POST" action="{{ route('admin.ssl.action', $order) }}" style="display:inline;">
                        @csrf
                        <input type="hidden" name="action" value="revoke">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return pnConfirm(event, @js(__('admin.ssl.confirm_revoke')), {danger: true})">{{ __('admin.ssl.revoke_certificate') }}</button>
                    </form>
                @endif
            </div>
        </td>
    </tr>
</table>

@if($order->admin_first_name)
<h2 style="margin-top:16px;">{{ __('admin.ssl.admin_contact') }}</h2>
<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td width="20%" class="fieldlabel">{{ __('admin.ssl.name') }}</td>
        <td width="30%" class="fieldarea">{{ $order->admin_first_name }} {{ $order->admin_last_name }}</td>
        <td width="20%" class="fieldlabel">{{ __('common.form.email') }}</td>
        <td width="30%" class="fieldarea">{{ $order->admin_email }}</td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.ssl.phone') }}</td>
        <td class="fieldarea">{{ $order->admin_phone ?: '—' }}</td>
        <td class="fieldlabel">{{ __('admin.ssl.organization') }}</td>
        <td class="fieldarea">{{ $order->admin_org ?: '—' }}</td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('common.form.address') }}</td>
        <td class="fieldarea" colspan="3">{{ collect([$order->admin_address, $order->admin_city, $order->admin_state, $order->admin_zip, $order->admin_country])->filter()->implode(', ') ?: '—' }}</td>
    </tr>
</table>
@endif

@if($order->csr)
<h2 style="margin-top:16px;">{{ __('admin.ssl.csr') }}</h2>
<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td width="20%" class="fieldlabel">{{ __('admin.ssl.csr') }}</td>
        <td class="fieldarea">
            <textarea class="form-control" style="font-family:monospace;" rows="6" readonly>{{ $order->csr }}</textarea>
        </td>
    </tr>
</table>
@endif

<div class="btn-container">
    <a href="{{ route('admin.ssl.index') }}" class="btn btn-default">{{ __('admin.ssl.back_to_list') }}</a>
</div>
@endsection

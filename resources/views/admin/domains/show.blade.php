@extends('admin.layouts.app')
@section('title', $domain->domain)
@section('content')
@php
    $badgeClass = match(strtolower($domain->status)) {
        'active'     => 'badge-active',
        'pending'    => 'badge-pending',
        'expired'    => 'badge-overdue',
        'grace'      => 'badge-pending',
        'redemption' => 'badge-overdue',
        default      => 'badge-cancelled',
    };
    $ns = is_array($domain->nameservers) ? $domain->nameservers : (json_decode($domain->nameservers ?? '[]', true) ?? []);
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
    <h1 style="margin:0;">
        {{ $domain->domain }}
        <span class="whmcs-status {{ $badgeClass }}" style="font-size:12px;vertical-align:middle;margin-left:6px;">{{ status_label($domain->status) }}</span>
    </h1>
    <a href="{{ route('admin.domains.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.domains.back') }}</a>
</div>

@if($domain->client)
    @include('admin.clients.partials.client-tabs', ['client' => $domain->client, 'activeTab' => 'domains'])
@endif
<div class="{{ $domain->client ? 'tab-content client-tabs' : '' }}">
<div class="{{ $domain->client ? 'tab-pane active' : '' }}">

@if(session('epp_code'))
<div class="infobox" style="margin-bottom:15px;">
    <strong>{{ __('admin.domains.epp_code') }}{{ ': ' }}</strong>
    <code style="font-size:15px;font-weight:700;">{{ session('epp_code') }}</code>
</div>
@endif

<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td width="20%" class="fieldlabel">{{ __('common.table.client') }}</td>
        <td width="30%" class="fieldarea">
            @if($domain->client)
            <a href="{{ route('admin.clients.show', $domain->client_id) }}">{{ $domain->client->full_name }}</a>
            @else
            {{ 'N/A' }}
            @endif
            @if($domain->order_id)
            <span class="text-muted">({{ __('admin.domains.order') }} <a href="{{ route('admin.orders.show', $domain->order_id) }}">{{ '#' . $domain->order_id }}</a>)</span>
            @endif
        </td>
        <td width="20%" class="fieldlabel">{{ __('admin.domains.registration_date') }}</td>
        <td width="30%" class="fieldarea">{{ $domain->registration_date?->format(date_fmt()) ?? '-' }}</td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('common.table.domain') }}</td>
        <td class="fieldarea">
            <strong>{{ $domain->domain }}</strong>
            <span class="text-muted">({{ ucfirst($domain->type) }}, {{ __('admin.domains.period_years', ['count' => $domain->registration_period]) }})</span>
        </td>
        <td class="fieldlabel">{{ __('admin.domains.expiry_date') }}</td>
        <td class="fieldarea" style="{{ $domain->expiry_date?->isPast() ? 'color:#d9534f;font-weight:600;' : '' }}">
            {{ $domain->expiry_date?->format(date_fmt()) ?? '-' }}
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.domains.registrar') }}</td>
        <td class="fieldarea">
            <form method="POST" action="{{ route('admin.domains.registrar', $domain) }}" style="display:inline-flex;gap:6px;align-items:center;">
                @csrf
                <select name="registrar" class="form-control select-inline">
                    @foreach($registrarOptions as $registrarValue => $registrarLabel)
                    <option value="{{ $registrarValue }}" @selected(strtolower((string) $domain->registrar) === strtolower((string) $registrarValue))>{{ $registrarLabel }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-xs">{{ __('admin.domains.save') }}</button>
            </form>
        </td>
        <td class="fieldlabel">{{ __('common.table.status') }}</td>
        <td class="fieldarea">
            <span class="whmcs-status {{ $badgeClass }}">{{ status_label($domain->status) }}</span>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.domains.registrar_lock') }}</td>
        <td class="fieldarea">{{ $locked === null ? '-' : ($locked ? __('admin.domains.enabled') : __('admin.domains.disabled')) }}</td>
        <td class="fieldlabel">{{ __('admin.domains.last_sync') }}</td>
        <td class="fieldarea">
            @if($domain->last_sync_at)
                {{ $domain->last_sync_at->format(datetime_fmt()) }}
                @if($domain->last_sync_status === 'ok')
                <span style="color:#2e7d32;font-weight:600;margin-left:6px;">{{ __('admin.domains.last_sync_ok') }}</span>
                @else
                <span style="color:#c62828;font-weight:600;margin-left:6px;">{{ __('admin.domains.last_sync_error') }}</span>
                @endif
            @else
                {{ '-' }}
            @endif
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.domains.features') }}</td>
        <td class="fieldarea">
            @foreach(['dns_management' => __('admin.domains.dns_management'), 'email_forwarding' => __('admin.domains.email_forwarding'), 'id_protection' => __('admin.domains.id_protection')] as $field => $label)
            <span style="display:inline-block;margin-right:10px;">
                {{ $label }}{{ ': ' }}<span class="whmcs-status {{ $domain->$field ? 'badge-active' : 'badge-cancelled' }}">{{ $domain->$field ? 'Enabled' : 'Disabled' }}</span>
            </span>
            @endforeach
        </td>
        <td class="fieldlabel">{{ __('admin.domains.client_payment_method') }}</td>
        <td class="fieldarea">
            {{ $domain->client?->default_payment_method ? payment_method_label((string) $domain->client->default_payment_method) : '-' }}
            <span class="text-muted">({{ __('admin.domains.premium') }}{{ ': ' }}{{ $domain->is_premium ? 'Yes' : 'No' }})</span>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ 'Registrar Commands' }}</td>
        <td class="fieldarea" colspan="3">
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                @if($domain->awaitsRegistration())
                <form method="POST" action="{{ route('admin.domains.register', $domain) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.domains.confirm_register_retry')))">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">{{ __('admin.domains.register_retry') }}</button>
                </form>
                @endif
                <form method="POST" action="{{ route('admin.domains.renew', $domain) }}" style="display:inline-flex;gap:4px;align-items:center;" onsubmit="return pnConfirm(event, @js(__('admin.domains.confirm_renew')))">
                    @csrf
                    <input type="number" name="years" value="1" min="1" max="10" class="form-control input-sm" style="width:65px;">
                    <button type="submit" class="btn btn-default btn-sm">{{ __('admin.domains.renew') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.domains.sync', $domain) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm">{{ __('admin.domains.sync') }}</button>
                </form>
                <a href="{{ route('admin.domains.epp', $domain) }}" class="btn btn-default btn-sm">{{ __('admin.domains.get_epp_code') }}</a>
                @if($canToggleLock)
                <form method="POST" action="{{ route('admin.domains.lock', $domain) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.domains.confirm_lock')))">
                    @csrf
                    <button type="submit" class="btn btn-{{ $locked ? 'warning' : 'default' }} btn-sm">{{ $locked ? __('admin.domains.unlock') : __('admin.domains.lock') }}</button>
                </form>
                @endif
                <form method="POST" action="{{ route('admin.domains.autorenew', $domain) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm">{{ $domain->auto_renew ? __('admin.domains.autorenew_disable') : __('admin.domains.autorenew_enable') }}</button>
                </form>
                @if($canTogglePrivacy)
                <form method="POST" action="{{ route('admin.domains.privacy', $domain) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.domains.confirm_privacy')))">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm">{{ $domain->id_protection ? __('admin.domains.privacy_disable') : __('admin.domains.privacy_enable') }}</button>
                </form>
                @endif
                @if($canEditContacts)
                <a href="{{ route('admin.domains.contacts', $domain) }}" class="btn btn-default btn-sm">{{ __('client.domains.contacts_title') }}</a>
                @endif
                @if($canManageGlue)
                <a href="{{ route('admin.domains.glue', $domain) }}" class="btn btn-default btn-sm">{{ __('client.domains.glue_title') }}</a>
                @endif
                <form method="POST" action="{{ route('admin.domains.destroy', $domain) }}" style="display:inline;margin-left:auto;" onsubmit="return pnConfirm(event, @js(__('admin.domains.delete_confirm', ['domain' => $domain->domain])))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('admin.domains.delete') }}</button>
                </form>
            </div>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.domains.move_title') }}</td>
        <td class="fieldarea" colspan="3">
            <form method="POST" action="{{ route('admin.domains.move', $domain) }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;" onsubmit="return pnConfirm(event, @js(__('admin.domains.move_confirm')))">
                @csrf
                <input type="text" name="client" value="{{ old('client') }}" required class="form-control input-sm" style="width:260px;" aria-label="{{ __('admin.domains.move_client') }}" placeholder="{{ __('admin.domains.move_client') }}">
                <button type="submit" class="btn btn-default btn-sm">{{ __('admin.domains.move_button') }}</button>
                <small class="text-muted">{{ __('admin.domains.move_hint') }}</small>
            </form>
            @error('client')<div style="color:#a94442;font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
        </td>
    </tr>
</table>

<h2 style="margin-top:16px;">{{ __('admin.domains.billing') }}</h2>
<form method="POST" action="{{ route('admin.domains.billing', $domain) }}">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="20%" class="fieldlabel">{{ __('admin.domains.first_payment') }}</td>
            <td width="30%" class="fieldarea">
                <input type="number" step="0.01" min="0" name="first_payment_amount" value="{{ old('first_payment_amount', $domain->first_payment_amount) }}" class="form-control input-150">
            </td>
            <td width="20%" class="fieldlabel">{{ __('admin.domains.recurring_amount') }}</td>
            <td width="30%" class="fieldarea">
                <input type="number" step="0.01" min="0" name="recurring_amount" value="{{ old('recurring_amount', $domain->recurring_amount) }}" class="form-control input-150">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.domains.next_due_date') }}</td>
            <td class="fieldarea" colspan="3">
                <input type="date" name="next_due_date" value="{{ old('next_due_date', $domain->next_due_date?->toDateString()) }}" class="form-control input-200">
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> {{ __('admin.domains.save') }}</button>
    </div>
</form>

<h2 style="margin-top:16px;">{{ __('admin.domains.nameservers') }}</h2>
<form method="POST" action="{{ route('admin.domains.nameservers', $domain) }}">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        @for($i = 1; $i <= 5; $i++)
        <tr>
            <td width="20%" class="fieldlabel">{{ 'Nameserver ' . $i }}</td>
            <td class="fieldarea">
                <input type="text" name="ns[]" value="{{ $ns[$i - 1] ?? '' }}" class="form-control input-300" placeholder="{{ 'ns' . $i . '.' . $domain->domain }}">
            </td>
        </tr>
        @endfor
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.domains.update_nameservers') }}</button>
    </div>
</form>

@if($domain->notes)
<h2 style="margin-top:16px;">{{ __('admin.domains.notes') }}</h2>
<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td width="20%" class="fieldlabel">{{ __('admin.domains.notes') }}</td>
        <td class="fieldarea" style="white-space:pre-wrap;">{{ $domain->notes }}</td>
    </tr>
</table>
@endif

</div>
</div>
@endsection

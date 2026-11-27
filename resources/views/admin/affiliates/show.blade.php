@extends('admin.layouts.app')
@section('title', __('admin.affiliates.title_show') . ' - ' . ($affiliate->client?->first_name ?? '') . ' ' . ($affiliate->client?->last_name ?? ''))
@section('content')
<h1>{{ __('admin.affiliates.title_named', ['name' => trim(($affiliate->client?->first_name ?? '').' '.($affiliate->client?->last_name ?? ''))]) }}</h1>

<div class="context-btn-container" style="margin-bottom:10px;">
    <a href="{{ route('admin.affiliates.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.affiliates.back') }}</a>
</div>

<div class="infobox">
    <strong>{{ __('admin.affiliates.referral_link') }}:</strong>
    <code style="background:#fff;padding:2px 6px;border:1px solid #ddd;border-radius:3px;">{{ $affiliate->link() }}</code>
</div>

<h2 style="font-size:15px;font-weight:600;color:#1a4d80;margin:14px 0 8px;">{{ __('admin.affiliates.affiliate_details') }} &amp; {{ __('admin.affiliates.settings') }}</h2>

<form method="POST" action="{{ route('admin.affiliates.update', $affiliate) }}">
    @csrf @method('PUT')
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="18%" class="fieldlabel">{{ __('admin.affiliates.client_label') }}</td>
            <td width="32%" class="fieldarea">
                @if($affiliate->client)
                <a href="{{ route('admin.clients.show', $affiliate->client) }}" style="font-weight:600;">{{ $affiliate->client->first_name }} {{ $affiliate->client->last_name }}</a> ({{ $affiliate->client->email }})
                @else
                -
                @endif
            </td>
            <td width="18%" class="fieldlabel">{{ __('admin.affiliates.visitors_label') }}</td>
            <td width="32%" class="fieldarea">{{ number_format($affiliate->visitors) }}</td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.affiliates.balance_label') }}</td>
            <td class="fieldarea"><strong style="color:#3c763d;">{{ money_fmt($affiliate->balance) }}</strong></td>
            <td class="fieldlabel">{{ __('admin.affiliates.withdrawn_label') }}</td>
            <td class="fieldarea">{{ money_fmt($affiliate->withdrawn) }}</td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.affiliates.commission_type') }}</td>
            <td class="fieldarea">
                <select name="pay_type" class="form-control select-inline">
                    <option value="percentage" {{ $affiliate->pay_type === 'percentage' ? 'selected' : '' }}>{{ __('admin.affiliates.percentage') }}</option>
                    <option value="flat" {{ $affiliate->pay_type === 'flat' ? 'selected' : '' }}>{{ __('admin.affiliates.flat_amount') }}</option>
                </select>
            </td>
            <td class="fieldlabel">{{ __('admin.affiliates.commission_amount') }}</td>
            <td class="fieldarea">
                <input type="number" name="pay_amount" class="form-control input-150" step="0.01" value="{{ $affiliate->pay_amount }}">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.affiliates.code_label') }}</td>
            <td class="fieldarea">
                <input type="text" name="code" class="form-control input-200" maxlength="32" value="{{ old('code', $affiliate->code) }}">
                @error('code')<div style="color:#a94442;font-size:12px;margin-top:3px;">{{ $message }}</div>@enderror
            </td>
            <td class="fieldlabel">{{ __('admin.affiliates.recurring_amount') }}</td>
            <td class="fieldarea">
                <input type="number" name="recurring_pay_amount" class="form-control input-150" step="0.01" min="0" value="{{ $affiliate->recurring_pay_amount }}" placeholder="{{ $affiliate->pay_amount }}">
                <div style="color:#777;font-size:11px;margin-top:2px;">{{ __('admin.affiliates.recurring_amount_hint') }}</div>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.affiliates.onetime_commission') }}</td>
            <td class="fieldarea" colspan="3">
                <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0;font-weight:400;">
                    <input type="checkbox" name="onetime" value="1" {{ $affiliate->onetime ? 'checked' : '' }}>
                    {{ __('admin.affiliates.onetime_commission') }}
                </label>
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button class="btn btn-primary" type="submit">{{ __('admin.affiliates.save_settings') }}</button>
    </div>
</form>

@if($affiliate->balance > 0)
<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-top:12px;">
    <tr>
        <td width="18%" class="fieldlabel">{{ __('admin.affiliates.payout_amount') }}</td>
        <td width="32%" class="fieldarea">
            <form method="POST" action="{{ route('admin.affiliates.payout', $affiliate) }}" style="display:flex;gap:6px;align-items:center;">
                @csrf
                <input type="number" name="amount" step="0.01" max="{{ $affiliate->balance }}" class="form-control input-150" style="width:140px;" value="{{ $affiliate->balance }}">
                <button class="btn btn-success btn-sm" type="submit">{{ __('admin.affiliates.process_payout') }}</button>
            </form>
        </td>
        <td width="18%" class="fieldlabel">{{ __('admin.affiliates.credit_amount') }}</td>
        <td width="32%" class="fieldarea">
            <form method="POST" action="{{ route('admin.affiliates.credit', $affiliate) }}" style="display:flex;gap:6px;align-items:center;">
                @csrf
                <input type="number" name="amount" step="0.01" max="{{ $affiliate->balance }}" class="form-control input-150" style="width:140px;" value="{{ $affiliate->balance }}">
                <button class="btn btn-primary btn-sm" type="submit">{{ __('admin.affiliates.add_to_balance') }}</button>
            </form>
        </td>
    </tr>
</table>
@endif

<h2 style="font-size:15px;font-weight:600;color:#1a4d80;margin:18px 0 8px;">{{ __('admin.affiliates.referral_history') }}</h2>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.date') }}</th>
                <th>{{ __('common.table.description') }}</th>
                <th>{{ __('common.table.amount') }}</th>
                <th>{{ __('admin.affiliates.gateway') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($transactions as $tx)
            <tr>
                <td>{{ $tx->date?->timezone(display_tz())->format(datetime_fmt()) ?? 'N/A' }}</td>
                <td>{{ $tx->description }}</td>
                <td>{{ money_fmt(($tx->amount_in ?? 0) > 0 ? $tx->amount_in : $tx->amount_out) }}</td>
                <td>{{ $tx->gateway ? payment_method_label((string) $tx->gateway) : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="text-align:center;color:#888;padding:20px;">{{ __('admin.affiliates.no_referrals') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div style="margin-top:10px;">{{ $transactions->links() }}</div>
@endsection

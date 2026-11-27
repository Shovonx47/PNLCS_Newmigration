@extends('admin.layouts.app')
@section('title', __('admin.affiliates.title'))
@section('content')
<h1>{{ __('admin.affiliates.title') }}</h1>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px;">
    <div class="clientssummarybox" style="margin:0;">
        <div class="title">{{ __('admin.affiliates.total_affiliates') }}</div>
        <div style="padding:10px;text-align:center;font-size:20px;font-weight:700;color:#1a4d80;">{{ $totalAffiliates }}</div>
    </div>
    <div class="clientssummarybox" style="margin:0;">
        <div class="title">{{ __('admin.affiliates.total_earnings') }}</div>
        <div style="padding:10px;text-align:center;font-size:20px;font-weight:700;color:#3c763d;">{{ money_fmt($totalEarnings) }}</div>
    </div>
    <div class="clientssummarybox" style="margin:0;">
        <div class="title">{{ __('admin.affiliates.total_withdrawn') }}</div>
        <div style="padding:10px;text-align:center;font-size:20px;font-weight:700;color:#333;">{{ money_fmt($totalWithdrawn) }}</div>
    </div>
    <div class="clientssummarybox" style="margin:0;">
        <div class="title">{{ __('admin.affiliates.total_visitors') }}</div>
        <div style="padding:10px;text-align:center;font-size:20px;font-weight:700;color:#1a4d80;">{{ number_format($totalVisitors) }}</div>
    </div>
</div>

<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="active">
        <a href="#tab-aff-search" onclick="var p=document.getElementById('tab-aff-search');p.style.display=p.style.display==='none'?'block':'none';return false;">
            {{ __('common.actions.search') }}/{{ __('common.actions.filter') }}
        </a>
    </li>
</ul>
<div class="tab-content admin-tabs">
    <div class="tab-pane active" id="tab-aff-search">
        <form method="GET" action="{{ route('admin.affiliates.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.actions.search') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="search" class="form-control input-300" placeholder="{{ __('common.placeholder.search') }}" value="{{ request('search') }}">
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button class="btn btn-default" type="submit">{{ __('common.actions.search') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="context-btn-container whmcs-record-bar" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin:10px 0;">
    <div style="font-size:12px;color:#555;">
        <strong>{{ __('admin.affiliates.all_affiliates') }}</strong> ({{ $affiliates->total() }})
    </div>
    @if(!($availableClients ?? collect())->isEmpty())
    <div>
        <button type="button" class="btn btn-primary btn-sm" onclick="var f=document.getElementById('add-affiliate-form');f.style.display=f.style.display==='none'?'block':'none';">
            {{ __('admin.affiliates.add_affiliate') }}
        </button>
    </div>
    @endif
</div>

@if(!($availableClients ?? collect())->isEmpty())
<div id="add-affiliate-form" style="display:none;margin-bottom:14px;">
    <form method="POST" action="{{ route('admin.affiliates.store') }}">
        @csrf
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td width="20%" class="fieldlabel">{{ __('admin.affiliates.select_client') }}</td>
                <td class="fieldarea">
                    <select name="client_id" class="form-control select-inline" required>
                        <option value="">{{ __('admin.affiliates.select_client') }}</option>
                        @foreach($availableClients as $client)
                        <option value="{{ $client->id }}">{{ $client->first_name }} {{ $client->last_name }} ({{ $client->email }})</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" style="margin-left:6px;">{{ __('admin.affiliates.add_affiliate') }}</button>
                </td>
            </tr>
        </table>
    </form>
</div>
@endif

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.client') }}</th>
                <th>{{ __('admin.affiliates.visitors') }}</th>
                <th>{{ __('common.table.type') }}</th>
                <th>{{ __('common.table.rate') }}</th>
                <th>{{ __('admin.affiliates.balance') }}</th>
                <th>{{ __('admin.affiliates.withdrawn') }}</th>
                <th>{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($affiliates as $aff)
            <tr>
                <td><a href="{{ route('admin.affiliates.show', $aff) }}" style="font-weight:600;">{{ $aff->client?->first_name }} {{ $aff->client?->last_name }}</a></td>
                <td>{{ number_format($aff->visitors) }}</td>
                <td>{{ ucfirst($aff->pay_type) }}</td>
                <td>{{ $aff->pay_type === 'percentage' ? $aff->pay_amount . '%' : '$' . number_format($aff->pay_amount, 2) }}@if($aff->recurring_pay_amount !== null) / {{ $aff->pay_type === 'percentage' ? $aff->recurring_pay_amount . '%' : '$' . number_format($aff->recurring_pay_amount, 2) }}@endif</td>
                <td><strong>{{ money_fmt($aff->balance) }}</strong></td>
                <td>{{ money_fmt($aff->withdrawn) }}</td>
                <td><a href="{{ route('admin.affiliates.show', $aff) }}" class="btn btn-xs btn-default">{{ __('common.actions.view') }}</a></td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#888;padding:20px;">{{ __('admin.affiliates.no_affiliates') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">{{ $affiliates->links() }}</div>
@endsection

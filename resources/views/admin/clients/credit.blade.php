@extends('admin.layouts.app')
@section('title', __('admin.clients.credit_title').' - '.$client->full_name)
@section('content')
@include('admin.clients.partials.client-tabs', ['client' => $client, 'activeTab' => 'credit'])

<div class="tab-content client-tabs">
    <div class="tab-pane active">
        <div class="clientsummaryactions">
            {{ __('admin.clients.credit_balance') }}: <strong style="color:#3c763d;">{{ money_fmt($client->credit) }}</strong>
            &nbsp;|&nbsp;
            <a href="{{ route('admin.clients.show', $client) }}">&larr; {{ $client->full_name }}</a>
        </div>
        <div class="client-summary-name">
            #{{ $client->id }} - {{ $client->full_name }} &mdash; {{ __('admin.clients.credit_title') }}
        </div>
        <div style="clear:both;margin-bottom:10px;"></div>

        @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom:14px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.clients.credit.store', $client) }}">
            @csrf
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel"><label for="cr-type" style="margin:0;">{{ __('common.table.type') }}</label></td>
                    <td class="fieldarea" width="35%">
                        <select id="cr-type" name="type" class="form-control select-inline">
                            <option value="add" @selected(old('type') !== 'remove')>{{ __('admin.clients.credit_add') }}</option>
                            <option value="remove" @selected(old('type') === 'remove')>{{ __('admin.clients.credit_remove') }}</option>
                        </select>
                    </td>
                    <td width="15%" class="fieldlabel"><label for="cr-amount" style="margin:0;">{{ __('common.table.amount') }}</label></td>
                    <td class="fieldarea" width="35%">
                        <input type="number" id="cr-amount" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required class="form-control input-150">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel"><label for="cr-desc" style="margin:0;">{{ __('common.table.description') }}</label></td>
                    <td class="fieldarea" colspan="3">
                        <input type="text" id="cr-desc" name="description" maxlength="255" value="{{ old('description') }}" required class="form-control">
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary">{{ __('common.actions.save') }}</button>
                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-default">{{ __('common.actions.back') }}</a>
            </div>
        </form>

        <h2 style="font-size:15px;font-weight:600;color:#1a4d80;margin:18px 0 8px;">{{ __('admin.clients.credit_history') }}</h2>

        <div class="tablebg">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th>{{ __('common.table.date') }}</th>
                        <th>{{ __('common.table.description') }}</th>
                        <th style="text-align:right;">{{ __('common.table.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($history as $row)
                    <tr>
                        <td style="white-space:nowrap;">{{ $row->date?->format(date_fmt()) }}</td>
                        <td>{{ $row->description }}</td>
                        <td style="text-align:right;font-weight:600;color:{{ (float) $row->amount < 0 ? '#a94442' : '#3c763d' }};">
                            {{ (float) $row->amount < 0 ? '−' : '+' }}{{ money_fmt(abs((float) $row->amount)) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center;color:#888;padding:18px;">{{ __('admin.clients.credit_none') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(!$history->isEmpty())
        <div style="margin-top:10px;">{{ $history->links() }}</div>
        @endif
    </div>
</div>
@endsection

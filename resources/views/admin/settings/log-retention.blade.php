@extends('admin.layouts.app')
@section('title', __('admin.log_retention.title'))
@section('content')
<h1>{{ __('admin.log_retention.title') }}</h1>
<div class="infobox">{{ __('admin.log_retention.intro') }}</div>

<form method="POST" action="{{ route('admin.settings.log-retention.update') }}" id="retention-form">
    @csrf @method('PUT')
</form>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.log_retention.log') }}</th>
                <th>{{ __('admin.log_retention.rows') }}</th>
                <th>{{ __('admin.log_retention.oldest') }}</th>
                <th>{{ __('admin.log_retention.keep_days') }}</th>
                <th style="text-align:right;width:120px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $r)
            <tr>
                <td style="font-weight:600;">{{ __('admin.log_retention.table_'.$r['table']) }}</td>
                <td>{{ number_format($r['rows']) }}</td>
                <td>{{ $r['oldest'] ? \Illuminate\Support\Carbon::parse($r['oldest'])->timezone(display_tz())->format(date_fmt()) : '—' }}</td>
                <td>
                    <input type="number" name="days[{{ $r['table'] }}]" form="retention-form" value="{{ $r['days'] }}" min="0" max="3650" class="form-control input-sm" style="width:100px;display:inline-block;">
                    <small style="color:#777;margin-left:4px;">{{ __('admin.log_retention.default', ['days' => $r['default']]) }}</small>
                </td>
                <td style="text-align:right;">
                    <form method="POST" action="{{ route('admin.settings.log-retention.prune') }}" style="margin:0;display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.log_retention.confirm_prune')))">
                        @csrf
                        <input type="hidden" name="table" value="{{ $r['table'] }}">
                        <button type="submit" class="btn btn-default btn-xs" @disabled($r['days'] === 0)>{{ __('admin.log_retention.prune_now') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="btn-container">
    <button type="submit" form="retention-form" class="btn btn-primary">{{ __('common.actions.save') }}</button>
</div>
@endsection

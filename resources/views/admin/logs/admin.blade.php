@extends('admin.layouts.app')
@section('title', __('admin.logs.admin_log'))
@section('content')

@php
    $filterOpen = request()->hasAny(['username', 'ip', 'date']) && collect(request()->only(['username', 'ip', 'date']))->contains(fn ($v) => trim((string) $v) !== '');
@endphp

<h1 style="margin:0 0 12px 0;">{{ __('admin.logs.admin_log') }}</h1>

<ul class="admin-tabs" role="tablist">
    <li class="active">
        <a href="#admin-log-search" class="active" onclick="var el=document.getElementById('admin-log-search-pane');if(el){el.style.display=el.style.display==='none'?'block':'none';}return false;">
            {{ __('admin.invoices.search_filter') }}
        </a>
    </li>
</ul>

<div class="tab-content admin-tabs" id="admin-log-search-pane" style="display:{{ $filterOpen ? 'block' : 'none' }};margin-bottom:10px;">
    <div class="tab-pane active">
        <form method="GET" action="{{ route('admin.logs.admin') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('admin.logs.admin_username') }}</td>
                    <td width="35%" class="fieldarea"><input type="text" name="username" value="{{ request('username') }}" class="form-control input-200"></td>
                    <td width="15%" class="fieldlabel">{{ __('admin.logs.ip_address') }}</td>
                    <td width="35%" class="fieldarea"><input type="text" name="ip" value="{{ request('ip') }}" class="form-control input-200"></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.date') }}</td>
                    <td class="fieldarea" colspan="3"><input type="date" name="date" value="{{ request('date') }}" class="form-control input-150"></td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.search') }}</button>
                <a href="{{ route('admin.logs.admin') }}" class="btn btn-default btn-sm">{{ __('common.actions.reset') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="whmcs-record-bar" style="margin:8px 0 6px;">
    <div style="font-size:12px;color:#333;">
        {{ number_format($logs->total()) }} {{ __('admin.invoices.records_found') }}@if($logs->total() > 0), {{ __('admin.invoices.showing_range', ['from' => $logs->firstItem(), 'to' => $logs->lastItem()]) }}@endif
    </div>
    <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#333;">
        <span>{{ __('admin.invoices.jump_to_page') }}</span>
        <select onchange="if(this.value) window.location.href=this.value;" aria-label="{{ __('admin.invoices.jump_to_page') }}" style="padding:2px 6px;border:1px solid #ccc;border-radius:3px;font-size:12px;background:#fff;">
            @for($pg = 1; $pg <= max(1, $logs->lastPage()); $pg++)
                <option value="{{ $logs->url($pg) }}" @selected($pg === $logs->currentPage())>{{ $pg }}</option>
            @endfor
        </select>
    </div>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th width="160">{{ __('admin.logs.login_time') }}</th>
                <th width="160">{{ __('admin.logs.logout_time') }}</th>
                <th>{{ __('admin.logs.admin_username') }}</th>
                <th width="140">{{ __('admin.logs.ip_address') }}</th>
                <th>{{ __('admin.logs.session_id') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td style="text-align:center;white-space:nowrap;">{{ $log->login_time?->timezone(display_tz())->format(datetime_fmt()) ?? '-' }}</td>
                    <td style="text-align:center;white-space:nowrap;">{{ $log->logout_time?->timezone(display_tz())->format(datetime_fmt()) ?? '-' }}</td>
                    <td style="text-align:center;font-weight:600;">{{ $log->admin_username }}</td>
                    <td style="text-align:center;font-family:monospace;">{{ $log->ip_address ?: '-' }}</td>
                    <td style="text-align:center;font-family:monospace;font-size:11px;color:#666;">{{ $log->session_id ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;color:#888;">{{ __('admin.logs.no_admin_logs') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.partials.whmcs-pagination', ['paginator' => $logs])

@endsection

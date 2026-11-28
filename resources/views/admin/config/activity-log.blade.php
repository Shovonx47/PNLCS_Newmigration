@extends('admin.layouts.app')
@section('title', __('admin.logs.system_activity_log'))
@section('content')

@php
    $filterOpen = $hasActiveFilters ?? false;
@endphp

<h1 style="margin:0 0 12px 0;">{{ __('admin.logs.system_activity_log') }}</h1>

{{-- ── Single Collapsible Search/Filter Tab (Exact WHMCS Blend Structure) ── --}}
<ul class="admin-tabs" role="tablist">
    <li class="active">
        <a href="#activity-search-tab"
           class="active"
           onclick="var el=document.getElementById('activity-search-pane');if(el){el.style.display=el.style.display==='none'?'block':'none';}return false;">
            {{ __('admin.invoices.search_filter') }}
        </a>
    </li>
</ul>

<div class="tab-content admin-tabs" id="activity-search-pane" style="display:{{ $filterOpen ? 'block' : 'none' }};margin-bottom:10px;">
    <div class="tab-pane active">
        <form method="GET" action="{{ route('admin.config.activity-log') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.table.date') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="date" name="date" value="{{ request('date') }}" class="form-control input-150">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('admin.logs.username') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="user" class="form-control select-inline">
                            <option value="">{{ __('admin.logs.any_user') }}</option>
                            <option value="System/Automated" @selected(request('user') === 'System/Automated')>{{ __('admin.logs.system_automated') }}</option>
                            @foreach($users ?? [] as $usr)
                                @continue(strtolower((string) $usr) === 'system' || strtolower((string) $usr) === 'system/automated')
                                <option value="{{ $usr }}" @selected(request('user') === $usr)>{{ $usr }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.logs.log_entry') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control input-250">
                    </td>
                    <td class="fieldlabel">{{ __('admin.logs.ip_address') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="ip" value="{{ request('ip') }}" class="form-control input-200">
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.search') }}</button>
                <a href="{{ route('admin.config.activity-log') }}" class="btn btn-default btn-sm">{{ __('common.actions.reset') }}</a>
            </div>
        </form>
    </div>
</div>

{{-- ── Records Count & Jump to Page Bar ── --}}
<div class="whmcs-record-bar" style="margin:8px 0 6px;">
    <div style="font-size:12px;color:#333;">
        {{ number_format($logs->total()) }} {{ __('admin.invoices.records_found') }}@if($logs->total() > 0), {{ __('admin.invoices.showing_range', ['from' => $logs->firstItem(), 'to' => $logs->lastItem()]) }}@endif
    </div>
    <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#333;">
        <span>{{ __('admin.invoices.jump_to_page') }}</span>
        <select onchange="if(this.value) window.location.href=this.value;"
                aria-label="{{ __('admin.invoices.jump_to_page') }}"
                style="padding:2px 6px;border:1px solid #ccc;border-radius:3px;font-size:12px;background:#fff;">
            @for($pg = 1; $pg <= max(1, $logs->lastPage()); $pg++)
                <option value="{{ $logs->url($pg) }}" @selected($pg === $logs->currentPage())>{{ $pg }}</option>
            @endfor
        </select>
    </div>
</div>

{{-- ── 5-Column WHMCS Activity Log Table ── --}}
<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th width="135">{{ __('common.table.date') }}</th>
                <th>{{ __('admin.logs.log_entry') }}</th>
                <th width="160">{{ __('admin.logs.client') }}</th>
                <th width="160">{{ __('admin.logs.user_label') }}</th>
                <th width="115">{{ __('admin.logs.ip_address') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                @php
                    $logTimestamp = ($log->date ?? $log->created_at);
                    $formattedDate = $logTimestamp ? $logTimestamp->timezone(display_tz())->format(datetime_fmt()) : '-';
                    $rawUser = trim((string) ($log->user ?? ''));
                    $displayUser = ($rawUser === '' || strtolower($rawUser) === 'system')
                        ? __('admin.logs.system_automated')
                        : $rawUser;
                @endphp
                <tr>
                    <td style="text-align:center;white-space:nowrap;">{{ $formattedDate }}</td>
                    <td style="text-align:left;overflow-wrap:anywhere;word-break:break-word;">{{ $log->description ?? '-' }}</td>
                    <td style="text-align:center;">
                        @if($log->client)
                            <a href="{{ route('admin.clients.show', $log->client) }}">{{ $log->client->full_name }}</a>
                        @elseif($log->client_id)
                            <a href="{{ route('admin.clients.show', $log->client_id) }}">#{{ $log->client_id }}</a>
                        @else
                            <span style="font-style:italic;color:#666;">{{ __('common.none') }}</span>
                        @endif
                    </td>
                    <td style="text-align:center;line-height:1.3;">
                        <div>{{ $displayUser }}</div>
                        <div style="font-size:11px;color:#888;">{{ '-' }}</div>
                    </td>
                    <td style="text-align:center;white-space:nowrap;">{{ $log->ip_address ?: '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;color:#888;">{{ __('admin.activity_log.no_entries') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.partials.whmcs-pagination', ['paginator' => $logs])

@endsection

@extends('admin.layouts.app')
@section('title', __('admin.logs.ticket_mail_import_log'))
@section('content')

@php
    $filterOpen = request()->hasAny(['search', 'status', 'date']) && collect(request()->only(['search', 'status', 'date']))->contains(fn ($v) => trim((string) $v) !== '');
@endphp

<h1 style="margin:0 0 12px 0;">{{ __('admin.logs.ticket_mail_import_log') }}</h1>

<ul class="admin-tabs" role="tablist">
    <li class="active">
        <a href="#ticket-mail-search" class="active" onclick="var el=document.getElementById('ticket-mail-search-pane');if(el){el.style.display=el.style.display==='none'?'block':'none';}return false;">
            {{ __('admin.invoices.search_filter') }}
        </a>
    </li>
</ul>

<div class="tab-content admin-tabs" id="ticket-mail-search-pane" style="display:{{ $filterOpen ? 'block' : 'none' }};margin-bottom:10px;">
    <div class="tab-pane active">
        <form method="GET" action="{{ route('admin.logs.ticket-mail') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.actions.search') }}</td>
                    <td width="35%" class="fieldarea"><input type="text" name="search" value="{{ request('search') }}" class="form-control input-250"></td>
                    <td width="15%" class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td width="35%" class="fieldarea"><input type="text" name="status" value="{{ request('status') }}" class="form-control input-150"></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.date') }}</td>
                    <td class="fieldarea" colspan="3"><input type="date" name="date" value="{{ request('date') }}" class="form-control input-150"></td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.search') }}</button>
                <a href="{{ route('admin.logs.ticket-mail') }}" class="btn btn-default btn-sm">{{ __('common.actions.reset') }}</a>
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
                <th width="140">{{ __('common.table.date') }}</th>
                <th>{{ __('common.table.name') }}</th>
                <th>{{ __('common.table.email') }}</th>
                <th>{{ __('common.table.subject') }}</th>
                <th width="120">{{ __('common.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td style="text-align:center;white-space:nowrap;">{{ ($log->date ?? $log->created_at)?->timezone(display_tz())->format(datetime_fmt()) ?? '-' }}</td>
                    <td style="text-align:center;">{{ $log->name ?: '-' }}</td>
                    <td style="text-align:center;">{{ $log->email ?: ($log->to ?: '-') }}</td>
                    <td style="text-align:left;">{{ $log->subject ?: '-' }}</td>
                    <td style="text-align:center;">{{ $log->status ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;color:#888;">{{ __('admin.logs.no_ticket_mail_logs') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('admin.partials.whmcs-pagination', ['paginator' => $logs])

@endsection

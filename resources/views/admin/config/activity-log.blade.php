@extends('admin.layouts.app')
@section('title', __('admin.activity_log.title'))
@section('content')

<h1>{{ __('admin.activity_log.title') }}</h1>

<ul class="admin-tabs">
    <li class="active"><a href="{{ route('admin.config.activity-log') }}">{{ __('admin.activity_log.title') }}</a></li>
    <li><a href="{{ route('admin.logs.index') }}">{{ __('admin.nav.system_logs') }}</a></li>
    <li><a href="{{ route('admin.logs.module') }}">{{ __('admin.sidebar.module_logs') }}</a></li>
    <li><a href="{{ route('admin.logs.email') }}">{{ __('admin.sidebar.email_logs') }}</a></li>
    <li><a href="{{ route('admin.logs.gateway') }}">{{ __('admin.sidebar.gateway_logs') }}</a></li>
</ul>

<div class="tab-content admin-tabs">
    <div class="tab-pane active">
        @if(($logs ?? collect())->isEmpty())
            <div class="contentbox">{{ __('admin.activity_log.no_entries') }}</div>
        @else
            <div class="tablebg">
                <table class="datatable">
                    <thead>
                        <tr>
                            <th>{{ __('admin.activity_log.date_time') }}</th>
                            <th class="text-left">{{ __('common.table.description') }}</th>
                            <th>{{ __('admin.activity_log.admin') }}</th>
                            <th>{{ __('common.table.ip_address') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td style="white-space:nowrap;text-align:center;">{{ $log->created_at->timezone(display_tz())->format(datetime_fmt()) }}</td>
                            <td>{{ $log->description ?? $log->action }}</td>
                            <td style="text-align:center;">{{ $log->user ?? $log->admin ?? '-' }}</td>
                            <td style="font-family:monospace;text-align:center;">{{ $log->ip_address ?? $log->ip ?? '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if(method_exists($logs, 'links'))
                <div style="margin-top:10px;">{{ $logs->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection

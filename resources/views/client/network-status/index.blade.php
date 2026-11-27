@extends('client.layouts.twentyone')
@section('title', __('client.network_status.title'))

@section('page_subheading', __('client.network_status.subtitle'))

@section('content')

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.network_status.active_issues') }}</h3>

        @if($activeIssues->isEmpty())
            <div class="alert alert-success text-center mb-0" role="alert">
                <i class="fas fa-check-circle fa-fw"></i>
                {{ __('client.network_status.all_operational') }} — {{ __('client.network_status.no_issues') }}
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-list">
                    <thead>
                        <tr>
                            <th>{{ __('common.form.title') }}</th>
                            <th>{{ __('common.table.type') }}</th>
                            <th>{{ __('common.table.priority') }}</th>
                            <th>{{ __('common.table.status') }}</th>
                            <th>{{ __('client.network_status.since') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activeIssues as $issue)
                        <tr>
                            <td>
                                <strong>{{ $issue->title }}</strong>
                                @if($issue->description)
                                    <div class="small text-muted" style="white-space:pre-wrap">{{ $issue->description }}</div>
                                @endif
                                @if($issue->affected_server)
                                    <div class="small text-muted">{{ __('client.network_status.affecting') }}: {{ $issue->affected_server }}</div>
                                @endif
                            </td>
                            <td>{{ __('client.network_status.type_' . strtolower($issue->type ?: 'general')) }}</td>
                            <td>
                                <span class="label status status-{{ in_array(strtolower((string) $issue->priority), ['critical', 'high'], true) ? 'terminated' : 'pending' }}">
                                    {{ __('client.network_status.priority_' . strtolower($issue->priority ?: 'medium')) }}
                                </span>
                            </td>
                            <td>
                                <span class="label status status-pending">{{ __('client.network_status.status_' . strtolower($issue->status ?: 'open')) }}</span>
                            </td>
                            <td class="small text-muted">{{ $issue->start_date?->timezone(display_tz())->format(datetime_fmt()) ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@if($resolvedIssues->isNotEmpty())
<div class="card">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.network_status.recently_resolved') }}</h3>
        <div class="table-responsive">
            <table class="table table-list">
                <thead>
                    <tr>
                        <th>{{ __('common.form.title') }}</th>
                        <th>{{ __('common.table.type') }}</th>
                        <th>{{ __('client.network_status.resolved_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resolvedIssues as $issue)
                    <tr>
                        <td>{{ $issue->title }}</td>
                        <td>{{ __('client.network_status.type_' . strtolower($issue->type ?: 'general')) }}</td>
                        <td class="small text-muted">{{ $issue->end_date?->timezone(display_tz())->format(datetime_fmt()) ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection

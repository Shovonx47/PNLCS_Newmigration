@extends("admin.layouts.app")
@section("title", __("admin.automation_status"))
@section("content")

@php
    $tasks = app(\App\Services\ScheduleStatusService::class)->tasks();
    $counts = app(\App\Services\ScheduleStatusService::class)->summary();
    $statusMap = [
        'ok'      => 'active',
        'overdue' => 'pending',
        'failed'  => 'terminated',
        'pending' => 'cancelled',
    ];
@endphp

<h1>{{ __('admin.automation.title') }}</h1>

<div class="context-btn-container">
    <div><strong>{{ __('admin.automation.cron_job_status') }}</strong></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach(['failed','overdue','pending','ok'] as $state)
            @if($counts[$state] > 0 || $state === 'ok')
                <span class="whmcs-status {{ $statusMap[$state] ?? 'cancelled' }}">
                    {{ __('admin.automation.state_' . $state) }}: {{ $counts[$state] }}
                </span>
            @endif
        @endforeach
    </div>
</div>

<div class="tablebg">
    <table class="datatable">
        <thead>
            <tr>
                <th class="text-left">{{ __('admin.automation.task') }}</th>
                <th>{{ __('admin.automation.frequency') }}</th>
                <th>{{ __('admin.automation.last_run') }}</th>
                <th>{{ __('admin.automation.next_due') }}</th>
                <th>{{ __('common.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($tasks as $t)
            <tr>
                <td>
                    <strong>{{ $t['label'] }}</strong>
                    <br><code style="font-size:11px;color:#777;">{{ $t['command'] }}</code>
                </td>
                <td style="text-align:center;white-space:nowrap;">{{ $t['frequency'] }}</td>
                <td style="text-align:center;white-space:nowrap;">
                    @if($t['last_run_at'])
                        {{ $t['last_run_at']->diffForHumans() }}
                        <br><small class="textgrey">
                            {{ $t['last_run_at']->setTimezone(display_tz())->format('d.m.Y H:i') }}
                            @if($t['runtime_ms'] !== null) &middot; {{ number_format($t['runtime_ms'] / 1000, 1) }}{{ 's' }} @endif
                        </small>
                    @else
                        <span class="textgrey">{{ __('admin.automation.never_yet') }}</span>
                    @endif
                    @if($t['failures'] > 0)
                        <br><small class="textred">{{ $t['failures'] }}&times; {{ __('admin.automation.state_failed') }}</small>
                    @endif
                </td>
                <td style="text-align:center;white-space:nowrap;" class="textgrey">
                    {{ $t['next_due'] ? $t['next_due']->diffForHumans() : '—' }}
                </td>
                <td style="text-align:center;white-space:nowrap;">
                    <span class="whmcs-status {{ $statusMap[$t['state']] ?? 'cancelled' }}">
                        {{ __('admin.automation.state_' . $t['state']) }}
                    </span>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="infobox" style="margin-top:16px;">
    <strong>{{ __('admin.automation.cron_configuration') }}</strong>
    @if(app(\App\Services\ScheduleStatusService::class)->schedulerRunsInContainer())
        <p style="margin:6px 0 0;">{{ __('admin.automation.container_scheduler') }}</p>
    @else
        <p style="margin:6px 0;">{{ __('admin.automation.cron_instruction') }}</p>
        <div style="background:#fff;border:1px solid #d4c484;padding:8px 12px;border-radius:3px;font-family:monospace;font-size:12px;">
            <code>* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
        </div>
        <p style="font-size:12px;color:#555;margin:6px 0 0;">{{ __('admin.automation.cron_explanation') }}</p>
    @endif
</div>

@endsection

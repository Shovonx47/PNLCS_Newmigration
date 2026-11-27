@extends('admin.layouts.app')
@section('title', $project->title)
@section('content')
@php
    $total = $project->tasks->count();
    $done = $project->tasks->where('completed', true)->count();
    $pct = $total > 0 ? round($done / $total * 100) : 0;
    $badgeClass = match($project->status) {
        'completed'   => 'badge-active',
        'cancelled'   => 'badge-cancelled',
        'in_progress' => 'badge-open',
        default       => 'badge-pending',
    };
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
    <h1 style="margin:0;">
        {{ $project->title }}
        <span class="whmcs-status {{ $badgeClass }}" style="font-size:12px;vertical-align:middle;margin-left:6px;">{{ status_label($project->status) }}</span>
    </h1>
    <div style="display:flex;gap:6px;align-items:center;">
        <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-default btn-sm">{{ __('admin.projects.edit_project') }}</a>
        <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.projects.confirm_delete_project')))">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">{{ __('admin.projects.delete_project') }}</button>
        </form>
        <a href="{{ route('admin.projects.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.projects.back') }}</a>
    </div>
</div>

<h2>{{ __('admin.projects.project_info') }}</h2>
<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td width="20%" class="fieldlabel">{{ __('admin.projects.client') }}</td>
        <td width="30%" class="fieldarea"><strong>{{ $project->client?->full_name ?? 'N/A' }}</strong></td>
        <td width="20%" class="fieldlabel">{{ __('admin.projects.status') }}</td>
        <td width="30%" class="fieldarea"><span class="whmcs-status {{ $badgeClass }}">{{ status_label($project->status) }}</span></td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.projects.start') }}</td>
        <td class="fieldarea">{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format(date_fmt()) : '—' }}</td>
        <td class="fieldlabel">{{ __('admin.projects.due_date_label') }}</td>
        <td class="fieldarea">{{ $project->due_date ? \Carbon\Carbon::parse($project->due_date)->format(date_fmt()) : '—' }}</td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.projects.tasks_done') }}</td>
        <td class="fieldarea">
            <strong>{{ $done . '/' . $total }}</strong>
            <span class="text-muted">({{ __('admin.projects.progress_pct', ['pct' => $pct]) }}, {{ __('admin.projects.created_on', ['date' => $project->created_at->format(date_fmt())]) }})</span>
            <div style="background:#e9e9e9;border-radius:3px;height:10px;margin-top:4px;max-width:260px;">
                <div style="height:10px;border-radius:3px;background:#337ab7;width:{{ $pct }}%;"></div>
            </div>
        </td>
        <td class="fieldlabel">{{ __('admin.projects.messages') }}</td>
        <td class="fieldarea">{{ $project->messages->count() }}</td>
    </tr>
    @if($project->description)
    <tr>
        <td class="fieldlabel">{{ __('admin.projects.description') }}</td>
        <td class="fieldarea" colspan="3" style="white-space:pre-wrap;">{{ $project->description }}</td>
    </tr>
    @endif
</table>

<h2 style="margin-top:18px;">{{ __('admin.projects.tasks') }}</h2>
<div class="tablebg" style="margin-bottom:10px;">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th style="width:40px;">{{ __('common.table.status') }}</th>
                <th>{{ __('admin.projects.tasks') }}</th>
                <th>{{ __('common.table.due_date') }}</th>
                <th style="width:70px;text-align:right;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($project->tasks->sortBy('sort_order') as $task)
            <tr>
                <td class="text-center">
                    <form method="POST" action="{{ route('admin.projects.tasks.update', [$project, $task]) }}" style="display:inline;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="completed" value="{{ $task->completed ? '0' : '1' }}">
                        <button type="submit" class="btn btn-xs {{ $task->completed ? 'btn-success' : 'btn-default' }}">
                            @if($task->completed){{ '✓' }}@else{{ '○' }}@endif
                        </button>
                    </form>
                </td>
                <td style="{{ $task->completed ? 'text-decoration:line-through;color:#999;' : '' }}">
                    {{ $task->task }}
                    @if($task->notes)<div class="text-muted" style="font-size:12px;">{{ $task->notes }}</div>@endif
                </td>
                <td>
                    @if($task->due_date)
                        {{ __('admin.projects.due_on', ['date' => \Carbon\Carbon::parse($task->due_date)->format(date_fmt())]) }}
                    @else
                        {{ '—' }}
                    @endif
                </td>
                <td style="text-align:right;">
                    <form method="POST" action="{{ route('admin.projects.tasks.destroy', [$project, $task]) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.projects.confirm_delete_task')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">&times;</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center">{{ __('admin.projects.no_tasks') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<form method="POST" action="{{ route('admin.projects.tasks.store', $project) }}" style="margin-bottom:18px;">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="20%" class="fieldlabel">{{ __('admin.projects.add_task_label') }}</td>
            <td class="fieldarea" style="display:flex;gap:8px;align-items:center;">
                <input type="text" name="task" placeholder="{{ __('admin.projects.task_placeholder') }}" required class="form-control input-400">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.add') }}</button>
            </td>
        </tr>
    </table>
</form>

<h2>{{ __('admin.projects.timeline_messages') }}</h2>
<form method="POST" action="{{ route('admin.projects.messages.store', $project) }}" style="margin-bottom:15px;">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="20%" class="fieldlabel">{{ __('admin.projects.post_message') }}</td>
            <td class="fieldarea">
                <textarea name="message" rows="3" placeholder="{{ __('admin.projects.post_placeholder') }}" required class="form-control"></textarea>
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.projects.post_message') }}</button>
    </div>
</form>

<div id="ticketreplies">
    @forelse($project->messages->sortByDesc('created_at') as $msg)
    <div class="reply staff">
        <div class="leftcol">
            <div class="submitter">
                <div class="name">{{ $msg->admin ?? 'Admin' }}</div>
                <div class="title">{{ 'Staff' }}</div>
            </div>
        </div>
        <div class="rightcol">
            <div class="postedon">{{ $msg->created_at->diffForHumans() }}</div>
            <div class="msgwrap">
                <div class="message" style="white-space:pre-wrap;">{{ $msg->message }}</div>
            </div>
        </div>
        <div class="clear"></div>
    </div>
    @empty
    <div class="infobox">{{ __('admin.projects.no_messages') }}</div>
    @endforelse
</div>
@endsection

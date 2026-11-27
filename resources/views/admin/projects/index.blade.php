@extends('admin.layouts.app')
@section('title', __('admin.projects.title'))
@section('content')
<h1>{{ __('admin.projects.title') }}</h1>

<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="active"><a href="#tabProjectFilter" role="tab" data-toggle="tab">{{ 'Search/Filter' }}</a></li>
</ul>
<div class="tab-content admin-tabs" style="margin-bottom:15px;">
    <div class="tab-pane active" id="tabProjectFilter">
        <form method="GET" action="{{ route('admin.projects.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.actions.search') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.projects.search_placeholder') }}" class="form-control input-300">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            @foreach(['' => __('common.form.all'), 'pending' => __('common.status.pending'), 'in_progress' => __('common.status.in_progress'), 'completed' => __('common.status.completed'), 'cancelled' => __('common.status.cancelled')] as $val => $label)
                            <option value="{{ $val }}" @selected(request('status', '') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default"><i class="fas fa-search"></i> {{ __('common.actions.filter') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="context-btn-container" style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
    <div style="display:flex;gap:4px;flex-wrap:wrap;">
        @foreach(['' => __('common.form.all'), 'pending' => __('common.status.pending'), 'in_progress' => __('common.status.in_progress'), 'completed' => __('common.status.completed'), 'cancelled' => __('common.status.cancelled')] as $val => $label)
        <a href="{{ route('admin.projects.index', ['status' => $val, 'search' => request('search')]) }}"
           class="btn btn-xs {{ request('status', '') == $val ? 'btn-primary' : 'btn-default' }}">{{ $label }}</a>
        @endforeach
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.projects.new_project') }}</a>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.projects.title_col') }}</th>
                <th>{{ __('common.table.client') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th>{{ __('admin.projects.progress') }}</th>
                <th>{{ __('common.table.due_date') }}</th>
                <th>{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($projects as $project)
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
            <tr>
                <td>
                    <a href="{{ route('admin.projects.show', $project) }}"><strong>{{ $project->title }}</strong></a>
                    @if($project->description)<div class="text-muted" style="font-size:12px;">{{ Str::limit($project->description, 60) }}</div>@endif
                </td>
                <td>{{ $project->client?->full_name ?? 'N/A' }}</td>
                <td><span class="whmcs-status {{ $badgeClass }}">{{ status_label($project->status) }}</span></td>
                <td style="min-width:120px;">
                    <div style="background:#e9e9e9;border-radius:3px;height:10px;margin-bottom:3px;">
                        <div style="height:10px;border-radius:3px;background:#337ab7;width:{{ $pct }}%;"></div>
                    </div>
                    <small class="text-muted">{{ __('admin.projects.tasks_count', ['done' => $done, 'total' => $total]) }}</small>
                </td>
                <td>{{ $project->due_date ? \Carbon\Carbon::parse($project->due_date)->format(date_fmt()) : '-' }}</td>
                <td>
                    <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-default btn-xs">{{ __('common.actions.view') }}</a>
                    <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</a>
                    <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" onsubmit="return pnConfirm(event, @js(__('admin.projects.confirm_delete')))" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('admin.projects.del') }}</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center">{{ __('admin.projects.no_projects') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">{{ $projects->withQueryString()->links() }}</div>
@endsection

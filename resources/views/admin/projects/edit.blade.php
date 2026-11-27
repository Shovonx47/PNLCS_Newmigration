@extends('admin.layouts.app')
@section('title', __('admin.projects.edit_project'))
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
    <h1 style="margin:0;">{{ __('admin.projects.edit_project') }}</h1>
    <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-default btn-sm">&larr; {{ __('common.actions.back') }}</a>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:15px;">
    <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.projects.update', $project) }}">
    @csrf
    @method('PUT')
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="20%" class="fieldlabel">{{ __('common.form.title') }} <span style="color:#d9534f;">{{ '*' }}</span></td>
            <td class="fieldarea">
                <input type="text" name="title" value="{{ old('title', $project->title) }}" required class="form-control input-400">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('common.form.description') }}</td>
            <td class="fieldarea">
                <textarea name="description" rows="4" class="form-control">{{ old('description', $project->description) }}</textarea>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('common.form.status') }} <span style="color:#d9534f;">{{ '*' }}</span></td>
            <td class="fieldarea">
                <select name="status" required class="form-control select-inline">
                    @foreach(['pending' => __('common.status.pending'), 'in_progress' => __('common.status.in_progress'), 'completed' => __('common.status.completed'), 'cancelled' => __('common.status.cancelled')] as $v => $l)
                    <option value="{{ $v }}" @selected(old('status', $project->status) == $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.projects.start_date') }}</td>
            <td class="fieldarea">
                <input type="date" name="start_date" value="{{ old('start_date', $project->start_date ? \Carbon\Carbon::parse($project->start_date)->toDateString() : '') }}" class="form-control input-200">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.projects.due_date') }}</td>
            <td class="fieldarea">
                <input type="date" name="due_date" value="{{ old('due_date', $project->due_date ? \Carbon\Carbon::parse($project->due_date)->toDateString() : '') }}" class="form-control input-200">
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ __('admin.projects.update_project') }}</button>
        <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
    </div>
</form>
@endsection

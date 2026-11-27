@extends('admin.layouts.app')
@section('title', __('admin.projects.create_project'))
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
    <h1 style="margin:0;">{{ __('admin.projects.create_project') }}</h1>
    <a href="{{ route('admin.projects.index') }}" class="btn btn-default btn-sm">&larr; {{ __('common.actions.back') }}</a>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:15px;">
    <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.projects.store') }}">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="20%" class="fieldlabel">{{ __('admin.projects.client') }} <span style="color:#d9534f;">{{ '*' }}</span></td>
            <td class="fieldarea">
                <select name="client_id" required class="form-control select-inline">
                    <option value="">{{ __('admin.projects.select_client') }}</option>
                    @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('client_id') == $client->id)>{{ $client->full_name }} ({{ $client->email }})</option>
                    @endforeach
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('common.form.title') }} <span style="color:#d9534f;">{{ '*' }}</span></td>
            <td class="fieldarea">
                <input type="text" name="title" value="{{ old('title') }}" required class="form-control input-400">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('common.form.description') }}</td>
            <td class="fieldarea">
                <textarea name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('common.form.status') }} <span style="color:#d9534f;">{{ '*' }}</span></td>
            <td class="fieldarea">
                <select name="status" required class="form-control select-inline">
                    @foreach(['pending' => __('common.status.pending'), 'in_progress' => __('common.status.in_progress'), 'completed' => __('common.status.completed'), 'cancelled' => __('common.status.cancelled')] as $v => $l)
                    <option value="{{ $v }}" @selected(old('status', 'pending') == $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.projects.start_date') }}</td>
            <td class="fieldarea">
                <input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control input-200">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.projects.due_date') }}</td>
            <td class="fieldarea">
                <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-control input-200">
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ __('admin.projects.create_project') }}</button>
        <a href="{{ route('admin.projects.index') }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
    </div>
</form>
@endsection

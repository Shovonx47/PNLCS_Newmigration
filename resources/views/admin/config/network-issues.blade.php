@extends('admin.layouts.app')
@section('title', __('admin.network_issues.title'))
@section('content')

<h1>{{ __('admin.network_issues.title') }}</h1>

<div class="context-btn-container">
    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
        <a href="{{ route('admin.config.network-issues') }}" class="btn {{ empty($currentStatus) ? 'btn-primary' : 'btn-default' }} btn-xs">{{ 'All' }}</a>
        <a href="{{ route('admin.config.network-issues', ['status' => 'open']) }}" class="btn {{ ($currentStatus ?? '') === 'open' ? 'btn-primary' : 'btn-default' }} btn-xs">{{ 'Open' }}</a>
        <a href="{{ route('admin.config.network-issues', ['status' => 'scheduled']) }}" class="btn {{ ($currentStatus ?? '') === 'scheduled' ? 'btn-primary' : 'btn-default' }} btn-xs">{{ 'Scheduled' }}</a>
        <a href="{{ route('admin.config.network-issues', ['status' => 'resolved']) }}" class="btn {{ ($currentStatus ?? '') === 'resolved' ? 'btn-primary' : 'btn-default' }} btn-xs">{{ 'Resolved' }}</a>
    </div>
    <button type="button" onclick="document.getElementById('modal-add-ni').style.display='flex'" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> {{ __('admin.network_issues.report_issue') }}
    </button>
</div>

@if(($networkIssues ?? collect())->isEmpty())
    <div class="contentbox">{{ __('admin.network_issues.no_issues') }}</div>
@else
    <div class="tablebg">
        <table class="datatable">
            <thead>
                <tr>
                    <th class="text-left">{{ __('common.form.title') }}</th>
                    <th>{{ __('common.table.type') }}</th>
                    <th>{{ __('admin.network_issues.reported') }}</th>
                    <th>{{ __('admin.network_issues.resolved') }}</th>
                    <th>{{ __('common.table.status') }}</th>
                    <th>{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($networkIssues as $issue)
                <tr>
                    <td><strong>{{ $issue->title }}</strong></td>
                    <td style="text-align:center;text-transform:capitalize;">{{ $issue->type ?? 'General' }}</td>
                    <td style="text-align:center;">{{ $issue->created_at->timezone(display_tz())->format(datetime_fmt()) }}</td>
                    <td style="text-align:center;">{{ $issue->end_date?->timezone(display_tz())->format(datetime_fmt()) ?? '—' }}</td>
                    <td style="text-align:center;"><span class="whmcs-status {{ $issue->status === 'resolved' ? 'active' : 'pending' }}">{{ $issue->status === 'resolved' ? __('admin.network_issues.resolved') : __('admin.network_issues.active') }}</span></td>
                    <td style="text-align:center;white-space:nowrap;">
                        <button type="button" class="btn btn-default btn-xs" onclick="document.getElementById('modal-edit-ni-{{ $issue->id }}').style.display='flex'">{{ __('common.actions.edit') }}</button>
                        <form method="POST" action="{{ route('admin.config.network-issues.destroy', $issue) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.network_issues.confirm_delete')))">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

@foreach($networkIssues as $issue)
<div id="modal-edit-ni-{{ $issue->id }}" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-edit-ni-{{ $issue->id }}').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:540px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);overflow:hidden;">
        <div style="padding:12px 16px;background:#1a4d80;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;color:#fff;">{{ __('admin.network_issues.edit_issue') }}</h4>
            <button type="button" onclick="document.getElementById('modal-edit-ni-{{ $issue->id }}').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#fff;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.network-issues.update', $issue) }}">
            @csrf @method('PUT')
            <div style="padding:15px;">
                <table class="form" style="margin:0;">
                    <tr><td class="fieldlabel">{{ __('common.form.title') }}</td><td class="fieldarea"><input type="text" name="title" value="{{ $issue->title }}" required class="form-control"></td></tr>
                    <tr><td class="fieldlabel">{{ __('admin.network_issues.type_label') }}</td><td class="fieldarea">
                        <select name="type" class="form-control">
                            @foreach(['general', 'network', 'server', 'datacenter'] as $type)<option value="{{ $type }}" @selected($issue->type === $type)>{{ __('admin.network_issues.type_'.$type) }}</option>@endforeach
                        </select>
                    </td></tr>
                    <tr><td class="fieldlabel">{{ __('common.table.status') }}</td><td class="fieldarea">
                        <select name="status" class="form-control">
                            @foreach(['open', 'scheduled', 'investigating', 'resolved'] as $state)<option value="{{ $state }}" @selected($issue->status === $state)>{{ __('admin.network_issues.status_'.$state) }}</option>@endforeach
                        </select>
                    </td></tr>
                    <tr><td class="fieldlabel label-top">{{ __('common.form.description') }}</td><td class="fieldarea"><textarea name="description" rows="4" required class="form-control">{{ $issue->description }}</textarea></td></tr>
                    <tr><td class="fieldlabel">{{ __('admin.network_issues.affected') }}</td><td class="fieldarea"><input type="text" name="affected_server" value="{{ $issue->affected_server }}" maxlength="255" list="ni-servers" class="form-control"></td></tr>
                    <tr><td class="fieldlabel">{{ __('admin.network_issues.end_date') }}</td><td class="fieldarea"><input type="date" name="end_date" value="{{ $issue->end_date?->format('Y-m-d') }}" class="form-control"></td></tr>
                </table>
            </div>
            <div style="padding:10px 15px;background:#f5f5f5;border-top:1px solid #ddd;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-edit-ni-{{ $issue->id }}').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<div id="modal-add-ni" style="display:{{ request('action') === 'create' ? 'flex' : 'none' }};position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-ni').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:540px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);overflow:hidden;">
        <div style="padding:12px 16px;background:#1a4d80;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;color:#fff;">{{ __('admin.network_issues.report_issue') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-ni').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#fff;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.network-issues.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" style="margin:0;">
                    <tr><td class="fieldlabel">{{ __('common.form.title') }}</td><td class="fieldarea"><input type="text" name="title" required class="form-control"></td></tr>
                    <tr><td class="fieldlabel">{{ __('admin.network_issues.type_label') }}</td><td class="fieldarea"><select name="type" class="form-control"><option value="general">{{ __('admin.network_issues.type_general') }}</option><option value="network">{{ __('admin.network_issues.type_network') }}</option><option value="server">{{ __('admin.network_issues.type_server') }}</option><option value="datacenter">{{ __('admin.network_issues.type_datacenter') }}</option></select></td></tr>
                    <tr><td class="fieldlabel">{{ __('common.table.status') }}</td><td class="fieldarea"><select name="status" class="form-control"><option value="open">{{ __('admin.network_issues.status_open') }}</option><option value="scheduled">{{ __('admin.network_issues.status_scheduled') }}</option><option value="investigating">{{ __('admin.network_issues.status_investigating') }}</option><option value="resolved">{{ __('admin.network_issues.status_resolved') }}</option></select></td></tr>
                    <tr><td class="fieldlabel label-top">{{ __('common.form.description') }}</td><td class="fieldarea"><textarea name="description" rows="4" required class="form-control"></textarea></td></tr>
                    <tr><td class="fieldlabel">{{ __('admin.network_issues.affected') }}</td><td class="fieldarea"><input type="text" name="affected_server" maxlength="255" list="ni-servers" class="form-control"></td></tr>
                </table>
            </div>
            <div style="padding:10px 15px;background:#f5f5f5;border-top:1px solid #ddd;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-add-ni').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.network_issues.report_issue') }}</button>
            </div>
        </form>
    </div>
</div>
<datalist id="ni-servers">@foreach($serverNames ?? [] as $name)<option value="{{ $name }}">@endforeach</datalist>
@endsection

@extends('admin.layouts.app')
@section('title', __('admin.server_groups.title'))
@section('content')

<h1>{{ __('admin.server_groups.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <button type="button" onclick="document.getElementById('modal-add-sg').style.display='flex'" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.server_groups.add_group') }}</button>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:15px;">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.name') }}</th>
                <th>{{ __('admin.server_groups.fill_type') }}</th>
                <th>{{ __('admin.server_groups.servers') }}</th>
                <th style="text-align:right;width:140px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($serverGroups ?? [] as $group)
            <tr>
                <td style="font-weight:600;">{{ $group->name }}</td>
                <td>{{ $group->fill_type === 'round_robin' ? __('admin.server_groups.roundrobin_short') : __('admin.server_groups.sequential_short') }}</td>
                <td>
                    @forelse($group->servers as $srv)
                    <span class="whmcs-status active badge-active" title="{{ $srv->type }}" style="margin-right:4px;">{{ $srv->name }}</span>
                    @empty
                    0
                    @endforelse
                </td>
                <td style="text-align:right;white-space:nowrap;">
                    <button type="button" class="btn btn-default btn-xs"
                        onclick="openEditSG({{ json_encode(['id'=>$group->id,'name'=>$group->name,'fill_type'=>$group->fill_type,'server_ids'=>$group->servers->pluck('id')]) }})">{{ __('common.actions.edit') }}</button>
                    <form method="POST" action="{{ route('admin.config.server-groups.destroy', $group) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.server_groups.confirm_delete')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center;padding:20px;color:#777;">{{ __('admin.server_groups.no_groups') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div id="modal-add-sg" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-sg').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:480px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.server_groups.add_group') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-sg').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.server-groups.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('admin.server_groups.group_name') }}</td>
                        <td class="fieldarea"><input type="text" name="name" value="{{ old('name') }}" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.server_groups.fill_type') }}</td>
                        <td class="fieldarea">
                            <select name="fill_type" class="form-control">
                                <option value="fill" @selected(old('fill_type')==='fill')>{{ __('admin.server_groups.sequential') }}</option>
                                <option value="round_robin" @selected(old('fill_type')==='round_robin')>{{ __('admin.server_groups.roundrobin') }}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.server_groups.servers') }}</td>
                        <td class="fieldarea">
                            <div style="max-height:160px;overflow-y:auto;border:1px solid #ddd;padding:6px 10px;background:#f9f9f9;">
                                @forelse($allServers ?? [] as $srv)
                                <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:400;cursor:pointer;margin:2px 0;">
                                    <input type="checkbox" name="server_ids[]" value="{{ $srv->id }}" @checked(collect(old('server_ids', []))->contains($srv->id))>
                                    {{ $srv->name }} <span style="color:#777;">({{ $srv->type ?? 'custom' }})</span>
                                </label>
                                @empty
                                <span style="color:#777;font-size:12px;">{{ __('admin.servers.no_servers') }}</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-add-sg').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.server_groups.add_group') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-sg" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-edit-sg').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:480px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.server_groups.edit_group') }}</h4>
            <button type="button" onclick="document.getElementById('modal-edit-sg').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" id="edit-sg-form" action="">
            @csrf @method('PUT')
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('admin.server_groups.group_name') }}</td>
                        <td class="fieldarea"><input type="text" name="name" id="esg-name" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.server_groups.fill_type') }}</td>
                        <td class="fieldarea">
                            <select name="fill_type" id="esg-fill" class="form-control">
                                <option value="fill">{{ __('admin.server_groups.sequential_short') }}</option>
                                <option value="round_robin">{{ __('admin.server_groups.roundrobin_short') }}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.server_groups.servers') }}</td>
                        <td class="fieldarea">
                            <div id="esg-servers" style="max-height:160px;overflow-y:auto;border:1px solid #ddd;padding:6px 10px;background:#f9f9f9;">
                                @forelse($allServers ?? [] as $srv)
                                <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:400;cursor:pointer;margin:2px 0;">
                                    <input type="checkbox" name="server_ids[]" value="{{ $srv->id }}">
                                    {{ $srv->name }} <span style="color:#777;">({{ $srv->type ?? 'custom' }})</span>
                                </label>
                                @empty
                                <span style="color:#777;font-size:12px;">{{ __('admin.servers.no_servers') }}</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-edit-sg').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditSG(d) {
    document.getElementById('edit-sg-form').action = '/admin/config/server-groups/' + d.id;
    document.getElementById('esg-name').value = d.name;
    document.getElementById('esg-fill').value = d.fill_type === 'round_robin' ? 'round_robin' : 'fill';
    var ids = (d.server_ids || []).map(Number);
    document.querySelectorAll('#esg-servers input[type=checkbox]').forEach(function (cb) {
        cb.checked = ids.indexOf(Number(cb.value)) !== -1;
    });
    document.getElementById('modal-edit-sg').style.display = 'flex';
}
@if($errors->any())
document.getElementById('modal-add-sg').style.display = 'flex';
@endif
</script>
@endsection

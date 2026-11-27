@extends('admin.layouts.app')
@section('title', __('admin.admin_roles.title'))
@section('content')

<h1>{{ __('admin.admin_roles.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <button type="button" onclick="document.getElementById('modal-add-role').style.display='flex'" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.admin_roles.add_role') }}</button>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.admin_roles.role_name') }}</th>
                <th>{{ __('common.table.description') }}</th>
                <th>{{ __('admin.admin_roles.admins') }}</th>
                <th style="text-align:right;width:140px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($roles ?? [] as $role)
            <tr>
                <td style="font-weight:600;">{{ $role->name }}</td>
                <td>{{ $role->description ?? '-' }}</td>
                <td>{{ $role->admins_count ?? 0 }}</td>
                <td style="text-align:right;white-space:nowrap;">
                    <button type="button" class="btn btn-default btn-xs"
                        onclick="openEditRole({{ json_encode(['id'=>$role->id,'name'=>$role->name,'description'=>$role->description,'is_full_admin'=>(bool) $role->is_full_admin,'permissions'=>$role->permissions ?? []]) }})">{{ __('common.actions.edit') }}</button>
                    <form method="POST" action="{{ route('admin.config.admin-roles.destroy', $role) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($role->name), {title: @js(__('admin.admin_roles.confirm_delete')), danger: true})">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center;padding:20px;color:#777;">{{ __('admin.admin_roles.no_roles') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div id="modal-add-role" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-role').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:680px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.admin_roles.add_role') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-role').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.admin-roles.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom:12px;">
                    <tr>
                        <td class="fieldlabel" width="28%">{{ __('admin.admin_roles.role_name') }}</td>
                        <td class="fieldarea"><input type="text" name="name" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.description') }}</td>
                        <td class="fieldarea"><input type="text" name="description" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.admin_roles.full_administrator') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;"><input type="checkbox" name="is_full_admin" value="1" class="js-full-admin"> {{ __('admin.admin_roles.full_administrator') }}</label>
                            <div style="color:#777;font-size:11px;margin-top:2px;">{{ __('admin.admin_roles.full_administrator_hint') }}</div>
                        </td>
                    </tr>
                </table>
                <div class="js-permission-grid" style="max-height:280px;overflow:auto;border:1px solid #dcdcdc;padding:10px;background:#f9f9f9;">
                    @foreach($permissionGroups as $group => $permissions)
                    <div style="margin-bottom:10px;">
                        <div style="font-weight:700;font-size:11px;text-transform:uppercase;color:#1a4d80;margin-bottom:4px;">{{ $group }}</div>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px;">
                            @foreach($permissions as $permission)
                            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:400;margin:0;">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" data-permission="{{ $permission }}">
                                {{ str_replace('_', ' ', $permission) }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-add-role').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.admin_roles.add_role') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-role" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-edit-role').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:680px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.admin_roles.edit_role') }}</h4>
            <button type="button" onclick="document.getElementById('modal-edit-role').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" id="edit-role-form" action="">
            @csrf @method('PUT')
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom:12px;">
                    <tr>
                        <td class="fieldlabel" width="28%">{{ __('admin.admin_roles.role_name') }}</td>
                        <td class="fieldarea"><input type="text" name="name" id="edit-role-name" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.description') }}</td>
                        <td class="fieldarea"><input type="text" name="description" id="edit-role-desc" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.admin_roles.full_administrator') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;"><input type="checkbox" name="is_full_admin" value="1" class="js-full-admin"> {{ __('admin.admin_roles.full_administrator') }}</label>
                            <div style="color:#777;font-size:11px;margin-top:2px;">{{ __('admin.admin_roles.full_administrator_hint') }}</div>
                        </td>
                    </tr>
                </table>
                <div class="js-permission-grid" style="max-height:280px;overflow:auto;border:1px solid #dcdcdc;padding:10px;background:#f9f9f9;">
                    @foreach($permissionGroups as $group => $permissions)
                    <div style="margin-bottom:10px;">
                        <div style="font-weight:700;font-size:11px;text-transform:uppercase;color:#1a4d80;margin-bottom:4px;">{{ $group }}</div>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px;">
                            @foreach($permissions as $permission)
                            <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:400;margin:0;">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" data-permission="{{ $permission }}">
                                {{ str_replace('_', ' ', $permission) }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-edit-role').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditRole(data) {
    var form = document.getElementById('edit-role-form');
    form.action = '/admin/config/admin-roles/' + data.id;
    document.getElementById('edit-role-name').value = data.name;
    document.getElementById('edit-role-desc').value = data.description || '';

    var granted = data.permissions || [];
    form.querySelectorAll('input[data-permission]').forEach(function (box) {
        box.checked = granted.indexOf(box.value) !== -1;
    });

    var full = form.querySelector('.js-full-admin');
    full.checked = !!data.is_full_admin;
    togglePermissionGrid(full);

    document.getElementById('modal-edit-role').style.display = 'flex';
}

function togglePermissionGrid(checkbox) {
    var grid = checkbox.closest('form').querySelector('.js-permission-grid');
    grid.style.display = checkbox.checked ? 'none' : 'block';
}

document.querySelectorAll('.js-full-admin').forEach(function (checkbox) {
    checkbox.addEventListener('change', function () {
        togglePermissionGrid(checkbox);
    });
});
</script>
@endsection

@extends('admin.layouts.app')
@section('title', 'Administrators')
@section('content')

@php
    $deptMap = ($departments ?? collect())->pluck('name', 'id');
    $activeList = $activeAdmins ?? ($admins ?? collect())->where('is_disabled', false)->values();
    $disabledList = $disabledAdmins ?? ($admins ?? collect())->where('is_disabled', true)->values();
    $isManage = !empty($manageMode);
    $editTarget = $editingAdmin ?? null;
@endphp

@if($isManage)
    <h1>{{ 'Administrators' }}</h1>
    <h2 style="font-size:17px;font-weight:400;color:#333;margin:10px 0 14px;">
        {{ $editTarget ? 'Edit Admin Details' : 'Add New Administrator' }}
    </h2>

    <form method="POST" action="{{ $editTarget ? route('admin.config.admins.update', $editTarget) : route('admin.config.admins.store') }}">
        @csrf
        @if($editTarget)
            @method('PUT')
        @endif
        <input type="hidden" name="notification_settings" value="1">
        <input type="hidden" name="is_disabled_present" value="1">
        <input type="hidden" name="verify_current_admin_password" value="1">

        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td width="20%" class="fieldlabel">{{ 'Administrator Role' }}</td>
                <td class="fieldarea">
                    <select name="role_id" required class="form-control select-inline">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected((int) old('role_id', $editTarget?->role_id) === (int) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'First Name' }}</td>
                <td class="fieldarea">
                    <input type="text" name="first_name" value="{{ old('first_name', $editTarget?->first_name) }}" required class="form-control input-250">
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Last Name' }}</td>
                <td class="fieldarea">
                    <input type="text" name="last_name" value="{{ old('last_name', $editTarget?->last_name) }}" required class="form-control input-250">
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Email Address' }}</td>
                <td class="fieldarea">
                    <input type="email" name="email" value="{{ old('email', $editTarget?->email) }}" required class="form-control input-300">
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Username' }}</td>
                <td class="fieldarea">
                    <input type="text" name="username" value="{{ old('username', $editTarget?->username) }}" required class="form-control input-250">
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Password' }}</td>
                <td class="fieldarea">
                    <input type="password" name="password" minlength="6" {{ $editTarget ? '' : 'required' }} class="form-control input-250" autocomplete="new-password">
                    @if($editTarget)
                        <span style="font-size:12px;color:#666;margin-left:6px;">{{ '(Enter only if you want to change the password)' }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Confirm Password' }}</td>
                <td class="fieldarea">
                    <input type="password" name="password_confirmation" minlength="6" {{ $editTarget ? '' : 'required' }} class="form-control input-250" autocomplete="new-password">
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Assigned Departments' }}</td>
                <td class="fieldarea">
                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,260px));gap:6px 18px;margin-bottom:8px;">
                        @forelse($departments as $department)
                            <label style="font-weight:400;margin:0;cursor:pointer;">
                                <input type="checkbox" name="support_departments[]" value="{{ $department->id }}"
                                    @checked(in_array((int) $department->id, array_map('intval', (array) old('support_departments', $editTarget?->support_departments ?? [])), true))>
                                {{ $department->name }}
                            </label>
                        @empty
                            <span style="font-size:12px;color:#777;">{{ __('admin.admins.no_departments') }}</span>
                        @endforelse
                    </div>
                    <label style="font-weight:400;margin:0;cursor:pointer;color:#444;">
                        <input type="checkbox" name="ticket_notifications" value="1" @checked((bool) old('ticket_notifications', $editTarget ? $editTarget->ticket_notifications : true))>
                        {{ __('admin.admins.ticket_notifications') }}
                    </label>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Support Ticket Signature' }}</td>
                <td class="fieldarea">
                    <textarea name="signature" rows="4" class="form-control" style="max-width:560px;">{{ old('signature', $editTarget?->signature) }}</textarea>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Language' }}</td>
                <td class="fieldarea">
                    <select name="language" class="form-control select-inline">
                        <option value="">{{ 'Default' }}</option>
                        @foreach(($languages ?? collect()) as $lang)
                            <option value="{{ $lang->code }}" @selected(old('language', $editTarget?->language) === $lang->code)>{{ $lang->native_name ?? $lang->name }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Disable' }}</td>
                <td class="fieldarea">
                    <label style="font-weight:400;margin:0;cursor:pointer;">
                        <input type="checkbox" name="is_disabled" value="1"
                            @checked((bool) old('is_disabled', $editTarget?->is_disabled ?? false))
                            @disabled($editTarget && $editTarget->id === (int) auth('admin')->id())>
                        {{ 'Tick this box to deactivate this account and prevent login (you cannot disable your own account)' }}
                    </label>
                </td>
            </tr>
        </table>

        <p style="margin:16px 0 8px;font-size:13px;color:#444;">
            {{ 'Please confirm your admin password to add or make changes to administrator account details.' }}
        </p>

        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td width="20%" class="fieldlabel">{{ 'Confirm Password' }}</td>
                <td class="fieldarea">
                    <input type="password" name="current_admin_password" required class="form-control input-250" autocomplete="current-password">
                </td>
            </tr>
        </table>

        <div class="btn-container">
            <button type="submit" class="btn btn-primary">{{ 'Save Changes' }}</button>
            <a href="{{ route('admin.config.admins') }}" class="btn btn-default">{{ 'Cancel Changes' }}</a>
        </div>
    </form>
@else
    <h1>{{ 'Administrators' }}</h1>
    <p style="margin:0 0 14px;color:#555;font-size:13px;">
        {{ 'This is where you configure the users which you want to allow to access the admin area.' }}
    </p>

    <h2 style="font-size:17px;font-weight:400;color:#333;margin:14px 0 10px;">{{ 'Active Administrators' }}</h2>

    <p style="margin:0 0 10px;">
        <a href="{{ route('admin.config.admins', ['action' => 'manage']) }}" class="btn btn-default btn-sm">
            <i class="fas fa-user-plus"></i> {{ 'Add New Administrator' }}
        </a>
    </p>

    <div class="tablebg" style="margin-bottom:22px;">
        <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
            <thead>
                <tr>
                    <th class="text-left">{{ 'Username' }}</th>
                    <th class="text-left">{{ 'Name' }}</th>
                    <th class="text-left">{{ 'Email Address' }}</th>
                    <th class="text-left">{{ 'Admin Role' }}</th>
                    <th class="text-left">{{ 'Assigned Departments' }}</th>
                    <th width="26"></th>
                    <th width="26"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($activeList as $admin)
                    @php
                        $assignedNames = collect((array) ($admin->support_departments ?? []))
                            ->map(fn ($id) => $deptMap->get((int) $id))
                            ->filter()
                            ->values();
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.config.admins', ['action' => 'manage', 'id' => $admin->id]) }}" style="font-weight:600;">
                                {{ $admin->username }}
                            </a>
                        </td>
                        <td>{{ $admin->full_name }}</td>
                        <td><a href="mailto:{{ $admin->email }}">{{ $admin->email }}</a></td>
                        <td>{{ $admin->role?->name ?? '—' }}</td>
                        <td style="font-size:12.5px;color:#444;">
                            @if($assignedNames->isNotEmpty())
                                {{ $assignedNames->implode(', ') }}
                                @if($admin->ticket_notifications)
                                    <span style="color:#6b7280;font-size:11.5px;">{{ '(Receives Ticket Notifications)' }}</span>
                                @endif
                            @else
                                {{ 'None' }}
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('admin.config.admins', ['action' => 'manage', 'id' => $admin->id]) }}" title="{{ 'Edit' }}">
                                <i class="fas fa-pencil-alt" style="color:#337ab7;font-size:13px;"></i>
                            </a>
                        </td>
                        <td style="text-align:center;">
                            @if($admin->id !== (int) auth('admin')->id())
                                <form method="POST" action="{{ route('admin.config.admins.destroy', $admin) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($admin->full_name), {title: @js(__('admin.admins.confirm_delete')), danger: true})">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="{{ 'Delete' }}" style="background:none;border:0;padding:0;cursor:pointer;">
                                        <i class="fas fa-minus-circle" style="color:#d9534f;font-size:14px;"></i>
                                    </button>
                                </form>
                            @else
                                <i class="fas fa-minus-circle" style="color:#cccccc;font-size:14px;" title="{{ 'You cannot delete your own account' }}"></i>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#777;padding:16px;">{{ 'No Records Found' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2 style="font-size:17px;font-weight:400;color:#333;margin:20px 0 10px;">{{ 'Disabled Administrators' }}</h2>

    <div class="tablebg">
        <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
            <thead>
                <tr>
                    <th class="text-left">{{ 'Username' }}</th>
                    <th class="text-left">{{ 'Name' }}</th>
                    <th class="text-left">{{ 'Email Address' }}</th>
                    <th class="text-left">{{ 'Admin Role' }}</th>
                    <th class="text-left">{{ 'Assigned Departments' }}</th>
                    <th width="26"></th>
                    <th width="26"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($disabledList as $admin)
                    @php
                        $assignedNames = collect((array) ($admin->support_departments ?? []))
                            ->map(fn ($id) => $deptMap->get((int) $id))
                            ->filter()
                            ->values();
                    @endphp
                    <tr>
                        <td>{{ $admin->username }}</td>
                        <td>{{ $admin->full_name }}</td>
                        <td><a href="mailto:{{ $admin->email }}">{{ $admin->email }}</a></td>
                        <td>{{ $admin->role?->name ?? '—' }}</td>
                        <td style="font-size:12.5px;color:#444;">{{ $assignedNames->isNotEmpty() ? $assignedNames->implode(', ') : 'None' }}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('admin.config.admins', ['action' => 'manage', 'id' => $admin->id]) }}" title="{{ 'Edit' }}">
                                <i class="fas fa-pencil-alt" style="color:#337ab7;font-size:13px;"></i>
                            </a>
                        </td>
                        <td style="text-align:center;">
                            <form method="POST" action="{{ route('admin.config.admins.destroy', $admin) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($admin->full_name), {title: @js(__('admin.admins.confirm_delete')), danger: true})">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="{{ 'Delete' }}" style="background:none;border:0;padding:0;cursor:pointer;">
                                    <i class="fas fa-minus-circle" style="color:#d9534f;font-size:14px;"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#777;padding:14px;">{{ 'No Records Found' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@endsection

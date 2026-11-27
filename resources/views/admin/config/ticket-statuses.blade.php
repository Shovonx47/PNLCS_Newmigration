@extends('admin.layouts.app')
@section('title', __('admin.ticket_statuses.title'))
@section('content')

<h1>{{ __('admin.ticket_statuses.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <button type="button" onclick="document.getElementById('modal-add-ts').style.display='flex'" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.ticket_statuses.add_status') }}</button>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:15px;">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('admin.config.ticket-statuses.auto-close') }}" style="margin-bottom:16px;">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td class="fieldlabel" width="24%"><label for="ts-autoclose" style="margin:0;">{{ __('admin.ticket_statuses.auto_close_hours') }}</label></td>
            <td class="fieldarea">
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <input type="number" id="ts-autoclose" name="hours" min="0" max="8760" value="{{ (int) \App\Models\Setting::get('TicketAutoCloseHours', 0) }}" class="form-control" style="width:120px;">
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save') }}</button>
                </div>
                <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.ticket_statuses.auto_close_hint') }}</div>
            </td>
        </tr>
    </table>
</form>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.ticket_statuses.status_name_col') }}</th>
                <th>{{ __('admin.ticket_statuses.color_col') }}</th>
                <th>{{ __('admin.ticket_statuses.show_on_client') }}</th>
                <th>{{ __('admin.ticket_statuses.sort_order') }}</th>
                <th>{{ __('admin.ticket_statuses.auto_close_col') }}</th>
                <th style="text-align:right;width:140px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($statuses ?? [] as $status)
            <tr>
                <td style="font-weight:600;">
                    <span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:{{ $status->color ?? '#999' }};margin-right:6px;vertical-align:middle;"></span>
                    {{ $status->title }}
                </td>
                <td style="font-family:monospace;font-size:12px;">{{ $status->color ?? '-' }}</td>
                <td>{{ $status->show_active ? __('common.yes') : __('common.no') }}</td>
                <td>{{ $status->sort_order ?? 0 }}</td>
                <td>{{ $status->auto_close ? __('common.yes') : __('common.no') }}</td>
                <td style="text-align:right;white-space:nowrap;">
                    <button type="button" class="btn btn-default btn-xs"
                        onclick="openEditTS({{ json_encode(['id'=>$status->id,'title'=>$status->title,'color'=>$status->color,'show_active'=>$status->show_active,'sort_order'=>$status->sort_order,'auto_close'=>(bool) $status->auto_close]) }})">{{ __('common.actions.edit') }}</button>
                    <form method="POST" action="{{ route('admin.config.ticket-statuses.destroy', $status) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.ticket_statuses.confirm_delete')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:20px;color:#777;">{{ __('admin.ticket_statuses.no_statuses_msg') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div id="modal-add-ts" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-ts').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:460px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.ticket_statuses.add_status') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-ts').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.ticket-statuses.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="35%">{{ __('admin.ticket_statuses.status_name') }}</td>
                        <td class="fieldarea"><input type="text" name="title" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.ticket_statuses.color_hex') }}</td>
                        <td class="fieldarea"><input type="color" name="color" value="#337ab7" class="form-control" style="height:34px;padding:2px 6px;max-width:100px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.ticket_statuses.sort_order') }}</td>
                        <td class="fieldarea"><input type="number" name="sort_order" value="0" class="form-control" style="max-width:120px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('Options') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;display:block;margin-bottom:4px;"><input type="checkbox" name="show_active" value="1" checked> {{ __('admin.ticket_statuses.show_to_clients') }}</label>
                            <label style="font-weight:400;display:block;margin:0;"><input type="checkbox" name="auto_close" value="1"> {{ __('admin.ticket_statuses.auto_close') }}</label>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-add-ts').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.ticket_statuses.add_status') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-ts" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-edit-ts').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:460px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.ticket_statuses.edit_status_title') }}</h4>
            <button type="button" onclick="document.getElementById('modal-edit-ts').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" id="edit-ts-form" action="">
            @csrf @method('PUT')
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="35%">{{ __('admin.ticket_statuses.status_name') }}</td>
                        <td class="fieldarea"><input type="text" name="title" id="ets-title" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.ticket_statuses.color') }}</td>
                        <td class="fieldarea"><input type="color" name="color" id="ets-color" class="form-control" style="height:34px;padding:2px 6px;max-width:100px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.ticket_statuses.sort_order') }}</td>
                        <td class="fieldarea"><input type="number" name="sort_order" id="ets-order" class="form-control" style="max-width:120px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('Options') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;display:block;margin-bottom:4px;"><input type="checkbox" name="show_active" value="1" id="ets-show"> {{ __('admin.ticket_statuses.show_to_clients') }}</label>
                            <label style="font-weight:400;display:block;margin:0;"><input type="checkbox" name="auto_close" value="1" id="ets-auto"> {{ __('admin.ticket_statuses.auto_close') }}</label>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-edit-ts').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditTS(d) {
    document.getElementById('edit-ts-form').action = '/admin/config/ticket-statuses/' + d.id;
    document.getElementById('ets-title').value = d.title;
    document.getElementById('ets-color').value = d.color || '#337ab7';
    document.getElementById('ets-order').value = d.sort_order || 0;
    document.getElementById('ets-show').checked = !!d.show_active;
    document.getElementById('ets-auto').checked = !!d.auto_close;
    document.getElementById('modal-edit-ts').style.display = 'flex';
}
</script>
@endsection

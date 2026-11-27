@extends('admin.layouts.app')
@section('title', __('admin.ticket_departments.title'))
@section('content')

<h1>{{ __('admin.ticket_departments.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <button type="button" onclick="document.getElementById('modal-add-dept').style.display='flex'" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.ticket_departments.add_department') }}</button>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.ticket_departments.department_name') }}</th>
                <th>{{ __('common.table.email') }}</th>
                <th>{{ __('admin.ticket_departments.public') }}</th>
                <th>{{ __('admin.ticket_departments.tickets') }}</th>
                <th>{{ __('admin.ticket_departments.mail_import') }}</th>
                <th style="text-align:right;width:140px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($departments ?? [] as $dept)
            <tr>
                <td style="font-weight:600;">{{ $dept->name }}</td>
                <td>{{ $dept->email ?? '-' }}</td>
                <td>{{ $dept->hidden ? __('common.no') : __('common.yes') }}</td>
                <td>{{ $dept->tickets_count ?? 0 }}</td>
                <td>
                    @if($dept->import_active)
                    <span class="whmcs-status active">{{ __('common.yes') }}</span>
                    @if($dept->last_import_at)<br><small style="color:#777;">{{ $dept->last_import_at->diffForHumans() }}</small>@endif
                    @else
                    <span style="color:#777;">{{ __('common.no') }}</span>
                    @endif
                </td>
                <td style="text-align:right;white-space:nowrap;">
                    <button type="button" class="btn btn-default btn-xs"
                        onclick="openEditDept({{ json_encode(['id'=>$dept->id,'name'=>$dept->name,'email'=>$dept->email,'description'=>$dept->description,'hidden'=>$dept->hidden,'import_active'=>$dept->import_active,'import_protocol'=>$dept->import_protocol,'import_host'=>$dept->import_host,'import_port'=>$dept->import_port,'import_encryption'=>$dept->import_encryption,'import_username'=>$dept->import_username,'import_folder'=>$dept->import_folder,'import_delete'=>$dept->import_delete,'import_allow_unknown'=>$dept->import_allow_unknown]) }})">{{ __('common.actions.edit') }}</button>
                    <form method="POST" action="{{ route('admin.config.ticket-departments.destroy', $dept) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.ticket_departments.confirm_delete')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center;padding:20px;color:#777;">{{ __('admin.ticket_departments.no_departments') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div id="modal-add-dept" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-dept').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:500px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.ticket_departments.add_department') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-dept').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.ticket-departments.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('admin.ticket_departments.department_name') }}</td>
                        <td class="fieldarea"><input type="text" name="name" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.email_address') }}</td>
                        <td class="fieldarea">
                            <input type="email" name="email" class="form-control">
                            <div style="font-size:11px;color:#777;margin-top:2px;">{{ __('admin.ticket_departments.email_hint_text') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.description') }}</td>
                        <td class="fieldarea"><textarea name="description" rows="2" class="form-control"></textarea></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.ticket_departments.hidden_from_clients') }}</td>
                        <td class="fieldarea"><label style="font-weight:400;margin:0;"><input type="checkbox" name="hidden" value="1"> {{ __('admin.ticket_departments.hidden_from_clients') }}</label></td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-add-dept').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.ticket_departments.add_department') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-dept" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-edit-dept').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:580px;max-width:95%;max-height:90vh;overflow-y:auto;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.ticket_departments.edit_department') }}</h4>
            <button type="button" onclick="document.getElementById('modal-edit-dept').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" id="edit-dept-form" action="">
            @csrf @method('PUT')
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('admin.ticket_departments.department_name') }}</td>
                        <td class="fieldarea"><input type="text" name="name" id="ed-name" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.email_address') }}</td>
                        <td class="fieldarea"><input type="email" name="email" id="ed-email" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.description') }}</td>
                        <td class="fieldarea"><textarea name="description" id="ed-desc" rows="2" class="form-control"></textarea></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.ticket_departments.hidden_short') }}</td>
                        <td class="fieldarea"><label style="font-weight:400;margin:0;"><input type="checkbox" name="hidden" value="1" id="ed-hidden"> {{ __('admin.ticket_departments.hidden_short') }}</label></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.ticket_departments.import_enable') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:600;margin:0;"><input type="checkbox" name="import_active" value="1" id="ed-imp-active"> {{ __('admin.ticket_departments.import_enable') }}</label>
                            <div style="font-size:11px;color:#777;margin-top:2px;">{{ __('admin.ticket_departments.import_hint') }}</div>
                        </td>
                    </tr>
                </table>

                <div id="ed-imp-fields" style="margin-top:10px;">
                    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                        <tr>
                            <td class="fieldlabel" width="32%">{{ __('admin.ticket_departments.import_protocol') }} / {{ __('admin.ticket_departments.import_encryption') }}</td>
                            <td class="fieldarea">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                                    <select name="import_protocol" id="ed-imp-protocol" class="form-control"><option value="imap">{{ 'IMAP' }}</option><option value="pop3">{{ 'POP3' }}</option></select>
                                    <select name="import_encryption" id="ed-imp-enc" class="form-control"><option value="ssl">{{ 'SSL' }}</option><option value="tls">{{ 'STARTTLS' }}</option><option value="none">{{ __('common.no') }}</option></select>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.ticket_departments.import_host') }} / {{ __('admin.ticket_departments.import_port') }}</td>
                            <td class="fieldarea">
                                <div style="display:grid;grid-template-columns:2fr 1fr;gap:8px;">
                                    <input type="text" name="import_host" id="ed-imp-host" class="form-control" placeholder="mail.example.com">
                                    <input type="number" name="import_port" id="ed-imp-port" class="form-control" placeholder="993">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.ticket_departments.import_username') }}</td>
                            <td class="fieldarea"><input type="text" name="import_username" id="ed-imp-user" class="form-control" autocomplete="off"></td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.ticket_departments.import_password') }}</td>
                            <td class="fieldarea"><input type="password" name="import_password" class="form-control" autocomplete="new-password" placeholder="{{ __('admin.ticket_departments.import_password_keep') }}"></td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.ticket_departments.import_folder') }}</td>
                            <td class="fieldarea"><input type="text" name="import_folder" id="ed-imp-folder" class="form-control" placeholder="INBOX"></td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('Options') }}</td>
                            <td class="fieldarea">
                                <label style="font-weight:400;display:block;margin-bottom:4px;"><input type="checkbox" name="import_delete" value="1" id="ed-imp-delete"> {{ __('admin.ticket_departments.import_delete') }}</label>
                                <label style="font-weight:400;display:block;margin:0;"><input type="checkbox" name="import_allow_unknown" value="1" id="ed-imp-unknown"> {{ __('admin.ticket_departments.import_allow_unknown') }}</label>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-edit-dept').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditDept(d) {
    document.getElementById('edit-dept-form').action = '/admin/config/ticket-departments/' + d.id;
    document.getElementById('ed-name').value = d.name;
    document.getElementById('ed-email').value = d.email || '';
    document.getElementById('ed-desc').value = d.description || '';
    document.getElementById('ed-hidden').checked = !!d.hidden;
    document.getElementById('ed-imp-active').checked = !!d.import_active;
    document.getElementById('ed-imp-protocol').value = d.import_protocol || 'imap';
    document.getElementById('ed-imp-enc').value = d.import_encryption || 'ssl';
    document.getElementById('ed-imp-host').value = d.import_host || '';
    document.getElementById('ed-imp-port').value = d.import_port || '';
    document.getElementById('ed-imp-user').value = d.import_username || '';
    document.getElementById('ed-imp-folder').value = d.import_folder || 'INBOX';
    document.getElementById('ed-imp-delete').checked = !!d.import_delete;
    document.getElementById('ed-imp-unknown').checked = !!d.import_allow_unknown;
    document.getElementById('modal-edit-dept').style.display = 'flex';
}
</script>
@endsection

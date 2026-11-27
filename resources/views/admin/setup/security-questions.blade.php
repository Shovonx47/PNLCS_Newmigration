@extends('admin.layouts.app')
@section('title', 'Security Questions')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
        <h1 style="margin:0;border:0;padding:0;">{{ 'Security Questions' }}</h1>
        <p style="margin:4px 0 0;color:#666;font-size:13px;">{{ 'Configure challenge questions used to verify client identity before account changes or support requests.' }}</p>
    </div>
    <a href="{{ route('admin.config.system-settings') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> {{ 'System Settings' }}</a>
</div>

<div class="tablebg" style="margin-bottom:20px;">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th width="60">{{ 'ID' }}</th>
                <th class="text-left">{{ 'Question' }}</th>
                <th width="110">{{ 'Uses' }}</th>
                <th width="160">{{ 'Actions' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($questions as $q)
                <tr>
                    <td style="text-align:center;">#{{ $q['id'] }}</td>
                    <td><strong>{{ $q['question'] }}</strong></td>
                    <td style="text-align:center;">{{ (int) ($q['uses'] ?? 0) }}</td>
                    <td style="text-align:center;white-space:nowrap;">
                        <button type="button" class="btn btn-default btn-xs"
                            data-id="{{ $q['id'] }}"
                            data-question="{{ $q['question'] }}"
                            onclick="openEditSecQuestion(this)">
                            {{ 'Edit' }}
                        </button>
                        <form method="POST" action="{{ route('admin.config.security-questions.destroy', $q['id']) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($q['question']), {title: 'Delete Security Question'})">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">{{ 'Delete' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:#777;padding:22px;">{{ 'No security questions configured yet. Add your first security question below.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 style="font-size:16px;margin:18px 0 10px;color:#1a4d80;">{{ 'Add Security Question' }}</h2>
<form method="POST" action="{{ route('admin.config.security-questions.store') }}">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="20%" class="fieldlabel">{{ 'Security Question' }}</td>
            <td class="fieldarea">
                <input type="text" name="question" required maxlength="255" class="form-control" style="max-width:540px;" placeholder="{{ 'e.g. What was the name of your first school?' }}">
                <div style="font-size:11.5px;color:#666;margin-top:4px;">{{ 'Client answers are stored encrypted in the database.' }}</div>
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ 'Save Security Question' }}</button>
    </div>
</form>

<div id="modalEditSecQuestion" style="display:none;position:fixed;inset:0;z-index:1060;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.45);" onclick="document.getElementById('modalEditSecQuestion').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:480px;max-width:95%;box-shadow:0 10px 30px rgba(0,0,0,0.25);overflow:hidden;">
        <div style="padding:12px 16px;background:#1a4d80;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <strong style="font-size:14px;">{{ 'Edit Security Question' }}</strong>
            <button type="button" onclick="document.getElementById('modalEditSecQuestion').style.display='none'" style="background:none;border:0;color:#fff;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <form method="POST" id="formEditSecQuestion" action="">
            @csrf
            @method('PUT')
            <div style="padding:15px;">
                <label style="display:block;font-size:12.5px;font-weight:600;margin-bottom:6px;">{{ 'Question' }}</label>
                <input type="text" name="question" id="editSqText" required maxlength="255" class="form-control" style="width:100%;">
            </div>
            <div style="padding:10px 15px;background:#f5f5f5;border-top:1px solid #ddd;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('modalEditSecQuestion').style.display='none'">{{ 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ 'Save Changes' }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditSecQuestion(btn) {
    var base = @js(url('/admin/config/security-questions'));
    document.getElementById('formEditSecQuestion').setAttribute('action', base + '/' + encodeURIComponent(btn.getAttribute('data-id')));
    document.getElementById('editSqText').value = btn.getAttribute('data-question') || '';
    document.getElementById('modalEditSecQuestion').style.display = 'flex';
}
</script>

@endsection

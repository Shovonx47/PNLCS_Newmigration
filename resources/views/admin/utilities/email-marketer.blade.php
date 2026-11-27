@extends('admin.layouts.app')
@section('title', 'Email Marketer')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
    <h1 style="margin:0;border:0;padding:0;">{{ 'Email Marketer' }}</h1>
    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('em-create-box').style.display = document.getElementById('em-create-box').style.display === 'none' ? 'block' : 'none'">
        <i class="fas fa-plus"></i> {{ 'Create New Rule' }}
    </button>
</div>

<div class="infobox">
    {{ 'Schedule automated follow-up and lifecycle emails triggered a set number of days after client registration or service activation.' }}
</div>

<div id="em-create-box" style="display:{{ $errors->any() ? 'block' : 'none' }};margin-bottom:16px;">
    <form method="POST" action="{{ route('admin.utilities.email-marketer.store') }}">
        @csrf
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td width="18%" class="fieldlabel">{{ 'Rule Name' }}</td>
                <td width="32%" class="fieldarea"><input type="text" name="name" required class="form-control" placeholder="{{ 'e.g. 7-Day Onboarding Check-in' }}"></td>
                <td width="18%" class="fieldlabel">{{ 'Rule Type' }}</td>
                <td width="32%" class="fieldarea">
                    <select name="type" class="form-control select-inline">
                        <option value="client">{{ 'Client Related' }}</option>
                        <option value="product">{{ 'Product/Service Related' }}</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Trigger Event' }}</td>
                <td class="fieldarea">
                    <select name="criteria" class="form-control select-inline">
                        <option value="days_after_registration">{{ 'Days After Registration' }}</option>
                        <option value="days_after_order">{{ 'Days After Service Activation' }}</option>
                        <option value="days_before_due">{{ 'Days Before Next Due Date' }}</option>
                    </select>
                </td>
                <td class="fieldlabel">{{ 'Days Count' }}</td>
                <td class="fieldarea"><input type="number" name="days" value="7" min="0" max="3650" required class="form-control input-100"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Email Template' }}</td>
                <td class="fieldarea">
                    <select name="template_id" class="form-control select-inline">
                        <option value="">{{ '— Select Email Template —' }}</option>
                        @foreach($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ $tpl->type }})</option>
                        @endforeach
                    </select>
                </td>
                <td class="fieldlabel">{{ 'Options' }}</td>
                <td class="fieldarea">
                    <label style="display:inline-flex;align-items:center;gap:5px;font-weight:400;margin-right:12px;">
                        <input type="checkbox" name="marketing_opt_in" value="1" checked> {{ 'Marketing Opt-In Only' }}
                    </label>
                    <label style="display:inline-flex;align-items:center;gap:5px;font-weight:400;">
                        <input type="checkbox" name="active" value="1" checked> {{ 'Active' }}
                    </label>
                </td>
            </tr>
        </table>
        <div class="btn-container">
            <button type="submit" class="btn btn-primary">{{ 'Save Rule' }}</button>
        </div>
    </form>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ 'ID' }}</th>
                <th class="text-left">{{ 'Rule Name' }}</th>
                <th>{{ 'Type' }}</th>
                <th>{{ 'Trigger' }}</th>
                <th>{{ 'Email Template' }}</th>
                <th>{{ 'Status' }}</th>
                <th width="150">{{ 'Actions' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rules as $r)
                <tr>
                    <td style="text-align:center;">{{ $r['id'] }}</td>
                    <td><strong>{{ $r['name'] }}</strong></td>
                    <td style="text-align:center;text-transform:capitalize;">{{ $r['type'] }}</td>
                    <td style="text-align:center;">{{ $r['days'] }}d ({{ str_replace('_', ' ', $r['criteria']) }})</td>
                    <td style="text-align:center;">{{ $r['template_name'] ?? '—' }}</td>
                    <td style="text-align:center;">
                        <span class="whmcs-status {{ !empty($r['active']) ? 'active' : 'inactive' }}">{{ !empty($r['active']) ? 'Active' : 'Disabled' }}</span>
                    </td>
                    <td style="text-align:center;white-space:nowrap;">
                        <form method="POST" action="{{ route('admin.utilities.email-marketer.toggle', $r['id']) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-default btn-xs">{{ !empty($r['active']) ? 'Disable' : 'Enable' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.utilities.email-marketer.destroy', $r['id']) }}" style="display:inline;" onsubmit="return pnConfirm(event, 'Delete this marketing rule?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">{{ 'Delete' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:#777;padding:22px;">{{ 'No Email Marketer rules configured yet.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

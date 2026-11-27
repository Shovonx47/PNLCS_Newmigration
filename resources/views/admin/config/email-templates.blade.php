@extends('admin.layouts.app')
@section('title', __('admin.email_templates.title'))
@section('content')

<h1>{{ __('admin.email_templates.title') }}</h1>

<ul class="admin-tabs">
    <li class="active"><a href="#">{{ __('admin.email_templates.language') }}</a></li>
</ul>
<div class="tab-content admin-tabs">
    <div class="tab-pane active">
        <form method="GET" action="{{ route('admin.config.email-templates') }}">
            <table class="form" style="margin:0;">
                <tr>
                    <td class="fieldlabel">{{ __('admin.email_templates.language') }}</td>
                    <td class="fieldarea">
                        <select name="lang" class="form-control input-250" onchange="this.form.submit()">
                            @foreach($languages ?? [] as $lang)
                            <option value="{{ $lang->code }}" @selected($lang->code === ($selectedLang ?? 'en'))>{{ $lang->native_name ?? $lang->name }} ({{ $lang->code }})</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

@if(($templates ?? collect())->isEmpty())
    <div class="contentbox">{{ __('admin.email_templates.no_templates') }}</div>
@else
    <div class="tablebg">
        <table class="datatable">
            <thead>
                <tr>
                    <th class="text-left">{{ __('admin.email_templates.template_name') }}</th>
                    <th class="text-left">{{ __('common.table.subject') }}</th>
                    <th>{{ __('common.table.type') }}</th>
                    <th>{{ __('common.table.status') }}</th>
                    <th>{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($templates as $tpl)
                <tr>
                    <td>
                        <strong>{{ $tpl->name }}</strong>
                        @if($tpl->custom)<span class="whmcs-status pending" style="margin-left:5px;">{{ 'Custom' }}</span>@endif
                    </td>
                    <td>{{ $tpl->subject }}</td>
                    <td style="text-align:center;text-transform:capitalize;">{{ $tpl->type ?? 'general' }}</td>
                    <td style="text-align:center;">
                        <span class="whmcs-status {{ $tpl->disabled ? 'cancelled' : 'active' }}">{{ $tpl->disabled ? __('common.status.disabled') : __('common.status.active') }}</span>
                    </td>
                    <td style="text-align:center;">
                        <button type="button" onclick="openModal('edit-tpl-{{ $loop->index }}')" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

@foreach($templates ?? [] as $tpl)
<x-modal :name="'edit-tpl-' . $loop->index" title="{{ __('admin.email_templates.edit_template_title') }}" maxWidth="xl">
    <form method="POST" action="{{ route('admin.config.email-templates.update', $tpl) }}">
        @csrf @method('PUT')
        <table class="form">
            <tr>
                <td class="fieldlabel">{{ __('admin.email_templates.template_name') }}</td>
                <td class="fieldarea"><input type="text" name="name" value="{{ $tpl->name }}" required class="form-control"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('common.form.subject') }}</td>
                <td class="fieldarea"><input type="text" name="subject" value="{{ $tpl->subject }}" required class="form-control"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.email_templates.type') }}</td>
                <td class="fieldarea">
                    <select name="type" class="form-control input-200">
                        <option value="general" @selected(($tpl->type ?? 'general') === 'general')>{{ __('admin.email_templates.type_general') }}</option>
                        <option value="service" @selected($tpl->type === 'service')>{{ __('admin.email_templates.type_service') }}</option>
                        <option value="domain" @selected($tpl->type === 'domain')>{{ __('admin.email_templates.type_domain') }}</option>
                        <option value="invoice" @selected($tpl->type === 'invoice')>{{ __('admin.email_templates.type_invoice') }}</option>
                        <option value="support" @selected($tpl->type === 'support')>{{ __('admin.email_templates.type_support') }}</option>
                    </select>
                    <span class="email-template-source textgrey" style="margin-left:10px;font-size:12px;">{{ $tpl->custom ? __('admin.email_templates.source_custom') : __('admin.email_templates.source_builtin') }}</span>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel label-top">{{ __('admin.email_templates.body_html') }}</td>
                <td class="fieldarea"><textarea name="message" rows="10" class="form-control" style="font-family:monospace;font-size:12px;">{{ $tpl->message }}</textarea></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.email_templates.copy_to') }}</td>
                <td class="fieldarea">
                    <input type="text" name="copy_to" value="{{ $tpl->copy_to }}" class="form-control">
                    <div style="font-size:11.5px;color:#777;margin-top:3px;">{{ __('admin.email_templates.copy_to_hint') }}</div>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('common.status.disabled') }}</td>
                <td class="fieldarea">
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="hidden" name="disabled" value="0">
                        <input type="checkbox" name="disabled" value="1" @checked($tpl->disabled)>
                        <span>{{ __('common.status.disabled') }}</span>
                    </label>
                </td>
            </tr>
        </table>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px;">
            @if($tpl->custom)
            <button type="submit" form="reset-tpl-{{ $loop->index }}" class="btn btn-default btn-sm" style="margin-right:auto;">{{ __('admin.email_templates.use_builtin') }}</button>
            @endif
            <button type="button" onclick="closeModal('edit-tpl-{{ $loop->index }}')" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.email_templates.save_template') }}</button>
        </div>
    </form>
    @if($tpl->custom)
    <form id="reset-tpl-{{ $loop->index }}" method="POST" action="{{ route('admin.config.email-templates.reset', $tpl) }}">@csrf</form>
    @endif
</x-modal>
@endforeach
@endsection

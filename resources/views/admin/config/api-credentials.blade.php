@extends('admin.layouts.app')
@section('title', __('admin.api_credentials.title'))
@section('content')

<h1>{{ __('admin.api_credentials.title') }}</h1>
<div class="infobox">{{ __('admin.api_credentials.description') }}</div>

<div class="context-btn-container" style="margin-bottom:12px;">
    <button type="button" onclick="document.getElementById('modal-add-api').style.display='flex'" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.api_credentials.generate_key') }}</button>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:15px;">
    <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

@if(session('new_secret'))
<div class="infobox" style="background:#fcf8e3;border-color:#faebcc;">
    <strong style="color:#8a6d3b;">{{ __('admin.api_credentials.save_secret_warning') }}</strong>
    <div style="margin-top:8px;display:flex;gap:8px;align-items:center;">
        <code id="api-secret-display" style="flex:1;padding:8px;background:#fff;border:1px solid #ddd;font-size:13px;word-break:break-all;">{{ session('new_secret') }}</code>
        <button type="button" class="btn btn-default btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('api-secret-display').textContent);this.textContent=@js(__('admin.api_credentials.copied'));">{{ __('common.actions.copy') }}</button>
    </div>
</div>
@endif

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.description') }}</th>
                <th>{{ __('admin.api_credentials.identifier') }}</th>
                <th>{{ __('admin.api_credentials.admin') }}</th>
                <th>{{ __('admin.api_credentials.allowed_from') }}</th>
                <th>{{ __('common.table.created') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th style="text-align:right;width:150px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($credentials ?? [] as $cred)
            <tr>
                <td style="font-weight:600;">{{ $cred->description ?: __('admin.api_credentials.no_description') }}</td>
                <td><code>{{ $cred->identifier }}</code></td>
                <td>{{ $cred->admin->full_name ?? 'N/A' }}</td>
                <td>@if(! empty($cred->allowed_ips)){{ implode(', ', $cred->allowed_ips) }}@else<span style="color:#777;">{{ __('admin.api_credentials.anywhere') }}</span>@endif</td>
                <td>{{ $cred->created_at?->timezone(display_tz())->format(datetime_fmt()) ?? '-' }}</td>
                <td><span class="whmcs-status {{ $cred->active ? 'active badge-active' : 'suspended badge-suspended' }}">{{ $cred->active ? __('common.status.active') : __('common.status.disabled') }}</span></td>
                <td style="text-align:right;white-space:nowrap;">
                    <button type="button" class="btn btn-default btn-xs" onclick="document.getElementById('modal-edit-api-{{ $cred->id }}').style.display='flex'">{{ __('common.actions.edit') }}</button>
                    <form method="POST" action="{{ route('admin.config.api-credentials.destroy', $cred) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.api_credentials.confirm_revoke')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('admin.api_credentials.revoke') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:20px;color:#777;">{{ __('admin.api_credentials.no_keys') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="infobox" style="margin-top:15px;">
    <strong>{{ __('admin.api_credentials.api_usage') }}:</strong> {{ __('admin.api_credentials.api_usage_desc') }} <code>/api/v1/{action}</code> {{ __('admin.api_credentials.api_usage_params') }}
    @if(Route::has('admin.api-docs'))
    <a href="{{ route('admin.api-docs') }}">{{ __('admin.api_credentials.view_docs') }}</a>
    @endif
</div>

{{-- Generate Modal --}}
<div id="modal-add-api" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="this.parentElement.style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:500px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.api_credentials.generate_credential') }}</h4>
            <button type="button" onclick="this.closest('[id]').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.api-credentials.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('common.form.description') }} *</td>
                        <td class="fieldarea"><input type="text" name="description" required maxlength="255" class="form-control" placeholder="{{ __('admin.api_credentials.description_placeholder') }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.api_credentials.allowed_ips') }}</td>
                        <td class="fieldarea">
                            <textarea name="allowed_ips" rows="3" class="form-control" style="font-family:monospace;font-size:12px;"></textarea>
                            <div style="color:#777;font-size:11px;margin-top:2px;">{{ __('admin.api_credentials.allowed_ips_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="this.closest('[id]').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.api_credentials.generate') }}</button>
            </div>
        </form>
    </div>
</div>

@foreach($credentials ?? [] as $cred)
<div id="modal-edit-api-{{ $cred->id }}" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="this.parentElement.style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:500px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.api_credentials.edit_credential') }}</h4>
            <button type="button" onclick="this.closest('[id]').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.api-credentials.update', $cred) }}">
            @csrf @method('PUT')
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('common.form.description') }} *</td>
                        <td class="fieldarea"><input type="text" name="description" required maxlength="255" class="form-control" value="{{ $cred->description }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.api_credentials.allowed_ips') }}</td>
                        <td class="fieldarea">
                            <textarea name="allowed_ips" rows="3" class="form-control" style="font-family:monospace;font-size:12px;">{{ implode("\n", (array) $cred->allowed_ips) }}</textarea>
                            <div style="color:#777;font-size:11px;margin-top:2px;">{{ __('admin.api_credentials.allowed_ips_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.table.status') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;"><input type="checkbox" name="active" value="1" @checked($cred->active)> {{ __('common.status.active') }}</label>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="this.closest('[id]').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection

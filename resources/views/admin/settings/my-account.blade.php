@extends("admin.layouts.app")
@section("title", __("admin.settings.my_account"))
@section("content")

<h1>{{ __('admin.settings.my_account_title') }}</h1>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:15px;">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route("admin.my-account.update") }}">
    @csrf

    <h2 style="font-size:15px;margin:0 0 8px;">{{ __('admin.settings.profile_information') }}</h2>
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td class="fieldlabel" width="22%">{{ __('common.form.first_name') }} *</td>
            <td class="fieldarea">
                <input type="text" name="first_name" class="form-control" style="max-width:340px;" value="{{ old("first_name", $admin->first_name) }}" required>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('common.form.last_name') }} *</td>
            <td class="fieldarea">
                <input type="text" name="last_name" class="form-control" style="max-width:340px;" value="{{ old("last_name", $admin->last_name) }}" required>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('common.form.email_address') }} *</td>
            <td class="fieldarea">
                <input type="email" name="email" class="form-control" style="max-width:340px;" value="{{ old("email", $admin->email) }}" required>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.settings.ticket_signature') }}</td>
            <td class="fieldarea">
                <textarea name="signature" class="form-control" rows="4" style="max-width:560px;resize:vertical;">{{ old("signature", $admin->signature) }}</textarea>
                <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.settings.ticket_signature_hint') }}</div>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('common.form.language') }} *</td>
            <td class="fieldarea">
                <select name="language" class="form-control" style="max-width:280px;" required>
                    @foreach($languages as $language)
                        <option value="{{ $language->code }}" {{ old('language', $admin->language) === $language->code ? 'selected' : '' }}>
                            {{ $language->native_name }}
                        </option>
                    @endforeach
                </select>
                @php
                    $languagesMenu = __('admin.nav.setup').' → '.__('admin.nav.languages');
                    [$hintBefore, $hintAfter] = array_pad(explode(':menu', __('admin.settings.language_active_only'), 2), 2, '');
                @endphp
                <small class="language-active-hint" style="color:#777;display:block;margin-top:4px;">{{ $hintBefore }}@if($admin->role?->hasPermission('manage_settings'))<a href="{{ route('admin.config.languages.index') }}">{{ $languagesMenu }}</a>@else{{ $languagesMenu }}@endif{{ $hintAfter }}</small>
            </td>
        </tr>
    </table>

    <h2 style="font-size:15px;margin:18px 0 8px;">{{ __('admin.settings.change_password') }} <small style="font-weight:400;color:#777;">{{ __('admin.settings.change_password_hint') }}</small></h2>
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td class="fieldlabel" width="22%">{{ __('common.form.new_password') }}</td>
            <td class="fieldarea">
                <input type="password" name="new_password" class="form-control" style="max-width:340px;" autocomplete="new-password">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.settings.confirm_new_password') }}</td>
            <td class="fieldarea">
                <input type="password" name="new_password_confirmation" class="form-control" style="max-width:340px;" autocomplete="new-password">
            </td>
        </tr>
    </table>

    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ __('common.actions.save_changes') }}</button>
        <a href="{{ route("admin.dashboard") }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
    </div>
</form>

<h2 style="font-size:15px;margin:22px 0 8px;">{{ __('admin.settings.two_factor') }}</h2>
<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td class="fieldlabel" width="22%">{{ __('admin.settings.two_factor') }}</td>
        <td class="fieldarea">
            <div style="color:#666;font-size:12px;margin-bottom:8px;">{{ __('admin.settings.two_factor_desc') }}</div>
            @if($admin->second_factor_type)
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <span class="whmcs-status active badge-active">&#10003; {{ __('admin.settings.two_factor_on') }}</span>
                    <form method="POST" action="{{ route('admin.2fa.disable') }}" style="display:flex;gap:8px;align-items:flex-start;margin:0;">
                        @csrf
                        <div>
                            <input type="password" name="password" class="form-control" placeholder="{{ __('admin.settings.confirm_with_password') }}" autocomplete="current-password" style="min-width:220px;">
                            @error('password')<div style="color:#c43c35;font-size:12px;margin-top:4px;">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm">{{ __('admin.settings.two_factor_turn_off') }}</button>
                    </form>
                </div>
            @else
                <div style="display:flex;align-items:center;gap:12px;">
                    <span class="whmcs-status suspended">{{ __('admin.settings.two_factor_off') }}</span>
                    <a href="{{ route('admin.2fa.enable') }}" class="btn btn-primary btn-sm">{{ __('admin.settings.two_factor_turn_on') }}</a>
                </div>
            @endif
        </td>
    </tr>
</table>

@endsection

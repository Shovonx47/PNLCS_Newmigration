@extends('admin.layouts.app')
@section('title', 'Two-Factor Authentication')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
        <h1 style="margin:0;border:0;padding:0;">{{ 'Two-Factor Authentication' }}</h1>
        <p style="margin:4px 0 0;color:#666;font-size:13px;">{{ 'Protect client and administrator accounts with Time-Based TOTP authenticators and SMS verification.' }}</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('admin.my-account') }}" class="btn btn-primary btn-sm"><i class="fas fa-user-shield"></i> {{ 'My Admin 2FA Setup' }}</a>
        <a href="{{ route('admin.config.system-settings') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> {{ 'System Settings' }}</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-bottom:16px;">
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:4px;padding:14px 18px;">
        <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">{{ 'Administrators with 2FA Active' }}</div>
        <div style="font-size:22px;font-weight:700;color:#1a4d80;margin-top:4px;">{{ $staffWith2fa }} / {{ $staffTotal }}</div>
    </div>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:4px;padding:14px 18px;">
        <div style="font-size:12px;color:#64748b;font-weight:600;text-transform:uppercase;">{{ 'Client Users with 2FA Active' }}</div>
        <div style="font-size:22px;font-weight:700;color:#16a34a;margin-top:4px;">{{ $usersWith2fa }}</div>
    </div>
</div>

<form method="POST" action="{{ route('admin.config.two-factor.save') }}">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="25%" class="fieldlabel">{{ 'Time-Based Tokens (TOTP)' }}</td>
            <td class="fieldarea">
                <label><input type="checkbox" name="totp_enabled" value="1" @checked($settings['totp_enabled'])> {{ 'Enable Google Authenticator / Authy / 1Password TOTP verification (Free)' }}</label>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Emergency Backup Codes' }}</td>
            <td class="fieldarea">
                <label><input type="checkbox" name="backup_codes_enabled" value="1" @checked($settings['backup_codes_enabled'])> {{ 'Generate one-time encrypted recovery codes upon 2FA enrollment' }}</label>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Force Administrator 2FA' }}</td>
            <td class="fieldarea">
                <label><input type="checkbox" name="require_admin_2fa" value="1" @checked($settings['require_admin_2fa'])> {{ 'Require all staff members to enroll in Two-Factor Authentication on login' }}</label>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Force Client 2FA' }}</td>
            <td class="fieldarea">
                <label><input type="checkbox" name="require_client_2fa" value="1" @checked($settings['require_client_2fa'])> {{ 'Prompt client area users to enable Two-Factor Authentication' }}</label>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Twilio Verify SMS / Voice' }}</td>
            <td class="fieldarea">
                <label style="display:block;margin-bottom:8px;"><input type="checkbox" name="twilio_enabled" value="1" @checked($settings['twilio_enabled'])> {{ 'Enable Twilio Verify SMS second factor' }}</label>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;max-width:720px;">
                    <input type="text" name="twilio_sid" value="{{ $settings['twilio_sid'] }}" class="form-control" placeholder="{{ 'Twilio Account SID' }}">
                    <input type="password" name="twilio_auth_token" value="" class="form-control" autocomplete="new-password" placeholder="{{ $settings['twilio_has_token'] ? '•••••• (Auth Token Set)' : 'Twilio Auth Token' }}">
                    <input type="text" name="twilio_service_sid" value="{{ $settings['twilio_service_sid'] }}" class="form-control" placeholder="{{ 'Verify Service SID' }}">
                </div>
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ 'Save Changes' }}</button>
    </div>
</form>

@endsection

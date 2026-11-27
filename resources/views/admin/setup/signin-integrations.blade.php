@extends('admin.layouts.app')
@section('title', 'Sign-In Integrations')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
        <h1 style="margin:0;border:0;padding:0;">{{ 'Sign-In Integrations' }}</h1>
        <p style="margin:4px 0 0;color:#666;font-size:13px;">{{ 'Allow clients to register and sign in seamlessly with Google, Facebook, Twitter/X, and GitHub.' }}</p>
    </div>
    <a href="{{ route('admin.config.system-settings') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> {{ 'System Settings' }}</a>
</div>

<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
    @foreach($providers as $key => $prov)
        <form method="POST" action="{{ route('admin.config.signin-integrations.save') }}" style="background:#fff;border:1px solid #e2e8f0;border-radius:4px;padding:18px;">
            @csrf
            <input type="hidden" name="provider" value="{{ $key }}">
            <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;margin-bottom:14px;border-bottom:1px solid #f1f5f9;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="width:36px;height:36px;border-radius:6px;background:{{ $prov['color'] }};color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:16px;">
                        <i class="{{ $prov['icon'] }}"></i>
                    </span>
                    <div>
                        <strong style="font-size:15px;color:#1e293b;">{{ $prov['name'] }}</strong>
                        <div style="font-size:11.5px;color:#64748b;">{{ $prov['linked_count'] }} {{ 'linked user account(s)' }}</div>
                    </div>
                </div>
                <span class="whmcs-status {{ $prov['enabled'] ? 'active' : 'inactive' }}">{{ $prov['enabled'] ? 'Active' : 'Disabled' }}</span>
            </div>

            <div style="margin-bottom:10px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">{{ 'Client ID / App ID' }}</label>
                <input type="text" name="client_id" value="{{ $prov['client_id'] }}" class="form-control" style="width:100%;" placeholder="{{ 'Enter OAuth Client ID' }}">
            </div>

            <div style="margin-bottom:10px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">{{ 'Client Secret (Encrypted at Rest)' }}</label>
                <input type="password" name="client_secret" value="" class="form-control" style="width:100%;" autocomplete="new-password"
                    placeholder="{{ $prov['has_secret'] ? '•••••••••••• (Stored — leave blank to keep current)' : 'Enter OAuth Client Secret' }}">
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:4px;">{{ 'Authorized Redirect Callback URL' }}</label>
                <input type="text" readonly value="{{ $prov['callback_url'] }}" class="form-control" style="width:100%;background:#f8fafc;font-family:monospace;font-size:12px;">
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:10px;border-top:1px solid #f1f5f9;">
                <label style="font-size:12.5px;font-weight:600;cursor:pointer;">
                    <input type="checkbox" name="enabled" value="1" @checked($prov['enabled'])> {{ 'Enable ' . $prov['name'] . ' Sign-In' }}
                </label>
                <button type="submit" class="btn btn-primary btn-sm">{{ 'Save Changes' }}</button>
            </div>
        </form>
    @endforeach
</div>

@endsection

@extends('admin.layouts.app')
@section('title', 'Fraud Protection')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
        <h1 style="margin:0;border:0;padding:0;">{{ 'Fraud Protection' }}</h1>
        <p style="margin:4px 0 0;color:#666;font-size:13px;">{{ 'Automate checkout risk scoring and block fraudulent orders before provisioning.' }}</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('admin.orders.index', ['status' => 'fraud']) }}" class="btn btn-default btn-sm">{{ 'Fraud Orders (' . $fraudOrdersCount . ')' }}</a>
        <a href="{{ route('admin.config.system-settings') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> {{ 'System Settings' }}</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
    <form method="POST" action="{{ route('admin.config.fraud-protection.save') }}" style="background:#fff;border:1px solid #e2e8f0;border-radius:4px;padding:18px;">
        @csrf
        <input type="hidden" name="module" value="maxmind">
        <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;margin-bottom:14px;border-bottom:1px solid #f1f5f9;">
            <div>
                <strong style="font-size:15px;color:#1a4d80;">{{ 'MaxMind minFraud' }}</strong>
                <div style="font-size:12px;color:#64748b;">{{ 'Industry-standard IP, proxy, email & billing address risk scoring.' }}</div>
            </div>
            <span class="whmcs-status {{ $config['maxmind_enabled'] ? 'active' : 'inactive' }}">{{ $config['maxmind_enabled'] ? 'Enabled' : 'Disabled' }}</span>
        </div>

        <table class="form" width="100%">
            <tr>
                <td width="40%" class="fieldlabel">{{ 'Enable MaxMind' }}</td>
                <td class="fieldarea"><label><input type="checkbox" name="maxmind_enabled" value="1" @checked($config['maxmind_enabled'])> {{ 'Screen new orders with MaxMind' }}</label></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Account / User ID' }}</td>
                <td class="fieldarea"><input type="text" name="maxmind_account_id" value="{{ $config['maxmind_account_id'] }}" class="form-control"></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'License Key' }}</td>
                <td class="fieldarea">
                    <input type="password" name="maxmind_license_key" value="" class="form-control" autocomplete="new-password"
                        placeholder="{{ $config['maxmind_has_key'] ? '•••••••••••• (Configured)' : 'Enter MaxMind License Key' }}">
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Fraud Risk Score Threshold' }}</td>
                <td class="fieldarea"><input type="number" name="maxmind_risk_score" value="{{ $config['maxmind_risk_score'] }}" min="1" max="100" step="0.5" class="form-control input-100"> <span style="font-size:11.5px;color:#666;">{{ '(1–100)' }}</span></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Screening Rules' }}</td>
                <td class="fieldarea">
                    <label style="display:block;margin-bottom:4px;"><input type="checkbox" name="maxmind_reject_anon_proxy" value="1" @checked($config['maxmind_reject_anon_proxy'])> {{ 'Reject Anonymous Proxy / VPN Orders' }}</label>
                    <label style="display:block;margin-bottom:4px;"><input type="checkbox" name="maxmind_reject_country_mismatch" value="1" @checked($config['maxmind_reject_country_mismatch'])> {{ 'Reject IP / Billing Country Mismatch' }}</label>
                    <label style="display:block;"><input type="checkbox" name="maxmind_reject_free_email" value="1" @checked($config['maxmind_reject_free_email'])> {{ 'Reject Disposable / Free Email Domains' }}</label>
                </td>
            </tr>
        </table>
        <div style="text-align:right;margin-top:12px;">
            <button type="submit" class="btn btn-primary btn-sm">{{ 'Save MaxMind Settings' }}</button>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.config.fraud-protection.save') }}" style="background:#fff;border:1px solid #e2e8f0;border-radius:4px;padding:18px;">
        @csrf
        <input type="hidden" name="module" value="fraudlabs">
        <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;margin-bottom:14px;border-bottom:1px solid #f1f5f9;">
            <div>
                <strong style="font-size:15px;color:#1a4d80;">{{ 'FraudLabs Pro' }}</strong>
                <div style="font-size:12px;color:#64748b;">{{ 'Device velocity, BIN validation, and blacklist fraud screening.' }}</div>
            </div>
            <span class="whmcs-status {{ $config['fraudlabs_enabled'] ? 'active' : 'inactive' }}">{{ $config['fraudlabs_enabled'] ? 'Enabled' : 'Disabled' }}</span>
        </div>

        <table class="form" width="100%">
            <tr>
                <td width="40%" class="fieldlabel">{{ 'Enable FraudLabs Pro' }}</td>
                <td class="fieldarea"><label><input type="checkbox" name="fraudlabs_enabled" value="1" @checked($config['fraudlabs_enabled'])> {{ 'Screen new orders with FraudLabs Pro' }}</label></td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'API Key' }}</td>
                <td class="fieldarea">
                    <input type="password" name="fraudlabs_api_key" value="" class="form-control" autocomplete="new-password"
                        placeholder="{{ $config['fraudlabs_has_key'] ? '•••••••••••• (Configured)' : 'Enter FraudLabs Pro API Key' }}">
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Fraud Score Threshold' }}</td>
                <td class="fieldarea"><input type="number" name="fraudlabs_risk_score" value="{{ $config['fraudlabs_risk_score'] }}" min="1" max="100" step="1" class="form-control input-100"> <span style="font-size:11.5px;color:#666;">{{ '(1–100)' }}</span></td>
            </tr>
        </table>
        <div style="text-align:right;margin-top:12px;">
            <button type="submit" class="btn btn-primary btn-sm">{{ 'Save FraudLabs Settings' }}</button>
        </div>
    </form>
</div>

@endsection

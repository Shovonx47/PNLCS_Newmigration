@extends('admin.layouts.app')
@section('title', 'MarketConnect')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
        <h1 style="margin:0;border:0;padding:0;">{{ 'MarketConnect' }}</h1>
        <p style="margin:4px 0 0;color:#666;font-size:13px;">{{ 'Re-sell market-leading digital services and security add-ons directly to your clients.' }}</p>
    </div>
    <a href="{{ route('admin.config.system-settings') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> {{ 'System Settings' }}</a>
</div>

<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:18px;">
    <div class="contentbox" style="margin:0;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:4px;">
        <div style="font-size:12px;color:#6b7280;text-transform:uppercase;font-weight:600;">{{ 'Active Services' }}</div>
        <div style="font-size:22px;font-weight:700;color:#1a4d80;margin-top:4px;">{{ collect($services)->where('enabled', true)->count() }} / {{ count($services) }}</div>
    </div>
    <div class="contentbox" style="margin:0;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:4px;">
        <div style="font-size:12px;color:#6b7280;text-transform:uppercase;font-weight:600;">{{ 'Configured SSL Authorities' }}</div>
        <div style="font-size:22px;font-weight:700;color:#16a34a;margin-top:4px;">{{ $sslModuleCount }}</div>
    </div>
    <div class="contentbox" style="margin:0;padding:14px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:4px;">
        <div style="font-size:12px;color:#6b7280;text-transform:uppercase;font-weight:600;">{{ 'Provisioned SSL Orders' }}</div>
        <div style="font-size:22px;font-weight:700;color:#0284c7;margin-top:4px;">{{ $sslOrderCount }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;">
    @foreach($services as $svc)
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:4px;padding:18px;display:flex;flex-direction:column;justify-content:space-between;gap:14px;">
            <div style="display:flex;align-items:flex-start;gap:14px;">
                <div style="width:46px;height:46px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-size:20px;color:{{ $svc['color'] }};flex-shrink:0;">
                    <i class="{{ $svc['icon'] }}"></i>
                </div>
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <strong style="font-size:14.5px;color:#1e293b;">{{ $svc['name'] }}</strong>
                        <span class="whmcs-status {{ $svc['enabled'] ? 'active' : 'inactive' }}">{{ $svc['enabled'] ? 'Selling' : 'Inactive' }}</span>
                    </div>
                    <p style="margin:5px 0 0;font-size:12.5px;color:#64748b;line-height:1.45;">{{ $svc['tagline'] }}</p>
                </div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid #f1f5f9;">
                <a href="{{ $svc['manage_url'] }}" class="btn btn-default btn-xs">{{ $svc['manage_label'] }}</a>
                <form method="POST" action="{{ route('admin.config.marketconnect.toggle') }}" style="margin:0;">
                    @csrf
                    <input type="hidden" name="service_key" value="{{ $svc['key'] }}">
                    <button type="submit" class="btn {{ $svc['enabled'] ? 'btn-default' : 'btn-success' }} btn-xs">
                        {{ $svc['enabled'] ? 'Deactivate Offering' : 'Start Selling' }}
                    </button>
                </form>
            </div>
        </div>
    @endforeach
</div>

@endsection

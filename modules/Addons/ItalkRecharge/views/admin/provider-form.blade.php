@extends('admin.layouts.app')
@section('title', $isEdit ? 'Edit Provider' : 'Add Provider')

@section('content')
<style>
    .iptsp-form-card {
        background: #fff;
        border: 1px solid #e6e8ec;
        border-radius: 6px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        padding: 28px 30px;
        max-width: 640px;
        margin: 10px auto 20px;
    }
    .iptsp-form-card h2 {
        margin: 0 0 18px 0;
        font-size: 20px;
        font-weight: 500;
        color: #2c3e50;
        border-bottom: 2px solid #f0f2f5;
        padding-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .iptsp-fg { margin-bottom: 16px; }
    .iptsp-fg label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; color: #34495e; }
    .iptsp-help { font-size: 12px; color: #7f8c8d; margin-top: 4px; }
</style>

<h1 style="font-size:26px;font-weight:400;color:#333;margin:0 0 14px 0;">Multi-Provider Auto Recharge</h1>

<div class="iptsp-form-card">
    <h2><i class="fas fa-cog" style="color:#3498db;"></i> {{ $isEdit ? 'Edit Provider' : 'Add Provider' }}</h2>

    <form method="POST"
          action="{{ $isEdit ? route('admin.extensions.iptsp-recharge.providers.update', $provider) : route('admin.extensions.iptsp-recharge.providers.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="iptsp-fg">
            <label>Provider Name <span style="color:#d9534f;">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $provider->name ?? '') }}" placeholder="e.g. CODE 09644" required>
        </div>

        <div class="iptsp-fg">
            <label>Logo (optional)</label>
            @if(! empty($provider?->logo_url))
                <div style="margin-bottom:6px;">
                    <img src="{{ asset($provider->logo_url) }}" alt="{{ $provider->name }}" style="max-height:52px;border:1px solid #eee;border-radius:4px;padding:3px;">
                </div>
            @endif
            <input type="file" name="logo_file" class="form-control" accept="image/*">
        </div>

        <div class="iptsp-fg">
            <label>Base URL (https) <span style="color:#d9534f;">*</span></label>
            <input type="url" name="base_url" class="form-control" value="{{ old('base_url', $provider->base_url ?? '') }}" placeholder="https://sip.italk.com.bd" required>
        </div>

        <div class="iptsp-fg">
            <label>Base Path <span style="color:#d9534f;">*</span></label>
            <input type="text" name="base_path" class="form-control" value="{{ old('base_path', $provider->base_path ?? '/iconiptsp/') }}" placeholder="/iconiptsp/">
            <div class="iptsp-help">Example: <code>/iconiptsp/</code>, <code>/ICC/</code>, or <code>/</code> for REST APIs</div>
        </div>

        <div class="iptsp-fg">
            <label>Driver / API Type</label>
            <select name="api_type" class="form-control">
                @foreach($apiTypes as $key => $label)
                    <option value="{{ $key }}" @selected(old('api_type', $provider->api_type ?? 'italk') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="iptsp-help">iTalk uses Web Scraper mode; Ranksitt and WebVoice use REST API mode.</div>
        </div>

        <div class="iptsp-fg">
            <label>API Key (optional, for REST)</label>
            <input type="password" name="api_key" class="form-control" placeholder="{{ ($provider?->api_key ? 'Stored (leave blank to keep)' : 'API Key if required') }}" autocomplete="new-password">
        </div>

        <div class="iptsp-fg">
            <label>Username <span style="color:#d9534f;">*</span></label>
            <input type="text" name="username" class="form-control" value="{{ old('username', $provider->username ?? '') }}" required>
        </div>

        <div class="iptsp-fg">
            <label>Password {!! $isEdit ? '<span style="color:#7f8c8d;font-weight:400;">(leave blank to keep stored)</span>' : '<span style="color:#d9534f;">*</span>' !!}</label>
            <input type="password" name="password" class="form-control" {{ $isEdit ? '' : 'required' }} autocomplete="new-password">
        </div>

        <div class="iptsp-fg">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="active" @selected(old('status', $provider->status ?? 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $provider->status ?? 'active') === 'inactive')>Inactive</option>
            </select>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:22px;">
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
                <a href="{{ route('admin.extensions.iptsp-recharge.index') }}" class="btn btn-default"><i class="fas fa-times"></i> Cancel</a>
            </div>
        </div>
    </form>

    @if($isEdit)
        <hr style="margin:22px 0 14px;">
        <form method="POST"
              action="{{ route('admin.extensions.iptsp-recharge.providers.destroy', $provider) }}"
              onsubmit="return pnConfirm(event, @js('Remove this provider? Its PIN accounts will be disabled and the audit log kept.'), { danger: true })">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Remove Provider</button>
        </form>
    @endif
</div>
@endsection

@extends('admin.layouts.app')
@section('title', 'Application Links')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
        <h1 style="margin:0;border:0;padding:0;">{{ 'Application Links' }}</h1>
        <p style="margin:4px 0 0;color:#666;font-size:13px;">{{ 'Link hosting control panels (cPanel, Panelica, DirectAdmin, Plesk) with client area Single Sign-On shortcuts.' }}</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('admin.config.servers') }}" class="btn btn-default btn-sm"><i class="fas fa-server"></i> {{ 'Manage Servers' }}</a>
        <a href="{{ route('admin.config.system-settings') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> {{ 'System Settings' }}</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.config.application-links.save') }}" style="margin-bottom:20px;">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="24%" class="fieldlabel">{{ 'Control Panel Application Linking' }}</td>
            <td class="fieldarea">
                <label><input type="checkbox" name="enabled" value="1" @checked($enabled)> {{ 'Enable Single Sign-On Application Linking between billing portal and hosting control panels' }}</label>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Client Area Quick Shortcuts' }}</td>
            <td class="fieldarea">
                <label><input type="checkbox" name="client_shortcuts" value="1" @checked($clientAreaShortcuts)> {{ 'Display Webmail, File Manager, phpMyAdmin & Databases SSO shortcuts inside client service details' }}</label>
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary btn-sm">{{ 'Save Application Link Settings' }}</button>
    </div>
</form>

<h2 style="font-size:16px;margin:16px 0 10px;color:#1a4d80;">{{ 'Connected Provisioning Servers' }}</h2>
<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th class="text-left">{{ 'Server Name' }}</th>
                <th>{{ 'Module Type' }}</th>
                <th>{{ 'Hostname / IP' }}</th>
                <th>{{ 'Active Services' }}</th>
                <th>{{ 'Application Link Status' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($servers as $srv)
                <tr>
                    <td><strong>{{ $srv->name }}</strong></td>
                    <td style="text-align:center;text-transform:capitalize;">{{ $srv->type ?: 'custom' }}</td>
                    <td style="text-align:center;font-family:monospace;font-size:12px;">{{ $srv->hostname ?: $srv->ip_address ?: '—' }}</td>
                    <td style="text-align:center;">{{ $srv->services_count ?? 0 }}</td>
                    <td style="text-align:center;">
                        @if($enabled && !$srv->disabled)
                            <span class="whmcs-status active">{{ 'Linked' }}</span>
                        @else
                            <span class="whmcs-status inactive">{{ 'Disabled' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;color:#777;padding:22px;">
                        {{ 'No servers added yet.' }} <a href="{{ route('admin.config.servers') }}">{{ 'Configure a server' }}</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

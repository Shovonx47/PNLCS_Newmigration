@extends('admin.layouts.app')
@section('title', 'WHMCS Connect')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
    <h1 style="margin:0;border:0;padding:0;">{{ 'WHMCS Connect' }}</h1>
    <a href="{{ route('admin.config.servers') }}" class="btn btn-primary btn-sm"><i class="fas fa-server"></i> {{ 'Manage Servers' }}</a>
</div>

<div class="infobox">
    {{ 'Centralized server directory and Single Sign-On launchpad for your connected hosting control panels.' }}
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th class="text-left">{{ 'Server Name' }}</th>
                <th>{{ 'Module' }}</th>
                <th>{{ 'Hostname / IP' }}</th>
                <th>{{ 'Active Accounts' }}</th>
                <th>{{ 'Status' }}</th>
                <th width="180">{{ 'Actions' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($servers as $srv)
                <tr>
                    <td><strong>{{ $srv->name }}</strong></td>
                    <td style="text-align:center;text-transform:capitalize;">{{ $srv->type ?: 'custom' }}</td>
                    <td style="text-align:center;font-family:monospace;font-size:12px;">{{ $srv->hostname ?: $srv->ip_address ?: '—' }}</td>
                    <td style="text-align:center;">{{ $srv->services_count ?? 0 }}{{ $srv->max_accounts ? ' / ' . $srv->max_accounts : '' }}</td>
                    <td style="text-align:center;">
                        <span class="whmcs-status {{ $srv->active ? 'active' : 'inactive' }}">{{ $srv->active ? 'Active' : 'Disabled' }}</span>
                    </td>
                    <td style="text-align:center;white-space:nowrap;">
                        @if($srv->hostname || $srv->ip_address)
                            @php
                                $scheme = $srv->secure ? 'https' : 'http';
                                $host = $srv->hostname ?: $srv->ip_address;
                                $port = $srv->port ? (':' . $srv->port) : '';
                            @endphp
                            <a href="{{ $scheme . '://' . $host . $port }}" target="_blank" rel="noopener" class="btn btn-primary btn-xs">
                                <i class="fas fa-external-link-alt"></i> {{ 'Control Panel' }}
                            </a>
                        @endif
                        <a href="{{ route('admin.config.servers') }}" class="btn btn-default btn-xs">{{ 'Configure' }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:#777;padding:22px;">
                        {{ 'No servers configured yet.' }} <a href="{{ route('admin.config.servers') }}">{{ 'Add a server' }}</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

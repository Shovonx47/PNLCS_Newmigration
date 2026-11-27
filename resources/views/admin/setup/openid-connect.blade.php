@extends('admin.layouts.app')
@section('title', 'OpenID Connect')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
        <h1 style="margin:0;border:0;padding:0;">{{ 'OpenID Connect' }}</h1>
        <p style="margin:4px 0 0;color:#666;font-size:13px;">{{ 'Generate and manage OpenID Connect Relying Party credentials for external applications.' }}</p>
    </div>
    <a href="{{ route('admin.config.system-settings') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> {{ 'System Settings' }}</a>
</div>

@if(session('generated_oidc_secret'))
    <div class="alert alert-warning" style="border-left:4px solid #d97706;">
        <strong>{{ 'Save Your New Client Secret Now:' }}</strong>
        {{ 'For security, this secret is hashed in storage and will not be displayed again.' }}
        <div style="margin-top:8px;font-family:monospace;font-size:12.5px;background:#fff;padding:8px 12px;border:1px solid #e5e7eb;border-radius:4px;">
            <div><strong>{{ 'Client ID:' }}</strong> {{ session('generated_oidc_client_id') }}</div>
            <div style="margin-top:4px;"><strong>{{ 'Client Secret:' }}</strong> {{ session('generated_oidc_secret') }}</div>
        </div>
    </div>
@endif

<div class="infobox" style="font-size:12.5px;">
    <strong>{{ 'Issuer URL:' }}</strong> <code>{{ url('/') }}</code> &nbsp;|&nbsp;
    <strong>{{ 'Discovery Endpoint:' }}</strong> <code>{{ url('/oauth/.well-known/openid-configuration') }}</code>
</div>

<div class="tablebg" style="margin-bottom:20px;">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th class="text-left">{{ 'Application Name' }}</th>
                <th class="text-left">{{ 'Client Identifier' }}</th>
                <th class="text-left">{{ 'Authorized Redirect URIs' }}</th>
                <th>{{ 'Created' }}</th>
                <th width="110">{{ 'Actions' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clients as $client)
                <tr>
                    <td>
                        <strong>{{ $client['name'] }}</strong>
                        @if(!empty($client['description']))
                            <div style="font-size:11.5px;color:#666;">{{ $client['description'] }}</div>
                        @endif
                    </td>
                    <td style="font-family:monospace;font-size:12px;">{{ $client['client_id'] }}</td>
                    <td style="font-family:monospace;font-size:11.5px;">
                        @foreach(($client['redirect_uris'] ?? []) as $uri)
                            <div>{{ $uri }}</div>
                        @endforeach
                    </td>
                    <td style="text-align:center;font-size:12px;">{{ $client['created_at'] ?? '—' }}</td>
                    <td style="text-align:center;">
                        <form method="POST" action="{{ route('admin.config.openid-connect.destroy', $client['id']) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($client['name']), {title: 'Revoke OpenID Client'})">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">{{ 'Revoke' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;color:#777;padding:22px;">{{ 'No OpenID Connect relying party credentials generated yet.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2 style="font-size:16px;margin:18px 0 10px;color:#1a4d80;">{{ 'Generate New Client API Credentials' }}</h2>
<form method="POST" action="{{ route('admin.config.openid-connect.store') }}">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="22%" class="fieldlabel">{{ 'Application Name' }}</td>
            <td class="fieldarea"><input type="text" name="name" required maxlength="120" class="form-control input-300" placeholder="{{ 'e.g. Control Panel SSO' }}"></td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Description' }}</td>
            <td class="fieldarea"><input type="text" name="description" maxlength="255" class="form-control input-400" placeholder="{{ 'Optional application notes' }}"></td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Authorized Redirect URIs' }}</td>
            <td class="fieldarea">
                <textarea name="redirect_uris" rows="3" required class="form-control" style="max-width:520px;font-family:monospace;font-size:12px;" placeholder="{{ 'https://panel.example.com/oidc/callback' }}"></textarea>
                <div style="font-size:11.5px;color:#666;margin-top:4px;">{{ 'Enter one full HTTPS/HTTP callback URL per line.' }}</div>
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ 'Generate Credentials' }}</button>
    </div>
</form>

@endsection

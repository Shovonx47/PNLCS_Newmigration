@extends('admin.layouts.app')
@section('title', 'Google Drive Backup')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Google Drive Automated Backup</h1>
    <form method="POST" action="{{ route('admin.extensions.gdrive-backup.run') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm" @disabled(! $isConfigured)>Run Backup Now</button>
    </form>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><strong>OAuth2 &amp; Retention Configuration</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.gdrive-backup.settings') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                <div class="form-group">
                    <label class="form-label">OAuth2 Client ID</label>
                    <input type="text" name="client_id" value="{{ $settings['client_id'] ?? '' }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Client Secret (leave blank to keep)</label>
                    <input type="password" name="client_secret" class="form-control" placeholder="{{ filled($settings['client_secret'] ?? '') ? '•••••••• (stored encrypted)' : 'Not configured' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Refresh Token (leave blank to keep)</label>
                    <input type="password" name="refresh_token" class="form-control" placeholder="{{ filled($settings['refresh_token'] ?? '') ? '•••••••• (stored encrypted)' : 'Not configured' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Drive Folder ID</label>
                    <input type="text" name="drive_folder_id" value="{{ $settings['drive_folder_id'] ?? '' }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Backups Retained</label>
                    <input type="number" name="max_backups" min="1" max="365" value="{{ $settings['max_backups'] ?? 10 }}" class="form-control">
                </div>
            </div>
            <div style="display:flex;gap:18px;flex-wrap:wrap;margin:12px 0;">
                <label style="font-size:12.5px;"><input type="checkbox" name="backup_db" value="1" @checked(in_array(strtolower((string)($settings['backup_db'] ?? '1')), ['1','on','true'], true))> Include Database Dump</label>
                <label style="font-size:12.5px;"><input type="checkbox" name="backup_files" value="1" @checked(in_array(strtolower((string)($settings['backup_files'] ?? '1')), ['1','on','true'], true))> Include Private KYC Uploads</label>
                <label style="font-size:12.5px;"><input type="checkbox" name="keep_local" value="1" @checked(in_array(strtolower((string)($settings['keep_local'] ?? '0')), ['1','on','true'], true))> Keep Local Archive Copy</label>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Save Configuration</button>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 16px;">
        <details @if(! empty($prefilledCode)) open @endif>
            <summary style="cursor:pointer;font-weight:600;font-size:13.5px;">OAuth2 Refresh Token Generator (Optional Rotation Helper)</summary>
            <div style="margin-top:12px;font-size:12.5px;color:#555;">
                <p style="margin:0 0 10px;">
                    Authorized Redirect URI: <code>{{ $oauthRedirectUri }}</code>
                    @if(! empty($oauthConsentUrl))
                        &mdash; <a href="{{ $oauthConsentUrl }}" target="_blank" rel="noopener noreferrer" style="font-weight:600;">1. Open Google Consent Screen &rarr;</a>
                    @endif
                </p>
                <form method="POST" action="{{ route('admin.extensions.gdrive-backup.oauth-exchange') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                    @csrf
                    <div class="form-group" style="margin:0;flex:1;min-width:240px;">
                        <label class="form-label">2. Authorization Code</label>
                        <input type="text" name="auth_code" value="{{ $prefilledCode ?? '' }}" required class="form-control" placeholder="Paste Google OAuth2 code parameter">
                    </div>
                    <input type="hidden" name="redirect_uri" value="{{ $oauthRedirectUri }}">
                    <button type="submit" class="btn btn-default btn-sm">Exchange for Refresh Token</button>
                </form>
            </div>
        </details>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Backup History</strong></div>
    <div class="card-body">
        @if($logs->isEmpty())
            <div style="color:#888;font-size:13px;">No Google Drive backups executed yet.</div>
        @else
            <table class="data-table">
                <thead><tr><th>When</th><th>Archive</th><th>Size</th><th>Drive ID</th><th>Status</th><th>Details</th></tr></thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="font-size:12px;color:#666;">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                        <td><code>{{ $log->file_name ?: '—' }}</code></td>
                        <td>{{ $log->size_bytes > 0 ? number_format($log->size_bytes / 1024, 1).' KB' : '—' }}</td>
                        <td><code>{{ $log->drive_file_id ?: '—' }}</code></td>
                        <td><span class="badge {{ $log->status === 'success' ? 'badge-active' : ($log->status === 'running' ? 'badge-pending' : 'badge-cancelled') }}">{{ ucfirst($log->status) }}</span></td>
                        <td style="font-size:12px;">{{ $log->message }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

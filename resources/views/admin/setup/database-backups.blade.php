@extends('admin.layouts.app')
@section('title', 'Database Backups')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
        <h1 style="margin:0;border:0;padding:0;">{{ 'Database Backups' }}</h1>
        <p style="margin:4px 0 0;color:#666;font-size:13px;">{{ 'Configure automated daily gzipped SQL backups, rotation retention, remote FTP delivery, and on-demand snapshots.' }}</p>
    </div>
    <div style="display:flex;gap:8px;">
        <form method="POST" action="{{ route('admin.config.database-backups.run') }}" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-database"></i> {{ 'Generate Backup Now' }}</button>
        </form>
        <a href="{{ route('admin.config.system-settings') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> {{ 'System Settings' }}</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.config.database-backups.save') }}" style="margin-bottom:22px;">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="24%" class="fieldlabel">{{ 'Daily Automated Backups' }}</td>
            <td class="fieldarea">
                <label><input type="checkbox" name="enabled" value="1" @checked($config['enabled'])> {{ 'Enable scheduled daily database backups via cron' }}</label>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Retention Count' }}</td>
            <td class="fieldarea">
                <input type="number" name="retention" value="{{ $config['retention'] }}" min="1" max="365" required class="form-control input-100">
                <span style="font-size:12px;color:#666;margin-left:6px;">{{ 'compressed backup archives kept before automatic rotation' }}</span>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Backup Notification Email' }}</td>
            <td class="fieldarea">
                <input type="email" name="email_to" value="{{ $config['email_to'] }}" class="form-control input-300" placeholder="{{ 'admin@example.com (optional)' }}">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Remote FTP / SFTP Backup' }}</td>
            <td class="fieldarea">
                <label style="display:block;margin-bottom:8px;"><input type="checkbox" name="ftp_enabled" value="1" @checked($config['ftp_enabled'])> {{ 'Upload daily backups to remote FTP/SFTP server' }}</label>
                <div style="display:grid;grid-template-columns:2fr 1fr 1.5fr 1.5fr 2fr;gap:8px;max-width:860px;">
                    <input type="text" name="ftp_host" value="{{ $config['ftp_host'] }}" class="form-control" placeholder="{{ 'FTP Hostname' }}">
                    <input type="number" name="ftp_port" value="{{ $config['ftp_port'] }}" class="form-control" placeholder="{{ '21' }}">
                    <input type="text" name="ftp_user" value="{{ $config['ftp_user'] }}" class="form-control" placeholder="{{ 'FTP Username' }}">
                    <input type="password" name="ftp_pass" value="" class="form-control" autocomplete="new-password" placeholder="{{ $config['ftp_has_pass'] ? '•••••• (Saved)' : 'FTP Password' }}">
                    <input type="text" name="ftp_path" value="{{ $config['ftp_path'] }}" class="form-control" placeholder="{{ '/backups' }}">
                </div>
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ 'Save Backup Configuration' }}</button>
    </div>
</form>

<h2 style="font-size:16px;margin:16px 0 10px;color:#1a4d80;">{{ 'Stored Backup Archives (storage/app/backups/db)' }}</h2>
<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th class="text-left">{{ 'Archive Filename' }}</th>
                <th>{{ 'Created At' }}</th>
                <th>{{ 'File Size' }}</th>
                <th width="180">{{ 'Actions' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($files as $file)
                <tr>
                    <td style="font-family:monospace;font-weight:600;">{{ $file['name'] }}</td>
                    <td style="text-align:center;">{{ $file['modified_at'] }}</td>
                    <td style="text-align:center;">{{ number_format($file['size_kb'], 1) }} KB</td>
                    <td style="text-align:center;white-space:nowrap;">
                        <a href="{{ route('admin.config.database-backups.download', $file['name']) }}" class="btn btn-primary btn-xs">
                            <i class="fas fa-download"></i> {{ 'Download' }}
                        </a>
                        <form method="POST" action="{{ route('admin.config.database-backups.destroy', $file['name']) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($file['name']), {title: 'Delete Backup Archive'})">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">{{ 'Delete' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:#777;padding:22px;">{{ 'No database backup archives stored yet. Click "Generate Backup Now" above to create an immediate snapshot.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

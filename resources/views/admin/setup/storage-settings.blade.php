@extends('admin.layouts.app')
@section('title', 'Storage Settings')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <div>
        <h1 style="margin:0;border:0;padding:0;">{{ 'Storage Settings' }}</h1>
        <p style="margin:4px 0 0;color:#666;font-size:13px;">{{ 'Configure where downloadable assets, ticket attachments, invoice PDFs, and backups are stored.' }}</p>
    </div>
    <a href="{{ route('admin.config.system-settings') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> {{ 'System Settings' }}</a>
</div>

<form method="POST" action="{{ route('admin.config.storage-settings.save') }}">
    @csrf
    <h2 style="font-size:16px;margin:10px 0 10px;color:#1a4d80;">{{ 'Asset Storage Mapping' }}</h2>
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom:20px;">
        @php
            $assetTypes = [
                'downloads' => 'Client Downloads Library',
                'ticket_attachments' => 'Support Ticket Attachments',
                'invoices' => 'Generated Invoice & Quote PDFs',
                'email_attachments' => 'Email Campaign & Template Attachments',
                'backups' => 'Automated Database Backup Archives',
            ];
        @endphp
        @foreach($assetTypes as $field => $label)
            <tr>
                <td width="28%" class="fieldlabel">{{ $label }}</td>
                <td class="fieldarea">
                    <select name="{{ $field }}" class="form-control select-inline">
                        <option value="local" @selected($storage[$field] === 'local')>{{ 'Local Private Storage (' . $storage['local_path'] . ')' }}</option>
                        <option value="s3" @selected($storage[$field] === 's3')>{{ 'S3-Compatible Object Storage' }}</option>
                    </select>
                </td>
            </tr>
        @endforeach
    </table>

    <h2 style="font-size:16px;margin:18px 0 10px;color:#1a4d80;">{{ 'Storage Configurations' }}</h2>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px;">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:4px;padding:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <strong style="font-size:14.5px;color:#1e293b;"><i class="fas fa-hdd" style="color:#1a4d80;margin-right:6px;"></i> {{ 'Local Disk (Private)' }}</strong>
                <span class="whmcs-status active">{{ 'Active' }}</span>
            </div>
            <div style="font-size:12.5px;color:#475569;line-height:1.6;">
                <div><strong>{{ 'Root Directory:' }}</strong> <code>{{ $storage['local_path'] }}</code></div>
                @if($storage['free_gb'] !== null)
                    <div><strong>{{ 'Available Capacity:' }}</strong> {{ $storage['free_gb'] }} GB free of {{ $storage['total_gb'] }} GB</div>
                @endif
                <div style="color:#64748b;margin-top:6px;">{{ 'Stored outside public webroot with authenticated download enforcement.' }}</div>
            </div>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:4px;padding:16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <strong style="font-size:14.5px;color:#1e293b;"><i class="fas fa-cloud" style="color:#0284c7;margin-right:6px;"></i> {{ 'Amazon S3 / S3-Compatible Storage' }}</strong>
                <label style="font-size:12px;font-weight:600;margin:0;">
                    <input type="checkbox" name="s3_enabled" value="1" @checked($storage['s3_enabled'])> {{ 'Enable S3' }}
                </label>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <input type="text" name="s3_bucket" value="{{ $storage['s3_bucket'] }}" class="form-control" placeholder="{{ 'Bucket Name' }}">
                <input type="text" name="s3_region" value="{{ $storage['s3_region'] }}" class="form-control" placeholder="{{ 'Region (e.g. us-east-1)' }}">
                <input type="text" name="s3_access_key" value="{{ $storage['s3_access_key'] }}" class="form-control" placeholder="{{ 'Access Key ID' }}">
                <input type="password" name="s3_secret_key" value="" class="form-control" autocomplete="new-password" placeholder="{{ $storage['s3_has_secret'] ? '•••••• (Secret Set)' : 'Secret Access Key' }}">
                <input type="url" name="s3_endpoint" value="{{ $storage['s3_endpoint'] }}" class="form-control" style="grid-column:span 2;" placeholder="{{ 'Custom S3 Endpoint URL (optional — Cloudflare R2 / MinIO / Wasabi)' }}">
            </div>
        </div>
    </div>

    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ 'Save Storage Settings' }}</button>
    </div>
</form>

@endsection

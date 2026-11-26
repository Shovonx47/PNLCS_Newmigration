@extends('admin.layouts.app')
@section('title', 'Softcents SMS')

@section('content')

<div class="page-header">
    <h1>Softcents SMS</h1>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:15px;">
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Gateway balance</div>
        <div style="font-size:22px;font-weight:700;color:{{ $balance['success'] ? '#065f46' : '#991b1b' }};">
            {{ $balance['success'] ? $balance['balance'] : '—' }}
        </div>
        @unless($balance['success'])
        <div style="font-size:11px;color:#991b1b;">{{ $balance['error'] }}</div>
        @endunless
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Sent today</div>
        <div style="font-size:22px;font-weight:700;">{{ $sentToday }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Failed today</div>
        <div style="font-size:22px;font-weight:700;color:{{ $failedToday > 0 ? '#991b1b' : '#111827' }};">{{ $failedToday }}</div>
    </div></div>
    <div class="card"><div class="card-body">
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6b7280;">Customer numbers</div>
        <div style="font-size:22px;font-weight:700;">{{ $clientCount }}</div>
    </div></div>
</div>

<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Gateway &amp; registration OTP</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.softcents-sms.settings') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Client ID</label>
                    <input type="text" name="client_id" class="form-control" value="{{ $settings->client_id }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Client secret</label>
                    <input type="password" name="client_secret" class="form-control" autocomplete="new-password"
                           placeholder="{{ $settings->client_secret ? '•••••••• (saved — leave blank to keep)' : 'Not set' }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Sender ID</label>
                    <input type="text" name="sender_id" class="form-control" value="{{ $settings->sender_id }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Date format</label>
                    <input type="text" name="date_format" class="form-control" value="{{ $settings->date_format }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Admin alert numbers</label>
                    <input type="text" name="admin_numbers" class="form-control" value="{{ $settings->admin_numbers }}"
                           placeholder="01711223344, 01811223344">
                    <div style="font-size:11px;color:#6b7280;margin-top:3px;">Comma separated. Used by admin templates only.</div>
                </div>
            </div>

            <hr style="margin:14px 0;border:0;border-top:1px solid #e5e7eb;">

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;">
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="otp_enabled" value="1" {{ $settings->otp_enabled ? 'checked' : '' }}>
                    Require SMS OTP on registration
                </label>
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="otp_checkout_enabled" value="1" {{ $settings->otp_checkout_enabled ? 'checked' : '' }}>
                    Also on checkout sign-up
                </label>
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;">
                    <input type="checkbox" name="admin_alerts" value="1" {{ $settings->admin_alerts ? 'checked' : '' }}>
                    Send admin alert templates
                </label>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-top:12px;">
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">OTP length</label>
                    <input type="number" name="otp_length" min="4" max="8" class="form-control" value="{{ $settings->otp_length }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Expiry (minutes)</label>
                    <input type="number" name="otp_expiry_minutes" min="1" max="60" class="form-control" value="{{ $settings->otp_expiry_minutes }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Resend cooldown (s)</label>
                    <input type="number" name="otp_resend_cooldown" min="0" max="600" class="form-control" value="{{ $settings->otp_resend_cooldown }}">
                </div>
                <div class="form-group">
                    <label style="font-size:12px;font-weight:600;">Max per hour</label>
                    <input type="number" name="otp_max_per_hour" min="1" max="50" class="form-control" value="{{ $settings->otp_max_per_hour }}">
                </div>
            </div>

            <div class="form-group">
                <label style="font-size:12px;font-weight:600;">OTP message</label>
                <textarea name="otp_template" class="form-control" rows="2">{{ $settings->otp_template }}</textarea>
                <div style="font-size:11px;color:#6b7280;margin-top:3px;">Placeholders: {otp} {minutes} {companyname}</div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Save settings</button>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Send a test message</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.softcents-sms.test') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
            @csrf
            <div class="form-group" style="margin:0;width:200px;">
                <label style="font-size:12px;font-weight:600;">Number</label>
                <input type="text" name="number" class="form-control" placeholder="01711223344" required>
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:260px;">
                <label style="font-size:12px;font-weight:600;">Message</label>
                <input type="text" name="message" class="form-control" maxlength="600" required>
            </div>
            <button type="submit" class="btn btn-default btn-sm">Send</button>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom:15px;">
    <div class="card-header">
        <strong>Templates</strong>
        <span style="font-size:12px;color:#666;">{{ $templates->where('active', true)->count() }} active of {{ $templates->count() }} — names match the events that fire them.</span>
    </div>
    <div class="card-body" style="padding:0;">
        @foreach($templates as $template)
        <form method="POST" action="{{ route('admin.extensions.softcents-sms.templates.update', $template) }}"
              style="border-bottom:1px solid #f0f0f0;padding:10px 14px;">
            @csrf
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:6px;">
                <strong style="font-size:13px;min-width:210px;">{{ $template->name }}</strong>
                <span style="font-size:11px;padding:1px 8px;border-radius:10px;background:{{ $template->type === 'admin' ? '#fef3c7' : '#dbeafe' }};color:{{ $template->type === 'admin' ? '#92400e' : '#1e40af' }};">{{ $template->type }}</span>
                <label style="display:flex;gap:6px;align-items:center;font-size:12px;color:#374151;">
                    <input type="checkbox" name="active" value="1" {{ $template->active ? 'checked' : '' }}> active
                </label>
                <span style="font-size:11px;color:#9ca3af;">{{ $template->description }}</span>
                <button type="submit" class="btn btn-default btn-xs" style="margin-left:auto;">Save</button>
            </div>
            <textarea name="template" class="form-control" rows="3" style="font-size:12px;font-family:monospace;">{{ $template->template }}</textarea>
            <div style="font-size:11px;color:#9ca3af;margin-top:3px;">{{ $template->variables }}</div>
        </form>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Recent messages</strong></div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
        <table class="table table-striped" style="margin:0;font-size:12px;">
            <thead>
                <tr>
                    <th>When</th><th>To</th><th>Template</th><th>Status</th><th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="white-space:nowrap;">{{ $log->created_at?->format('d M H:i') }}</td>
                    <td>{{ $log->to_number }}</td>
                    <td>{{ $log->template_name ?: '—' }}</td>
                    <td style="color:{{ $log->status === 'sent' ? '#065f46' : '#991b1b' }};font-weight:600;">{{ $log->status }}</td>
                    <td style="max-width:520px;color:#6b7280;">{{ $log->error ?: \Illuminate\Support\Str::limit($log->message, 90) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:14px;color:#9ca3af;">No messages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

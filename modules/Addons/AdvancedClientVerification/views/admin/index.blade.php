@extends('admin.layouts.app')
@section('title', 'Client Verification (KYC)')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Client Verification (KYC)</h1>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('admin.extensions.kyc.export', array_filter(['status' => $status])) }}" class="btn btn-default btn-sm">Export CSV</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px;">
    <div class="card"><div class="card-body" style="padding:12px 16px;"><div style="font-size:11px;color:#777;text-transform:uppercase;">Total Records</div><div style="font-size:22px;font-weight:700;">{{ $counts['all'] }}</div></div></div>
    <div class="card"><div class="card-body" style="padding:12px 16px;"><div style="font-size:11px;color:#777;text-transform:uppercase;">Verified</div><div style="font-size:22px;font-weight:700;color:#15803d;">{{ $counts['verified'] }}</div></div></div>
    <div class="card"><div class="card-body" style="padding:12px 16px;"><div style="font-size:11px;color:#777;text-transform:uppercase;">Pending Review</div><div style="font-size:22px;font-weight:700;color:#b45309;">{{ $counts['pending'] }}</div></div></div>
    <div class="card"><div class="card-body" style="padding:12px 16px;"><div style="font-size:11px;color:#777;text-transform:uppercase;">Declined / Failed</div><div style="font-size:22px;font-weight:700;color:#b91c1c;">{{ $counts['failed'] }}</div></div></div>
</div>

<details class="card" style="margin-bottom:16px;">
    <summary class="card-header" style="cursor:pointer;"><strong>Verification &amp; Policy Settings</strong></summary>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.kyc.settings') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                <div class="form-group">
                    <label class="form-label">Verification Mode</label>
                    <select name="verification_mode" class="form-control">
                        <option value="hybrid" @selected(($settings['verification_mode'] ?? '') === 'hybrid')>Hybrid (Didit API + Manual Upload)</option>
                        <option value="api_only" @selected(($settings['verification_mode'] ?? '') === 'api_only')>Didit API Only</option>
                        <option value="manual_only" @selected(($settings['verification_mode'] ?? '') === 'manual_only')>Manual Upload Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Didit API Key (leave blank to keep stored)</label>
                    <input type="password" name="api_key" class="form-control" placeholder="{{ filled($settings['api_key'] ?? '') ? '•••••••• (configured)' : 'Not set' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Didit Workflow ID</label>
                    <input type="text" name="workflow_id" value="{{ $settings['workflow_id'] ?? '' }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Webhook Secret (leave blank to keep stored)</label>
                    <input type="password" name="webhook_secret" class="form-control" placeholder="{{ filled($settings['webhook_secret'] ?? '') ? '•••••••• (configured)' : 'Not set' }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Checkout Requirement</label>
                    <select name="verification_requirement" class="form-control">
                        <option value="disabled" @selected(($settings['verification_requirement'] ?? '') === 'disabled')>Disabled</option>
                        <option value="all_clients" @selected(($settings['verification_requirement'] ?? '') === 'all_clients')>All Orders</option>
                        <option value="restricted_groups" @selected(($settings['verification_requirement'] ?? '') === 'restricted_groups')>Restricted Product Groups</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Restricted Group IDs</label>
                    <input type="text" name="restricted_groups" value="{{ $settings['restricted_groups'] ?? '' }}" class="form-control" placeholder="e.g. 1, 4, 7">
                    @if($productGroups->isNotEmpty())
                        <small style="color:#777;">Groups: {{ $productGroups->map(fn($g) => $g->id.':'.$g->name)->take(6)->implode(', ') }}</small>
                    @endif
                </div>
                <div class="form-group">
                    <label class="form-label">Deadline Days</label>
                    <input type="number" name="verification_deadline_days" min="1" max="90" value="{{ $settings['verification_deadline_days'] ?? 7 }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Max Upload Size (MB)</label>
                    <input type="number" name="max_file_size" min="1" max="10" value="{{ $settings['max_file_size'] ?? 5 }}" class="form-control">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Business Types Requiring Trade License</label>
                    <input type="text" name="business_types" value="{{ $settings['business_types'] ?? '' }}" class="form-control">
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:16px;margin:12px 0;">
                @foreach([
                    'client_access' => 'Client Portal Access',
                    'auto_approve' => 'Auto-Approve Webhook Pass',
                    'require_selfie' => 'Require Selfie',
                    'require_document_back' => 'Require Document Back',
                    'require_trade_license' => 'Require Trade License',
                    'enable_service_suspension' => 'Suspend Past Deadline',
                    'enable_daily_reminders' => 'Daily Reminders',
                ] as $flag => $label)
                    <label style="font-size:12.5px;display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" name="{{ $flag }}" value="1" @checked(in_array(strtolower((string)($settings[$flag] ?? '0')), ['1','on','true','yes'], true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <div style="font-size:12px;color:#666;margin-bottom:10px;">Webhook endpoint: <code>{{ url('/kyc/didit/webhook') }}</code></div>
            <button type="submit" class="btn btn-primary btn-sm">Save Settings</button>
        </form>
    </div>
</details>

<div style="display:flex;gap:8px;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;">
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
        @foreach(['' => 'All', 'pending' => 'Pending', 'verified' => 'Verified', 'failed' => 'Declined', 'not_started' => 'Not Started'] as $k => $lbl)
            <a href="{{ route('admin.extensions.kyc.index', array_filter(['status' => $k, 'q' => $search])) }}"
               class="btn {{ $status === $k ? 'btn-primary' : 'btn-default' }} btn-sm">{{ $lbl }}</a>
        @endforeach
    </div>
    <form method="GET" action="{{ route('admin.extensions.kyc.index') }}" style="display:flex;gap:6px;">
        @if($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
        <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search client, email, ID..." style="width:220px;">
        <button type="submit" class="btn btn-default btn-sm">Search</button>
    </form>
</div>

<div class="card">
    <div class="card-body">
        @if($verifications->isEmpty())
            <div style="text-align:center;padding:36px;color:#888;">No verification records match your filter.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Method</th>
                        <th>Document</th>
                        <th>Files</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($verifications as $v)
                    <tr>
                        <td>{{ $v->id }}</td>
                        <td>
                            @if($v->client)
                                <a href="{{ route('admin.clients.show', $v->client) }}">{{ trim($v->client->first_name.' '.$v->client->last_name) }}</a>
                                <div style="font-size:11.5px;color:#777;">{{ $v->client->email }}</div>
                            @else
                                Client #{{ $v->client_id }}
                            @endif
                        </td>
                        <td>{{ strtoupper($v->verification_method) }}</td>
                        <td>{{ $v->document_type ? ucwords(str_replace('_', ' ', $v->document_type)) : '—' }}</td>
                        <td>{{ $v->documents_count }}</td>
                        <td>
                            <span class="badge {{ $v->status === 'verified' ? 'badge-active' : ($v->status === 'pending' ? 'badge-pending' : 'badge-cancelled') }}">
                                {{ ucfirst(str_replace('_', ' ', $v->status)) }}
                            </span>
                        </td>
                        <td style="font-size:12px;color:#666;">{{ $v->updated_at?->format('d M Y, H:i') }}</td>
                        <td style="text-align:right;white-space:nowrap;">
                            <a href="{{ route('admin.extensions.kyc.show', $v) }}" class="btn btn-default btn-xs">Review</a>
                            @if($v->status !== 'verified')
                                <form method="POST" action="{{ route('admin.extensions.kyc.approve', $v) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-xs">Approve</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:14px;">{{ $verifications->links() }}</div>
        @endif
    </div>
</div>
@endsection

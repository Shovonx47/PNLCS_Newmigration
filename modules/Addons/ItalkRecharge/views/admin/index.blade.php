@extends('admin.layouts.app')
@section('title', 'IPTSP Recharge')

@section('content')

<div class="page-header">
    <h1>IPTSP Recharge</h1>
</div>

<p style="color:#666;font-size:13px;margin-bottom:15px;">
    Multi-provider IP number top-ups. Credentials are encrypted at rest and never shown back.
    A paid top-up invoice runs the recharge automatically when a matching PIN account exists.
</p>

{{-- Providers --}}
<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Providers</strong></div>
    <div class="card-body">
        @if($providers->isEmpty())
        <div style="color:#999;font-size:13px;margin-bottom:12px;">No providers yet.</div>
        @else
        <table class="data-table" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Driver</th>
                    <th>Base URL</th>
                    <th>Username</th>
                    <th>Secret</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($providers as $provider)
                <tr>
                    <td>{{ $provider->name }}</td>
                    <td>{{ $apiTypes[$provider->api_type] ?? $provider->api_type }}</td>
                    <td style="font-family:ui-monospace,Menlo,monospace;font-size:11.5px;word-break:break-all;">{{ $provider->base_url }}{{ $provider->base_path }}</td>
                    <td>{{ $provider->username }}</td>
                    <td>{{ $provider->password ? 'stored' : '—' }}</td>
                    <td><span class="badge {{ $provider->isActive() ? 'badge-active' : 'badge-cancelled' }}">{{ ucfirst($provider->status) }}</span></td>
                    <td style="text-align:right;white-space:nowrap;">
                        <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.providers.update', $provider) }}" style="display:inline;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" value="{{ $provider->name }}">
                            <input type="hidden" name="base_url" value="{{ $provider->base_url }}">
                            <input type="hidden" name="base_path" value="{{ $provider->base_path }}">
                            <input type="hidden" name="api_type" value="{{ $provider->api_type }}">
                            <input type="hidden" name="username" value="{{ $provider->username }}">
                            <input type="hidden" name="status" value="{{ $provider->isActive() ? 'inactive' : 'active' }}">
                            <button type="submit" class="btn btn-default btn-xs">{{ $provider->isActive() ? 'Disable' : 'Enable' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.providers.destroy', $provider) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js('Remove this provider? Its PIN accounts are disabled and the log is kept.'))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.providers.store') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;align-items:end;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. CODE 09644" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Base URL (https)</label>
                <input type="url" name="base_url" class="form-control" placeholder="https://sip.italk.com.bd" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Base path</label>
                <input type="text" name="base_path" class="form-control" placeholder="/iconiptsp/">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Driver</label>
                <select name="api_type" class="form-control">
                    @foreach($apiTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Password</label>
                <input type="password" name="password" class="form-control" required autocomplete="new-password">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">API key (optional)</label>
                <input type="password" name="api_key" class="form-control" autocomplete="new-password">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Status</label>
                <select name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Add provider</button>
        </form>
    </div>
</div>

{{-- Client PIN accounts --}}
<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Client PIN accounts</strong></div>
    <div class="card-body">
        @if($accounts->isEmpty())
        <div style="color:#999;font-size:13px;margin-bottom:12px;">No client PIN accounts yet.</div>
        @else
        <table class="data-table" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Provider</th>
                    <th>PIN</th>
                    <th>Total recharged</th>
                    <th>Last recharge</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts as $account)
                <tr>
                    <td>{{ $account->client ? trim($account->client->first_name.' '.$account->client->last_name) : '#'.$account->client_id }}</td>
                    <td>{{ $account->provider?->name ?? '—' }}</td>
                    <td style="font-family:ui-monospace,Menlo,monospace;">{{ $account->pin_number }}</td>
                    <td>৳{{ number_format((float) $account->total_recharged, 2) }}</td>
                    <td style="font-size:12px;color:#666;">{{ $account->last_recharge?->format('d M Y, H:i') ?? 'never' }}</td>
                    <td><span class="badge {{ $account->status === 'active' ? 'badge-active' : 'badge-cancelled' }}">{{ ucfirst($account->status) }}</span></td>
                    <td style="text-align:right;">
                        <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.accounts.destroy', $account) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js('Remove this client PIN account?'))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.accounts.store') }}" style="display:grid;grid-template-columns:2fr 2fr 1fr auto;gap:10px;align-items:end;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Customer</label>
                <select name="client_id" class="form-control" required>
                    <option value="">Select a customer…</option>
                    @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ trim($client->first_name.' '.$client->last_name) }}{{ $client->company_name ? ' ('.$client->company_name.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Provider</label>
                <select name="provider_id" class="form-control" required>
                    @foreach($providers as $provider)
                    <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">PIN / IP number</label>
                <input type="text" name="pin_number" class="form-control" placeholder="096XXXXXXXX" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Save PIN</button>
        </form>
    </div>
</div>

{{-- Manual top-up --}}
<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Manual top-up</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.recharge') }}" style="display:grid;grid-template-columns:2fr 2fr 1fr 1fr auto;gap:10px;align-items:end;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Customer</label>
                <select name="client_id" class="form-control" required>
                    <option value="">Select a customer…</option>
                    @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ trim($client->first_name.' '.$client->last_name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Provider</label>
                <select name="provider_id" class="form-control" required>
                    @foreach($providers as $provider)
                    <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">PIN / IP number</label>
                <input type="text" name="pin_number" class="form-control" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Amount (BDT)</label>
                <input type="number" name="amount" step="0.01" min="1" max="{{ (int) $maxAmount }}" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm" onclick="return pnConfirm(event, @js('Send this top-up to the provider now?'))">Recharge</button>
        </form>
        <p style="font-size:12px;color:#666;margin:8px 0 0;">A single top-up is capped at ৳{{ number_format($maxAmount, 2) }}. Every attempt is written to the log below before the provider is called.</p>
    </div>
</div>

{{-- Audit log --}}
<div class="card">
    <div class="card-header"><strong>Recharge log</strong></div>
    <div class="card-body">
        @if($logs->isEmpty())
        <div style="color:#999;font-size:13px;">No recharge attempts yet.</div>
        @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Provider</th>
                    <th>PIN</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Attempts</th>
                    <th>Last attempt</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>{{ $log->client ? trim($log->client->first_name.' '.$log->client->last_name) : '#'.$log->client_id }}</td>
                    <td>{{ $log->provider?->name ?? '—' }}</td>
                    <td style="font-family:ui-monospace,Menlo,monospace;">{{ $log->pin_number ?? '—' }}</td>
                    <td>৳{{ number_format((float) $log->amount, 2) }}</td>
                    <td>
                        <span class="badge {{ $log->status === 'success' ? 'badge-active' : ($log->status === 'failed' ? 'badge-cancelled' : 'badge-pending') }}">{{ ucfirst($log->status) }}</span>
                        @if($log->api_response)
                        <div style="font-size:11px;color:#666;max-width:320px;word-break:break-word;">{{ \Illuminate\Support\Str::limit($log->api_response, 160) }}</div>
                        @endif
                    </td>
                    <td>{{ $log->attempts }}</td>
                    <td style="font-size:12px;color:#666;">{{ $log->last_attempt_at?->format('d M Y, H:i') ?? '—' }}</td>
                    <td style="text-align:right;">
                        @if($log->status !== 'success')
                        <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.logs.retry', $log) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-default btn-xs">Retry</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@endsection

@extends('admin.layouts.app')
@section('title', 'History: ' . $provider->name)

@section('content')
<style>
    .history-container {
        background: #fff;
        border: 1px solid #e6e8ec;
        border-radius: 6px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        padding: 22px;
        margin-bottom: 18px;
    }
    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #f0f2f5;
        padding-bottom: 14px;
        margin-bottom: 20px;
    }
    .history-header h2 { margin: 0; font-size: 20px; font-weight: 500; }
    .history-header h2 strong { color: #3498db; }
    .iptsp-stats-grid { display: flex; gap: 16px; margin-bottom: 22px; flex-wrap: wrap; }
    .iptsp-stat-box {
        flex: 1 1 160px;
        padding: 15px 18px;
        border-radius: 6px;
        text-align: center;
        background: #f8f9fa;
        border-top: 4px solid #bdc3c7;
    }
    .iptsp-stat-box .number { font-size: 26px; font-weight: 700; }
    .iptsp-stat-box .lbl { font-size: 13px; color: #7f8c8d; }
    .iptsp-stat-total { border-top-color: #3498db; }
    .iptsp-stat-total .number { color: #2980b9; }
    .iptsp-stat-success { border-top-color: #27ae60; background: #f0fff4; }
    .iptsp-stat-success .number { color: #27ae60; }
    .iptsp-stat-pending { border-top-color: #f39c12; background: #fffef5; }
    .iptsp-stat-pending .number { color: #f39c12; }
    .iptsp-stat-failed { border-top-color: #e74c3c; background: #fff5f5; }
    .iptsp-stat-failed .number { color: #e74c3c; }
</style>

<h1 style="font-size:26px;font-weight:400;color:#333;margin:0 0 14px 0;">Multi-Provider Auto Recharge</h1>

<div class="history-container">
    <div class="history-header">
        <h2><i class="fas fa-history" style="color:#3498db;"></i> History: <strong>{{ $provider->name }}</strong></h2>
        <a href="{{ route('admin.extensions.iptsp-recharge.index') }}" class="btn btn-default btn-sm">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="iptsp-stats-grid">
        <div class="iptsp-stat-box iptsp-stat-total">
            <div class="number">৳{{ number_format($totalAmount, 2) }}</div>
            <div class="lbl">Total Recharged</div>
        </div>
        <div class="iptsp-stat-box iptsp-stat-success">
            <div class="number">{{ $successCount }}</div>
            <div class="lbl">&#9989; Success</div>
        </div>
        <div class="iptsp-stat-box iptsp-stat-pending">
            <div class="number">{{ $pendingCount }}</div>
            <div class="lbl">&#9203; Pending</div>
        </div>
        <div class="iptsp-stat-box iptsp-stat-failed">
            <div class="number">{{ $failedCount }}</div>
            <div class="lbl">&#10060; Failed</div>
        </div>
    </div>

    @if($logs->isEmpty())
        <div style="text-align:center;padding:30px;color:#95a5a6;">No recharge history found.</div>
    @else
        <div class="tablebg">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th>#</th>
                        <th class="text-left">Client</th>
                        <th class="text-left">Email</th>
                        <th>PIN</th>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td style="text-align:center;"><strong>{{ $log->id }}</strong></td>
                            <td>
                                @if($log->client)
                                    <a href="{{ route('admin.clients.show', $log->client) }}">{{ $log->client->full_name }}</a>
                                @else
                                    #{{ $log->client_id }}
                                @endif
                            </td>
                            <td>{{ $log->client?->email ?? 'N/A' }}</td>
                            <td style="text-align:center;font-family:monospace;">{{ $log->pin_number ?: 'N/A' }}</td>
                            <td style="text-align:center;">
                                @if($log->invoice_id)
                                    <a href="{{ route('admin.invoices.show', $log->invoice_id) }}">#{{ $log->invoice_id }}</a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td style="text-align:center;"><strong>{{ number_format((float) $log->amount, 2) }}</strong> BDT</td>
                            <td style="text-align:center;">
                                <span class="whmcs-status {{ $log->status === 'success' ? 'active' : ($log->status === 'failed' ? 'terminated' : 'pending') }}">{{ ucfirst($log->status) }}</span>
                                @if($log->api_response)
                                    <div style="font-size:11px;color:#666;max-width:260px;margin:2px auto 0;word-break:break-word;">{{ \Illuminate\Support\Str::limit($log->api_response, 120) }}</div>
                                @endif
                            </td>
                            <td style="text-align:center;">{{ $log->attempts }}</td>
                            <td style="text-align:center;font-size:12px;color:#666;">{{ ($log->last_attempt_at ?? $log->created_at)?->format('d M Y, H:i') ?? '—' }}</td>
                            <td style="text-align:center;">
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
        </div>
    @endif
</div>

{{-- Manual Top-up & Client PIN Accounts for this Provider --}}
<div class="history-container">
    <div class="history-header">
        <h2><i class="fas fa-bolt" style="color:#27ae60;"></i> Manual Top-Up ({{ $provider->name }})</h2>
    </div>
    <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.recharge') }}" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end;">
        @csrf
        <input type="hidden" name="provider_id" value="{{ $provider->id }}">
        <div>
            <label style="font-size:12px;font-weight:600;">Customer</label>
            <select name="client_id" class="form-control" required>
                <option value="">Select a customer...</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ trim($client->first_name.' '.$client->last_name) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;">PIN / IP number</label>
            <input type="text" name="pin_number" class="form-control" required>
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;">Amount (BDT)</label>
            <input type="number" name="amount" step="0.01" min="1" max="{{ (int) $maxAmount }}" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-sm" onclick="return pnConfirm(event, @js('Send this top-up to the provider now?'))">Recharge</button>
    </form>
</div>

<div class="history-container">
    <div class="history-header">
        <h2><i class="fas fa-id-card" style="color:#3498db;"></i> Client PIN Accounts ({{ $provider->name }})</h2>
    </div>
    <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.accounts.store') }}" style="display:grid;grid-template-columns:2fr 1fr auto;gap:10px;align-items:end;margin-bottom:14px;">
        @csrf
        <input type="hidden" name="provider_id" value="{{ $provider->id }}">
        <div>
            <label style="font-size:12px;font-weight:600;">Customer</label>
            <select name="client_id" class="form-control" required>
                <option value="">Select a customer...</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}">{{ trim($client->first_name.' '.$client->last_name) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:12px;font-weight:600;">PIN / IP number</label>
            <input type="text" name="pin_number" class="form-control" placeholder="096XXXXXXXX" required>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Save PIN</button>
    </form>

    @if($accounts->isNotEmpty())
        <div class="tablebg">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th class="text-left">Customer</th>
                        <th>PIN</th>
                        <th>Total recharged</th>
                        <th>Last recharge</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $account)
                        <tr>
                            <td>{{ $account->client?->full_name ?? '#'.$account->client_id }}</td>
                            <td style="text-align:center;font-family:monospace;">{{ $account->pin_number }}</td>
                            <td style="text-align:center;">৳{{ number_format((float) $account->total_recharged, 2) }}</td>
                            <td style="text-align:center;font-size:12px;color:#666;">{{ $account->last_recharge?->format('d M Y, H:i') ?? 'never' }}</td>
                            <td style="text-align:center;"><span class="whmcs-status {{ $account->status === 'active' ? 'active' : 'cancelled' }}">{{ ucfirst($account->status) }}</span></td>
                            <td style="text-align:center;">
                                <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.accounts.destroy', $account) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js('Remove this client PIN account?'), { danger: true })">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-xs">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@extends('admin.layouts.app')
@section('title', 'Pending Recharge Logs')

@section('content')
<style>
    .pending-container {
        background: #fff;
        border: 1px solid #e6e8ec;
        border-radius: 6px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        padding: 22px;
        margin-bottom: 18px;
    }
    .pending-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #f0f2f5;
        padding-bottom: 14px;
        margin-bottom: 18px;
    }
    .pending-header h2 { margin: 0; font-size: 20px; font-weight: 500; color: #2c3e50; }
    .iptsp-summary-cards { display: flex; gap: 18px; margin-bottom: 22px; flex-wrap: wrap; }
    .iptsp-summary-card {
        background: #f8f9fa;
        padding: 15px 22px;
        border-radius: 6px;
        flex: 1 1 220px;
        text-align: center;
        border-left: 4px solid #f39c12;
    }
    .iptsp-summary-card.amount { border-left-color: #e67e22; background: #fef9e7; }
    .iptsp-summary-card .number { font-size: 26px; font-weight: 700; color: #2c3e50; }
    .iptsp-summary-card .lbl { font-size: 13px; color: #7f8c8d; }
</style>

<h1 style="font-size:26px;font-weight:400;color:#333;margin:0 0 14px 0;">Multi-Provider Auto Recharge</h1>

<div class="pending-container">
    <div class="pending-header">
        <h2><i class="far fa-clock" style="color:#f39c12;"></i> Pending Recharge Logs</h2>
        <a href="{{ route('admin.extensions.iptsp-recharge.index') }}" class="btn btn-default btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Providers
        </a>
    </div>

    <div class="iptsp-summary-cards">
        <div class="iptsp-summary-card">
            <div class="number">{{ $totalPendingCount }}</div>
            <div class="lbl">&#9203; Total Pending Requests</div>
        </div>
        <div class="iptsp-summary-card amount">
            <div class="number">৳{{ number_format($totalPendingAmount, 2) }}</div>
            <div class="lbl">&#128176; Total Pending Amount</div>
        </div>
    </div>

    @if($pendingLogs->isEmpty())
        <div style="text-align:center;padding:36px;color:#27ae60;">
            <i class="fas fa-check-circle" style="font-size:40px;display:block;margin-bottom:8px;"></i>
            <p style="margin:0;font-size:16px;font-weight:500;">No pending logs. Everything is clean!</p>
        </div>
    @else
        <div class="tablebg">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th>#</th>
                        <th class="text-left">Client</th>
                        <th class="text-left">Email</th>
                        <th>Provider</th>
                        <th>PIN</th>
                        <th>Amount (BDT)</th>
                        <th>Invoice</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingLogs as $log)
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
                            <td style="text-align:center;">{{ $log->provider?->name ?? 'Unknown' }}</td>
                            <td style="text-align:center;font-family:monospace;">{{ $log->pin_number ?: 'N/A' }}</td>
                            <td style="text-align:center;"><strong>৳{{ number_format((float) $log->amount, 2) }}</strong></td>
                            <td style="text-align:center;">
                                @if($log->invoice_id)
                                    <a href="{{ route('admin.invoices.show', $log->invoice_id) }}">#{{ $log->invoice_id }}</a>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td style="text-align:center;font-size:12px;color:#7f8c8d;">{{ $log->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                            <td style="text-align:center;white-space:nowrap;">
                                <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.logs.retry', $log) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-xs"><i class="fas fa-sync"></i> Retry</button>
                                </form>
                                <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.logs.resolve', $log) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js('Set this log as SUCCESS?'))">
                                    @csrf
                                    <input type="hidden" name="status" value="success">
                                    <button type="submit" class="btn btn-success btn-xs"><i class="fas fa-check"></i> Success</button>
                                </form>
                                <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.logs.resolve', $log) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js('Set this log as FAILED?'), { danger: true })">
                                    @csrf
                                    <input type="hidden" name="status" value="failed">
                                    <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-times"></i> Failed</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Manual Top-up & Client PIN Accounts --}}
<div class="pending-container">
    <div class="pending-header">
        <h2><i class="fas fa-bolt" style="color:#27ae60;"></i> Manual Top-Up</h2>
    </div>
    <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.recharge') }}" style="display:grid;grid-template-columns:2fr 2fr 1fr 1fr auto;gap:10px;align-items:end;">
        @csrf
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
            <label style="font-size:12px;font-weight:600;">Provider</label>
            <select name="provider_id" class="form-control" required>
                @foreach($providers as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
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

<div class="pending-container">
    <div class="pending-header">
        <h2><i class="fas fa-id-card" style="color:#3498db;"></i> Client PIN Accounts</h2>
    </div>
    <form method="POST" action="{{ route('admin.extensions.iptsp-recharge.accounts.store') }}" style="display:grid;grid-template-columns:2fr 2fr 1fr auto;gap:10px;align-items:end;margin-bottom:16px;">
        @csrf
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
            <label style="font-size:12px;font-weight:600;">Provider</label>
            <select name="provider_id" class="form-control" required>
                @foreach($providers as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
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
                        <th>Provider</th>
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
                            <td style="text-align:center;">{{ $account->provider?->name ?? '—' }}</td>
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

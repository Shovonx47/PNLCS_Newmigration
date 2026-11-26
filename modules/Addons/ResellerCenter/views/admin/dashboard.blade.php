@extends('admin.layouts.app')
@section('title', 'Resellers Center')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Resellers Center</h1>
    <a href="{{ route('admin.extensions.reseller-center.resellers') }}" class="btn btn-primary btn-sm">Manage Resellers</a>
</div>

@include('resellercenter::admin._nav')

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:16px;">
    <div class="card">
        <div class="card-body" style="padding:12px 16px;">
            <div style="font-size:11px;color:#777;text-transform:uppercase;font-weight:700;">Total Resellers</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px;">{{ $stats['total_resellers'] }}</div>
            <div style="font-size:11.5px;color:#888;">{{ $stats['active_resellers'] }} active</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:12px 16px;">
            <div style="font-size:11px;color:#777;text-transform:uppercase;font-weight:700;">End Clients Mapped</div>
            <div style="font-size:22px;font-weight:700;margin-top:4px;">{{ $stats['end_clients'] }}</div>
            <div style="font-size:11.5px;color:#888;">Across {{ $stats['groups_count'] }} pricing tier(s)</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:12px 16px;">
            <div style="font-size:11px;color:#777;text-transform:uppercase;font-weight:700;">Pending Payouts</div>
            <div style="font-size:22px;font-weight:700;color:#b45309;margin-top:4px;">{{ money_fmt($stats['pending_payouts']) }}</div>
            <div style="font-size:11.5px;color:#888;"><a href="{{ route('admin.extensions.reseller-center.payouts') }}">Review payout queue</a></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="padding:12px 16px;">
            <div style="font-size:11px;color:#777;text-transform:uppercase;font-weight:700;">Total Paid Out</div>
            <div style="font-size:22px;font-weight:700;color:#15803d;margin-top:4px;">{{ money_fmt($stats['paid_payouts']) }}</div>
            <div style="font-size:11.5px;color:#888;">Completed commissions</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <strong>Recent Reseller Accounts</strong>
        <a href="{{ route('admin.extensions.reseller-center.groups') }}" class="btn btn-default btn-sm">Configure Pricing Tiers</a>
    </div>
    <div class="card-body">
        @if($recentResellers->isEmpty())
            <div style="color:#888;font-size:13px;padding:12px 0;">
                No reseller accounts registered yet. Create one from the <a href="{{ route('admin.extensions.reseller-center.resellers') }}">Resellers</a> tab.
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company / Client</th>
                        <th>Pricing Tier</th>
                        <th>End Clients</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentResellers as $reseller)
                    <tr>
                        <td>#{{ $reseller->id }}</td>
                        <td>
                            <strong>{{ $reseller->company_name ?: trim(($reseller->client?->first_name ?? '').' '.($reseller->client?->last_name ?? '')) }}</strong>
                            <div style="font-size:11.5px;color:#777;">
                                {{ $reseller->email_address ?: $reseller->client?->email }}
                                @if($reseller->cname) &middot; <code>{{ $reseller->cname }}</code>@endif
                            </div>
                        </td>
                        <td>{{ $reseller->group?->name ?: 'Standard' }}</td>
                        <td>{{ $reseller->end_clients_count }}</td>
                        <td>{{ money_fmt((float) $reseller->balance) }}</td>
                        <td>
                            <span class="badge {{ $reseller->isActive() ? 'badge-active' : 'badge-cancelled' }}">
                                {{ ucfirst($reseller->status) }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <a href="{{ route('admin.extensions.reseller-center.resellers.show', $reseller) }}" class="btn btn-default btn-sm">Manage</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

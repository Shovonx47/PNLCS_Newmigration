<div style="background:#1e293b;color:#e2e8f0;font-size:12px;border-bottom:1px solid #334155;padding:6px 16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <a href="{{ url('/admin/clients') }}" style="color:inherit;text-decoration:none;">Clients Today: <strong style="color:#fff;">{{ $stats['new_clients_today'] }}</strong></a>
            <a href="{{ url('/admin/orders?status=pending') }}" style="color:inherit;text-decoration:none;">Pending Orders: <strong style="color:{{ $stats['pending_orders'] > 0 ? '#fbbf24' : '#fff' }};">{{ $stats['pending_orders'] }}</strong></a>
            <a href="{{ url('/admin/invoices?status=paid') }}" style="color:inherit;text-decoration:none;">Renewals Today: <strong style="color:#4ade80;">{{ $stats['renewals_today'] }}</strong>@if(($stats['renewals_amount_today'] ?? 0) > 0) <span style="color:#94a3b8;">({{ money_fmt($stats['renewals_amount_today']) }})</span>@endif</a>
            <a href="{{ url('/admin/tickets') }}" style="color:inherit;text-decoration:none;">Open Tickets: <strong style="color:{{ $stats['open_tickets'] > 0 ? '#38bdf8' : '#fff' }};">{{ $stats['open_tickets'] }}</strong></a>
            <a href="{{ url('/admin/config/activity-log') }}" style="color:inherit;text-decoration:none;">Queue Alerts: <strong style="color:{{ $stats['errors_today'] > 0 ? '#f87171' : '#fff' }};">{{ $stats['errors_today'] }}</strong></a>
            <a href="{{ url('/admin/invoices?status=unpaid') }}" style="color:inherit;text-decoration:none;">Unpaid: <strong style="color:#fbbf24;">{{ money_fmt($stats['unpaid_total']) }}</strong></a>
            <a href="{{ url('/admin/invoices?status=overdue') }}" style="color:inherit;text-decoration:none;">Overdue: <strong style="color:#f87171;">{{ money_fmt($stats['overdue_total']) }}</strong></a>
            <a href="{{ url('/admin/invoices?status=paid') }}" style="color:inherit;text-decoration:none;">Paid Today ({{ $stats['paid_today_count'] }}): <strong style="color:#4ade80;">{{ money_fmt($stats['income_today']) }}</strong></a>
            @if(! empty($stats['recharges']))
                <a href="{{ url('/admin/extensions/iptsp-recharge') }}" style="color:inherit;text-decoration:none;">Recharges Today:
                    <strong style="color:#4ade80;">{{ $stats['recharges']['success_count'] }}</strong> (৳{{ number_format($stats['recharges']['success_bdt'], 0) }})
                    @if(($stats['recharges']['pending_count'] ?? 0) > 0)
                        / <strong style="color:#fbbf24;">{{ $stats['recharges']['pending_count'] }} pending</strong>
                    @endif
                    @if($stats['recharges']['failed_count'] > 0)
                        / <strong style="color:#f87171;">{{ $stats['recharges']['failed_count'] }} failed</strong>
                    @endif
                </a>
            @endif
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <a href="{{ url('/admin/extensions/bdpbx-accounting') }}" style="color:#93c5fd;text-decoration:none;font-weight:600;">PBX Accounting</a>
            <a href="{{ url('/admin/extensions/iptsp-recharge') }}" style="color:#93c5fd;text-decoration:none;font-weight:600;">IPTSP</a>
            <a href="{{ url('/admin/extensions/telegram-bot') }}" style="color:#93c5fd;text-decoration:none;font-weight:600;">Telegram</a>
            <a href="{{ url('/admin/extensions/client-verification') }}" style="color:#93c5fd;text-decoration:none;font-weight:600;">KYC</a>
        </div>
    </div>
</div>

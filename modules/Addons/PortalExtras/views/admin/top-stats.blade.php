@php
    $rcg = $stats['recharges'] ?? [
        'success_count' => 0, 'success_bdt' => 0,
        'pending_count' => 0, 'pending_bdt' => 0,
        'failed_count' => 0, 'failed_bdt' => 0,
    ];
    $fmtBdt = fn ($v) => '৳' . number_format((float) $v, 2);
@endphp
<div id="customTopStatsBar" style="text-align:center;background-color:#112239;padding:6px 8px;border-bottom:1px solid #0b1829;width:100%;white-space:nowrap;overflow-x:auto;z-index:9999;position:relative;">
    <a href="{{ url('/admin/clients') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#34495e;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        {{ $stats['new_clients_today'] }} New Clients
    </a>

    <a href="{{ url('/admin/orders?status=pending') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#5cb85c;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        {{ $stats['pending_orders'] }} Orders
    </a>

    <a href="{{ url('/admin/invoices?status=paid') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#009688;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        &#128260; {{ $stats['renewals_today'] }} Renew ({{ $fmtBdt($stats['renewals_amount_today'] ?? 0) }})
    </a>

    <a href="{{ url('/admin/tickets') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#17a2b8;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        {{ $stats['open_tickets'] }} Tickets
    </a>

    <a href="{{ url('/admin/config/activity-log') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#e83e8c;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        {{ $stats['errors_today'] }} Errors Today
    </a>

    <a href="{{ url('/admin/invoices?status=unpaid') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#6f42c1;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        Due: {{ $fmtBdt($stats['unpaid_total']) }}
    </a>

    <a href="{{ url('/admin/invoices?status=overdue') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#dc3545;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        Overdue: {{ $fmtBdt($stats['overdue_total']) }}
    </a>

    <a href="{{ url('/admin/config/transactions') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#007bff;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        {{ $stats['paid_today_count'] }} Paid Today
    </a>

    <a href="{{ url('/admin/config/transactions') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#28a745;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        Income: {{ $fmtBdt($stats['income_today']) }}
    </a>

    <a href="{{ url('/admin/extensions/bdpbx-accounting') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#0284c7;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        <i class="fas fa-phone-alt"></i> BDPBX Daily Accounting
    </a>

    <a href="{{ url('/admin/extensions/telegram-bot') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#0088cc;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        <i class="fas fa-paper-plane"></i> Telegram Bot (A-Z)
    </a>

    <a href="{{ url('/admin/extensions/iptsp-recharge') }}" style="display:inline-block;margin:0 2px;padding:3px 8px;background-color:#8e44ad;color:#fff;border-radius:4px;text-decoration:none;font-size:11px;font-weight:600;">
        &#9889; &#10004; {{ $rcg['success_count'] }} ({{ $fmtBdt($rcg['success_bdt']) }}) | &#9203; {{ $rcg['pending_count'] }} ({{ $fmtBdt($rcg['pending_bdt']) }}) | &#10060; {{ $rcg['failed_count'] }} ({{ $fmtBdt($rcg['failed_bdt']) }})
    </a>
</div>

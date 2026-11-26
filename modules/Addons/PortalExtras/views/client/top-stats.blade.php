<div style="background:var(--card,#fff);border-bottom:1px solid var(--border,#e5e7eb);font-size:12.5px;color:var(--text,#1f2937);">
    <div style="max-width:1280px;margin:0 auto;padding:6px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <a href="{{ route('client.services.index') }}" style="color:inherit;text-decoration:none;">Active Services: <strong>{{ $stats['active_services'] }}</strong></a>
            <a href="{{ route('client.domains.index') }}" style="color:inherit;text-decoration:none;">Domains: <strong>{{ $stats['active_domains'] }}</strong></a>
            <a href="{{ route('client.invoices.index') }}" style="color:inherit;text-decoration:none;">Unpaid Invoices: <strong>{{ $stats['unpaid_count'] }}</strong> ({{ display_money_fmt($stats['unpaid_total']) }})</a>
            <a href="{{ route('client.tickets.index') }}" style="color:inherit;text-decoration:none;">Open Tickets: <strong>{{ $stats['open_tickets'] }}</strong></a>
            @if(! empty($stats['client_recharges']) && \App\Models\Setting::get('addon_recharge_requests_active', '0') === '1' && \Illuminate\Support\Facades\Route::has('client.recharge'))
                <a href="{{ route('client.recharge') }}" style="color:inherit;text-decoration:none;">Total Recharge: <strong>৳{{ number_format($stats['client_recharges']['success_bdt'], 0) }}</strong></a>
            @endif
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <a href="{{ route('client.funds.index') }}" style="color:inherit;text-decoration:none;">Credit Balance: <strong style="color:var(--primary,#2563eb);">{{ display_money_fmt($stats['credit_balance']) }}</strong></a>
            <a href="{{ route('client.funds.index') }}" style="color:var(--primary,#2563eb);font-weight:600;text-decoration:none;">+ Add Funds</a>
        </div>
    </div>
</div>

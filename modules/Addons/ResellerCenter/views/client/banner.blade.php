<div class="pn-card" style="padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
    <div>
        <div class="pn-section-title" style="margin:0;">
            Reseller Partner Hub{{ $reseller->company_name ? ' — '.$reseller->company_name : '' }}
        </div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px;">
            Tier: <strong>{{ $reseller->group?->name ?: 'Standard' }}</strong>
            &middot; End Clients: <strong>{{ $endClientsCount }}</strong>
            &middot; Balance: <strong>{{ money_fmt((float) $reseller->balance) }}</strong>
            @if($pendingAmount > 0)
                &middot; Pending Payouts: <strong>{{ money_fmt($pendingAmount) }}</strong>
            @endif
        </div>
    </div>
    <a href="{{ route('client.reseller.index') }}" class="btn btn-primary">
        Open Reseller Portal
    </a>
</div>

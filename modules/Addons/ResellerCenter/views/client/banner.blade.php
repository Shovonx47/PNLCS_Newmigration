<div class="card mb-4">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
        <div class="mr-3">
            <h5 class="mb-1"><i class="fas fa-handshake fa-fw"></i> {{ 'Reseller Partner Hub' . ($reseller->company_name ? ' — '.$reseller->company_name : '') }}</h5>
            <div class="text-muted small">
                {{ 'Tier:' }} <strong>{{ $reseller->group?->name ?: 'Standard' }}</strong>
                &middot; {{ 'End Clients:' }} <strong>{{ $endClientsCount }}</strong>
                &middot; {{ 'Balance:' }} <strong>{{ money_fmt((float) $reseller->balance) }}</strong>
                @if($pendingAmount > 0)
                    &middot; {{ 'Pending Payouts:' }} <strong>{{ money_fmt($pendingAmount) }}</strong>
                @endif
            </div>
        </div>
        <a href="{{ route('client.reseller.index') }}" class="btn btn-primary btn-sm mt-2 mt-sm-0">
            {{ 'Open Reseller Portal' }}
        </a>
    </div>
</div>

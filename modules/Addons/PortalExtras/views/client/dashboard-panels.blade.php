<div class="row mb-4">
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-check fa-fw"></i>&nbsp;{{ 'Account Summary' }}</h5>
            </div>
            <div class="card-body py-2">
                <div class="detail-row">
                    <span class="k">{{ 'Account Holder' }}</span>
                    <span class="v">{{ trim($client->first_name.' '.$client->last_name) }}</span>
                </div>
                @if($client->company_name)
                <div class="detail-row">
                    <span class="k">{{ 'Organization' }}</span>
                    <span class="v">{{ $client->company_name }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="k">{{ 'Account Status' }}</span>
                    <span class="v"><span class="label {{ strtolower($stats['status'] ?? 'active') === 'active' ? 'label-success' : 'label-default' }}">{{ $stats['status'] ?? 'Active' }}</span></span>
                </div>
                <div class="detail-row">
                    <span class="k">{{ 'Member Since' }}</span>
                    <span class="v">{{ $stats['member_since'] ? $stats['member_since']->format('d M Y').' ('.($stats['tenure'] ?? '—').')' : '—' }}</span>
                </div>
                @if(! empty($stats['last_login']))
                <div class="detail-row">
                    <span class="k">{{ 'Last Sign-In' }}</span>
                    <span class="v">{{ $stats['last_login']->format('d M Y, H:i') }}</span>
                </div>
                @endif
                <div class="detail-row">
                    <span class="k">{{ 'Available Credit' }}</span>
                    <span class="v text-success">{{ display_money_fmt($stats['credit_balance']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="fas fa-wallet fa-fw"></i>&nbsp;{{ 'Recent Credit Activity' }}</h5>
                <a href="{{ route('client.funds.index') }}" class="btn btn-default btn-sm">{{ 'Add Funds' }}</a>
            </div>
            <div class="card-body">
                @if($stats['recent_credits']->isEmpty())
                    <div class="text-muted small">{{ 'No credit transactions recorded yet.' }}</div>
                @else
                    @foreach($stats['recent_credits'] as $cr)
                    <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                        <div>
                            <div class="small">{{ \Illuminate\Support\Str::limit($cr->description, 44) }}</div>
                            @if($cr->date)
                                <div class="text-muted" style="font-size:11px;">{{ $cr->date->format('d M Y') }}</div>
                            @endif
                        </div>
                        <strong class="small {{ (float) $cr->amount >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ (float) $cr->amount >= 0 ? '+' : '' }}{{ display_money_fmt((float) $cr->amount) }}
                        </strong>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

@extends('client.layouts.twentyone')
@section('title', __('client.affiliates.title'))
@section('content')

<div class="mb-3">
    <h2 class="h4 mb-1">{{ __('client.affiliates.page_title') }}</h2>
    <p class="text-muted mb-0">{{ __('client.affiliates.page_subtitle') }}</p>
</div>

<div class="row mb-3">
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="affiliate-stat affiliate-stat-green alert-warning mb-0">
            <i class="fas fa-users"></i>
            <span>{{ $stats['referrals'] ?? 0 }}</span>
            {{ __('client.affiliates.total_referrals_stat') }}
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="affiliate-stat affiliate-stat-green alert-info mb-0">
            <i class="fas fa-shopping-cart"></i>
            <span>{{ $stats['signups'] ?? 0 }}</span>
            {{ __('client.affiliates.signups') }}
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="affiliate-stat affiliate-stat-green alert-success mb-0">
            <i class="far fa-chart-bar"></i>
            <span>{{ money_fmt($stats['earnings'] ?? 0) }}</span>
            {{ __('client.affiliates.total_earnings') }}
        </div>
    </div>
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="affiliate-stat affiliate-stat-green alert-warning mb-0">
            <i class="far fa-clock"></i>
            <span>{{ money_fmt($stats['pending'] ?? 0) }}</span>
            {{ __('client.affiliates.pending') }}
        </div>
    </div>
</div>

@if($affiliate)
    <div class="card my-3">
        <div class="card-body">
            <h3 class="card-title h5">{{ __('client.affiliates.referral_link') }}</h3>
            <p class="text-muted small mb-3">{{ __('client.affiliates.referral_share_desc') }}</p>
            <div class="input-group mb-3">
                <input type="text" id="refLink" class="form-control" value="{{ $referralLink }}" readonly="readonly">
                <div class="input-group-append">
                    <button type="button" class="btn btn-primary" id="copyBtn" onclick="copyLink()">
                        <i class="far fa-copy mr-1"></i>{{ __('client.affiliates.copy_link') }}
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('client.affiliates.code') }}">
                @csrf
                <div class="form-group mb-0">
                    <label for="aff-code">{{ __('client.affiliates.code_label') }}</label>
                    <div class="input-group">
                        <input type="text" id="aff-code" name="code" value="{{ old('code', $affiliate->code) }}" maxlength="32" class="form-control" placeholder="{{ __('client.affiliates.code_placeholder') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">{{ __('common.actions.save') }}</button>
                        </div>
                    </div>
                    @error('code')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">{{ __('client.affiliates.code_hint') }}</small>
                </div>
            </form>
        </div>
    </div>

    <div class="row my-4">
        <div class="col-md-8 offset-md-2">
            <table class="table table-bordered table-striped table-rounded mb-0">
                <tbody>
                    <tr>
                        <td class="text-right">{{ __('client.affiliates.pending') }}:</td>
                        <td><strong>{{ money_fmt($stats['pending'] ?? 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-right">{{ __('client.affiliates.total_earnings') }}:</td>
                        <td><strong>{{ money_fmt($affiliate->balance ?? 0) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if(($affiliate->balance ?? 0) > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title h5 mb-0">{{ __('client.affiliates.withdraw') }}</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    {{ __('client.affiliates.withdraw_desc', ['minimum' => money_fmt(\App\Models\Setting::get('AffiliateMinPayout', 25))]) }}
                </p>
                <form method="POST" action="{{ route('client.affiliates.withdraw') }}" class="mb-3">
                    @csrf
                    <div class="input-group">
                        <input type="number" name="amount" step="0.01" min="0" max="{{ $affiliate->balance }}"
                               value="{{ old('amount') }}" class="form-control" placeholder="{{ '0.00' }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-university mr-1"></i>{{ __('client.affiliates.withdraw') }}
                            </button>
                        </div>
                    </div>
                    @error('amount')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </form>

                <hr>

                <p class="text-muted small mb-3">{{ __('client.affiliates.add_to_balance_desc') }}</p>
                <form method="POST" action="{{ route('client.affiliates.toBalance') }}">
                    @csrf
                    <div class="input-group">
                        <input type="number" name="amount" step="0.01" min="0.01" max="{{ $affiliate->balance }}"
                               value="{{ old('amount') }}" class="form-control" placeholder="{{ '0.00' }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary">{{ __('client.affiliates.add_to_balance') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
@else
    <div class="card mb-4">
        <div class="card-body py-4 text-center">
            <h3 class="card-title">{{ __('client.affiliates.join_title') }}</h3>
            <p class="lead text-muted">{{ __('client.affiliates.join_desc') }}</p>
            <form method="POST" action="{{ route('client.affiliates.activate') }}">
                @csrf
                <button id="activateAffiliate" type="submit" class="btn btn-success btn-lg px-5 py-2">
                    {{ __('client.affiliates.join_button') }}
                </button>
            </form>
        </div>
    </div>
@endif

<h2 class="h4 mb-3">{{ __('client.affiliates.commission_history') }}</h2>

<div class="table-container clearfix">
    <table id="tableAffiliatesList" class="table table-list">
        <thead>
            <tr>
                <th>{{ __('common.table.date') }}</th>
                <th>{{ __('client.affiliates.referred_client') }}</th>
                <th>{{ __('common.table.type') }}</th>
                <th>{{ __('common.table.amount') }}</th>
                <th>{{ __('common.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($commissions ?? [] as $comm)
                <tr>
                    <td>{{ $comm->date?->format(date_fmt()) ?? '-' }}</td>
                    <td>{{ $comm->invoice?->client?->full_name ?? '-' }}</td>
                    <td>{{ __('client.affiliates.commission') }}</td>
                    <td class="font-weight-bold text-success">{{ money_fmt(($comm->amount_in ?? 0) > 0 ? $comm->amount_in : $comm->amount_out) }}</td>
                    <td><span class="label status status-active badge badge-success">{{ __('client.affiliates.credited') }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">{{ __('client.affiliates.no_commissions') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@section('scripts')
<script>
function copyLink() {
    var el = document.getElementById("refLink");
    var btn = document.getElementById("copyBtn");
    if (!el || !btn) return;
    el.select();
    el.setSelectionRange(0, 99999);
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(el.value).catch(function () { document.execCommand("copy"); });
    } else {
        document.execCommand("copy");
    }
    btn.textContent = @json(__('client.affiliates.copied'));
    btn.classList.remove("btn-primary");
    btn.classList.add("btn-success");
    setTimeout(function () {
        btn.textContent = @json(__('client.affiliates.copy_link'));
        btn.classList.remove("btn-success");
        btn.classList.add("btn-primary");
    }, 2500);
}
</script>
@endsection

@extends('client.layouts.twentyone')
@section('title', __('client.domain_search.title'))
@section('content')

<div class="domain-checker-container card mb-4">
    <div class="card-body p-4 text-center">
        <h2 class="h3 mb-2">{{ __('client.domain_search.find_perfect') }}</h2>
        <p class="text-muted mb-4">{{ __('client.domain_search.search_desc') }}</p>

        <form method="GET" action="{{ route('client.domain.search') }}" id="domain-search-form">
            <div class="input-group input-group-lg">
                <input type="text" name="domain" id="domain-input"
                    class="form-control"
                    value="{{ $searchDomain ? explode('.', $searchDomain)[0] : old('domain') }}"
                    placeholder="{{ __('client.domain_search.placeholder') }}" required>
                <div class="input-group-append">
                    <select name="tld" id="tld-select" class="custom-select form-control h-100">
                        @foreach($tlds->whereIn('extension', ['.com','.net','.org','.io','.dev','.app','.co','.ai','.me','.xyz']) as $t)
                            <option value="{{ $t->extension }}" {{ ($results['tld'] ?? '.com') === $t->extension ? 'selected' : '' }}>{{ $t->extension }}</option>
                        @endforeach
                        <optgroup label="{{ __('client.domain_search.more_tlds') }}">
                            @foreach($tlds->reject(fn($t) => in_array($t->extension, ['.com','.net','.org','.io','.dev','.app','.co','.ai','.me','.xyz'])) as $t)
                                <option value="{{ $t->extension }}" {{ ($results['tld'] ?? '') === $t->extension ? 'selected' : '' }}>{{ $t->extension }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <button type="submit" class="btn btn-primary px-4">{{ __('common.actions.search') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($results)
    @php $primary = $results['primary']; @endphp
    @if($primary)
        @php $primaryChecked = $primary['checked'] ?? true; @endphp
        <div class="card mb-4 {{ ! $primaryChecked ? 'border-warning' : ($primary['available'] ? 'border-success' : 'border-danger') }}">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    @if($primary['available'] && $primaryChecked)
                        <i class="fas fa-check-circle text-success fa-2x mr-3"></i>
                    @elseif(! $primaryChecked)
                        <i class="fas fa-exclamation-circle text-warning fa-2x mr-3"></i>
                    @else
                        <i class="fas fa-times-circle text-danger fa-2x mr-3"></i>
                    @endif
                    <div>
                        <h4 class="mb-0 font-weight-bold">{{ $primary['domain'] }}</h4>
                        <div class="small font-weight-bold {{ ! $primaryChecked ? 'text-warning' : ($primary['available'] ? 'text-success' : 'text-danger') }}">
                            @if(! $primaryChecked)
                                {{ __('client.domain_search.could_not_check') }}
                            @else
                                {{ $primary['available'] ? __('client.domain_search.available') : __('client.domain_search.already_registered') }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    @if($primary['available'] && $primaryChecked)
                        <div class="text-right mr-3">
                            <div class="h4 mb-0 font-weight-bold text-primary">
                                {{ domain_money_fmt($primary['price']) }}<small class="text-muted font-weight-normal">/{{ __('client.domain_search.per_year') }}</small>
                            </div>
                            <div class="small text-muted">{{ __('client.domain_search.renews_at') }} {{ domain_money_fmt($primary['renew_price']) }}/{{ __('client.domain_search.per_year') }}</div>
                            @if(($primary['grace_period'] ?? 0) > 0)
                                <div class="small text-muted" title="{{ __('client.domain_search.grace_note') }}">{{ __('client.domain_search.grace') }}: {{ $primary['grace_period'] }} {{ __('client.domain_search.days') }}</div>
                            @endif
                            @if(($primary['restore_price'] ?? 0) > 0)
                                <div class="small text-muted" title="{{ __('client.domain_search.restore_note') }}">{{ __('client.domain_search.restore') }}: {{ domain_money_fmt($primary['restore_price']) }}</div>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('client.cart.add-domain') }}">
                            @csrf
                            <input type="hidden" name="domain" value="{{ $primary['domain'] }}">
                            <input type="hidden" name="type" value="register">
                            <input type="hidden" name="years" value="1">
                            <button type="submit" class="btn btn-success btn-lg">{{ __('common.actions.add_to_cart') }}</button>
                        </form>
                    @else
                        <a href="{{ route('client.domains.transfer', ['domain' => $primary['domain']]) }}" class="btn btn-default">
                            {{ __('client.domains.transfer_domain') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if(!empty($results['alternatives']))
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title h5 mb-0">{{ __('client.domain_search.other_options') }}</h3>
            </div>
            <div class="table-container clearfix">
                <table class="table table-list mb-0">
                    <tbody>
                        @foreach($results['alternatives'] as $alt)
                            @if($alt)
                                @php $altChecked = $alt['checked'] ?? true; @endphp
                                <tr>
                                    <td class="font-weight-bold">
                                        <i class="fas {{ ($alt['available'] && $altChecked) ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} mr-2"></i>
                                        {{ $alt['domain'] }}
                                    </td>
                                    <td>
                                        <span class="badge {{ ! $altChecked ? 'badge-warning' : ($alt['available'] ? 'badge-success' : 'badge-secondary') }}">
                                            {{ ! $altChecked ? __('client.domain_search.could_not_check_short') : ($alt['available'] ? __('client.domain_search.available_short') : __('client.domain_search.taken')) }}
                                        </span>
                                    </td>
                                    <td class="text-right font-weight-bold">
                                        @if($alt['available'] && $altChecked)
                                            {{ domain_money_fmt($alt['price']) }}<small class="text-muted font-weight-normal">/{{ __('client.domain_search.per_year') }}</small>
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($alt['available'] && $altChecked)
                                            <form method="POST" action="{{ route('client.cart.add-domain') }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="domain" value="{{ $alt['domain'] }}">
                                                <input type="hidden" name="type" value="register">
                                                <input type="hidden" name="years" value="1">
                                                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.add') }}</button>
                                            </form>
                                        @else
                                            <span class="text-muted small">{{ $altChecked ? __('client.domain_search.taken') : __('client.domain_search.could_not_check_short') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h2 class="card-title h5 mb-0">{{ __('client.nav.domain_pricing') }}</h2>
        <input type="text" id="ds-filter" class="form-control form-control-sm w-auto mt-2 mt-sm-0" placeholder="{{ __('client.domain_pricing.filter') }}" oninput="dsApply()">
    </div>
    <div class="card-body py-2 border-bottom">
        <div class="btn-group btn-group-sm flex-wrap" id="ds-tabs" role="group">
            <button type="button" onclick="dsFilter('popular')" id="ds-tab-popular" class="btn btn-primary">{{ __('client.domain_pricing.popular') }}</button>
            <button type="button" onclick="dsFilter('generic')" id="ds-tab-generic" class="btn btn-default">{{ __('client.domain_pricing.generic') }}</button>
            <button type="button" onclick="dsFilter('local')" id="ds-tab-local" class="btn btn-default">{{ __('client.domain_pricing.local_tlds') }}</button>
            <button type="button" onclick="dsFilter('country')" id="ds-tab-country" class="btn btn-default">{{ __('client.domain_pricing.country_tab') }}</button>
            <button type="button" onclick="dsFilter('new')" id="ds-tab-new" class="btn btn-default">{{ __('client.domain_pricing.new_tlds') }}</button>
        </div>
    </div>
    <div class="table-container clearfix">
        <table class="table table-list mb-0">
            <thead>
                <tr>
                    <th>{{ __('client.domain_search.extension') }}</th>
                    <th class="text-center">{{ __('common.actions.register') }}</th>
                    <th class="text-center">{{ __('client.domain_search.transfer') }}</th>
                    <th class="text-center">{{ __('client.domain_search.renew') }}</th>
                    <th class="text-center">{{ __('client.domain_search.grace') }}</th>
                    <th class="text-center">{{ __('client.domain_search.restore') }}</th>
                    <th class="text-center">{{ __('client.security.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tlds as $tld)
                    <tr class="ds-row" data-cats="{{ $tld->category }}{{ $tld->is_popular ? ',popular' : '' }}" data-ext="{{ $tld->extension }}">
                        <td class="text-monospace font-weight-bold text-primary">{{ $tld->extension }}</td>
                        <td class="text-center font-weight-bold">{{ domain_money_fmt($tld->register_price) }}</td>
                        <td class="text-center text-muted">{{ domain_money_fmt($tld->transfer_price) }}</td>
                        <td class="text-center text-muted">{{ domain_money_fmt($tld->renew_price) }}</td>
                        <td class="text-center text-muted">{{ $tld->grace_period > 0 ? $tld->grace_period . ' ' . __('client.domain_search.days') : '-' }}</td>
                        <td class="text-center text-muted">
                            @if($tld->restore_price > 0)
                                {{ domain_money_fmt($tld->restore_price) }}
                            @else
                                <span title="{{ __('client.domain_search.no_restore_title') }}" class="badge badge-danger">{{ __('client.domain_search.no_restore') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('client.domain.search') }}?tld={{ $tld->extension }}" class="btn btn-default btn-sm">{{ __('common.actions.search') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title h6 mb-0">{{ __('client.domain_search.legend_title') }}</h3>
    </div>
    <div class="card-body">
        <ul class="mb-0 pl-3 text-muted small">
            <li>{{ __('client.domain_search.legend_grace') }}</li>
            <li>{{ __('client.domain_search.legend_restore') }}</li>
            <li class="text-body"><strong>{{ __('client.domain_search.legend_local') }}</strong></li>
            <li class="text-body"><strong>{{ __('client.domain_search.legend_data') }}</strong></li>
            <li>{{ __('client.domain_search.legend_notice') }}</li>
        </ul>
    </div>
</div>

@endsection

@section('scripts')
<script>
var dsCurrent = 'popular';
function dsFilter(cat) {
    dsCurrent = cat;
    document.querySelectorAll('#ds-tabs button').forEach(function(b) {
        b.classList.remove('btn-primary');
        b.classList.add('btn-default');
    });
    var a = document.getElementById('ds-tab-' + cat);
    if (a) {
        a.classList.remove('btn-default');
        a.classList.add('btn-primary');
    }
    dsApply();
}
function dsApply() {
    var filterEl = document.getElementById('ds-filter');
    var q = (filterEl ? filterEl.value : '').toLowerCase().trim();
    var n = 0;
    document.querySelectorAll('.ds-row').forEach(function(row) {
        var cats = (row.dataset.cats || '').split(',');
        var ext  = row.dataset.ext || '';
        var okCat = (!dsCurrent || cats.indexOf(dsCurrent) !== -1);
        var okTxt = !q || ext.indexOf(q) !== -1;
        if (okCat && okTxt) { row.style.display = ''; n++; } else { row.style.display = 'none'; }
    });
    var e = document.getElementById('ds-empty');
    if (e) e.style.display = n ? 'none' : 'block';
}
dsApply();
</script>
@endsection

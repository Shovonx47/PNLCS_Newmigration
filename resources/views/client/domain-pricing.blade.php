@extends("client.layouts.twentyone")
@section("title", __("client.domain_search.pricing_title"))
@section("content")
<div class="domain-pricing">

    <div class="clearfix mb-3">
        <a href="{{ route('client.domain.search') }}" class="btn btn-primary float-right">
            <i class="fas fa-search"></i> {{ __('client.domain_pricing.search_availability') }}
        </a>
        <p class="mb-0">{{ __('client.domain_pricing.subtitle') }}</p>
    </div>

    <h4>{{ __('client.domain_pricing.transparent') }}</h4>

    <div class="tld-filters mb-3">
        <a href="#" data-category="all" class="badge badge-secondary active" onclick="filterTLDs('all');return false;">{{ __('client.domain_pricing.all') }}</a>
        <a href="#" data-category="popular" class="badge badge-secondary" onclick="filterTLDs('popular');return false;">{{ __('client.domain_pricing.popular') }}</a>
        <a href="#" data-category="generic" class="badge badge-secondary" onclick="filterTLDs('generic');return false;">{{ __('client.domain_pricing.generic') }}</a>
        <a href="#" data-category="local" class="badge badge-secondary" onclick="filterTLDs('local');return false;">{{ __('client.domain_pricing.local_tlds') }}</a>
        <a href="#" data-category="country" class="badge badge-secondary" onclick="filterTLDs('country');return false;">{{ __('client.domain_pricing.country_tab') }}</a>
        <a href="#" data-category="new" class="badge badge-secondary" onclick="filterTLDs('new');return false;">{{ __('client.domain_pricing.new_tlds') }}</a>
        <input type="text" id="tld-filter" class="form-control form-control-sm d-inline-block align-middle" style="width:180px;margin-left:8px;" placeholder="{{ __('client.domain_pricing.filter') }}" oninput="applyFilters()">
    </div>

    <div id="local-warning" class="alert alert-danger" style="display:none;" role="alert">
        {{ __("client.domain_search.local_warning") }}
    </div>

    <div class="table-container clearfix overflow-auto">
        <table class="table table-list" id="pricing-table">
            <thead>
                <tr>
                    <th>{{ __('client.domain_search.extension') }}</th>
                    <th>{{ __('common.actions.register') }}</th>
                    <th>{{ __('client.domain_search.transfer') }}</th>
                    <th>{{ __('client.domain_search.renew') }}</th>
                    <th>{{ __('client.domain_search.grace') }}</th>
                    <th>{{ __('client.domain_search.restore') }}</th>
                    <th>{{ __('client.domain_pricing.min_years') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($popular as $tld)
                <tr class="tld-row" data-cats="{{ $tld->category }}{{ $tld->is_popular ? ',popular' : '' }}" data-ext="{{ $tld->extension }}">
                    <td>
                        {{ $tld->extension }}
                        @if($tld->dns_management)<span class="badge badge-info">DNS</span>@endif
                    </td>
                    <td>
                        {{ domain_money_fmt($tld->register_price) }}
                        <br><small>1 {{ 'Year' }}</small>
                    </td>
                    <td>{{ domain_money_fmt($tld->transfer_price) }}</td>
                    <td>{{ domain_money_fmt($tld->renew_price) }}</td>
                    <td>{{ $tld->grace_period > 0 ? $tld->grace_period . ' ' . __('client.domain_search.days') : '-' }}</td>
                    <td>
                        @if($tld->restore_price > 0)
                            {{ domain_money_fmt($tld->restore_price) }}
                        @else
                            <small title="{{ __('client.domain_search.no_restore_title') }}">{{ __('client.domain_search.no_restore') }}</small>
                        @endif
                    </td>
                    <td>{{ $tld->min_years }}</td>
                    <td class="text-center">
                        <a href="{{ route('client.domain.search') }}?tld={{ $tld->extension }}" class="btn btn-xs btn-success">{{ __('common.actions.register') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div id="no-results" class="text-center p-4" style="display:none;">{{ __('client.domain_pricing.no_results') }}</div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <p class="mb-0"><strong>{{ __('client.domain_pricing.note') }}:</strong> {{ __('client.domain_pricing.note_text') }} <a href="{{ route('client.domain.search') }}">{{ __('client.domain_pricing.search_link') }}</a> {{ __('client.domain_pricing.note_text_2') }}</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">{{ __("client.domain_search.legend_title") }}</div>
        <div class="card-body">
            <ul class="mb-0">
                <li>{{ __("client.domain_search.legend_grace") }}</li>
                <li>{{ __("client.domain_search.legend_restore") }}</li>
                <li><strong>{{ __("client.domain_search.legend_local") }}</strong></li>
                <li><strong>{{ __("client.domain_search.legend_data") }}</strong></li>
                <li>{{ __("client.domain_search.legend_notice") }}</li>
            </ul>
        </div>
    </div>
</div>

<script>
var currentFilter = 'all';
function filterTLDs(cat) {
    currentFilter = cat;
    document.querySelectorAll('.tld-filters a').forEach(function (a) {
        a.classList.remove('badge-success');
    });
    var ab = document.querySelector('.tld-filters a[data-category="' + cat + '"]');
    if (ab) { ab.classList.add('badge-success'); }
    var w = document.getElementById('local-warning');
    if (w) { w.style.display = (cat === 'local') ? 'block' : 'none'; }
    applyFilters();
}
function applyFilters() {
    var input = document.getElementById('tld-filter');
    var tv = (input ? input.value : '').toLowerCase().trim();
    var vis = 0;
    document.querySelectorAll('.tld-row').forEach(function (row) {
        var cats = row.dataset.cats ? row.dataset.cats.split(',') : [];
        var ext = row.dataset.ext || '';
        var cm = (currentFilter === 'all' || cats.indexOf(currentFilter) !== -1);
        var tm = !tv || ext.indexOf(tv) !== -1;
        if (cm && tm) { row.style.display = ''; vis++; } else { row.style.display = 'none'; }
    });
    document.getElementById('no-results').style.display = vis === 0 ? 'block' : 'none';
}
</script>
@endsection

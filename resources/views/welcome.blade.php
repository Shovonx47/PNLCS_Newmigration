@extends('client.layouts.twentyone')
@section('title', __('client.nav.portal_home'))
@section('no_page_header', '1')
@section('full_width', '1')

@section('hero_banner')
@php
    $heroSection = ($sections ?? collect())->firstWhere('slug', 'hero');
    $heroContent = $sectionContent['hero'] ?? collect();
    $customHeroTitle = isset($heroContent['title']) ? trim((string) $heroContent['title']->content_value) : '';
    $customHeroSub = isset($heroContent['subtitle']) ? trim((string) $heroContent['subtitle']->content_value) : '';

    $dynHeroTitle = '';
    if ($heroSection && $heroSection->is_enabled) {
        try {
            $dynHeroTitle = trim((string) \App\Models\DynamicTranslation::where('language', app()->getLocale())
                ->where('group', 'sections')
                ->where('key', 'hero.title')
                ->value('value'));
        } catch (\Throwable $e) {
            $dynHeroTitle = '';
        }
    }

    $heroTitle = $dynHeroTitle !== ''
        ? $dynHeroTitle
        : ($customHeroTitle !== '' ? $customHeroTitle : __('client.domain_search.secure_your_domain_short'));
@endphp
<form method="GET" action="{{ route('client.domain.search') }}" id="frmDomainHomepage">
    <div class="home-domain-search bg-white">
        <div class="container">
            <div class="p-5 clearfix">
                <h2 class="text-center">{{ inline_markup($heroTitle) }}</h2>
                @if($customHeroSub !== '')
                    <p class="text-center text-muted mb-0 mt-2">{{ inline_markup($customHeroSub) }}</p>
                @endif
                <div class="input-group-wrapper">
                    <div class="input-group">
                        <input type="text" class="form-control" name="domain" placeholder="{{ __('client.domain_search.example_domain') }}" autocapitalize="none">
                        <span class="input-group-append d-none d-sm-block">
                            <button type="submit" class="btn btn-primary" id="btnDomainSearch">
                                {{ __('common.actions.search') }}
                            </button>
                            <button type="submit" id="btnTransfer" formaction="{{ route('client.domains.transfer') }}" class="btn btn-success">
                                {{ __('client.domain_search.transfer') }}
                            </button>
                        </span>
                    </div>
                </div>
                <div class="row d-sm-none">
                    <div class="col-6">
                        <button type="submit" class="btn btn-primary btn-block" id="btnDomainSearch2">{{ __('common.actions.search') }}</button>
                    </div>
                    <div class="col-6">
                        <button type="submit" id="btnTransfer2" formaction="{{ route('client.domains.transfer') }}" class="btn btn-success btn-block">{{ __('client.domain_search.transfer') }}</button>
                    </div>
                </div>

                @php
                    $spotlightSetting = trim((string) (\App\Models\Setting::get('SpotlightTLDs', '') ?: \App\Models\Setting::get('FeatureTlds', '')));
                    $featuredTlds = collect();
                    if ($spotlightSetting !== '' && isset($domainPricing) && $domainPricing->isNotEmpty()) {
                        $spotlightExts = array_filter(array_map('trim', explode(',', $spotlightSetting)));
                        $featuredTlds = $domainPricing->filter(fn ($tld) => in_array($tld->extension, $spotlightExts, true))->values();
                    }
                @endphp
                @if($featuredTlds->isNotEmpty())
                    <ul class="tld-logos">
                        @foreach($featuredTlds->take(3) as $tld)
                            <li>
                                <strong>{{ $tld->extension }}</strong>
                                {{ display_money_fmt($tld->register_price) }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                <a href="{{ route('client.domain.pricing') }}" class="btn btn-link btn-sm float-right">{{ __('client.domain_search.view_all_pricing') }}</a>
            </div>
        </div>
    </div>
</form>
@endsection

@section('content')
@php
    $productGroups = collect($products ?? [])
        ->pluck('group')
        ->filter(fn ($g) => $g && ! $g->hidden)
        ->unique('id')
        ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
        ->values();
    if ($productGroups->isEmpty()) {
        try {
            $productGroups = \App\Models\ProductGroup::where('hidden', false)
                ->whereHas('products', fn ($q) => $q->where('hidden', false)->where('retired', false))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            $productGroups = collect();
        }
    }
@endphp

<h2 class="text-center m-4">{{ __('client.homepage.products_and_services') }}</h2>

<div class="card-columns home">
    @foreach($productGroups as $pg)
        <div class="card mb-3">
            <div class="card-body p-lg-4 p-xl-5 text-center">
                <h3 class="card-title pricing-card-title">
                    {{ $pg->name }}
                </h3>
                <p>{{ html_entity_decode((string) ($pg->tagline ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') }}</p>
                <a href="{{ route('client.store', ['group' => $pg->slug ?? $pg->id]) }}" class="btn btn-block btn-outline-primary">
                    {{ __('client.homepage.browse_products') }}
                </a>
            </div>
        </div>
    @endforeach

    <div class="card mb-3">
        <div class="card-body p-lg-4 p-xl-5 text-center">
            <h3 class="card-title pricing-card-title">
                {{ __('client.homepage.register_new_domain') }}
            </h3>
            <p>{{ __('client.homepage.secure_your_domain') }}</p>
            <a href="{{ route('client.domain.search') }}" class="btn btn-block btn-outline-primary">
                {{ __('client.homepage.domain_search') }}
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body p-lg-4 p-xl-5 text-center">
            <h3 class="card-title pricing-card-title">
                {{ __('client.homepage.transfer_your_domain') }}
            </h3>
            <p>{{ __('client.homepage.transfer_extend') }}</p>
            <a href="{{ route('client.domains.transfer') }}" class="btn btn-block btn-outline-primary">
                {{ __('client.homepage.transfer_your_domain') }}
            </a>
        </div>
    </div>
</div>

@if(!empty($apps))
    <h2 class="text-center m-4">{{ __('sections.apps.title') !== 'sections.apps.title' ? __('sections.apps.title') : 'One-Click Applications' }}</h2>
    <div class="row mb-4 oca__grid">
        @foreach($apps as $app)
            @php($appLine = ($app['tagline'] ?? '') ?: ($app['description'] ?? ''))
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h4 class="h5 mb-2">{{ $app['name'] ?? '' }}</h4>
                        @if($appLine !== '')
                            <p class="text-muted small mb-3">{{ $appLine }}</p>
                        @endif
                        <a href="{{ route('client.store', ['app' => $app['slug'] ?? '']) }}" class="btn btn-sm btn-outline-primary">
                            {{ __('sections.apps.cta') }}
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<h2 class="text-center m-4">{{ __('client.homepage.how_can_we_help') }}</h2>

<div class="row my-5 action-icon-btns">
    <div class="col-6 col-md-4 col-lg">
        <a href="{{ route('client.announcements.index') }}" class="card-accent-teal">
            <figure class="ico-container">
                <i class="fal fa-bullhorn"></i>
            </figure>
            {{ __('client.nav.announcements') }}
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="{{ \Illuminate\Support\Facades\Route::has('client.network-status') ? route('client.network-status') : route('client.contact') }}" class="card-accent-pomegranate">
            <figure class="ico-container">
                <i class="fal fa-server"></i>
            </figure>
            {{ __('client.nav.network_status') }}
        </a>
    </div>
    @if(kb_enabled())
    <div class="col-6 col-md-4 col-lg">
        <a href="{{ route('client.kb.index') }}" class="card-accent-sun-flower">
            <figure class="ico-container">
                <i class="fal fa-book"></i>
            </figure>
            {{ __('client.homepage.knowledgebase') }}
        </a>
    </div>
    @endif
    <div class="col-6 col-md-4 offset-md-2 offset-lg-0 col-lg">
        <a href="{{ \Illuminate\Support\Facades\Route::has('client.downloads.index') ? route('client.downloads.index') : route('client.kb.index') }}" class="card-accent-asbestos">
            <figure class="ico-container">
                <i class="fal fa-download"></i>
            </figure>
            {{ __('client.homepage.downloads') }}
        </a>
    </div>
    <div class="col-6 offset-3 offset-md-0 col-md-4 col-lg">
        <a href="{{ auth('web')->check() ? route('client.tickets.create') : route('client.contact') }}" class="card-accent-green">
            <figure class="ico-container">
                <i class="fal fa-life-ring"></i>
            </figure>
            {{ __('client.homepage.submit_ticket') }}
        </a>
    </div>
</div>

<h2 class="text-center m-4">{{ __('client.homepage.your_account') }}</h2>

<div class="row my-5 action-icon-btns">
    <div class="col-6 col-md-4 col-lg">
        <a href="{{ auth('web')->check() ? route('client.home') : route('client.login') }}" class="card-accent-midnight-blue">
            <figure class="ico-container">
                <i class="fal fa-home"></i>
            </figure>
            {{ __('client.homepage.your_account') }}
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="{{ route('client.services.index') }}" class="card-accent-midnight-blue">
            <figure class="ico-container">
                <i class="far fa-cubes"></i>
            </figure>
            {{ __('client.homepage.manage_services') }}
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="{{ route('client.domains.index') }}" class="card-accent-midnight-blue">
            <figure class="ico-container">
                <i class="fal fa-globe"></i>
            </figure>
            {{ __('client.homepage.manage_domains') }}
        </a>
    </div>
    <div class="col-6 col-md-4 offset-md-2 offset-lg-0 col-lg">
        <a href="{{ route('client.tickets.index') }}" class="card-accent-midnight-blue">
            <figure class="ico-container">
                <i class="fal fa-comments"></i>
            </figure>
            {{ __('client.homepage.support_requests') }}
        </a>
    </div>
    <div class="col-6 offset-3 offset-md-0 col-md-4 col-lg">
        <a href="{{ route('client.invoices.index') }}" class="card-accent-midnight-blue">
            <figure class="ico-container">
                <i class="fal fa-credit-card"></i>
            </figure>
            {{ __('client.homepage.make_a_payment') }}
        </a>
    </div>
</div>
@endsection

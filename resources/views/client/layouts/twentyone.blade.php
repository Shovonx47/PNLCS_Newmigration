@php
    // WHMCS Twenty-One chrome. Pages being ported to the legacy look extend
    // this layout instead of client.layouts.app, so the port can land page by
    // page without touching the rest of the client area.
    $cartCount = 0;
    try {
        $cart = \App\Models\Cart::query()
            ->when(auth('web')->check(), fn ($q) => $q->where('user_id', auth('web')->id()))
            ->when(! auth('web')->check(), fn ($q) => $q->where('session_id', session()->getId()))
            ->latest('id')
            ->first();
        $cartCount = count($cart->data['items'] ?? []);
    } catch (\Throwable $e) {
        $cartCount = 0;
    }

    $brandLogo = ! empty($customLogo) ? $customLogo : asset('assets/img/logo.png');
    $homeUrl = auth('web')->check() ? route('client.home') : route('home');
    $isHomeRoute = (Route::currentRouteName() === 'home');

    if (auth('web')->check()) {
        $nav = [
            ['label' => __('client.nav.home_site'), 'url' => route('client.home')],
            ['label' => __('client.nav.my_services'), 'url' => route('client.services.index'), 'children' => [
                ['label' => __('client.nav.my_services'), 'url' => route('client.services.index')],
                ['label' => 'SSL Certificates', 'url' => route('client.ssl.index')],
                ['label' => __('client.nav.order_new'), 'url' => route('client.store')],
            ]],
            ['label' => __('client.nav.my_domains'), 'url' => route('client.domains.index'), 'children' => [
                ['label' => __('client.nav.my_domains'), 'url' => route('client.domains.index')],
                ['label' => 'Register a New Domain', 'url' => route('client.domain.search')],
                ['label' => 'Transfer Domains to Us', 'url' => route('client.domains.transfer')],
                ['label' => 'Domain Pricing', 'url' => route('client.domain.pricing')],
            ]],
            ['label' => 'Billing', 'url' => route('client.invoices.index'), 'children' => [
                ['label' => __('client.nav.invoices'), 'url' => route('client.invoices.index')],
                ['label' => 'My Quotes', 'url' => route('client.quotes.index')],
                ['label' => 'Payment Methods', 'url' => route('client.payment-methods.index')],
                ['label' => __('client.nav.add_funds'), 'url' => route('client.funds.index')],
            ]],
            ['label' => 'Support', 'url' => route('client.tickets.index'), 'children' => array_values(array_filter([
                ['label' => 'Tickets', 'url' => route('client.tickets.index')],
                ['label' => __('client.nav.announcements'), 'url' => route('client.announcements.index')],
                kb_enabled() ? ['label' => 'Knowledgebase', 'url' => route('client.kb.index')] : null,
                \Illuminate\Support\Facades\Route::has('client.downloads.index') ? ['label' => 'Downloads', 'url' => route('client.downloads.index')] : null,
                \Illuminate\Support\Facades\Route::has('client.network-status') ? ['label' => __('client.nav.network_status'), 'url' => route('client.network-status')] : null,
            ]))],
            ['label' => 'Open Ticket', 'url' => route('client.tickets.create')],
            ['label' => 'Store', 'url' => route('client.store'), 'children' => [
                ['label' => __('client.nav.order_new'), 'url' => route('client.store')],
                ['label' => 'Register a New Domain', 'url' => route('client.domain.search')],
                ['label' => 'Transfer in a Domain', 'url' => route('client.domains.transfer')],
                ['label' => __('client.nav.view_cart'), 'url' => route('client.cart.index')],
            ]],
        ];
        if (\Illuminate\Support\Facades\Route::has('client.affiliates.index')) {
            $nav[] = ['label' => __('client.nav.affiliates'), 'url' => route('client.affiliates.index')];
        }
    } else {
        $nav = [
            ['label' => __('client.nav.home_site'), 'url' => route('home')],
            ['label' => 'Store', 'url' => route('client.store'), 'children' => [
                ['label' => __('client.nav.order_new'), 'url' => route('client.store')],
                ['label' => 'Register a New Domain', 'url' => route('client.domain.search')],
                ['label' => 'Transfer in a Domain', 'url' => route('client.domains.transfer')],
                ['label' => __('client.nav.view_cart'), 'url' => route('client.cart.index')],
            ]],
            ['label' => __('client.nav.announcements'), 'url' => route('client.announcements.index')],
        ];
        if (kb_enabled()) {
            $nav[] = ['label' => 'Knowledgebase', 'url' => route('client.kb.index')];
        }
        if (\Illuminate\Support\Facades\Route::has('client.network-status')) {
            $nav[] = ['label' => __('client.nav.network_status'), 'url' => route('client.network-status')];
        }
        if (\Illuminate\Support\Facades\Route::has('client.affiliates.index')) {
            $nav[] = ['label' => __('client.nav.affiliates'), 'url' => route('client.affiliates.index')];
        }
        $nav[] = ['label' => __('client.nav.contact'), 'url' => route('client.contact')];
    }

    $currentRouteName = Route::currentRouteName() ?? '';
    $fullWidthRoutes = ['home', 'client.contact', 'client.domain.pricing', 'client.domain.search', 'client.unsubscribe'];
    $hasClientSidebar = ! View::hasSection('full_width')
        && ! in_array($currentRouteName, $fullWidthRoutes, true)
        && ! str_starts_with($currentRouteName, 'client.cart.')
        && ! ($currentRouteName === 'client.store')
        && ! str_starts_with($currentRouteName, 'client.login')
        && ! str_starts_with($currentRouteName, 'client.register')
        && ! str_starts_with($currentRouteName, 'client.password.')
        && ! str_starts_with($currentRouteName, 'client.2fa.')
        && ! str_starts_with($currentRouteName, 'client.invite.')
        && ! str_starts_with($currentRouteName, 'client.verification.')
        && ! str_starts_with($currentRouteName, 'client.confirm-')
        && (
            auth('web')->check()
            || str_starts_with($currentRouteName, 'client.kb.')
            || str_starts_with($currentRouteName, 'client.announcements.')
            || str_starts_with($currentRouteName, 'client.downloads.')
            || $currentRouteName === 'client.network-status'
        );
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $textDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __("client.my_account")) - {{ company_name() }}</title>
    @if($isHomeRoute ?? false)
        @include('partials.seo-meta', ['isHome' => true, 'title' => ($brandName ?? company_name()).' — '.__('client.welcome.meta_title_suffix'), 'description' => __('client.welcome.meta_description', ['brand' => $brandName ?? company_name()])])
    @elseif(View::hasSection('legal-title') || View::hasSection('legal-description'))
        @include('partials.seo-meta', ['title' => trim($__env->yieldContent('legal-title')).' — '.($brandName ?? company_name()), 'description' => trim($__env->yieldContent('legal-description')) ?: null])
    @else
        @include('partials.seo-meta')
    @endif
    <link href="{{ asset('assets/fonts/css/open-sans-family.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/twenty-one/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/twenty-one/css/theme.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/fontawesome-all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/pnlcs-theme-compat.css') }}" rel="stylesheet">
    @stack('styles')
    @if(!empty($customFavicon))
    <link rel="icon" href="{{ $customFavicon }}" type="image/png">
    @endif
    @if(!empty($themeCssVars))
    <style id="theme-vars">{!! $themeCssVars !!}</style>
    @endif
    {!! hook_output('ClientAreaHeadOutput', ['user' => auth()->user()]) !!}
    @include('partials.locale-alternates')
</head>
<body class="primary-bg-color">
{!! hook_output('ClientAreaTopOutput', ['user' => auth('web')->user()]) !!}
<header id="header" class="header">
    @auth('web')
    <div class="topbar">
        <div class="container">
            <div class="d-flex">
                <div class="mr-auto">
                    <a href="{{ route('client.invoices.index') }}" class="btn">
                        <i class="far fa-flag"></i>
                        <span class="d-none d-sm-inline">{{ __('client.nav.invoices') }}</span>
                    </a>
                </div>
                <div class="ml-auto">
                    <div class="input-group active-client" role="group">
                        <div class="input-group-prepend d-none d-md-inline">
                            <span class="input-group-text">{{ __('client.nav.logged_in_as') }}</span>
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('client.account.profile') }}" class="btn btn-active-client">
                                <span>{{ auth('web')->user()->full_name ?? trim(auth('web')->user()->first_name . ' ' . auth('web')->user()->last_name) }}</span>
                            </a>
                            @if(session('impersonator_admin_id') || auth('admin')->check())
                                <a href="{{ session('impersonator_admin_id') ? route('admin.clients.stop-impersonation') : route('admin.dashboard') }}" class="btn btn-return-to-admin" title="{{ __('client.nav.return_to_admin') }}">
                                    <i class="fas fa-redo-alt"></i>
                                    <span class="d-none d-md-inline-block">{{ __('client.nav.return_to_admin') }}</span>
                                </a>
                            @endif
                            <form method="POST" action="{{ route('client.logout') }}" class="d-inline m-0">
                                @csrf
                                <button type="submit" class="btn" data-toggle="tooltip" data-placement="bottom" title="{{ __('client.nav.sign_out') }}">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endauth

    <div class="navbar navbar-light">
        <div class="container">
            <a class="navbar-brand mr-3" href="{{ $homeUrl }}">
                <img src="{{ $brandLogo }}" alt="{{ company_name() }}" class="logo-img">
            </a>

            <form method="GET" action="{{ route('client.kb.index') }}" class="form-inline ml-auto">
                <div class="input-group search d-none d-xl-flex">
                    <div class="input-group-prepend">
                        <button class="btn btn-default" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <input class="form-control appended-form-control font-weight-light" type="text" name="q" placeholder="{{ __('client.nav.search_kb') }}">
                </div>
            </form>

            <ul class="navbar-nav toolbar">
                <li class="nav-item ml-3">
                    <a class="btn nav-link cart-btn" href="{{ route('client.cart.index') }}">
                        <i class="far fa-shopping-cart fa-fw"></i>
                        <span id="cartItemCount" class="badge badge-info">{{ $cartCount }}</span>
                        <span class="sr-only">{{ __('client.cart.view_cart') }}</span>
                    </a>
                </li>
                <li class="nav-item ml-3 d-xl-none">
                    <button class="btn nav-link" type="button" data-toggle="collapse" data-target="#mainNavbar">
                        <span class="fas fa-bars fa-fw"></span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
    <div class="navbar navbar-expand-xl main-navbar-wrapper">
        <div class="container">
            <div class="collapse navbar-collapse" id="mainNavbar">
                @if(kb_enabled())
                <form method="GET" action="{{ route('client.kb.index') }}" class="d-xl-none">
                    <div class="input-group search w-100 mb-2">
                        <div class="input-group-prepend">
                            <button class="btn btn-default" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <input class="form-control prepended-form-control" type="text" name="q" placeholder="{{ __('client.nav.search_kb') }}">
                    </div>
                </form>
                @endif
                <ul id="nav" class="navbar-nav mr-auto">
                    @foreach($nav as $item)
                        <li class="d-block{{ $loop->first ? ' no-collapse' : '' }}{{ !empty($item['children']) ? ' dropdown no-collapse' : '' }}">
                            <a class="pr-4{{ !empty($item['children']) ? ' dropdown-toggle' : '' }}"
                               @if(!empty($item['children'])) data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#" @else href="{{ $item['url'] }}" @endif>
                                {{ $item['label'] }}
                            </a>
                            @if(!empty($item['children']))
                                <ul class="dropdown-menu">
                                    @foreach($item['children'] as $child)
                                        <li><a href="{{ $child['url'] }}" class="dropdown-item px-2 py-0">{{ $child['label'] }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <ul class="navbar-nav ml-auto">
                    <li class="d-block dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" href="#">
                            @auth('web')
                                {{ 'Hello, ' . (auth('web')->user()->first_name ?: __('client.nav.account')) . '!' }}
                            @else
                                {{ __('client.nav.account') }}
                            @endauth
                        </a>
                        <ul class="dropdown-menu dropdown-menu-right">
                            @auth('web')
                                <li><a href="{{ route('client.account.profile') }}" class="dropdown-item px-2 py-0">{{ __('client.nav.my_profile') }}</a></li>
                                <li><a href="{{ route('client.payment-methods.index') }}" class="dropdown-item px-2 py-0">{{ 'Payment Methods' }}</a></li>
                                <li><a href="{{ route('client.account.contacts') }}" class="dropdown-item px-2 py-0">{{ __('client.nav.contacts') }}</a></li>
                                @if(\Illuminate\Support\Facades\Route::has('client.account.users'))
                                <li><a href="{{ route('client.account.users') }}" class="dropdown-item px-2 py-0">{{ 'User Management' }}</a></li>
                                @endif
                                <li><a href="{{ route('client.account.password') }}" class="dropdown-item px-2 py-0">{{ __('client.nav.password') }}</a></li>
                                @if(\Illuminate\Support\Facades\Route::has('client.account.security'))
                                <li><a href="{{ route('client.account.security') }}" class="dropdown-item px-2 py-0">{{ 'Security Settings' }}</a></li>
                                @endif
                                @if(\Illuminate\Support\Facades\Route::has('client.emails.index'))
                                <li><a href="{{ route('client.emails.index') }}" class="dropdown-item px-2 py-0">{{ 'Email History' }}</a></li>
                                @endif
                                <div class="dropdown-divider"></div>
                                <li>
                                    <form method="POST" action="{{ route('client.logout') }}" class="m-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item px-2 py-0 border-0 bg-transparent w-100 text-left">{{ __('client.nav.sign_out') }}</button>
                                    </form>
                                </li>
                            @else
                                <li><a href="{{ route('client.login') }}" class="dropdown-item px-2 py-0">{{ __('common.actions.login') }}</a></li>
                                <li><a href="{{ route('client.register') }}" class="dropdown-item px-2 py-0">{{ __('common.actions.register') }}</a></li>
                                <div class="dropdown-divider"></div>
                                <li><a href="{{ route('client.password.request') }}" class="dropdown-item px-2 py-0">{{ __('client.nav.forgot_password') }}</a></li>
                            @endauth
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

@unless(View::hasSection('no_breadcrumb'))
<nav class="master-breadcrumb" aria-label="{{ __('client.nav.portal_home') }}">
    <div class="container">
        <ol class="breadcrumb">
            @if(($isHomeRoute ?? false) && ! View::hasSection('breadcrumb'))
                <li class="breadcrumb-item active" aria-current="page">{{ __('client.nav.portal_home') }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $homeUrl }}">{{ __('client.nav.portal_home') }}</a></li>
                @hasSection('breadcrumb')
                    @yield('breadcrumb')
                @else
                    <li class="breadcrumb-item active" aria-current="page">@yield('title')</li>
                @endif
            @endif
        </ol>
    </div>
</nav>
@endunless

@yield('hero_banner')

<section id="main-body">
    <div class="container">
        <div class="row">
            @if($hasClientSidebar ?? false)
            <div class="col-lg-4 col-xl-3">
                <div class="sidebar">
                    @include('client.layouts.partials.twentyone-sidebar')
                    @yield('sidebar')
                </div>
            </div>
            @endif
            <div class="{{ ($hasClientSidebar ?? false) ? 'col-lg-8 col-xl-9' : 'col-12' }} primary-content">
                @if(session('success'))
                    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                @endif
                @if(session('info'))
                    <div class="alert alert-info" role="alert">{{ session('info') }}</div>
                @endif
                @if(session('warning'))
                    <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        @foreach($errors->all() as $validationError)
                            <div>{{ $validationError }}</div>
                        @endforeach
                    </div>
                @endif

                {{-- WHMCS prints the page title as a header-lined block at the top
                     of every client page; pages that draw their own heading (or
                     none, such as the homepage and the login form) opt out. --}}
                @unless(View::hasSection('no_page_header'))
                    <div class="header-lined">
                        <h1>@yield('page_heading', trim($__env->yieldContent('title')) ?: __('client.my_account'))</h1>
                        @hasSection('page_subheading')
                            <p class="mb-0">@yield('page_subheading')</p>
                        @endif
                    </div>
                @endunless

                @yield('content')
            </div>
        </div>
    </div>
</section>

<footer id="footer" class="footer">
    <div class="container">
        <ul class="list-inline mb-7 text-center float-lg-right">
            @unless($isHomeRoute ?? false)
            <li class="list-inline-item">@include('client.layouts.partials.currency-selector')</li>
            <li class="list-inline-item">@include('client.layouts.partials.language-selector')</li>
            @endunless
            @if((isset($activeLanguages) && $activeLanguages->count() > 1) || (isset($customerCurrencyChoice) && $customerCurrencyChoice->count() > 1))
                @php
                    $currentFlag = isset($activeLanguages) && $currentLocale
                        ? optional($activeLanguages->firstWhere('code', $currentLocale))->flag_code
                        : null;
                @endphp
                <li class="list-inline-item">
                    <button type="button" class="btn" data-toggle="modal" data-target="#modalChooseLanguage">
                        <div class="d-inline-block align-middle">
                            @if($currentFlag ?? false)
                                <x-language-flag :code="$currentFlag" size="20x15" />
                            @endif
                        </div>
                        {{ $currentLocaleName ?? 'English' }}
                        /
                        {{ $displayCurrency?->code ?? 'BDT' }}
                    </button>
                </li>
            @endif
        </ul>

        @php
            $showFooterTos = in_array(strtolower((string) \App\Models\Setting::get('EnableTOSAccept', '')), ['1', 'on', 'true', 'yes'], true);
        @endphp
        <ul class="nav justify-content-center justify-content-lg-start mb-7">
            <li class="nav-item"><a class="nav-link" href="{{ route('client.contact') }}">{{ __('client.nav.contact') }}</a></li>
            @if($showFooterTos && \Illuminate\Support\Facades\Route::has('legal.index'))
            <li class="nav-item"><a class="nav-link" href="{{ route('legal.index') }}">{{ __('client.footer.legal') !== 'client.footer.legal' ? __('client.footer.legal') : 'Terms of Service' }}</a></li>
            @endif
            @php
                $socialLabels = [
                    'x' => 'X', 'facebook' => 'Facebook', 'instagram' => 'Instagram',
                    'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'github' => 'GitHub',
                    'tiktok' => 'TikTok', 'medium' => 'Medium',
                ];
                $socials = function_exists('social_profiles') ? social_profiles() : [];
            @endphp
            @foreach($socials as $network => $socialUrl)
                <li class="nav-item">
                    <a class="nav-link" href="{{ $socialUrl }}" target="_blank" rel="noopener" aria-label="{{ $socialLabels[$network] ?? ucfirst($network) }}">
                        {{ $socialLabels[$network] ?? ucfirst($network) }}
                    </a>
                </li>
            @endforeach
        </ul>

        <p class="copyright mb-0">
            {{ __('client.footer.copyright_notice', ['year' => date('Y'), 'company' => company_name()]) }}
        </p>
    </div>
</footer>

<form method="GET" action="{{ url()->current() }}">
    <div class="modal modal-localisation" id="modalChooseLanguage" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close text-light" data-dismiss="modal" aria-label="{{ __('common.actions.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>

                    @if(isset($activeLanguages) && $activeLanguages->count() > 1)
                        <h5 class="h5 pt-5 pb-3">{{ __('client.footer.choose_language') }}</h5>
                        <div class="row item-selector">
                            <input type="hidden" name="language" value="{{ $currentLocale }}" />
                            @foreach($activeLanguages as $lang)
                                <div class="col-4">
                                    <a href="#" class="item{{ $lang->code === $currentLocale ? ' active' : '' }}" data-value="{{ $lang->code }}">
                                        {{ $lang->native_name }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if(isset($customerCurrencyChoice) && $customerCurrencyChoice->count() > 1)
                        <p class="h5 pt-5 pb-3">{{ __('common.form.currency') }}</p>
                        <div class="row item-selector">
                            <input type="hidden" name="currency" value="{{ $displayCurrency?->code }}" />
                            @foreach($customerCurrencyChoice as $choice)
                                <div class="col-4">
                                    <a href="#" class="item{{ $choice->id === $displayCurrency?->id ? ' active' : '' }}" data-value="{{ $choice->code }}">
                                        {{ $choice->code }} ({{ $choice->symbol() }})
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-default">{{ __('client.footer.apply') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>

@yield('scripts')
@stack('scripts')
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
{{-- The globals the theme bundle expects (WHMCS's head.tpl defines these). --}}
<script>
    var csrfToken = '{{ csrf_token() }}',
        markdownGuide = 'Markdown Guide',
        locale = '{{ app()->getLocale() }}',
        saved = 'Saved!',
        saving = 'Saving...',
        whmcsBaseUrl = "{{ url('') }}";
</script>
{{-- The theme's own script bundle: clickableSafeRedirect, popupWindow, tooltips,
     dropdown behaviour and the rest of the Twenty-One interactions. --}}
<script src="{{ asset('templates/twenty-one/js/scripts.min.js') }}"></script>
<script>
document.querySelectorAll('#modalChooseLanguage .item-selector .item').forEach(function (item) {
    item.addEventListener('click', function (e) {
        e.preventDefault();
        var row = item.closest('.item-selector');
        row.querySelectorAll('.item').forEach(function (el) { el.classList.remove('active'); });
        item.classList.add('active');
        var input = row.querySelector('input[type=hidden]');
        if (input) input.value = item.getAttribute('data-value');
    });
});
document.querySelectorAll('.btn-reveal-pw').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = btn.closest('.input-group').querySelector('input');
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
    });
});
</script>
{!! hook_output('ClientAreaFooterOutput', ['user' => auth()->user()]) !!}
</body>
</html>

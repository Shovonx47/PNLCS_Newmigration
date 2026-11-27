{{-- WHMCS Twenty-One Contextual Left Sidebar (includes/sidebar.tpl structure) --}}
@php
    $rn = Route::currentRouteName() ?? '';
    $authUser = auth('web')->user();
    $activeClient = null;
    if ($authUser && method_exists($authUser, 'clients')) {
        try {
            $activeClient = $authUser->clients()->first();
        } catch (\Throwable $e) {
            $activeClient = null;
        }
    }
@endphp

{{-- Dashboard Sidebar --}}
@if($rn === 'client.home')
    @if($activeClient || $authUser)
    <div class="mb-3 card card-sidebar">
        <div class="card-header">
            <h3 class="card-title m-0">
                <i class="fas fa-user"></i>&nbsp;{{ __('client.account.your_info') !== 'client.account.your_info' ? __('client.account.your_info') : 'Your Info' }}
                <i class="fas fa-chevron-up card-minimise float-right"></i>
            </h3>
        </div>
        <div class="collapsable-card-body">
            <div class="card-body">
                @if($activeClient?->company_name)
                    <strong>{{ $activeClient->company_name }}</strong><br>
                @endif
                <strong>{{ $activeClient?->full_name ?? ($authUser->full_name ?? trim($authUser->first_name . ' ' . $authUser->last_name)) }}</strong>
                @if($activeClient)
                    @if($activeClient->address1)<br>{{ $activeClient->address1 }}@endif
                    @if($activeClient->city || $activeClient->state)<br>{{ trim(($activeClient->city ?? '') . ($activeClient->state ? ', ' . $activeClient->state : '')) }}@endif
                    @if($activeClient->postcode || $activeClient->country)<br>{{ trim(($activeClient->postcode ?? '') . ' ' . ($activeClient->country ?? '')) }}@endif
                @endif
            </div>
        </div>
        <div class="card-footer clearfix">
            <a href="{{ route('client.account.profile') }}" class="btn btn-success btn-sm btn-block">
                <i class="fas fa-pencil-alt"></i> {{ __('common.actions.update') !== 'common.actions.update' ? __('common.actions.update') : 'Update' }}
            </a>
        </div>
    </div>
    @endif

    <div class="mb-3 card card-sidebar">
        <div class="card-header">
            <h3 class="card-title m-0">
                <i class="far fa-bookmark"></i>&nbsp;{{ 'Shortcuts' }}
                <i class="fas fa-chevron-up card-minimise float-right"></i>
            </h3>
        </div>
        <div class="collapsable-card-body">
            <div class="list-group list-group-flush d-md-flex">
                <a href="{{ route('client.store') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-shopping-cart fa-fw"></i>&nbsp;{{ __('client.nav.order_new') }}
                </a>
                <a href="{{ route('client.domain.search') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-globe fa-fw"></i>&nbsp;{{ 'Register a New Domain' }}
                </a>
                <a href="{{ route('client.tickets.create') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-comments fa-fw"></i>&nbsp;{{ 'Open Ticket' }}
                </a>
            </div>
        </div>
    </div>

{{-- Services & SSL Sidebar --}}
@elseif(str_starts_with($rn, 'client.services.') || str_starts_with($rn, 'client.ssl.'))
    <div class="mb-3 card card-sidebar">
        <div class="card-header">
            <h3 class="card-title m-0">
                <i class="fas fa-cube"></i>&nbsp;{{ __('client.nav.my_services') }}
            </h3>
        </div>
        <div class="collapsable-card-body">
            <div class="list-group list-group-flush d-md-flex">
                <a href="{{ route('client.services.index') }}" class="list-group-item list-group-item-action{{ $rn === 'client.services.index' && !request('status') ? ' active' : '' }}">
                    <i class="fas fa-list fa-fw"></i>&nbsp;{{ __('client.nav.my_services') }}
                </a>
                <a href="{{ route('client.ssl.index') }}" class="list-group-item list-group-item-action{{ str_starts_with($rn, 'client.ssl.') ? ' active' : '' }}">
                    <i class="fas fa-lock fa-fw"></i>&nbsp;{{ 'SSL Certificates' }}
                </a>
                <a href="{{ route('client.store') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-shopping-cart fa-fw"></i>&nbsp;{{ __('client.nav.order_new') }}
                </a>
            </div>
        </div>
    </div>

{{-- Domains Sidebar --}}
@elseif(str_starts_with($rn, 'client.domains.'))
    <div class="mb-3 card card-sidebar">
        <div class="card-header">
            <h3 class="card-title m-0">
                <i class="fas fa-globe"></i>&nbsp;{{ 'Actions' }}
            </h3>
        </div>
        <div class="collapsable-card-body">
            <div class="list-group list-group-flush d-md-flex">
                <a href="{{ route('client.domains.index') }}" class="list-group-item list-group-item-action{{ $rn === 'client.domains.index' ? ' active' : '' }}">
                    <i class="fas fa-globe fa-fw"></i>&nbsp;{{ __('client.nav.my_domains') }}
                </a>
                <a href="{{ route('client.domain.search') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-plus fa-fw"></i>&nbsp;{{ 'Register a New Domain' }}
                </a>
                <a href="{{ route('client.domains.transfer') }}" class="list-group-item list-group-item-action{{ $rn === 'client.domains.transfer' ? ' active' : '' }}">
                    <i class="fas fa-share fa-fw"></i>&nbsp;{{ 'Transfer in a Domain' }}
                </a>
                <a href="{{ route('client.domain.pricing') }}" class="list-group-item list-group-item-action">
                    <i class="fas fa-tags fa-fw"></i>&nbsp;{{ 'Domain Pricing' }}
                </a>
            </div>
        </div>
    </div>

{{-- Billing / Invoices / Quotes / Funds / Payment Methods Sidebar --}}
@elseif(str_starts_with($rn, 'client.invoices.') || str_starts_with($rn, 'client.quotes.') || str_starts_with($rn, 'client.funds.') || str_starts_with($rn, 'client.payment-methods.'))
    <div class="mb-3 card card-sidebar">
        <div class="card-header">
            <h3 class="card-title m-0">
                <i class="fas fa-credit-card"></i>&nbsp;{{ 'Billing' }}
            </h3>
        </div>
        <div class="collapsable-card-body">
            <div class="list-group list-group-flush d-md-flex">
                <a href="{{ route('client.invoices.index') }}" class="list-group-item list-group-item-action{{ str_starts_with($rn, 'client.invoices.') ? ' active' : '' }}">
                    <i class="fas fa-file-invoice-dollar fa-fw"></i>&nbsp;{{ __('client.nav.invoices') }}
                </a>
                <a href="{{ route('client.quotes.index') }}" class="list-group-item list-group-item-action{{ str_starts_with($rn, 'client.quotes.') ? ' active' : '' }}">
                    <i class="fas fa-file-signature fa-fw"></i>&nbsp;{{ 'My Quotes' }}
                </a>
                <a href="{{ route('client.payment-methods.index') }}" class="list-group-item list-group-item-action{{ str_starts_with($rn, 'client.payment-methods.') ? ' active' : '' }}">
                    <i class="far fa-credit-card fa-fw"></i>&nbsp;{{ 'Payment Methods' }}
                </a>
                <a href="{{ route('client.funds.index') }}" class="list-group-item list-group-item-action{{ str_starts_with($rn, 'client.funds.') ? ' active' : '' }}">
                    <i class="fas fa-wallet fa-fw"></i>&nbsp;{{ __('client.nav.add_funds') }}
                </a>
            </div>
        </div>
    </div>

{{-- Support / Tickets / KB / Announcements / Downloads / Network Status Sidebar --}}
@elseif(str_starts_with($rn, 'client.tickets.') || str_starts_with($rn, 'client.kb.') || str_starts_with($rn, 'client.announcements.') || str_starts_with($rn, 'client.downloads.') || $rn === 'client.network-status')
    <div class="mb-3 card card-sidebar">
        <div class="card-header">
            <h3 class="card-title m-0">
                <i class="fas fa-life-ring"></i>&nbsp;{{ 'Support' }}
            </h3>
        </div>
        <div class="collapsable-card-body">
            <div class="list-group list-group-flush d-md-flex">
                @auth('web')
                <a href="{{ route('client.tickets.index') }}" class="list-group-item list-group-item-action{{ $rn === 'client.tickets.index' || $rn === 'client.tickets.show' ? ' active' : '' }}">
                    <i class="fas fa-ticket-alt fa-fw"></i>&nbsp;{{ 'My Support Tickets' }}
                </a>
                @endauth
                <a href="{{ route('client.announcements.index') }}" class="list-group-item list-group-item-action{{ str_starts_with($rn, 'client.announcements.') ? ' active' : '' }}">
                    <i class="fas fa-newspaper fa-fw"></i>&nbsp;{{ __('client.nav.announcements') }}
                </a>
                @if(kb_enabled())
                <a href="{{ route('client.kb.index') }}" class="list-group-item list-group-item-action{{ str_starts_with($rn, 'client.kb.') ? ' active' : '' }}">
                    <i class="fas fa-info-circle fa-fw"></i>&nbsp;{{ 'Knowledgebase' }}
                </a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('client.downloads.index'))
                <a href="{{ route('client.downloads.index') }}" class="list-group-item list-group-item-action{{ str_starts_with($rn, 'client.downloads.') ? ' active' : '' }}">
                    <i class="fas fa-download fa-fw"></i>&nbsp;{{ 'Downloads' }}
                </a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('client.network-status'))
                <a href="{{ route('client.network-status') }}" class="list-group-item list-group-item-action{{ $rn === 'client.network-status' ? ' active' : '' }}">
                    <i class="fas fa-rocket fa-fw"></i>&nbsp;{{ __('client.nav.network_status') }}
                </a>
                @endif
                @auth('web')
                <a href="{{ route('client.tickets.create') }}" class="list-group-item list-group-item-action{{ $rn === 'client.tickets.create' ? ' active' : '' }}">
                    <i class="fas fa-comments fa-fw"></i>&nbsp;{{ 'Open Ticket' }}
                </a>
                @endauth
            </div>
        </div>
    </div>

{{-- Account / Profile / Contacts / Users / Security / Password / Emails Sidebar --}}
@elseif(str_starts_with($rn, 'client.account.') || str_starts_with($rn, 'client.emails.'))
    <div class="mb-3 card card-sidebar">
        <div class="card-header">
            <h3 class="card-title m-0">
                <i class="fas fa-user"></i>&nbsp;{{ __('client.nav.account') }}
            </h3>
        </div>
        <div class="collapsable-card-body">
            <div class="list-group list-group-flush d-md-flex">
                <a href="{{ route('client.account.profile') }}" class="list-group-item list-group-item-action{{ $rn === 'client.account.profile' ? ' active' : '' }}">
                    <i class="fas fa-user fa-fw"></i>&nbsp;{{ __('client.nav.my_profile') }}
                </a>
                <a href="{{ route('client.payment-methods.index') }}" class="list-group-item list-group-item-action">
                    <i class="far fa-credit-card fa-fw"></i>&nbsp;{{ 'Payment Methods' }}
                </a>
                <a href="{{ route('client.account.contacts') }}" class="list-group-item list-group-item-action{{ $rn === 'client.account.contacts' ? ' active' : '' }}">
                    <i class="far fa-address-book fa-fw"></i>&nbsp;{{ __('client.nav.contacts') }}
                </a>
                @if(\Illuminate\Support\Facades\Route::has('client.account.users'))
                <a href="{{ route('client.account.users') }}" class="list-group-item list-group-item-action{{ $rn === 'client.account.users' ? ' active' : '' }}">
                    <i class="fas fa-users fa-fw"></i>&nbsp;{{ 'User Management' }}
                </a>
                @endif
                <a href="{{ route('client.account.password') }}" class="list-group-item list-group-item-action{{ $rn === 'client.account.password' ? ' active' : '' }}">
                    <i class="fas fa-key fa-fw"></i>&nbsp;{{ __('client.nav.password') }}
                </a>
                @if(\Illuminate\Support\Facades\Route::has('client.account.security'))
                <a href="{{ route('client.account.security') }}" class="list-group-item list-group-item-action{{ $rn === 'client.account.security' ? ' active' : '' }}">
                    <i class="fas fa-shield-alt fa-fw"></i>&nbsp;{{ 'Security Settings' }}
                </a>
                @endif
                @if(\Illuminate\Support\Facades\Route::has('client.emails.index'))
                <a href="{{ route('client.emails.index') }}" class="list-group-item list-group-item-action{{ str_starts_with($rn, 'client.emails.') ? ' active' : '' }}">
                    <i class="far fa-envelope fa-fw"></i>&nbsp;{{ 'Email History' }}
                </a>
                @endif
            </div>
        </div>
    </div>
@endif

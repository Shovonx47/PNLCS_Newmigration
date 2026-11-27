@extends('admin.layouts.app')
@section('title', __('admin.nav.setup'))
@section('content')

<style>
    .whmcs-setup-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin: 0 0 18px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e5e7eb;
    }
    .whmcs-setup-header h1 {
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        font-size: 22px !important;
        font-weight: 300 !important;
        color: #333 !important;
    }
    .whmcs-setup-search {
        position: relative;
        width: 320px;
        max-width: 100%;
    }
    .whmcs-setup-search i {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: #888;
        font-size: 13px;
        pointer-events: none;
    }
    .whmcs-setup-search input {
        width: 100%;
        height: 36px;
        padding: 6px 12px 6px 34px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 13px;
        background: #fff;
        color: #333;
    }
    .whmcs-setup-search input:focus {
        border-color: #1a4d80;
        outline: none;
        box-shadow: 0 0 0 2px rgba(26,77,128,0.12);
    }
    .whmcs-setup-cats {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 18px;
    }
    .whmcs-setup-cat-btn {
        padding: 5px 13px;
        font-size: 12.5px;
        font-weight: 600;
        border: 1px solid #d1d5db;
        border-radius: 20px;
        background: #fff;
        color: #4b5563;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .whmcs-setup-cat-btn:hover {
        border-color: #1a4d80;
        color: #1a4d80;
    }
    .whmcs-setup-cat-btn.active {
        background: #1a4d80;
        border-color: #1a4d80;
        color: #fff;
    }
    .whmcs-setup-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }
    @media (max-width: 1200px) {
        .whmcs-setup-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 900px) {
        .whmcs-setup-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 560px) {
        .whmcs-setup-grid { grid-template-columns: 1fr; }
    }
    .whmcs-setup-card {
        display: flex;
        align-items: flex-start;
        gap: 13px;
        padding: 15px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        text-decoration: none !important;
        color: #333;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.1s ease;
    }
    .whmcs-setup-card:hover {
        border-color: #1a4d80;
        box-shadow: 0 4px 12px rgba(26,77,128,0.09);
        text-decoration: none !important;
    }
    .whmcs-setup-card .setup-ico {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        background: #eef4fb;
        color: #1a4d80;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .whmcs-setup-card .setup-title {
        font-size: 13.5px;
        font-weight: 600;
        color: #1a4d80;
        margin: 0 0 3px;
        line-height: 1.3;
    }
    .whmcs-setup-card .setup-desc {
        font-size: 12px;
        color: #6b7280;
        margin: 0;
        line-height: 1.4;
    }
</style>

@php
    $setupCards = [
        ['cat' => 'general',  'icon' => 'fas fa-sliders-h',          'title' => 'General Settings',       'desc' => 'Company details, ordering, billing, mail & localization.', 'url' => route('admin.settings.general')],
        ['cat' => 'general',  'icon' => 'fas fa-cogs',               'title' => 'Automation Settings',    'desc' => 'Cron scheduling, automatic billing, suspension & tasks.',  'url' => route('admin.config.automation')],
        ['cat' => 'general',  'icon' => 'fas fa-palette',            'title' => 'Appearance & Branding',  'desc' => 'Themes, logos, white-label branding and homepage layout.', 'url' => route('admin.settings.appearance')],
        ['cat' => 'general',  'icon' => 'fas fa-language',           'title' => 'Languages',              'desc' => 'Active client & admin languages and translation strings.', 'url' => route('admin.config.languages.index')],
        ['cat' => 'general',  'icon' => 'fas fa-bell',               'title' => 'Notification Settings',  'desc' => 'Slack, webhook, email & staff alert routing rules.',       'url' => route('admin.config.notifications')],
        ['cat' => 'general',  'icon' => 'fas fa-envelope-open-text', 'title' => 'Email Templates',        'desc' => 'Customize automated client, billing & support emails.',    'url' => route('admin.config.email-templates')],

        ['cat' => 'products', 'icon' => 'fas fa-box-open',           'title' => 'Products/Services',      'desc' => 'Set up hosting plans, VPS packages & recurring services.', 'url' => route('admin.products.index')],
        ['cat' => 'products', 'icon' => 'fas fa-sliders-h',          'title' => 'Configurable Options',   'desc' => 'Add customizable upgradeable options to products.',        'url' => route('admin.config.config-options')],
        ['cat' => 'products', 'icon' => 'fas fa-puzzle-piece',       'title' => 'Product Addons',         'desc' => 'Configure optional add-on packages for active services.',  'url' => route('admin.config.addons')],
        ['cat' => 'products', 'icon' => 'fas fa-boxes',              'title' => 'Product Bundles',        'desc' => 'Bundle multiple products and domains with discounts.',     'url' => route('admin.config.bundles')],
        ['cat' => 'products', 'icon' => 'fas fa-server',             'title' => 'Servers',                'desc' => 'Configure provisioning servers and control panels.',       'url' => route('admin.config.servers')],
        ['cat' => 'products', 'icon' => 'fas fa-network-wired',      'title' => 'Server Groups',          'desc' => 'Organize servers into rotation and fill groups.',          'url' => route('admin.config.server-groups')],

        ['cat' => 'payments', 'icon' => 'fas fa-credit-card',        'title' => 'Payment Gateways',       'desc' => 'Configure online payment gateways & offline methods.',     'url' => route('admin.config.gateways')],
        ['cat' => 'payments', 'icon' => 'fas fa-coins',              'title' => 'Currencies',             'desc' => 'Manage BDT, USD & multi-currency exchange rates.',         'url' => route('admin.config.currencies')],
        ['cat' => 'payments', 'icon' => 'fas fa-percentage',         'title' => 'Tax Configuration',      'desc' => 'Configure VAT, inclusive/exclusive tax rules & zones.',    'url' => route('admin.config.tax')],
        ['cat' => 'payments', 'icon' => 'fas fa-tags',               'title' => 'Promotions and Coupons', 'desc' => 'Create recurring or one-time promotional discount codes.', 'url' => route('admin.config.promotions')],

        ['cat' => 'domains',  'icon' => 'fas fa-globe',              'title' => 'Domain Pricing',         'desc' => 'Configure TLD extensions, registration & renewal prices.', 'url' => route('admin.config.domain-pricing')],
        ['cat' => 'domains',  'icon' => 'fas fa-atlas',              'title' => 'Domain Registrars',      'desc' => 'Connect and configure automated domain registrars.',       'url' => route('admin.config.registrars')],
        ['cat' => 'domains',  'icon' => 'fas fa-lock',               'title' => 'SSL Modules',            'desc' => 'Configure automated SSL certificate authorities.',         'url' => route('admin.config.sslModules')],

        ['cat' => 'support',  'icon' => 'fas fa-life-ring',          'title' => 'Support Departments',    'desc' => 'Configure ticket departments, POP3/IMAP piping & staff.',  'url' => route('admin.config.ticket-departments')],
        ['cat' => 'support',  'icon' => 'fas fa-tasks',              'title' => 'Ticket Statuses',        'desc' => 'Customize support ticket statuses and auto-close rules.',  'url' => route('admin.config.ticket-statuses')],
        ['cat' => 'support',  'icon' => 'fas fa-level-up-alt',       'title' => 'Escalation Rules',       'desc' => 'Automate SLA escalations for unanswered support tickets.', 'url' => route('admin.config.ticket-escalation')],
        ['cat' => 'support',  'icon' => 'fas fa-reply-all',          'title' => 'Predefined Replies',     'desc' => 'Saved canned responses for faster support replies.',       'url' => route('admin.config.predefined-replies')],
        ['cat' => 'support',  'icon' => 'fas fa-shield-virus',       'title' => 'Spam Control',           'desc' => 'Block unwanted senders, phrases & subjects in tickets.',   'url' => route('admin.config.ticket-spam')],

        ['cat' => 'staff',    'icon' => 'fas fa-user-shield',        'title' => 'Administrator Users',    'desc' => 'Manage staff accounts, 2FA, signatures & departments.',    'url' => route('admin.config.admins')],
        ['cat' => 'staff',    'icon' => 'fas fa-user-tag',           'title' => 'Administrator Roles',    'desc' => 'Configure granular role-based permissions for staff.',     'url' => route('admin.config.admin-roles')],
        ['cat' => 'staff',    'icon' => 'fas fa-key',                'title' => 'Manage API Credentials', 'desc' => 'Generate and restrict API tokens and role credentials.',   'url' => route('admin.config.api-credentials')],
        ['cat' => 'staff',    'icon' => 'fas fa-users',              'title' => 'Client Groups',          'desc' => 'Group clients with custom colors, discounts & rules.',     'url' => route('admin.config.client-groups')],
        ['cat' => 'staff',    'icon' => 'fas fa-list-alt',           'title' => 'Custom Client Fields',   'desc' => 'Add custom profile and registration fields for clients.',  'url' => route('admin.config.custom-fields')],
        ['cat' => 'staff',    'icon' => 'fas fa-ban',                'title' => 'Banned IPs',             'desc' => 'Restrict abusive IP addresses from accessing portal.',     'url' => route('admin.config.banned-ips')],
        ['cat' => 'staff',    'icon' => 'fas fa-envelope-square',    'title' => 'Banned Emails',          'desc' => 'Block disposable or banned email domains on signup.',      'url' => route('admin.config.banned-emails')],

        ['cat' => 'apps',     'icon' => 'fas fa-cubes',              'title' => 'Apps & Integrations',    'desc' => 'Activate and configure Addon Modules & SoftCents tools.',  'url' => route('admin.config.addons.modules')],
        ['cat' => 'apps',     'icon' => 'fas fa-toggle-on',          'title' => 'System Modules',         'desc' => 'Enable or disable server, gateway & registrar modules.',   'url' => route('admin.config.modules')],
        ['cat' => 'apps',     'icon' => 'fas fa-database',           'title' => 'WHMCS Migration Tool',   'desc' => 'Import data and sync records from WHMCS installations.',   'url' => route('admin.whmcs-import.index')],
        ['cat' => 'apps',     'icon' => 'fas fa-cloud-download-alt', 'title' => 'System Updates',         'desc' => 'Check update channels and apply platform updates.',        'url' => route('admin.config.updates')],
        ['cat' => 'apps',     'icon' => 'fas fa-broom',              'title' => 'Log Retention',          'desc' => 'Configure automated pruning schedules for system logs.',   'url' => route('admin.settings.log-retention')],
    ];

    $categories = [
        'all'      => 'All Settings',
        'general'  => 'General',
        'products' => 'Products/Services',
        'payments' => 'Payments & Billing',
        'domains'  => 'Domains & SSL',
        'support'  => 'Support',
        'staff'    => 'Staff & Security',
        'apps'     => 'Apps & System',
    ];
@endphp

<div class="whmcs-setup-header">
    <h1>{{ 'System Settings' }}</h1>
    <div class="whmcs-setup-search">
        <i class="fas fa-search"></i>
        <input type="text" id="setupSearchInput" placeholder="{{ 'Search System Settings...' }}" autocomplete="off">
    </div>
</div>

<div class="whmcs-setup-cats" id="setupCategoryBar">
    @foreach($categories as $catKey => $catLabel)
        <button type="button" class="whmcs-setup-cat-btn {{ $loop->first ? 'active' : '' }}" data-cat="{{ $catKey }}">
            {{ $catLabel }}
        </button>
    @endforeach
</div>

<div class="whmcs-setup-grid" id="setupCardsGrid">
    @foreach($setupCards as $card)
        <a href="{{ $card['url'] }}" class="whmcs-setup-card" data-cat="{{ $card['cat'] }}" data-keywords="{{ strtolower($card['title'] . ' ' . $card['desc']) }}">
            <span class="setup-ico"><i class="{{ $card['icon'] }}"></i></span>
            <span>
                <div class="setup-title">{{ $card['title'] }}</div>
                <p class="setup-desc">{{ $card['desc'] }}</p>
            </span>
        </a>
    @endforeach
</div>

<script>
(function () {
    var input = document.getElementById('setupSearchInput');
    var catBtns = document.querySelectorAll('#setupCategoryBar .whmcs-setup-cat-btn');
    var cards = document.querySelectorAll('#setupCardsGrid .whmcs-setup-card');
    var activeCat = 'all';

    function filterCards() {
        var q = (input ? input.value : '').toLowerCase().trim();
        cards.forEach(function (card) {
            var matchCat = (activeCat === 'all') || (card.getAttribute('data-cat') === activeCat);
            var matchQuery = !q || (card.getAttribute('data-keywords') || '').indexOf(q) !== -1;
            card.style.display = (matchCat && matchQuery) ? 'flex' : 'none';
        });
    }

    catBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            catBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            activeCat = btn.getAttribute('data-cat') || 'all';
            filterCards();
        });
    });

    if (input) {
        input.addEventListener('input', filterCards);
    }
})();
</script>

@endsection

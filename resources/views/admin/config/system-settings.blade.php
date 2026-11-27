@extends('admin.layouts.app')
@section('title', 'System Settings')
@section('content')

<style>
    .whmcs-ss-banner {
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
        padding: 24px 30px 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }
    .whmcs-ss-banner h1.whmcs-ss-title {
        margin: 0 0 8px 0 !important;
        padding: 0 !important;
        border: 0 !important;
        font-size: 20px !important;
        font-weight: 400 !important;
        color: #333333 !important;
        line-height: 1.3 !important;
    }
    .whmcs-ss-subtitle {
        margin: 0;
        font-size: 20px;
        font-weight: 300;
        color: #666666;
        line-height: 1.35;
    }
    .whmcs-ss-progress-box {
        min-width: 295px;
    }
    .whmcs-ss-tasks-link {
        background: none;
        border: 0;
        padding: 0;
        margin: 0 0 9px 0;
        font-size: 13.5px;
        font-weight: 600;
        color: #222222;
        cursor: pointer;
        display: inline-block;
        text-align: left;
    }
    .whmcs-ss-tasks-link:hover {
        color: #1a4d80;
        text-decoration: underline;
    }
    .whmcs-ss-progress-row {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .whmcs-ss-progress-track {
        width: 245px;
        height: 10px;
        background: #f0f2f4;
        border-radius: 5px;
        overflow: hidden;
    }
    .whmcs-ss-progress-fill {
        height: 100%;
        background: #5cb85c;
        border-radius: 5px;
        transition: width 0.25s ease;
    }
    .whmcs-ss-progress-pct {
        font-size: 13px;
        color: #555555;
        min-width: 34px;
    }

    .whmcs-ss-body {
        background: #f8f9fa;
        padding: 25px 30px 52px;
        min-height: calc(100vh - 220px);
    }
    .whmcs-ss-layout {
        display: flex;
        align-items: flex-start;
        gap: 32px;
    }
    .whmcs-ss-rail {
        width: 230px;
        flex-shrink: 0;
    }
    .whmcs-ss-search {
        position: relative;
        margin-bottom: 20px;
    }
    .whmcs-ss-search i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #888888;
        font-size: 13.5px;
        pointer-events: none;
    }
    .whmcs-ss-search input {
        width: 100%;
        height: 38px;
        padding: 6px 12px 6px 35px;
        background: #ffffff;
        border: 1px solid #d1d5db;
        border-radius: 3px;
        font-size: 13.5px;
        color: #333333;
    }
    .whmcs-ss-search input:focus {
        outline: none;
        border-color: #66afe9;
        box-shadow: 0 0 0 2px rgba(102, 175, 233, 0.2);
    }
    .whmcs-ss-cats {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .whmcs-ss-cats li {
        margin: 0;
    }
    .whmcs-ss-cat-btn {
        display: block;
        width: 100%;
        text-align: left;
        background: transparent;
        border: 0;
        border-left: 3px solid transparent;
        padding: 10px 14px;
        font-size: 13.5px;
        font-weight: 400;
        color: #333333;
        cursor: pointer;
        transition: background 0.12s ease;
    }
    .whmcs-ss-cat-btn:hover {
        background: #eef2f5;
    }
    .whmcs-ss-cat-btn.active {
        background: #e6f0e4 !important;
        border-left-color: #5cb85c !important;
        color: #222222 !important;
    }

    .whmcs-ss-main {
        flex: 1;
        min-width: 0;
    }
    .whmcs-ss-main-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding-bottom: 14px;
        margin-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }
    .whmcs-ss-main-top h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 400;
        color: #333333;
    }
    .whmcs-ss-sort {
        width: 215px;
        height: 34px;
        padding: 5px 10px;
        border: 1px solid #cccccc;
        border-radius: 3px;
        background: #ffffff;
        font-size: 13px;
        color: #444444;
    }

    .whmcs-ss-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
    }
    @media (max-width: 1280px) {
        .whmcs-ss-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 980px) {
        .whmcs-ss-layout { flex-direction: column; }
        .whmcs-ss-rail { width: 100%; }
        .whmcs-ss-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 580px) {
        .whmcs-ss-grid { grid-template-columns: 1fr; }
    }

    .whmcs-ss-card {
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border: 1px solid #eceeef;
        border-radius: 2px;
        min-height: 214px;
        text-decoration: none !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .whmcs-ss-card:hover {
        border-color: #cfd8e3;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
        text-decoration: none !important;
    }
    .whmcs-ss-card-icon {
        height: 102px;
        margin: 0 18px;
        border-bottom: 1px solid #f2f3f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 44px;
        color: #dcdcdc;
        transition: color 0.15s ease;
    }
    .whmcs-ss-card:hover .whmcs-ss-card-icon {
        color: #0c3b5e;
    }
    .whmcs-ss-card-body {
        padding: 16px 20px 20px;
        flex: 1;
    }
    .whmcs-ss-card-title {
        font-size: 14px;
        font-weight: 600;
        color: #0c3b5e;
        line-height: 1.38;
        margin: 0 0 5px 0;
    }
    .whmcs-ss-badge {
        display: inline-block;
        padding: 2px 5px;
        font-size: 9.5px;
        font-weight: 700;
        line-height: 1.2;
        color: #ffffff;
        border-radius: 3px;
        text-transform: uppercase;
        vertical-align: middle;
        margin-left: 4px;
        letter-spacing: 0.2px;
    }
    .whmcs-ss-badge.updated {
        background: #5bc0de;
    }
    .whmcs-ss-badge.new-service {
        background: #5cb85c;
    }
    .whmcs-ss-card-desc {
        font-size: 13px;
        font-weight: 400;
        color: #9ca3af;
        margin: 0;
        line-height: 1.42;
    }
    .whmcs-ss-empty {
        display: none;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 3px;
        padding: 36px 20px;
        text-align: center;
        color: #6b7280;
        font-size: 14px;
    }
</style>

@php
    $categories = [
        'all'      => ['nav' => 'All',                 'heading' => 'All Settings'],
        'system'   => ['nav' => 'System',              'heading' => 'System'],
        'apps'     => ['nav' => 'Apps & Integrations', 'heading' => 'Apps & Integrations'],
        'users'    => ['nav' => 'User Management',     'heading' => 'User Management'],
        'products' => ['nav' => 'Products & Services', 'heading' => 'Products & Services'],
        'support'  => ['nav' => 'Support',             'heading' => 'Support'],
        'security' => ['nav' => 'API & Security',      'heading' => 'API & Security'],
    ];

    $setupCards = [
        // Row 1
        ['cat' => 'system',   'icon' => 'fas fa-cog',              'badge' => null,          'title' => 'General Settings',                 'desc' => 'General settings and configuration',             'url' => route('admin.settings.general')],
        ['cat' => 'apps',     'icon' => 'fas fa-cubes',            'badge' => 'updated',     'title' => 'Apps & Integrations',              'desc' => 'Browse available modules and integrations',      'url' => route('admin.config.addons.modules')],
        ['cat' => 'system',   'icon' => 'far fa-clock',            'badge' => 'updated',     'title' => 'Automation Settings',              'desc' => 'Timing and automation setup',                    'url' => route('admin.config.automation', ['view' => 'settings'])],
        ['cat' => 'apps',     'icon' => 'far fa-dot-circle',       'badge' => 'new-service', 'title' => 'MarketConnect',                    'desc' => 'Re-sell Market Leading Services',                'url' => route('admin.config.marketconnect')],

        // Row 2
        ['cat' => 'products', 'icon' => 'fas fa-cube',             'badge' => null,          'title' => 'Products/Services',                'desc' => 'Setup and manage products',                      'url' => route('admin.products.index')],
        ['cat' => 'products', 'icon' => 'fas fa-sliders-h',        'badge' => null,          'title' => 'Configurable Options',             'desc' => 'Manage extras and options for products',         'url' => route('admin.config.config-options')],
        ['cat' => 'products', 'icon' => 'far fa-sticky-note',      'badge' => 'updated',     'title' => 'Product Addons',                   'desc' => 'Setup and manage product add-ons',               'url' => route('admin.config.addons')],
        ['cat' => 'products', 'icon' => 'fas fa-cubes',            'badge' => null,          'title' => 'Product Bundles',                  'desc' => 'Create and manage product bundles',              'url' => route('admin.config.bundles')],

        // Row 3
        ['cat' => 'products', 'icon' => 'fas fa-ticket-alt',       'badge' => null,          'title' => 'Promotions and Coupons',           'desc' => 'Setup and manage promotion codes',               'url' => route('admin.config.promotions')],
        ['cat' => 'products', 'icon' => 'fas fa-table',            'badge' => null,          'title' => 'Domain Pricing',                   'desc' => 'Setup domain extensions and pricing',            'url' => route('admin.config.domain-pricing')],
        ['cat' => 'products', 'icon' => 'fas fa-server',           'badge' => null,          'title' => 'Servers',                          'desc' => 'Configure and manage your servers',              'url' => route('admin.config.servers')],
        ['cat' => 'apps',     'icon' => 'fas fa-cube',             'badge' => null,          'title' => 'Addon Modules',                    'desc' => 'Activate and manage add-on modules',             'url' => route('admin.config.addons.modules')],

        // Row 4
        ['cat' => 'support',  'icon' => 'far fa-life-ring',        'badge' => null,          'title' => 'Support Departments',              'desc' => 'Setup and manage ticket departments',            'url' => route('admin.config.ticket-departments')],
        ['cat' => 'support',  'icon' => 'fas fa-random',           'badge' => null,          'title' => 'Escalation Rules',                 'desc' => 'Manage ticket escalation rules',                 'url' => route('admin.config.ticket-escalation')],
        ['cat' => 'support',  'icon' => 'fas fa-filter',           'badge' => null,          'title' => 'Spam Control',                     'desc' => 'Manage email spam filters',                      'url' => route('admin.config.ticket-spam')],
        ['cat' => 'system',   'icon' => 'fas fa-envelope',         'badge' => 'updated',     'title' => 'Email Templates',                  'desc' => 'Customise email templates',                      'url' => route('admin.config.email-templates')],

        // Row 5
        ['cat' => 'users',    'icon' => 'fas fa-user',             'badge' => null,          'title' => 'Client Groups',                    'desc' => 'Manage client groups',                           'url' => route('admin.config.client-groups')],
        ['cat' => 'users',    'icon' => 'fas fa-tags',             'badge' => null,          'title' => 'Custom Fields',                    'desc' => 'Manage custom profile fields',                   'url' => route('admin.config.custom-fields')],
        ['cat' => 'products', 'icon' => 'fas fa-file-alt',         'badge' => null,          'title' => 'Order Statuses',                   'desc' => 'Setup and manage order statuses',                'url' => route('admin.config.order-statuses')],
        ['cat' => 'security', 'icon' => 'fas fa-location-arrow',   'badge' => null,          'title' => 'Banned IPs',                       'desc' => 'View and manage IP restrictions',                'url' => route('admin.config.banned-ips')],

        // Row 6
        ['cat' => 'security', 'icon' => 'far fa-envelope',         'badge' => null,          'title' => 'Banned Emails',                    'desc' => 'Manage banned email domains',                    'url' => route('admin.config.banned-emails')],
        ['cat' => 'users',    'icon' => 'fas fa-link',             'badge' => null,          'title' => 'Sign-In Integrations',             'desc' => 'Setup sign-in with Google, Facebook and Twitter', 'url' => route('admin.config.signin-integrations')],
        ['cat' => 'system',   'icon' => 'fas fa-bell',             'badge' => null,          'title' => 'Notifications',                    'desc' => 'Setup Notifications with Slack and more.',       'url' => route('admin.config.notifications')],
        ['cat' => 'system',   'icon' => 'fas fa-university',       'badge' => null,          'title' => 'Payment Gateways',                 'desc' => 'Setup and manage payment gateways',              'url' => route('admin.config.gateways')],

        // Row 7
        ['cat' => 'products', 'icon' => 'fas fa-globe',            'badge' => null,          'title' => 'Domain Registrars',                'desc' => 'Configure and manage registrars',                'url' => route('admin.config.registrars')],
        ['cat' => 'system',   'icon' => 'far fa-money-bill-alt',   'badge' => null,          'title' => 'Currencies',                       'desc' => 'Setup and manage currencies',                    'url' => route('admin.config.currencies')],
        ['cat' => 'system',   'icon' => 'fas fa-gavel',            'badge' => 'updated',     'title' => 'Tax Configuration',                'desc' => 'Configure Taxes and VAT',                        'url' => route('admin.config.tax')],
        ['cat' => 'security', 'icon' => 'fas fa-rocket',           'badge' => null,          'title' => 'Fraud Protection',                 'desc' => 'Configure fraud monitoring automation',          'url' => route('admin.config.fraud-protection')],

        // Row 8
        ['cat' => 'users',    'icon' => 'fas fa-user',             'badge' => null,          'title' => 'Administrator Users',              'desc' => 'Setup and manage staff members',                 'url' => route('admin.config.admins')],
        ['cat' => 'users',    'icon' => 'fas fa-lock',             'badge' => null,          'title' => 'Administrator Roles & Permissions', 'desc' => 'Configure user permissions',                    'url' => route('admin.config.admin-roles')],
        ['cat' => 'security', 'icon' => 'fas fa-mobile-alt',       'badge' => null,          'title' => 'Two-Factor Authentication',        'desc' => 'Add a second layer of security to logins',       'url' => route('admin.config.two-factor')],
        ['cat' => 'support',  'icon' => 'fas fa-star',             'badge' => null,          'title' => 'Ticket Statuses',                  'desc' => 'Customise support ticket statuses',              'url' => route('admin.config.ticket-statuses')],

        // Row 9
        ['cat' => 'security', 'icon' => 'far fa-question-circle',  'badge' => null,          'title' => 'Security Questions',               'desc' => 'Setup and manage security questions',            'url' => route('admin.config.security-questions')],
        ['cat' => 'security', 'icon' => 'fas fa-laptop',           'badge' => null,          'title' => 'API Credentials',                  'desc' => 'Create and manage API users',                    'url' => route('admin.config.api-credentials')],
        ['cat' => 'security', 'icon' => 'far fa-handshake',        'badge' => null,          'title' => 'Application Links',                'desc' => 'Setup and manage application links',             'url' => route('admin.config.application-links')],
        ['cat' => 'security', 'icon' => 'fas fa-universal-access', 'badge' => null,          'title' => 'OpenID Connect',                   'desc' => 'Manage OpenID API Credentials',                  'url' => route('admin.config.openid-connect')],

        // Row 10
        ['cat' => 'system',   'icon' => 'fas fa-hdd',              'badge' => null,          'title' => 'Storage Settings',                 'desc' => 'Configure where and how file assets are stored', 'url' => route('admin.config.storage-settings')],
        ['cat' => 'system',   'icon' => 'fas fa-server',           'badge' => null,          'title' => 'Database Backups',                 'desc' => 'Manage automated backups',                       'url' => route('admin.config.database-backups')],
    ];

    $pct = isset($setupPercent) ? (int) $setupPercent : 44;
    $tasks = $setupTasks ?? [];
@endphp

<div class="whmcs-ss-banner">
    <div>
        <h1 class="whmcs-ss-title">{{ 'System Settings' }}</h1>
        <p class="whmcs-ss-subtitle">{{ 'Set up and configure your WHMCS installation.' }}</p>
    </div>
    <div class="whmcs-ss-progress-box">
        <button type="button" class="whmcs-ss-tasks-link" onclick="document.getElementById('modalSetupTasks').style.display='flex'">
            {{ 'Click here to view the setup tasks' }}
        </button>
        <div class="whmcs-ss-progress-row">
            <div class="whmcs-ss-progress-track">
                <div class="whmcs-ss-progress-fill" style="width: {{ max(0, min(100, $pct)) }}%;"></div>
            </div>
            <span class="whmcs-ss-progress-pct">{{ $pct }}%</span>
        </div>
    </div>
</div>

<div class="whmcs-ss-body">
    <div class="whmcs-ss-layout">
        <aside class="whmcs-ss-rail">
            <div class="whmcs-ss-search">
                <i class="fas fa-search"></i>
                <input type="text" id="setupSearchInput" placeholder="{{ 'Search' }}" autocomplete="off">
            </div>
            <ul class="whmcs-ss-cats" id="setupCategoryBar">
                @foreach($categories as $catKey => $catMeta)
                    <li>
                        <button type="button"
                            class="whmcs-ss-cat-btn {{ $loop->first ? 'active' : '' }}"
                            data-cat="{{ $catKey }}"
                            data-heading="{{ $catMeta['heading'] }}">
                            {{ $catMeta['nav'] }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </aside>

        <section class="whmcs-ss-main">
            <div class="whmcs-ss-main-top">
                <h2 id="setupCategoryHeading">{{ 'All Settings' }}</h2>
                <select id="setupSortSelect" class="whmcs-ss-sort" aria-label="{{ 'Sort System Settings' }}">
                    <option value="popularity">{{ 'Popularity' }}</option>
                    <option value="alphabetical">{{ 'Alphabetical' }}</option>
                </select>
            </div>

            <div class="whmcs-ss-grid" id="setupCardsGrid">
                @foreach($setupCards as $idx => $card)
                    <a href="{{ $card['url'] }}"
                        class="whmcs-ss-card"
                        data-cat="{{ $card['cat'] }}"
                        data-order="{{ $idx }}"
                        data-title="{{ strtolower($card['title']) }}"
                        data-keywords="{{ strtolower($card['title'] . ' ' . $card['desc']) }}">
                        <div class="whmcs-ss-card-icon">
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                        <div class="whmcs-ss-card-body">
                            <div class="whmcs-ss-card-title">
                                {{ $card['title'] }}
                                @if($card['badge'] === 'updated')
                                    <span class="whmcs-ss-badge updated">{{ 'UPDATED' }}</span>
                                @elseif($card['badge'] === 'new-service')
                                    <span class="whmcs-ss-badge new-service">{{ 'NEW SERVICE' }}</span>
                                @endif
                            </div>
                            <p class="whmcs-ss-card-desc">{{ $card['desc'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="whmcs-ss-empty" id="setupEmptyState">
                {{ 'No matching system settings found for your search filter.' }}
            </div>
        </section>
    </div>
</div>

{{-- Setup Tasks Checklist Modal --}}
<div id="modalSetupTasks" style="display:none;position:fixed;inset:0;z-index:1060;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.45);" onclick="document.getElementById('modalSetupTasks').style.display='none'"></div>
    <div style="position:relative;background:#ffffff;border-radius:4px;width:580px;max-width:95%;max-height:86vh;display:flex;flex-direction:column;box-shadow:0 12px 32px rgba(0,0,0,0.28);overflow:hidden;">
        <div style="padding:14px 18px;background:#1a4d80;color:#ffffff;display:flex;align-items:center;justify-content:space-between;">
            <strong style="font-size:15px;">{{ 'System Setup Checklist (' . $pct . '% Complete)' }}</strong>
            <button type="button" onclick="document.getElementById('modalSetupTasks').style.display='none'" style="background:none;border:0;color:#ffffff;font-size:22px;line-height:1;cursor:pointer;">&times;</button>
        </div>
        <div style="padding:16px 18px;overflow-y:auto;">
            @foreach($tasks as $task)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #f1f5f9;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        @if($task['done'])
                            <i class="fas fa-check-circle" style="color:#5cb85c;font-size:17px;"></i>
                        @else
                            <i class="far fa-circle" style="color:#cbd5e1;font-size:17px;"></i>
                        @endif
                        <span style="font-size:13.5px;color:{{ $task['done'] ? '#334155' : '#111827' }};font-weight:{{ $task['done'] ? '400' : '600' }};">{{ $task['title'] }}</span>
                    </div>
                    <a href="{{ $task['url'] }}" class="btn {{ $task['done'] ? 'btn-default' : 'btn-primary' }} btn-xs">
                        {{ $task['done'] ? 'Review' : 'Configure' }}
                    </a>
                </div>
            @endforeach
        </div>
        <div style="padding:10px 18px;background:#f8f9fa;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;gap:8px;">
                <a href="{{ route('admin.settings.appearance') }}" class="btn btn-default btn-xs">{{ 'Appearance & Themes' }}</a>
                <a href="{{ route('admin.config.languages.index') }}" class="btn btn-default btn-xs">{{ 'Languages' }}</a>
                <a href="{{ route('admin.whmcs-import.index') }}" class="btn btn-default btn-xs">{{ 'WHMCS Import' }}</a>
            </div>
            <button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('modalSetupTasks').style.display='none'">{{ 'Close' }}</button>
        </div>
    </div>
</div>

<script>
(function () {
    var input = document.getElementById('setupSearchInput');
    var catBtns = Array.prototype.slice.call(document.querySelectorAll('#setupCategoryBar .whmcs-ss-cat-btn'));
    var grid = document.getElementById('setupCardsGrid');
    var cards = Array.prototype.slice.call(document.querySelectorAll('#setupCardsGrid .whmcs-ss-card'));
    var headingEl = document.getElementById('setupCategoryHeading');
    var sortSelect = document.getElementById('setupSortSelect');
    var emptyEl = document.getElementById('setupEmptyState');
    var activeCat = 'all';

    function applyFilterAndSort() {
        var q = (input ? input.value : '').toLowerCase().trim();
        var sortMode = sortSelect ? sortSelect.value : 'popularity';

        var ordered = cards.slice().sort(function (a, b) {
            if (sortMode === 'alphabetical') {
                var ta = a.getAttribute('data-title') || '';
                var tb = b.getAttribute('data-title') || '';
                return ta.localeCompare(tb);
            }
            return parseInt(a.getAttribute('data-order') || '0', 10) - parseInt(b.getAttribute('data-order') || '0', 10);
        });

        var visibleCount = 0;
        ordered.forEach(function (card) {
            grid.appendChild(card);
            var matchCat = (activeCat === 'all') || (card.getAttribute('data-cat') === activeCat);
            var matchQuery = !q || (card.getAttribute('data-keywords') || '').indexOf(q) !== -1;
            var show = matchCat && matchQuery;
            card.style.display = show ? 'flex' : 'none';
            if (show) {
                visibleCount++;
            }
        });

        if (emptyEl) {
            emptyEl.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    catBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            catBtns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            activeCat = btn.getAttribute('data-cat') || 'all';
            if (headingEl) {
                headingEl.textContent = btn.getAttribute('data-heading') || 'All Settings';
            }
            applyFilterAndSort();
        });
    });

    if (input) {
        input.addEventListener('input', applyFilterAndSort);
    }
    if (sortSelect) {
        sortSelect.addEventListener('change', applyFilterAndSort);
    }
})();
</script>

@endsection

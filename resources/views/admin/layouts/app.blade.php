<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}" dir="{{ $textDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield("title", "Admin") - {{ company_name() }}</title>
    @vite(["resources/css/app.css"])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
    @if(!empty($customFavicon))
    <link rel="icon" href="{{ $customFavicon }}" type="image/png">
    @endif
    @if(!empty($themeCssVars))
    <style id="theme-vars">{!! $themeCssVars !!}</style>
    @endif
    {{-- Admin views and widgets style themselves with --pn-* names (report
         headings, muted text, borders). Nothing defined them, so a report's
         white heading text sat on no background. They follow the theme's
         tokens, with the starter colours when a token is not set. --}}
    <style id="pn-vars">:root{--pn-primary:var(--theme-primary,#1a4d80);--pn-heading:var(--theme-heading-color,#1e293b);--pn-text:var(--theme-text-color,#333333);--pn-muted:var(--theme-muted-color,#64748b);--pn-border:var(--theme-border-color,#e2e8f0);--pn-link:var(--theme-link-color,#2563eb);--pn-badge-bg:var(--theme-badge-bg,#e8f0fb);--pn-danger:var(--theme-danger,#ef4444);--pn-body-bg:var(--theme-body-bg,#f6f6f6)}</style>
    <style>
        .sidebar.collapsed { width: 0 !important; min-height: 0; overflow: hidden; padding: 0; border: 0; }
        .sidebar.collapsed + .contentarea { margin-left: 0 !important; border-left: none; }
        .sidebar-toggle-btn { display: none; position: fixed; top: 185px; left: 0; z-index: 2000; padding: 5px 8px; background: #666; color: #fff; border: none; border-radius: 0 4px 4px 0; cursor: pointer; font-size: 11px; line-height: 1.2; box-shadow: 0 2px 6px rgba(0,0,0,0.2); }
        .sidebar.collapsed ~ .sidebar-toggle-btn { display: inline-flex; align-items: center; gap: 4px; }
        @media (max-width: 768px) { .sidebar-toggle-btn { display: none !important; } }
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:7px 14px;font-size:13px;font-weight:600;border-radius:4px;border:none;cursor:pointer;text-decoration:none;transition:all 0.15s;line-height:1.3;white-space:nowrap;vertical-align:middle}        .btn svg,.btn i{flex-shrink:0;line-height:1}        .btn-primary{background:var(--theme-primary, #1a4d80);color:#fff}.btn-primary:hover{background:var(--theme-primary-dark, #143d66);color:#fff}        .btn-default{background:#f5f5f5;color:#333;border:1px solid #ddd}.btn-default:hover{background:#e8e8e8}        .btn-success{background:#5cb85c;color:#fff}.btn-success:hover{background:#449d44}        .btn-danger{background:#d9534f;color:#fff}.btn-danger:hover{background:#c9302c}        .btn-sm{padding:5px 10px;font-size:12px}        .btn-xs{padding:3px 8px;font-size:11px}

        /* ── WHMCS Blend Exact Topbar & Dropdowns ── */
        .navigation {
            background-color: var(--theme-nav-bg, #1a4d80) !important;
            min-height: 45px !important;
            padding: 5px 8px 5px 5px !important;
            position: relative !important;
            z-index: 1030 !important;
        }
        .navigation .logo {
            float: left !important;
            width: 185px !important;
            height: 35px !important;
            padding: 0 10px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: 35px !important;
        }
        .navigation .logo img {
            max-width: 125px !important;
            max-height: 31px !important;
            display: block !important;
        }
        .navigation .caret {
            display: inline-block;
            width: 0;
            height: 0;
            margin-left: 4px;
            vertical-align: middle;
            border-top: 4px dashed;
            border-right: 4px solid transparent;
            border-left: 4px solid transparent;
            opacity: 0.85;
        }
        @media (min-width: 1275px) {
            .navigation .navbar-collapse > ul {
                display: flex !important;
                align-items: center !important;
                gap: 2px !important;
            }
            .navigation .navbar-collapse > ul > li {
                float: left !important;
                width: auto !important;
                position: relative !important;
            }
            .navigation .navbar-collapse > ul > li > a {
                padding: 0 10px !important;
                height: 35px !important;
                line-height: 35px !important;
                border-radius: 4px !important;
                font-size: 13px !important;
                font-weight: 400 !important;
                color: #fff !important;
                text-decoration: none !important;
                display: flex !important;
                align-items: center !important;
            }
            .navigation .navbar-collapse > ul > li.bt > a {
                width: 38px !important;
                padding: 0 !important;
                justify-content: center !important;
            }
            .navigation .navbar-collapse > ul > li > a i:not(.always) {
                display: none !important;
            }
            .navigation .navbar-collapse > ul > li > a i.always {
                display: inline-block !important;
                margin: 0 !important;
                width: auto !important;
            }
            .navigation ul li > a:hover,
            .navigation ul li.has-dropdown:hover > a,
            .navigation ul li.has-dropdown.open > a {
                background-color: rgba(0, 0, 0, 0.45) !important;
                color: #fff !important;
                border-radius: 4px !important;
                text-decoration: none !important;
            }
            /* Standard WHMCS Dropdown Menus */
            .navigation ul li.has-dropdown > ul.dropdown-menu {
                display: none;
                position: absolute !important;
                top: calc(100% + 5px) !important;
                left: 0;
                min-width: 205px;
                padding: 5px 0 !important;
                margin: 0 !important;
                background: #fff !important;
                border: 1px solid rgba(0, 0, 0, 0.15) !important;
                border-radius: 0 0 4px 4px !important;
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.175) !important;
                z-index: 1050 !important;
            }
            /* Invisible bridge so diagonal mouse movement never closes hover menu */
            .navigation ul li.has-dropdown > ul.dropdown-menu::before {
                content: "";
                position: absolute;
                top: -8px;
                left: 0;
                right: 0;
                height: 8px;
            }
            .navigation ul li.has-dropdown:hover > ul.dropdown-menu,
            .navigation ul li.has-dropdown.open > ul.dropdown-menu {
                display: block;
            }
            .navigation ul li.has-dropdown > ul.dropdown-menu.slim {
                min-width: 155px !important;
                width: 155px !important;
            }
            .navigation ul li.has-dropdown > ul.dropdown-menu.addons-menu {
                min-width: 265px !important;
            }
            .navigation ul li.has-dropdown > ul.dropdown-menu > li {
                position: relative;
                width: 100%;
                float: none;
            }
            .navigation ul li.has-dropdown > ul.dropdown-menu li a {
                display: block !important;
                padding: 4px 20px !important;
                font-size: 13px !important;
                line-height: 1.45 !important;
                color: #444 !important;
                text-align: left !important;
                white-space: nowrap !important;
                border-radius: 0 !important;
                text-decoration: none !important;
                background: transparent;
            }
            .navigation ul li.has-dropdown > ul.dropdown-menu li a:hover {
                background-color: #1b4d7f !important;
                color: #fff !important;
            }
            .navigation ul li.has-dropdown > ul.dropdown-menu li a.addon-hl:hover {
                background-color: #1b4d7f !important;
                color: #fff !important;
            }
            /* Flyout Sub-menus (Products/Services, Invoices, Support Tickets, etc.) */
            .navigation ul.dropdown-menu li.has-sub > a {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding-right: 14px !important;
            }
            .navigation ul.dropdown-menu li.has-sub > a::after {
                content: "\203A";
                font-size: 16px;
                line-height: 1;
                opacity: 0.65;
                margin-left: 10px;
            }
            .navigation ul.dropdown-menu li.has-sub > ul.sub-dropdown {
                display: none;
                position: absolute;
                left: 100%;
                top: -6px;
                min-width: 195px;
                padding: 5px 0;
                margin: 0;
                list-style: none;
                background: #fff;
                border: 1px solid rgba(0, 0, 0, 0.15);
                border-radius: 4px;
                box-shadow: 0 6px 12px rgba(0, 0, 0, 0.175);
                z-index: 1060;
            }
            .navigation ul.dropdown-menu li.has-sub:hover > ul.sub-dropdown {
                display: block;
            }

            /* ── Right-Side WHMCS Icon Bar ── */
            .navigation .nav-right-items {
                float: right !important;
                display: flex !important;
                align-items: center !important;
                height: 35px !important;
                gap: 2px !important;
            }
            .navigation .right-nav {
                display: flex !important;
                align-items: center !important;
                list-style: none !important;
                margin: 0 !important;
                padding: 0 !important;
                gap: 2px !important;
            }
            .navigation .right-nav > li.bt {
                width: auto !important;
                position: relative !important;
            }
            .navigation .right-nav > li.bt > a {
                width: 38px !important;
                height: 35px !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                border-radius: 4px !important;
                color: #fff !important;
                font-size: 14px !important;
                text-decoration: none !important;
            }
            .navigation .right-nav > li.bt > a.update-btn {
                color: #ffd741 !important;
            }
            .navigation .right-nav > li.bt > a i {
                margin: 0 !important;
                width: auto !important;
            }
            .navigation .right-nav .profile-avatar {
                width: 23px;
                height: 23px;
                border-radius: 50%;
                display: block;
                flex-shrink: 0;
            }

            /* ── Wrench 2x3 Icon-Grid Dropdown (ul.drop-icons) ── */
            .navigation .right-nav > li.has-dropdown > ul.dropdown-menu.drop-icons {
                width: 324px !important;
                min-width: 324px !important;
                right: -80px !important;
                left: auto !important;
                padding: 8px 6px !important;
                border-radius: 0 0 4px 4px !important;
            }
            .navigation .right-nav > li.has-dropdown:hover > ul.dropdown-menu.drop-icons,
            .navigation .right-nav > li.has-dropdown.open > ul.dropdown-menu.drop-icons {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 4px !important;
            }
            .navigation ul.dropdown-menu.drop-icons > li {
                width: 100% !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .navigation ul.dropdown-menu.drop-icons > li > a {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: flex-start !important;
                height: 94px !important;
                padding: 12px 6px 6px !important;
                margin: 0 !important;
                border-radius: 4px !important;
                text-align: center !important;
                white-space: normal !important;
                color: #444 !important;
                font-size: 12px !important;
                line-height: 1.3 !important;
                background: transparent !important;
            }
            .navigation ul.dropdown-menu.drop-icons > li > a:hover {
                background-color: #f1f3f5 !important;
                color: #222 !important;
            }
            .navigation ul.dropdown-menu.drop-icons .ico-container {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 34px;
                margin: 0 auto 7px;
            }
            .navigation ul.dropdown-menu.drop-icons .ico-container svg {
                width: 30px;
                height: 30px;
                display: block;
            }

            /* ── Account Avatar & Help Right-Aligned Dropdowns ── */
            .navigation .right-nav > li.account > ul.dropdown-menu {
                min-width: 158px !important;
                width: 158px !important;
                right: -40px !important;
                left: auto !important;
            }
            .navigation .right-nav > li.help > ul.dropdown-menu {
                min-width: 185px !important;
                width: 185px !important;
                right: 0 !important;
                left: auto !important;
            }

            /* ── Collapsible WHMCS IntelliSearch Icon -> Bar ── */
            .navigation .intellisearch {
                width: 38px !important;
                height: 35px !important;
                transition: width 0.2s ease !important;
                position: relative !important;
                overflow: visible !important;
                float: none !important;
            }
            .navigation .intellisearch:not(.active) .is-toggle-btn {
                width: 38px;
                height: 35px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: transparent;
                border: 0;
                border-radius: 4px;
                color: #fff;
                font-size: 14px;
                cursor: pointer;
                padding: 0;
            }
            .navigation .intellisearch:not(.active) .is-toggle-btn:hover {
                background: rgba(0, 0, 0, 0.45);
            }
            .navigation .intellisearch:not(.active) form {
                display: none !important;
            }
            .navigation .intellisearch.active {
                width: 245px !important;
            }
            .navigation .intellisearch.active .is-toggle-btn {
                display: none !important;
            }
            .navigation .intellisearch.active form {
                display: flex !important;
                align-items: center !important;
                position: relative !important;
                height: 35px !important;
            }
            .navigation .intellisearch.active form .form-control {
                background: #14273e !important;
                border: 1px solid #0f1f33 !important;
                color: #fff !important;
                height: 34px !important;
                padding: 4px 30px 4px 34px !important;
                border-radius: 4px !important;
                font-size: 12.5px !important;
                width: 100% !important;
                box-shadow: none !important;
            }
            .navigation .intellisearch.active form .is-close-btn {
                position: absolute;
                right: 6px;
                top: 50%;
                transform: translateY(-50%);
                background: transparent;
                border: 0;
                color: rgba(255,255,255,0.65);
                cursor: pointer;
                font-size: 14px;
                line-height: 1;
                padding: 2px 4px;
            }
            .navigation .intellisearch.active form .is-close-btn:hover {
                color: #fff;
            }
        }

        /* ── WHMCS My Notes Modal ── */
        .whmcs-notes-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.48);
            z-index: 2100;
            align-items: flex-start;
            justify-content: center;
            padding-top: 70px;
        }
        .whmcs-notes-backdrop.open {
            display: flex;
        }
        .whmcs-notes-dialog {
            width: 560px;
            max-width: 92vw;
            background: #fff;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            border: 1px solid #1a4d80;
        }
        .whmcs-notes-header {
            background: #1a4d80;
            color: #fff;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .whmcs-notes-header h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 500;
            color: #fff;
        }
        .whmcs-notes-close {
            background: transparent;
            border: 0;
            color: rgba(255,255,255,0.8);
            font-size: 20px;
            line-height: 1;
            cursor: pointer;
            padding: 0 4px;
        }
        .whmcs-notes-close:hover { color: #fff; }
        .whmcs-notes-body { padding: 15px; }
        .whmcs-notes-footer {
            padding: 10px 15px;
            background: #f5f5f5;
            border-top: 1px solid #ddd;
            text-align: right;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
    </style>

    @if(($textDirection ?? 'ltr') === 'rtl')
    <link rel="stylesheet" href="{{ asset('css/rtl.css') }}">
    @endif
    {!! hook_output('AdminAreaHeadOutput', ['admin' => auth('admin')->user()]) !!}
    @include('partials.dialog-boot')
</head>
<body>
{!! hook_output('AdminAreaTopOutput', ['admin' => auth('admin')->user()]) !!}

{{-- ═══════════════════════════════════════════════
     NAVIGATION BAR (WHMCS Blend exact structure)
     ═══════════════════════════════════════════════ --}}
<div class="navigation clearfix">
    {{-- Logo --}}
    <a href="{{ route('admin.dashboard') }}" class="logo" title="{{ __('admin.sidebar.dashboard') }}">
        <img src="{{ !empty($customLogo) ? $customLogo : asset('images/whmcs-blend-logo.png') }}" alt="{{ __('admin.appearance.logo') }}">
    </a>

    {{-- Mobile toggle --}}
    <ul class="left-nav" style="float:left;">
        <li class="nav-toggle-li" style="float:left;">
            <a href="#" class="nav-toggle" onclick="event.preventDefault(); document.querySelector('.navbar-collapse').classList.toggle('open');">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    {{-- Main navigation (exact WHMCS Blend left menus) --}}
    <div class="navbar-collapse">
        <ul>
            {{-- + Add New (WHMCS Blend slim plus icon) --}}
            <li class="bt has-dropdown">
                <a href="#" onclick="toggleNavDropdown(event, this.parentElement)" title="{{ __('common.actions.add_new') }}">
                    <i class="fas fa-plus always"></i>
                </a>
                <ul class="dropdown-menu slim">
                    <li><a href="{{ route('admin.clients.create') }}">{{ 'Add New Client' }}</a></li>
                    <li><a href="{{ route('admin.orders.create') }}">{{ 'Add New Order' }}</a></li>
                    <li><a href="{{ route('admin.invoices.create') }}">{{ 'Create Invoice' }}</a></li>
                    <li><a href="{{ route('admin.quotes.create') }}">{{ 'Create New Quote' }}</a></li>
                    <li><a href="{{ route('admin.tickets.create') }}">{{ 'Open New Ticket' }}</a></li>
                </ul>
            </li>

            {{-- Clients --}}
            <li class="has-dropdown">
                <a href="#" onclick="toggleNavDropdown(event, this.parentElement)">
                    <i class="fas fa-user"></i>{{ 'Clients' }}<span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="{{ route('admin.clients.index') }}">{{ 'View/Search Clients' }}</a></li>
                    <li><a href="{{ route('admin.clients.billing') }}">{{ 'Manage Users' }}</a></li>
                    <li><a href="{{ route('admin.clients.create') }}">{{ 'Add New Client' }}</a></li>
                    <li class="has-sub">
                        <a href="{{ route('admin.services.index') }}">{{ 'Products/Services' }}</a>
                        <ul class="sub-dropdown">
                            <li><a href="{{ route('admin.services.index', ['type' => 'hostingaccount']) }}">{{ '- Shared Hosting' }}</a></li>
                            <li><a href="{{ route('admin.services.index', ['type' => 'reselleraccount']) }}">{{ '- Reseller Accounts' }}</a></li>
                            <li><a href="{{ route('admin.services.index', ['type' => 'server']) }}">{{ '- VPS/Servers' }}</a></li>
                            <li><a href="{{ route('admin.services.index', ['type' => 'other']) }}">{{ '- Other Services' }}</a></li>
                        </ul>
                    </li>
                    <li><a href="{{ route('admin.config.addons') }}">{{ 'Service Addons' }}</a></li>
                    <li><a href="{{ route('admin.domains.index') }}">{{ 'Domain Registrations' }}</a></li>
                    <li><a href="{{ route('admin.ssl.index') }}">{{ 'SSL Certificates' }}</a></li>
                    <li><a href="{{ route('admin.services.index', ['status' => 'cancelled']) }}">{{ 'Cancellation Requests' }}</a></li>
                    <li><a href="{{ route('admin.affiliates.index') }}">{{ 'Manage Affiliates' }}</a></li>
                </ul>
            </li>

            {{-- Orders --}}
            <li class="has-dropdown">
                <a href="#" onclick="toggleNavDropdown(event, this.parentElement)">
                    <i class="fas fa-shopping-cart"></i>{{ 'Orders' }}<span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="{{ route('admin.orders.index') }}">{{ 'List All Orders' }}</a></li>
                    <li><a href="{{ route('admin.orders.index', ['status' => 'pending']) }}">{{ '- Pending Orders' }}</a></li>
                    <li><a href="{{ route('admin.orders.index', ['status' => 'active']) }}">{{ '- Active Orders' }}</a></li>
                    <li><a href="{{ route('admin.orders.index', ['status' => 'fraud']) }}">{{ '- Fraud Orders' }}</a></li>
                    <li><a href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}">{{ '- Cancelled Orders' }}</a></li>
                    <li><a href="{{ route('admin.orders.create') }}">{{ 'Add New Order' }}</a></li>
                </ul>
            </li>

            {{-- Billing --}}
            <li class="has-dropdown">
                <a href="#" onclick="toggleNavDropdown(event, this.parentElement)">
                    <i class="fas fa-credit-card"></i>{{ 'Billing' }}<span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="{{ route('admin.config.transactions') }}">{{ 'Transactions List' }}</a></li>
                    <li class="has-sub">
                        <a href="{{ route('admin.invoices.index') }}">{{ 'Invoices' }}</a>
                        <ul class="sub-dropdown">
                            <li><a href="{{ route('admin.invoices.index', ['status' => 'paid']) }}">{{ '- Paid' }}</a></li>
                            <li><a href="{{ route('admin.invoices.index', ['status' => 'draft']) }}">{{ '- Draft' }}</a></li>
                            <li><a href="{{ route('admin.invoices.index', ['status' => 'unpaid']) }}">{{ '- Unpaid' }}</a></li>
                            <li><a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}">{{ '- Overdue' }}</a></li>
                            <li><a href="{{ route('admin.invoices.index', ['status' => 'cancelled']) }}">{{ '- Cancelled' }}</a></li>
                            <li><a href="{{ route('admin.invoices.index', ['status' => 'refunded']) }}">{{ '- Refunded' }}</a></li>
                            <li><a href="{{ route('admin.invoices.index', ['status' => 'collections']) }}">{{ '- Collections' }}</a></li>
                            <li><a href="{{ route('admin.payment-notifications.index') }}">{{ '- Payment Pending' }}</a></li>
                        </ul>
                    </li>
                    <li class="has-sub">
                        <a href="{{ route('admin.config.billable-items') }}">{{ 'Billable Items' }}</a>
                        <ul class="sub-dropdown">
                            <li><a href="{{ route('admin.config.billable-items', ['status' => 'uninvoiced']) }}">{{ '- Uninvoiced Items' }}</a></li>
                            <li><a href="{{ route('admin.config.billable-items', ['status' => 'recurring']) }}">{{ '- Recurring Items' }}</a></li>
                        </ul>
                    </li>
                    <li class="has-sub">
                        <a href="{{ route('admin.quotes.index') }}">{{ 'Quotes' }}</a>
                        <ul class="sub-dropdown">
                            <li><a href="{{ route('admin.quotes.index', ['status' => 'valid']) }}">{{ '- Valid' }}</a></li>
                            <li><a href="{{ route('admin.quotes.index', ['status' => 'expired']) }}">{{ '- Expired' }}</a></li>
                            <li><a href="{{ route('admin.quotes.create') }}">{{ '- Create New Quote' }}</a></li>
                        </ul>
                    </li>
                    <li><a href="{{ route('admin.payment-notifications.index') }}">{{ 'Offline Payment Notifications' }}</a></li>
                    @if($ksefActive ?? false)
                    <li><a href="{{ route('admin.ksef.index') }}">{{ __('admin.nav.ksef_status') }}</a></li>
                    @endif
                    <li><a href="{{ route('admin.logs.gateway') }}">{{ 'Gateway Log' }}</a></li>
                </ul>
            </li>

            {{-- Support --}}
            <li class="has-dropdown">
                <a href="#" onclick="toggleNavDropdown(event, this.parentElement)">
                    <i class="fas fa-life-ring"></i>{{ 'Support' }}<span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="{{ route('admin.tickets.index') }}">{{ 'Support Overview' }}</a></li>
                    <li class="has-sub">
                        <a href="{{ route('admin.tickets.index') }}">{{ 'Support Tickets' }}</a>
                        <ul class="sub-dropdown">
                            <li><a href="{{ route('admin.tickets.index', ['flagged' => 1]) }}">{{ '- Flagged Tickets' }}</a></li>
                            <li><a href="{{ route('admin.tickets.index') }}">{{ '- All Active Tickets' }}</a></li>
                            <li><a href="{{ route('admin.tickets.index', ['status' => 'Open']) }}">{{ '- Open' }}</a></li>
                            <li><a href="{{ route('admin.tickets.index', ['status' => 'Answered']) }}">{{ '- Answered' }}</a></li>
                            <li><a href="{{ route('admin.tickets.index', ['status' => 'Customer-Reply']) }}">{{ '- Customer-Reply' }}</a></li>
                            <li><a href="{{ route('admin.tickets.index', ['status' => 'On Hold']) }}">{{ '- On Hold' }}</a></li>
                            <li><a href="{{ route('admin.tickets.index', ['status' => 'In Progress']) }}">{{ '- In Progress' }}</a></li>
                            <li><a href="{{ route('admin.tickets.index', ['status' => 'Closed']) }}">{{ '- Closed' }}</a></li>
                        </ul>
                    </li>
                    <li><a href="{{ route('admin.tickets.create') }}">{{ 'Open New Ticket' }}</a></li>
                    <li><a href="{{ route('admin.config.predefined-replies') }}">{{ 'Predefined Replies' }}</a></li>
                    <li><a href="{{ route('admin.config.announcements') }}">{{ 'Announcements' }}</a></li>
                    <li><a href="{{ route('admin.config.downloads') }}">{{ 'Downloads' }}</a></li>
                    <li><a href="{{ route('admin.config.knowledge-base') }}">{{ 'Knowledgebase' }}</a></li>
                    <li class="has-sub">
                        <a href="{{ route('admin.config.network-issues') }}">{{ 'Network Issues' }}</a>
                        <ul class="sub-dropdown">
                            <li><a href="{{ route('admin.config.network-issues') }}">{{ '- Open' }}</a></li>
                            <li><a href="{{ route('admin.config.network-issues', ['status' => 'scheduled']) }}">{{ '- Scheduled' }}</a></li>
                            <li><a href="{{ route('admin.config.network-issues', ['status' => 'resolved']) }}">{{ '- Resolved' }}</a></li>
                        </ul>
                    </li>
                </ul>
            </li>

            {{-- Reports --}}
            <li class="has-dropdown">
                <a href="#" onclick="toggleNavDropdown(event, this.parentElement)">
                    <i class="fas fa-chart-bar"></i>{{ 'Reports' }}<span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="{{ route('admin.reports.index') }}">{{ 'Reports' }}</a></li>
                    <li><a href="{{ route('admin.reports.index') }}">{{ 'Daily Performance' }}</a></li>
                    <li><a href="{{ route('admin.reports.index') }}">{{ 'Income Forecast' }}</a></li>
                    <li><a href="{{ route('admin.reports.index') }}">{{ 'Annual Income Report' }}</a></li>
                    <li><a href="{{ route('admin.reports.index') }}">{{ 'New Customers' }}</a></li>
                    <li><a href="{{ route('admin.reports.index') }}">{{ 'Ticket Feedback Scores' }}</a></li>
                    <li><a href="{{ url('/admin/addons/pdf-export') }}">{{ 'Batch Invoice PDF Export' }}</a></li>
                    <li><a href="{{ route('admin.reports.index') }}">{{ 'More...' }}</a></li>
                </ul>
            </li>

            {{-- Utilities --}}
            <li class="has-dropdown">
                <a href="#" onclick="toggleNavDropdown(event, this.parentElement)">
                    <i class="fas fa-file-alt"></i>{{ 'Utilities' }}<span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="{{ route('admin.config.updates') }}">{{ 'Update' }}</a></li>
                    <li><a href="{{ route('admin.config.automation') }}">{{ 'Automation Status' }}</a></li>
                    <li><a href="{{ route('admin.logs.module') }}">{{ 'Module Queue' }}</a></li>
                    <li><a href="{{ route('admin.config.domain-pricing') }}">{{ 'TLD Sync' }}</a></li>
                    <li><a href="{{ route('admin.bulk.mass-email') }}">{{ 'Email Campaigns' }}</a></li>
                    <li><a href="{{ route('admin.calendar') }}">{{ 'Calendar' }}</a></li>
                    <li><a href="{{ route('admin.config.todo') }}">{{ 'To-Do List' }}</a></li>
                    <li><a href="{{ route('admin.whois.index') }}">{{ 'WHOIS Lookups' }}</a></li>
                    <li><a href="{{ route('admin.whmcs-import.index') }}">{{ __('whmcs_import.menu_title') }}</a></li>
                    <li class="has-sub">
                        <a href="#" onclick="event.preventDefault();">{{ 'System' }}</a>
                        <ul class="sub-dropdown">
                            <li><a href="{{ route('admin.config.system-database') }}">{{ 'Database Status' }}</a></li>
                            <li><a href="{{ route('admin.config.activity-log') }}">{{ 'Activity Log' }}</a></li>
                            <li><a href="{{ route('admin.logs.index') }}">{{ 'System Logs' }}</a></li>
                            <li><a href="{{ route('admin.config.system-phpinfo') }}">{{ 'PHP Info' }}</a></li>
                        </ul>
                    </li>
                </ul>
            </li>

            {{-- Addons --}}
            <li class="has-dropdown">
                <a href="#" onclick="toggleNavDropdown(event, this.parentElement)">
                    <i class="fas fa-cube"></i>{{ 'Addons' }}<span class="caret"></span>
                </a>
                <ul class="dropdown-menu addons-menu">
                    <li><a href="{{ url('/admin/addons/bdpbx-accounting') }}" class="addon-hl" style="font-weight:700;color:#0284c7;background:#f0f9ff;"><i class="fas fa-phone-square" style="width:16px;margin-right:6px;"></i>{{ 'BDPBX Multi-Server Daily Accounting' }}</a></li>
                    <li><a href="{{ url('/admin/addons/softcents-voice') }}" class="addon-hl" style="font-weight:700;color:#059669;background:#f0fdf4;"><i class="fas fa-phone-volume" style="width:16px;margin-right:6px;"></i>{{ 'Softcents Voice SMS Gateway' }}</a></li>
                    <li><a href="{{ url('/admin/addons/telegram-bot') }}" class="addon-hl" style="font-weight:700;color:#0369a1;background:#e0f2fe;"><i class="fab fa-telegram-plane" style="width:16px;margin-right:6px;"></i>{{ 'Softcents Telegram Notifier (A-Z)' }}</a></li>
                    @if(!empty($addonMenuItems))
                        <li class="divider"></li>
                        @php
                            $pinnedAddonPaths = ['/admin/addons/bdpbx-accounting', '/admin/addons/softcents-voice', '/admin/addons/telegram-bot'];
                        @endphp
                        @foreach($addonMenuItems as $item)
                            @php
                                $itemPath = parse_url($item['url'], PHP_URL_PATH) ?: $item['url'];
                            @endphp
                            @continue(in_array($itemPath, $pinnedAddonPaths, true))
                            <li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                            @foreach($item['children'] ?? [] as $child)
                                @continue($child['url'] === $item['url'])
                                <li><a href="{{ $child['url'] }}" style="padding-left:30px !important;">{{ '- ' . $child['label'] }}</a></li>
                            @endforeach
                        @endforeach
                    @endif
                    <li class="divider"></li>
                    <li><a href="{{ route('admin.config.addons.modules') }}">{{ 'Apps & Integrations' }}</a></li>
                </ul>
            </li>

            @if(auth('admin')->user()?->hasPermission('manage_products') && \Modules\Servers\PanelicaLicense\PanelicaLicenseModule::enabled())
            <li>
                <a href="{{ route('admin.panelica-licenses.index') }}">{{ __('panelica_license.page.menu') }}</a>
            </li>
            @endif
        </ul>
    </div>

    {{-- Right-side WHMCS Blend Icon Bar (Exact 6 items: Search, Automation, Update, Wrench Grid, Avatar, Help) --}}
    <div class="nav-right-items">
        {{-- 1. IntelliSearch (compact magnifying glass button expanding on click) --}}
        <div class="intellisearch" id="intellisearch">
            <button type="button" class="is-toggle-btn" title="{{ __('common.placeholder.search') }}" onclick="openIntelliSearch(event)">
                <i class="fas fa-search"></i>
            </button>
            <form action="{{ route('admin.clients.index') }}" method="GET">
                <i class="fas fa-search" style="color:rgba(255,255,255,0.7);"></i>
                <input type="text" name="search" id="inputIntelliSearchValue" class="form-control" placeholder="{{ __('common.placeholder.search') }}" autocomplete="off"
                       role="combobox" aria-expanded="false" aria-controls="intellisearch-results" aria-autocomplete="list">
                <button type="button" class="is-close-btn" id="btnIntelliSearchClose" title="{{ __('common.actions.close') }}" onclick="closeIntelliSearch(event)">&times;</button>
            </form>
            @include('admin.partials.intellisearch')
        </div>

        <ul class="right-nav">
            {{-- 2. Automation Status (double gears icon -> direct link to Automation Status) --}}
            <li class="bt">
                <a href="{{ route('admin.config.automation') }}" id="Menu-Automation-Status" title="{{ 'Automation Status' }}">
                    <i class="fas fa-cogs always"></i>
                </a>
            </li>

            {{-- 3. Update Available (golden download icon) --}}
            <li class="bt">
                <a href="{{ route('admin.config.updates') }}" class="update-btn" title="{{ 'An update is available!' }}">
                    <i class="fas fa-download always"></i>
                </a>
            </li>

            {{-- 4. Setup / Wrench (WHMCS 2x3 Duotone Icon-Grid Dropdown) --}}
            <li class="bt has-dropdown">
                <a id="Menu-Setup" href="#" onclick="toggleNavDropdown(event, this.parentElement)" title="{{ 'System Settings' }}">
                    <i class="fas fa-wrench always"></i>
                </a>
                <ul class="dropdown-menu drop-icons">
                    <li>
                        <a id="Menu-Config-Setup" href="{{ route('admin.config.system-settings') }}">
                            <span class="ico-container">
                                {{-- Duotone Sliders Icon --}}
                                <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                                    <rect x="3" y="7" width="26" height="2.6" rx="1.3" fill="#9ab3cc"/>
                                    <rect x="3" y="14.7" width="26" height="2.6" rx="1.3" fill="#9ab3cc"/>
                                    <rect x="3" y="22.4" width="26" height="2.6" rx="1.3" fill="#9ab3cc"/>
                                    <rect x="18" y="4.5" width="4.2" height="7.6" rx="1.4" fill="#1a4d80"/>
                                    <rect x="9" y="12.2" width="4.2" height="7.6" rx="1.4" fill="#1a4d80"/>
                                    <rect x="20" y="19.9" width="4.2" height="7.6" rx="1.4" fill="#1a4d80"/>
                                </svg>
                            </span>
                            <span>{{ 'System' }}<br>{{ 'Settings' }}</span>
                        </a>
                    </li>
                    <li>
                        <a id="Menu-Config-Apps-Integrations" href="{{ route('admin.config.addons.modules') }}">
                            <span class="ico-container">
                                {{-- Duotone 3D Cubes Icon --}}
                                <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                                    <polygon points="16,4 22.5,7.6 16,11.2 9.5,7.6" fill="#b3c7dc"/>
                                    <polygon points="9.5,7.6 16,11.2 16,18 9.5,14.4" fill="#7d9fc0"/>
                                    <polygon points="22.5,7.6 16,11.2 16,18 22.5,14.4" fill="#1a4d80"/>
                                    <polygon points="9.5,15.2 15.5,18.6 9.5,22 3.5,18.6" fill="#b3c7dc"/>
                                    <polygon points="3.5,18.6 9.5,22 9.5,28.2 3.5,24.8" fill="#7d9fc0"/>
                                    <polygon points="15.5,18.6 9.5,22 9.5,28.2 15.5,24.8" fill="#1a4d80"/>
                                    <polygon points="22.5,15.2 28.5,18.6 22.5,22 16.5,18.6" fill="#b3c7dc"/>
                                    <polygon points="16.5,18.6 22.5,22 22.5,28.2 16.5,24.8" fill="#7d9fc0"/>
                                    <polygon points="28.5,18.6 22.5,22 22.5,28.2 28.5,24.8" fill="#1a4d80"/>
                                </svg>
                            </span>
                            <span>{{ 'Apps &' }}<br>{{ 'Integrations' }}</span>
                        </a>
                    </li>
                    <li>
                        <a id="Menu-Config-Admins" href="{{ route('admin.config.admins') }}">
                            <span class="ico-container">
                                {{-- Duotone User Friends Icon --}}
                                <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                                    <circle cx="21.5" cy="11" r="4.2" fill="#9ab3cc"/>
                                    <path d="M16.5 24.5c0-3.4 2.6-5.8 5.8-5.8 3 0 5.7 2.4 5.7 5.8v1H16.5v-1z" fill="#9ab3cc"/>
                                    <circle cx="12" cy="10.5" r="4.8" fill="#1a4d80"/>
                                    <path d="M4 25.5c0-4.2 3.5-7 8-7s8 2.8 8 7v1.2H4v-1.2z" fill="#1a4d80"/>
                                </svg>
                            </span>
                            <span>{{ 'Manage' }}<br>{{ 'Admins' }}</span>
                        </a>
                    </li>
                    <li>
                        <a id="Menu-Config-HealthStatus" href="{{ route('admin.config.automation') }}">
                            <span class="ico-container">
                                {{-- Duotone Heartbeat / Pulse Icon --}}
                                <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                                    <polyline points="2,17 8.5,17 11.5,8 15.5,25 19.2,12.5 22,17 30,17" stroke="#8eaac6" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>{{ 'System' }}<br>{{ 'Health' }}</span>
                        </a>
                    </li>
                    <li>
                        <a id="Menu-Config-SetupWizard" href="{{ route('admin.settings.general') }}">
                            <span class="ico-container">
                                {{-- Duotone Magic Wand Icon --}}
                                <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                                    <path d="M5.5 24.5L21 9l2.5 2.5L8 27 5.5 24.5z" fill="#1a4d80"/>
                                    <path d="M21 9l3.2-3.2 2.5 2.5L23.5 11.5 21 9z" fill="#9ab3cc"/>
                                    <path d="M16 4l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8L16 4zM26.5 13l.7 1.7 1.7.7-1.7.7-.7 1.7-.7-1.7-1.7-.7 1.7-.7.7-1.7zM9.5 8.5l.6 1.4 1.4.6-1.4.6-.6 1.4-.6-1.4-1.4-.6 1.4-.6.6-1.4z" fill="#9ab3cc"/>
                                </svg>
                            </span>
                            <span>{{ 'Getting Started' }}<br>{{ 'Wizard' }}</span>
                        </a>
                    </li>
                    <li>
                        <a id="Menu-Config-SysLogs" href="{{ route('admin.logs.index') }}">
                            <span class="ico-container">
                                {{-- Duotone Stacked Copy / Logs Icon --}}
                                <svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
                                    <rect x="6" y="8" width="14" height="19" rx="1.8" fill="#9ab3cc"/>
                                    <path d="M12 5h10l5 5v14a1.8 1.8 0 01-1.8 1.8H12A1.8 1.8 0 0110.2 24V6.8A1.8 1.8 0 0112 5z" fill="#1a4d80"/>
                                    <path d="M22 5v5h5" fill="#9ab3cc"/>
                                </svg>
                            </span>
                            <span>{{ 'System' }}<br>{{ 'Logs' }}</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- 5. User Avatar (Circular Gravatar Mystery-Person Silhouette + Exact WHMCS Menu) --}}
            <li class="bt account has-dropdown">
                <a id="Menu-Account" href="#" onclick="toggleNavDropdown(event, this.parentElement)" title="{{ auth('admin')->user()->full_name ?? 'Account' }}">
                    <svg class="profile-avatar" viewBox="0 0 40 40" aria-hidden="true">
                        <circle cx="20" cy="20" r="20" fill="#d9dde3"/>
                        <circle cx="20" cy="15" r="6.8" fill="#ffffff"/>
                        <path d="M7.5 35.5c1.8-6.4 6.7-9.8 12.5-9.8s10.7 3.4 12.5 9.8a19.9 19.9 0 01-25 0z" fill="#ffffff"/>
                    </svg>
                </a>
                <ul class="dropdown-menu slim drop-left">
                    <li><a href="{{ route('admin.my-account') }}">{{ 'My Account' }}</a></li>
                    <li><a href="#" onclick="openMyNotesModal(event)">{{ 'My Notes' }}</a></li>
                    <li class="divider"></li>
                    <li><a href="{{ url('/') }}" target="_blank">{{ 'Visit Client Area' }}</a></li>
                    <li class="divider"></li>
                    <li>
                        <a id="Menu-Account-Logout" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ 'Logout' }}</a>
                    </li>
                </ul>
            </li>

            {{-- 6. Help Icon (? Dropdown) --}}
            <li class="bt help has-dropdown">
                <a id="Menu-Help" href="#" onclick="toggleNavDropdown(event, this.parentElement)" title="{{ 'Help' }}">
                    <i class="far fa-question-circle always" style="font-size:18px;"></i>
                </a>
                <ul class="dropdown-menu drop-left">
                    <li><a href="{{ route('admin.api-docs') }}">{{ 'Documentation' }}</a></li>
                    <li><a href="{{ route('admin.tickets.create') }}">{{ 'Technical Support' }}</a></li>
                    <li><a href="https://whmcs.community/" target="_blank" rel="noopener">{{ 'Community Forums' }}</a></li>
                    <li><a href="{{ route('admin.config.updates') }}">{{ "What's New" }}</a></li>
                    <li class="divider"></li>
                    <li><a href="{{ route('admin.config.system-phpinfo') }}">{{ 'License Information' }}</a></li>
                </ul>
            </li>
        </ul>
    </div>
</div>

<form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display:none;">
    @csrf
</form>

{{-- ═══════════════════════════════════════════════
     SIDEBAR (WHMCS Blend exact structure)
     ═══════════════════════════════════════════════ --}}
<div class="sidebar" id="sidebar">

    @php
        $routeName = Route::currentRouteName() ?? '';
        $segment = request()->segment(2) ?? 'dashboard';
    @endphp

    {{-- ── Dashboard Sidebar (Exact WHMCS Blend structure) ── --}}
    @if($segment === '' || $segment === 'dashboard' || $routeName === 'admin.dashboard')
        <div class="sidebar-header"><i class="fas fa-star"></i> {{ __('admin.sidebar.shortcuts') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.clients.create') }}"><i class="fas fa-user-plus" style="width:16px;color:#337ab7;"></i> {{ __('admin.nav.add_new_client') }}</a></li>
            <li><a href="{{ route('admin.orders.create') }}"><i class="fas fa-shopping-cart" style="width:16px;color:#5cb85c;"></i> {{ 'Add New Order' }}</a></li>
            <li><a href="{{ route('admin.quotes.create') }}"><i class="fas fa-file-signature" style="width:16px;color:#f0ad4e;"></i> {{ __('admin.sidebar.create_quote') }}</a></li>
            <li><a href="{{ route('admin.config.todo') }}"><i class="fas fa-check-square" style="width:16px;color:#5bc0de;"></i> {{ 'Create New To-Do Entry' }}</a></li>
            <li><a href="{{ route('admin.tickets.create') }}"><i class="fas fa-ticket-alt" style="width:16px;color:#d9534f;"></i> {{ __('admin.nav.open_ticket') }}</a></li>
            <li><a href="{{ route('admin.whois.index') }}"><i class="fas fa-globe" style="width:16px;color:#16a34a;"></i> {{ __('admin.nav.whois_lookup') }}</a></li>
            <li><a href="{{ route('admin.config.automation') }}"><i class="fas fa-file-invoice-dollar" style="width:16px;color:#6f42c1;"></i> {{ 'Generate Due Invoices' }}</a></li>
            <li><a href="{{ route('admin.config.automation') }}"><i class="fas fa-credit-card" style="width:16px;color:#0284c7;"></i> {{ 'Attempt CC Captures' }}</a></li>
        </ul>

        <div class="sidebar-header"><i class="fas fa-home"></i> {{ 'System Information' }}</div>
        <div class="content-padded" style="font-size:11.5px;color:#444;line-height:1.65;">
            {{ 'Registered To:' }} {{ company_name() }}<br>
            {{ 'License Type:' }} {{ 'Enterprise Unlimited' }}<br>
            {{ 'Expires:' }} {{ '31st December 2099' }}<br>
            {{ 'Version:' }} {{ config('pnlcs.version', '1.32.2') }}<br>
            <a href="{{ route('admin.config.updates') }}" style="color:#c00;font-weight:700;text-decoration:none;">{{ 'An update is available!' }}</a>
        </div>

    {{-- ── WHMCS Import Sidebar ── --}}
    @elseif($segment === 'whmcs-import')
        <div class="sidebar-header"><i class="fas fa-wrench"></i> {{ __('admin.sidebar.utilities') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.whmcs-import.index') }}" @if($routeName === 'admin.whmcs-import.index') class="active" @endif>{{ __('whmcs_import.menu_title') }}</a></li>
            <li><a href="{{ route('admin.config.automation') }}">{{ __('admin.nav.automation_status') }}</a></li>
            <li><a href="{{ route('admin.whois.index') }}">{{ __('admin.nav.whois_lookup') }}</a></li>
        </ul>

    {{-- ── Clients Sidebar ── --}}
    @elseif(str_starts_with($segment, 'client'))
        <div class="sidebar-header"><i class="fas fa-user"></i> {{ __('admin.sidebar.clients') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.clients.index') }}" @if($routeName === 'admin.clients.index') class="active" @endif>{{ __('admin.nav.view_search_clients') }}</a></li>
            <li><a href="{{ route('admin.clients.billing') }}" @if($routeName === 'admin.clients.billing') class="active" @endif>{{ __('admin.clients.billing_identity') }}</a></li>
            <li><a href="{{ route('admin.clients.create') }}" @if($routeName === 'admin.clients.create') class="active" @endif>{{ __('admin.nav.add_new_client') }}</a></li>
            <li><a href="{{ route('admin.bulk.mass-email') }}" @if($routeName === 'admin.bulk.mass-email') class="active" @endif>{{ __('admin.bulk.mass_email') }}</a></li>
        </ul>
        <div class="sidebar-header"><i class="fas fa-cube"></i> {{ __('admin.sidebar.services') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.services.index') }}" @if($routeName === 'admin.services.index') class="active" @endif>{{ __('admin.nav.products_services') }}</a></li>
            <li><a href="{{ route('admin.domains.index') }}" @if($routeName === 'admin.domains.index') class="active" @endif>{{ __('admin.nav.domains') }}</a></li>
        </ul>
        <div class="sidebar-header"><i class="fas fa-coins"></i> {{ __('admin.sidebar.affiliates') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.affiliates.index') }}" @if($routeName === 'admin.affiliates.index') class="active" @endif>{{ __('admin.sidebar.affiliates') }}</a></li>
        </ul>

    {{-- ── Orders Sidebar ── --}}
    @elseif($segment === 'orders')
        <div class="sidebar-header"><i class="fas fa-cube"></i> {{ __('admin.sidebar.orders') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.orders.index') }}" @if(!request()->has('status')) class="active" @endif>{{ __('admin.sidebar.all_orders') }}</a></li>
            <li><a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" @if(request()->get('status') === 'pending') class="active" @endif>{{ __('admin.nav.pending') }} @if(($sidebarCounts->pending_orders ?? 0) > 0)<span class="sb-badge sb-badge-warning">{{ $sidebarCounts->pending_orders }}</span>@endif</a></li>
            <li><a href="{{ route('admin.orders.index', ['status' => 'active']) }}" @if(request()->get('status') === 'active') class="active" @endif>{{ __('admin.nav.active') }}</a></li>
            <li><a href="{{ route('admin.orders.index', ['status' => 'fraud']) }}" @if(request()->get('status') === 'fraud') class="active" @endif>{{ __('admin.nav.fraud') }}</a></li>
            <li><a href="{{ route('admin.orders.index', ['status' => 'cancelled']) }}" @if(request()->get('status') === 'cancelled') class="active" @endif>{{ __('admin.status.cancelled') }}</a></li>
        </ul>

    {{-- ── Invoices / Billing Sidebar ── --}}
    @elseif($segment === 'invoices' || $segment === 'quotes' || $segment === 'affiliates' || $segment === 'ksef' || $routeName === 'admin.config.transactions' || $routeName === 'admin.config.billable-items')
        <div class="sidebar-header"><i class="fas fa-money-bill-wave"></i> {{ __('admin.nav.billing') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.invoices.index', ['status' => 'all']) }}" @if(request()->get('status') === 'all') class="active" @endif>{{ __('admin.sidebar.all_invoices') }}</a></li>
            <li><a href="{{ route('admin.invoices.index', ['status' => 'paid']) }}" @if(request()->get('status') === 'paid') class="active" @endif>{{ __('admin.sidebar.paid') }}</a></li>
            <li><a href="{{ route('admin.invoices.index', ['status' => 'unpaid']) }}" @if(request()->get('status') === 'unpaid') class="active" @endif>{{ __('admin.sidebar.unpaid') }} @if(($sidebarCounts->unpaid_invoices ?? 0) > 0)<span class="sb-badge sb-badge-warning">{{ $sidebarCounts->unpaid_invoices }}</span>@endif</a></li>
            <li><a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}" @if(request()->get('status') === 'overdue') class="active" @endif>{{ __('admin.sidebar.overdue') }} @if(($sidebarCounts->overdue_invoices ?? 0) > 0)<span class="sb-badge">{{ $sidebarCounts->overdue_invoices }}</span>@endif</a></li>
            <li><a href="{{ route('admin.invoices.index', ['status' => 'cancelled']) }}" @if(request()->get('status') === 'cancelled') class="active" @endif>{{ __('admin.status.cancelled') }}</a></li>
            <li><a href="{{ route('admin.invoices.create') }}" @if($routeName === 'admin.invoices.create') class="active" @endif>{{ __('admin.sidebar.create_invoice') }}</a></li>
        </ul>
        <div class="sidebar-header"><i class="fas fa-coins"></i> {{ __('admin.nav.transactions') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.config.transactions') }}" @if($routeName === 'admin.config.transactions') class="active" @endif>{{ __('admin.nav.transactions') }}</a></li>
            <li><a href="{{ route('admin.config.billable-items') }}" @if($routeName === 'admin.config.billable-items') class="active" @endif>{{ __('admin.nav.billable_items') }}</a></li>
            @if($ksefActive ?? false)<li><a href="{{ route('admin.ksef.index') }}" @if($routeName === 'admin.ksef.index') class="active" @endif>{{ __('admin.nav.ksef_status') }}</a></li>@endif
            <li><a href="{{ route('admin.affiliates.index') }}" @if($routeName === 'admin.affiliates.index') class="active" @endif>{{ __('admin.sidebar.affiliates') }}</a></li>
        </ul>
        <div class="sidebar-header"><i class="fas fa-file-signature"></i> {{ __('admin.nav.quotes') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.quotes.index') }}" @if($routeName === 'admin.quotes.index') class="active" @endif>{{ __('admin.nav.quotes') }}</a></li>
            <li><a href="{{ route('admin.quotes.create') }}" @if($routeName === 'admin.quotes.create') class="active" @endif>{{ __('admin.sidebar.create_quote') }}</a></li>
        </ul>

    {{-- ── Support / Tickets Sidebar ── --}}
    @elseif($segment === 'tickets')
        <div class="sidebar-header"><i class="fas fa-life-ring"></i> {{ __('admin.nav.support') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.tickets.index') }}" @if($routeName === 'admin.tickets.index' && !request()->has('status')) class="active" @endif>{{ __('admin.sidebar.all_tickets') }} @if(($sidebarCounts->active_tickets ?? 0) > 0)<span class="sb-badge sb-badge-info">{{ $sidebarCounts->active_tickets }}</span>@endif</a></li>
            <li><a href="{{ route('admin.tickets.index', ['status' => 'Open']) }}" @if(request()->get('status') === 'Open') class="active" @endif>{{ __('admin.sidebar.open') }} @if(($sidebarCounts->open_tickets_only ?? 0) > 0)<span class="sb-badge sb-badge-info">{{ $sidebarCounts->open_tickets_only }}</span>@endif</a></li>
            <li><a href="{{ route('admin.tickets.index', ['status' => 'Customer-Reply']) }}" @if(request()->get('status') === 'Customer-Reply') class="active" @endif>{{ __('admin.sidebar.awaiting_reply') }} @if(($sidebarCounts->awaiting_tickets ?? 0) > 0)<span class="sb-badge sb-badge-warning">{{ $sidebarCounts->awaiting_tickets }}</span>@endif</a></li>
            <li><a href="{{ route('admin.tickets.index', ['status' => 'Closed']) }}" @if(request()->get('status') === 'Closed') class="active" @endif>{{ __('admin.status.closed') }}</a></li>
        </ul>

        <div class="sidebar-header"><i class="fas fa-comments"></i> {{ __('admin.sidebar.filter_tickets_header') }}</div>
        <div class="advanced-search">
            <form action="{{ route('admin.tickets.index') }}" method="GET">
                <select name="department_id">
                    <option value="">{{ __('admin.sidebar.department') }}</option>
                    @foreach($sidebarDepartments ?? [] as $dep)
                    <option value="{{ $dep->id }}" @selected(request('department_id') == $dep->id)>{{ $dep->name }}</option>
                    @endforeach
                </select>
                <select name="status">
                    <option value="">{{ __('admin.sidebar.status') }}</option>
                    <option value="Open">{{ __('admin.sidebar.open') }}</option>
                    <option value="Answered">{{ __('admin.sidebar.answered') }}</option>
                    <option value="Customer-Reply">{{ __('admin.sidebar.customer_reply') }}</option>
                    <option value="Closed">{{ __('admin.sidebar.closed') }}</option>
                </select>
                <select name="priority">
                    <option value="">{{ __('admin.sidebar.priority') }}</option>
                    <option value="High">{{ __('admin.sidebar.high') }}</option>
                    <option value="Medium">{{ __('admin.sidebar.medium') }}</option>
                    <option value="Low">{{ __('admin.sidebar.low') }}</option>
                </select>
                <button type="submit" class="btn-go">{{ __('common.actions.filter') }}</button>
            </form>
        </div>

        <div class="sidebar-header"><i class="fas fa-bullhorn"></i> {{ __('admin.sidebar.content') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.config.announcements') }}">{{ __('admin.nav.announcements') }}</a></li>
            <li><a href="{{ route('admin.config.downloads') }}">{{ __('admin.nav.downloads') }}</a></li>
            <li><a href="{{ route('admin.config.knowledge-base') }}">{{ __('admin.nav.knowledge_base') }}</a></li>
            <li><a href="{{ route('admin.config.network-issues') }}">{{ __('admin.nav.network_issues') }}</a></li>
        </ul>

    {{-- ── Reports Sidebar ── --}}
    @elseif($segment === 'reports')
        <div class="sidebar-header"><i class="fas fa-chart-bar"></i> {{ __('admin.nav.reports') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.reports.index') }}" class="active">{{ __('admin.sidebar.reports_overview') }}</a></li>
        </ul>

    {{-- ── Config / Setup Sidebar ── --}}
    @elseif($segment === 'config' || $segment === 'settings' || $segment === 'products')
        <div class="sidebar-header"><i class="fas fa-users-cog"></i> {{ __('admin.sidebar.staff_management') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.config.admins') }}" @if($routeName === 'admin.config.admins') class="active" @endif>{{ __('admin.sidebar.administrator_accounts') }}</a></li>
            <li><a href="{{ route('admin.config.admin-roles') }}" @if($routeName === 'admin.config.admin-roles') class="active" @endif>{{ __('admin.sidebar.administrator_roles') }}</a></li>
            <li><a href="{{ route('admin.config.api-credentials') }}" @if($routeName === 'admin.config.api-credentials') class="active" @endif>{{ __('admin.nav.api_credentials') }}</a></li>
            <li><a href="https://docs.pnlcs.com/" target="_blank" rel="noopener">{{ __('admin.nav.user_guide') }}</a></li>
            <li><a href="{{ route('admin.api-docs') }}" @if($routeName === 'admin.api-docs') class="active" @endif>{{ __('admin.nav.api_documentation') }}</a></li>
            <li><a href="{{ route('admin.config.modules') }}" @if($routeName === 'admin.config.modules') class="active" @endif>{{ __('admin.nav.modules') }}</a></li>
        </ul>

        <div class="sidebar-header"><i class="fas fa-credit-card"></i> {{ __('admin.sidebar.payments') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.config.gateways') }}" @if($routeName === 'admin.config.gateways') class="active" @endif>{{ __('admin.nav.payment_gateways') }}</a></li>
            <li><a href="{{ route('admin.config.currencies') }}" @if($routeName === 'admin.config.currencies') class="active" @endif>{{ __('admin.nav.currencies') }}</a></li>
            <li><a href="{{ route('admin.config.tax') }}" @if($routeName === 'admin.config.tax') class="active" @endif>{{ __('admin.nav.tax_rules') }}</a></li>
            <li><a href="{{ route('admin.config.custom-fields') }}" @if($routeName === 'admin.config.custom-fields') class="active" @endif>{{ __('admin.nav.custom_fields') }}</a></li>
            <li><a href="{{ route('admin.config.promotions') }}" @if($routeName === 'admin.config.promotions') class="active" @endif>{{ __('admin.nav.promotions') }}</a></li>
        </ul>

        <div class="sidebar-header"><i class="fas fa-cube"></i> {{ __('admin.sidebar.products') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.products.index') }}" @if($routeName === 'admin.products.index') class="active" @endif>{{ __('admin.nav.products_services') }}</a></li>
            <li><a href="{{ route('admin.config.config-options') }}" @if($routeName === 'admin.config.config-options') class="active" @endif>{{ __('admin.config_options.title') }}</a></li>
            <li><a href="{{ route('admin.products.create') }}" @if($routeName === 'admin.products.create') class="active" @endif>{{ __('admin.sidebar.create_product') }}</a></li>
            <li><a href="{{ route('admin.products.groups.create') }}" @if(in_array($routeName, ['admin.products.groups.create', 'admin.products.groups.edit'], true)) class="active" @endif>{{ __('admin.sidebar.product_groups') }}</a></li>
            <li><a href="{{ route('admin.docker-apps.index') }}" @if($routeName === 'admin.docker-apps.index') class="active" @endif>{{ __('admin.nav.docker_apps') }}</a></li>
        </ul>

        <div class="sidebar-header"><i class="fas fa-server"></i> {{ __('admin.sidebar.servers_domains') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.config.servers') }}" @if($routeName === 'admin.config.servers') class="active" @endif>{{ __('admin.nav.servers') }}</a></li>
            <li><a href="{{ route('admin.config.server-groups') }}" @if($routeName === 'admin.config.server-groups') class="active" @endif>{{ __('admin.nav.server_groups') }}</a></li>
            <li><a href="{{ route('admin.config.domain-pricing') }}" @if($routeName === 'admin.config.domain-pricing') class="active" @endif>{{ __('admin.nav.domain_pricing') }}</a></li>
            <li><a href="{{ route('admin.config.registrars') }}" @if($routeName === 'admin.config.registrars') class="active" @endif>{{ __('admin.nav.domain_registrars') }}</a></li>
        </ul>

        <div class="sidebar-header"><i class="fas fa-life-ring"></i> {{ __('admin.nav.support') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.config.ticket-departments') }}" @if($routeName === 'admin.config.ticket-departments') class="active" @endif>{{ __('admin.nav.ticket_departments') }}</a></li>
            <li><a href="{{ route('admin.config.ticket-statuses') }}" @if($routeName === 'admin.config.ticket-statuses') class="active" @endif>{{ __('admin.nav.ticket_statuses') }}</a></li>
            <li><a href="{{ route('admin.config.ticket-escalation') }}" @if($routeName === 'admin.config.ticket-escalation') class="active" @endif>{{ __('admin.ticket_escalation.title') }}</a></li>
            <li><a href="{{ route('admin.config.predefined-replies') }}" @if($routeName === 'admin.config.predefined-replies') class="active" @endif>{{ __('admin.nav.predefined_replies') }}</a></li>
            <li><a href="{{ route('admin.config.email-templates') }}" @if($routeName === 'admin.config.email-templates') class="active" @endif>{{ __('admin.nav.email_templates') }}</a></li>
        </ul>

        <div class="sidebar-header"><i class="fas fa-wrench"></i> {{ __('admin.sidebar.other') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.settings.general') }}" @if($routeName === 'admin.settings.general') class="active" @endif>{{ __('admin.nav.general_settings') }}</a></li>
            <li><a href="{{ route('admin.settings.appearance') }}" @if($routeName === 'admin.settings.appearance') class="active" @endif><i class="fas fa-palette"></i> {{ __('admin.nav.appearance') }}</a></li>
            <li><a href="{{ route('admin.config.client-groups') }}" @if($routeName === 'admin.config.client-groups') class="active" @endif>{{ __('admin.nav.client_groups') }}</a></li>
            <li><a href="{{ route('admin.config.banned-ips') }}" @if($routeName === 'admin.config.banned-ips') class="active" @endif>{{ __('admin.nav.banned_ips') }}</a></li>
            <li><a href="{{ route('admin.config.banned-emails') }}" @if($routeName === 'admin.config.banned-emails') class="active" @endif>{{ __('admin.nav.banned_emails') }}</a></li>
            <li><a href="{{ route('admin.config.notifications') }}" @if($routeName === 'admin.config.notifications') class="active" @endif>{{ __('admin.nav.notification_channels') }}</a></li>
            <li><a href="{{ route('admin.config.ticket-spam') }}" @if($routeName === 'admin.config.ticket-spam') class="active" @endif>{{ __('admin.nav.ticket_spam_filter') }}</a></li>
            <li><a href="{{ route('admin.config.addons') }}" @if($routeName === 'admin.config.addons') class="active" @endif>{{ __('admin.nav.product_addons') }}</a></li>
                    <li><a href="/admin/config/addons/modules">{{ __('admin.nav.addon_modules') }}</a></li>
            <li><a href="{{ route('admin.config.bundles') }}" @if($routeName === 'admin.config.bundles') class="active" @endif>{{ __('admin.nav.product_bundles') }}</a></li>
            @if(auth('admin')->user()?->hasPermission('manage_updates'))
            <li><a href="{{ route('admin.config.updates') }}" @if($routeName === 'admin.config.updates') class="active" @endif><i class="fas fa-cloud-download-alt"></i> {{ __('admin.nav.updates') }}</a></li>
            @endif
        </ul>

    {{-- ── Logs Sidebar ── --}}
    @elseif($segment === 'logs')
        <div class="sidebar-header"><i class="fas fa-file-signature"></i> {{ __('admin.sidebar.logs') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.logs.index') }}" @if($routeName === 'admin.logs.index') class="active" @endif>{{ __('admin.nav.system_logs') }}</a></li>
            <li><a href="{{ route('admin.logs.gateway') }}" @if($routeName === 'admin.logs.gateway') class="active" @endif>{{ __('admin.sidebar.gateway_logs') }}</a></li>
            <li><a href="{{ route('admin.logs.module') }}" @if($routeName === 'admin.logs.module') class="active" @endif>{{ __('admin.sidebar.module_logs') }}</a></li>
            <li><a href="{{ route('admin.logs.email') }}" @if($routeName === 'admin.logs.email') class="active" @endif>{{ __('admin.sidebar.email_logs') }}</a></li>
        </ul>

    {{-- ── Calendar Sidebar ── --}}
    @elseif($segment === 'calendar')
        <div class="sidebar-header"><i class="fas fa-calendar-alt"></i> {{ __('admin.nav.calendar') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.calendar') }}" class="active">{{ __('admin.nav.calendar') }}</a></li>
            <li><a href="{{ route('admin.config.todo') }}">{{ __('admin.nav.todo_list') }}</a></li>
        </ul>
        <div class="sidebar-header"><i class="fas fa-wrench"></i> {{ __('admin.sidebar.utilities') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.config.automation') }}">{{ __('admin.nav.automation_status') }}</a></li>
            <li><a href="{{ route('admin.config.activity-log') }}">{{ __('admin.nav.activity_log') }}</a></li>
        </ul>

    {{-- ── Default Sidebar (fallback) ── --}}
    @else
        <div class="sidebar-header"><i class="fas fa-star"></i> {{ __('admin.sidebar.quick_links') }}</div>
        <ul class="menu">
            <li><a href="{{ route('admin.dashboard') }}">{{ __('admin.sidebar.dashboard') }}</a></li>
            <li><a href="{{ route('admin.clients.index') }}">{{ __('admin.sidebar.clients') }}</a></li>
            <li><a href="{{ route('admin.orders.index') }}">{{ __('admin.sidebar.orders') }}</a></li>
            <li><a href="{{ route('admin.invoices.index') }}">{{ __('admin.sidebar.invoices') }}</a></li>
            <li><a href="{{ route('admin.tickets.index') }}">{{ __('admin.sidebar.tickets') }}</a></li>
            <li><a href="{{ route('admin.reports.index') }}"><i class="fas fa-chart-bar"></i> {{ __('admin.nav.reports') }}</a></li>
        </ul>
    @endif

    {{-- ── Advanced Search (WHMCS Blend 2-select + inline Search button) ── --}}
    <div class="sidebar-header"><i class="fas fa-binoculars"></i> {{ __('admin.sidebar.advanced_search') }}</div>
    <div class="advanced-search">
        <form action="{{ route('admin.clients.index') }}" method="GET" id="whmcsSidebarSearchForm">
            <select onchange="document.getElementById('whmcsSidebarSearchForm').action = this.value">
                <option value="{{ route('admin.clients.index') }}">{{ 'Clients' }}</option>
                <option value="{{ route('admin.orders.index') }}">{{ 'Orders' }}</option>
                <option value="{{ route('admin.invoices.index') }}">{{ 'Invoices' }}</option>
                <option value="{{ route('admin.services.index') }}">{{ 'Services' }}</option>
                <option value="{{ route('admin.domains.index') }}">{{ 'Domains' }}</option>
                <option value="{{ route('admin.tickets.index') }}">{{ 'Tickets' }}</option>
            </select>
            <select>
                <option value="name">{{ 'Client Name' }}</option>
                <option value="company">{{ 'Company Name' }}</option>
                <option value="email">{{ 'Email Address' }}</option>
            </select>
            <div style="display:flex;gap:4px;">
                <input type="text" name="search" style="margin-bottom:0;flex:1;min-width:0;" placeholder="">
                <button type="submit" class="btn btn-default btn-xs" style="padding:4px 8px !important;height:26px;">{{ __('common.actions.search') }}</button>
            </div>
        </form>
    </div>

    {{-- ── Staff Online ── --}}
    <div class="sidebar-header"><i class="fas fa-users"></i> {{ __('admin.sidebar.staff_online') }}</div>
    <div class="staff-online" style="font-size:12px;color:#444;padding:6px 10px 10px;">
        {{ auth('admin')->user()->username ?? (auth('admin')->user()->full_name ?? 'admin') }}
    </div>

    {{-- ── Minimise Sidebar Bar ── --}}
    <button type="button" onclick="toggleSidebar()" style="display:block;width:100%;padding:7px 10px;background:#555;color:#eee;border:0;font-size:11.5px;text-align:center;cursor:pointer;margin-top:8px;">
        &laquo; {{ 'Minimise Sidebar' }}
    </button>

</div>

{{-- ═══════════════════════════════════════════════
     CONTENT AREA
     ═══════════════════════════════════════════════ --}}
<div class="contentarea" id="contentarea">
    

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @php
        // A refused form came back with its reasons in $errors, and most admin
        // pages never print them: the page reloaded as if nothing had happened
        // (#247, a product edit refused over a Proxmox field read as "the
        // group cannot be changed"). The reasons a page does not print itself
        // are listed here; a page that prints them is left as it is.
        $pageContent = $__env->yieldContent('content');
        $unshownErrors = isset($errors) && $errors->any()
            ? collect($errors->all())->reject(fn ($message) => str_contains($pageContent, e($message)))->values()
            : collect();
    @endphp
    @if($unshownErrors->isNotEmpty())
        <div class="alert alert-danger" role="alert">
            @foreach($unshownErrors as $message)<div>&bull; {{ $message }}</div>@endforeach
        </div>
    @endif

    {!! $pageContent !!}
</div>

{{-- ═══════════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════════ --}}
<div class="footerbar clearfix" style="background-color:var(--theme-footer-bg, #1a4d80);">
    <div style="float:left;">
        {{ 'Copyright' }} &copy; {{ company_name() }} {{ date('Y') }}. {{ 'All Rights Reserved.' }}
    </div>
    <div style="float:right;">
        <a href="{{ route('admin.logs.index') }}">{{ 'Report a Bug' }}</a> |
        <a href="{{ route('admin.api-docs') }}">{{ 'Documentation' }}</a> |
        <a href="/" target="_blank">{{ 'Contact Us' }}</a>
    </div>
</div>

@php
    $currentAdminNotes = auth('admin')->check()
        ? (string) \App\Models\Setting::get('admin_notes_' . auth('admin')->id(), '')
        : '';
@endphp
<div class="whmcs-notes-backdrop" id="modalMyNotes" onclick="if(event.target===this) closeMyNotesModal();">
    <form method="POST" action="{{ route('admin.my-notes.save') }}" class="whmcs-notes-dialog">
        @csrf
        <div class="whmcs-notes-header">
            <h4>{{ 'My Notes' }}</h4>
            <button type="button" class="whmcs-notes-close" onclick="closeMyNotesModal()" aria-label="{{ __('common.actions.close') }}">&times;</button>
        </div>
        <div class="whmcs-notes-body">
            <textarea name="notes" rows="10" class="form-control" placeholder="{{ 'Write your personal admin scratchpad notes here...' }}">{{ $currentAdminNotes }}</textarea>
        </div>
        <div class="whmcs-notes-footer">
            <button type="button" class="btn btn-default" onclick="closeMyNotesModal()">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary">{{ 'Save Changes' }}</button>
        </div>
    </form>
</div>

@include('admin.partials.update-bar')
@vite(["resources/js/app.js"])
<button class="sidebar-toggle-btn" id="sidebarToggle" title="{{ __('admin.nav.toggle_sidebar') }}" onclick="toggleSidebar()">&raquo; {{ 'Open Sidebar' }}</button>
<script>
(function(){
    var collapsed = localStorage.getItem("pnlcs_sidebar_collapsed") === "1";
    var sidebar = document.getElementById("sidebar");
    if (sidebar && collapsed) sidebar.classList.add("collapsed");
})();
function toggleSidebar() {
    var sidebar = document.getElementById("sidebar");
    if (!sidebar) return;
    sidebar.classList.toggle("collapsed");
    localStorage.setItem("pnlcs_sidebar_collapsed", sidebar.classList.contains("collapsed") ? "1" : "0");
}
function toggleNavDropdown(e, li) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    if (!li) return;
    var wasOpen = li.classList.contains("open");
    document.querySelectorAll(".navigation li.has-dropdown.open").forEach(function (el) {
        el.classList.remove("open");
    });
    if (!wasOpen) {
        li.classList.add("open");
    }
}
function openIntelliSearch(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    var box = document.getElementById("intellisearch");
    if (!box) return;
    box.classList.add("active");
    var input = document.getElementById("inputIntelliSearchValue");
    if (input) {
        setTimeout(function () { input.focus(); }, 20);
    }
}
function closeIntelliSearch(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    var box = document.getElementById("intellisearch");
    var results = document.getElementById("intellisearch-results");
    var input = document.getElementById("inputIntelliSearchValue");
    if (input) input.value = "";
    if (results) results.hidden = true;
    if (box) box.classList.remove("active");
}
function openMyNotesModal(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    document.querySelectorAll(".navigation li.has-dropdown.open").forEach(function (el) {
        el.classList.remove("open");
    });
    var modal = document.getElementById("modalMyNotes");
    if (modal) modal.classList.add("open");
}
function closeMyNotesModal() {
    var modal = document.getElementById("modalMyNotes");
    if (modal) modal.classList.remove("open");
}
document.addEventListener("click", function (e) {
    if (!e.target.closest(".navigation li.has-dropdown")) {
        document.querySelectorAll(".navigation li.has-dropdown.open").forEach(function (el) {
            el.classList.remove("open");
        });
    }
    var box = document.getElementById("intellisearch");
    if (box && box.classList.contains("active") && !e.target.closest("#intellisearch")) {
        var input = document.getElementById("inputIntelliSearchValue");
        if (!input || !input.value.trim()) {
            box.classList.remove("active");
        }
    }
});
document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
        document.querySelectorAll(".navigation li.has-dropdown.open").forEach(function (el) {
            el.classList.remove("open");
        });
        closeMyNotesModal();
        closeIntelliSearch();
    }
});
</script>
@stack('scripts')
{!! hook_output('AdminAreaFooterOutput', ['admin' => auth('admin')->user()]) !!}
</body>
</html>

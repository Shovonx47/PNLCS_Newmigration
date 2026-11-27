@extends("admin.layouts.app")
@section("title", __("admin.dashboard.title"))
@section("content")

<style>
    /* WHMCS Blend Dashboard Exact Styling */
    .contentarea {
        background-color: #f8f8f8 !important;
    }
    .whmcs-dash-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0 0 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e9e9e9;
    }
    .whmcs-dash-header h1 {
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        font-size: 21px !important;
        font-weight: 400 !important;
        color: #333 !important;
    }
    .whmcs-gear-wrap {
        position: relative;
    }
    .whmcs-gear-btn {
        background: transparent;
        border: none;
        color: #bbb;
        font-size: 19px;
        cursor: pointer;
        padding: 2px 6px;
        line-height: 1;
    }
    .whmcs-gear-btn:hover { color: #666; }
    .whmcs-gear-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        width: 230px;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: 0 6px 14px rgba(0,0,0,0.16);
        z-index: 1050;
        padding: 8px 0;
    }
    .whmcs-gear-menu.open { display: block; }
    .whmcs-gear-menu h4 {
        margin: 0 0 6px;
        padding: 4px 12px 6px;
        font-size: 12px;
        font-weight: 700;
        color: #444;
        border-bottom: 1px solid #eee;
    }
    .whmcs-gear-menu label {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 12px;
        font-size: 12.5px;
        color: #333;
        cursor: pointer;
        margin: 0;
    }
    .whmcs-gear-menu label:hover { background: #f5f5f5; }

    /* Top 4 WHMCS Status Badges */
    .whmcs-badge-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 14px;
    }
    @media (max-width: 991px) {
        .whmcs-badge-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575px) {
        .whmcs-badge-grid { grid-template-columns: 1fr; }
    }
    .health-status-block {
        display: flex;
        align-items: stretch;
        height: 70px;
        border-radius: 4px;
        color: #fff;
        overflow: hidden;
        text-decoration: none !important;
        transition: filter 0.15s ease;
    }
    .health-status-block:hover { filter: brightness(0.97); color: #fff; text-decoration: none !important; }
    .health-status-block .icon {
        width: 30%;
        min-width: 66px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: #fff;
    }
    .health-status-block .detail {
        width: 70%;
        padding: 8px 15px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        color: #fff;
    }
    .health-status-block .detail .count {
        font-size: 25px;
        font-weight: 400;
        line-height: 1.15;
    }
    .health-status-block .detail .desc {
        font-size: 11.5px;
        opacity: 0.95;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .status-badge-green { background-color: #5dc560; }
    .status-badge-green .icon { background-color: #49a94d; }
    .status-badge-pink { background-color: #ea5395; }
    .status-badge-pink .icon { background-color: #d61a6c; }
    .status-badge-orange { background-color: #eaae53; }
    .status-badge-orange .icon { background-color: #d28818; }
    .status-badge-cyan { background-color: #8dd5d9; }
    .status-badge-cyan .icon { background-color: #68b1b5; }

    /* Main 2-Column Asymmetric WHMCS Grid (Left 2/3, Right 1/3) */
    .whmcs-main-grid {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
        gap: 14px;
        align-items: start;
    }
    .whmcs-sub-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        align-items: start;
    }
    @media (max-width: 1100px) {
        .whmcs-main-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 767px) {
        .whmcs-sub-grid { grid-template-columns: 1fr; }
    }

    /* WHMCS Widget Panel Chrome */
    .whmcs-widget {
        background: #fff;
        border: 1px solid #ececec;
        border-radius: 3px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        margin-bottom: 14px;
        overflow: hidden;
    }
    .whmcs-widget-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 15px;
        background: #fff;
        border-bottom: 1px solid #eee;
    }
    .whmcs-widget-title {
        margin: 0;
        color: #275f96;
        font-size: 13px;
        font-weight: 600;
    }
    .whmcs-widget-tools {
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .whmcs-widget-tools button {
        background: none;
        border: 0;
        padding: 0 2px;
        color: #ccc;
        font-size: 12px;
        cursor: pointer;
        line-height: 1;
    }
    .whmcs-widget-tools button:hover { color: #777; }
    .whmcs-widget-body {
        background: #fff;
    }
    .whmcs-widget.minimised .whmcs-widget-body {
        display: none;
    }

    /* Ultimate Business Overview Cards (exact WHMCS hook styles) */
    .ubo-wrap { padding: 12px 15px 6px; }
    .ubo-sec-title {
        font-size: 13px;
        font-weight: 700;
        color: #444;
        text-transform: uppercase;
        margin: 8px 0 10px;
        border-bottom: 2px solid #eee;
        padding-bottom: 5px;
    }
    .ubo-sec-title:first-child { margin-top: 0; }
    .ubo-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }
    .ubo-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 12px;
    }
    @media (max-width: 767px) {
        .ubo-grid-4, .ubo-grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .a2z-card {
        transition: all 0.25s ease;
        border-radius: 8px;
        color: #fff !important;
        padding: 14px 15px;
        text-decoration: none !important;
        display: block;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.09);
    }
    .a2z-card:hover { transform: translateY(-2px); box-shadow: 0 7px 12px rgba(0,0,0,0.16); color: #fff !important; }
    .a2z-card .w-icon {
        font-size: 34px;
        opacity: 0.22;
        position: absolute;
        right: 6px;
        bottom: -4px;
        transition: all 0.25s ease;
    }
    .a2z-card:hover .w-icon { transform: scale(1.08); opacity: 0.35; }
    .a2z-card .val {
        font-size: 19px;
        font-weight: 700;
        margin: 0;
        line-height: 1.25;
        position: relative;
        z-index: 2;
    }
    .a2z-card .sub-val {
        font-size: 11.5px;
        font-weight: 600;
        opacity: 0.92;
        margin-bottom: 3px;
        position: relative;
        z-index: 2;
    }
    .a2z-card .lbl {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.88;
        font-weight: 600;
        margin-top: 3px;
        position: relative;
        z-index: 2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bg-c-dark   { background: linear-gradient(135deg, #343a40, #6c757d); }
    .bg-c-blue   { background: linear-gradient(135deg, #0d6efd, #0dcaf0); }
    .bg-c-gold   { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .bg-c-orange { background: linear-gradient(135deg, #fd7e14, #ffc107); }
    .bg-c-green  { background: linear-gradient(135deg, #198754, #20c997); }
    .bg-c-teal   { background: linear-gradient(135deg, #009688, #4db6ac); }
    .bg-c-pink   { background: linear-gradient(135deg, #e83e8c, #fd7e14); }
    .bg-c-purple { background: linear-gradient(135deg, #6f42c1, #d63384); }
    .bg-c-red    { background: linear-gradient(135deg, #8b0000, #dc3545); }
    .bg-c-crimson{ background: linear-gradient(135deg, #dc3545, #e83e8c); }
    .bg-c-cyan   { background: linear-gradient(135deg, #0dcaf0, #20c997); }

    /* Shared WHMCS Widget Colors */
    .color-blue   { color: #6ecacc; }
    .color-grey   { color: #959595; }
    .color-purple { color: #af5dd5; }
    .color-green  { color: #5dc560; }
    .color-orange { color: #eaae53; }
    .color-pink   { color: #ea5395; }

    /* Scrollable Widget Content */
    .whmcs-scroll-box {
        padding: 12px 18px;
        max-height: 280px;
        overflow-y: auto;
        font-size: 12px;
    }
    .whmcs-feed-row {
        padding: 9px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .whmcs-feed-row:last-child { border-bottom: 0; }

    /* Automation Sparkline Grid */
    .whmcs-auto-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .whmcs-auto-cell {
        padding: 12px 10px 10px;
        text-align: center;
        border-bottom: 1px solid #f0f0f0;
    }
    .whmcs-auto-cell:nth-child(odd) { border-right: 1px solid #f0f0f0; }
    .whmcs-auto-title {
        font-size: 12px;
        color: #a2a6af;
        font-weight: 400;
        margin: 4px 0 2px;
    }
    .whmcs-auto-val {
        font-size: 20px;
        font-weight: 400;
        line-height: 1.2;
    }
    .whmcs-auto-foot {
        padding: 8px 14px;
        font-size: 11.5px;
        color: #888;
        background: #fafafa;
    }

    /* Billing 2x2 Grid */
    .whmcs-billing-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
    .whmcs-billing-cell {
        padding: 15px 18px;
    }
    .whmcs-billing-cell:nth-child(1) { border-right: 1px solid #eee; border-bottom: 1px solid #eee; }
    .whmcs-billing-cell:nth-child(2) { border-bottom: 1px solid #eee; }
    .whmcs-billing-cell:nth-child(3) { border-right: 1px solid #eee; }
    .whmcs-billing-cell .val { font-size: 22px; font-weight: 400; line-height: 1.2; }
    .whmcs-billing-cell .note { font-size: 11.5px; color: #a2a6af; margin-top: 3px; }

    /* Period / Project Tabs */
    .whmcs-period-btns {
        display: inline-flex;
        border: 1px solid #ddd;
        border-radius: 3px;
        overflow: hidden;
    }
    .whmcs-period-btns button {
        background: #fff;
        border: 0;
        border-right: 1px solid #ddd;
        padding: 4px 11px;
        font-size: 11.5px;
        color: #777;
        cursor: pointer;
    }
    .whmcs-period-btns button:last-child { border-right: 0; }
    .whmcs-period-btns button.active {
        background: #e9ecef;
        color: #333;
        font-weight: 600;
    }
    .whmcs-proj-tabs {
        display: flex;
        border-bottom: 1px solid #ddd;
        padding: 10px 15px 0;
        gap: 2px;
    }
    .whmcs-proj-tabs button {
        background: transparent;
        border: 1px solid transparent;
        border-bottom: 0;
        padding: 7px 14px;
        font-size: 12.5px;
        color: #337ab7;
        cursor: pointer;
        border-radius: 3px 3px 0 0;
        margin-bottom: -1px;
    }
    .whmcs-proj-tabs button.active {
        background: #fff;
        color: #555;
        border-color: #ddd;
        font-weight: 600;
    }
</style>

{{-- ── Dashboard Title + Gear Dropdown ── --}}
<div class="whmcs-dash-header">
    <h1>{{ __('admin.dashboard.title') }}</h1>
    <div class="whmcs-gear-wrap">
        <button type="button" class="whmcs-gear-btn" onclick="document.getElementById('whmcsGearMenu').classList.toggle('open')" title="{{ __('admin.addon_modules.settings') }}">
            <i class="fas fa-cog"></i>
        </button>
        <div class="whmcs-gear-menu" id="whmcsGearMenu">
            <h4>{{ 'Configure Widgets' }}</h4>
            @foreach([
                'w-ubo' => 'Ultimate Business Overview',
                'w-automation' => 'Automation Overview',
                'w-activity' => 'Activity',
                'w-marketconnect' => 'MarketConnect',
                'w-staff' => 'Staff Online',
                'w-health' => 'System Health',
                'w-clientactivity' => 'Client Activity',
                'w-todo' => 'To-Do List',
                'w-network' => 'Network Status',
                'w-billing' => 'Billing',
                'w-support' => 'Support',
                'w-sysoverview' => 'System Overview',
                'w-projects' => 'Project Management',
            ] as $wid => $wlabel)
                <label>
                    <input type="checkbox" checked onchange="toggleWhmcsWidget('{{ $wid }}', this.checked)">
                    <span>{{ $wlabel }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>

{{-- ── Row 1: 4 Classic WHMCS Blend Colored Status Badges ── --}}
<div class="whmcs-badge-grid">
    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="health-status-block status-badge-green">
        <div class="icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="detail">
            <span class="count">{{ $badges['pending_orders'] ?? 0 }}</span>
            <span class="desc">{{ 'Pending Orders' }}</span>
        </div>
    </a>

    <a href="{{ route('admin.tickets.index') }}" class="health-status-block status-badge-pink">
        <div class="icon"><i class="fas fa-comment"></i></div>
        <div class="detail">
            <span class="count">{{ $badges['tickets_waiting'] ?? 0 }}</span>
            <span class="desc">{{ 'Tickets Waiting' }}</span>
        </div>
    </a>

    <a href="{{ route('admin.services.index', ['status' => 'cancelled']) }}" class="health-status-block status-badge-orange">
        <div class="icon"><i class="fas fa-ban"></i></div>
        <div class="detail">
            <span class="count">{{ $badges['pending_cancellations'] ?? 0 }}</span>
            <span class="desc">{{ 'Pending Cancellations' }}</span>
        </div>
    </a>

    <a href="{{ route('admin.logs.module') }}" class="health-status-block status-badge-cyan">
        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="detail">
            <span class="count">{{ $badges['pending_module_actions'] ?? 0 }}</span>
            <span class="desc">{{ 'Pending Module Actions' }}</span>
        </div>
    </a>
</div>

{{-- ── Main 2-Column Asymmetric WHMCS Widget Grid ── --}}
<div class="whmcs-main-grid">

    {{-- ══════════════════════════════════════════════════
         LEFT 2/3 COLUMN
         ══════════════════════════════════════════════════ --}}
    <div>

        {{-- 1. Ultimate Business Overview --}}
        <div class="whmcs-widget" id="w-ubo">
            <div class="whmcs-widget-head">
                <h3 class="whmcs-widget-title">{{ 'Ultimate Business Overview' }}</h3>
                <div class="whmcs-widget-tools">
                    <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                    <button type="button" onclick="minimiseWhmcsWidget('w-ubo', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                    <button type="button" onclick="toggleWhmcsWidget('w-ubo', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="whmcs-widget-body">
                <div class="ubo-wrap">
                    <div class="ubo-sec-title">&#128202; {{ 'General, Support & Sales' }}</div>
                    <div class="ubo-grid-4">
                        <a href="{{ route('admin.clients.index') }}" class="a2z-card bg-c-dark">
                            <i class="fas fa-user-plus w-icon"></i>
                            <div class="val">{{ $ubo['newClients'] }}</div>
                            <div class="lbl">{{ 'New Clients' }}</div>
                        </a>
                        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="a2z-card bg-c-blue">
                            <i class="fas fa-box-open w-icon"></i>
                            <div class="val">{{ $ubo['orders'] }}</div>
                            <div class="lbl">{{ 'Pending Orders' }}</div>
                        </a>
                        <a href="{{ route('admin.reports.index') }}" class="a2z-card bg-c-gold">
                            <i class="fas fa-trophy w-icon"></i>
                            <div class="val">{{ $ubo['topProdSales'] }} {{ 'Sold' }}</div>
                            <div class="lbl" title="{{ $ubo['topProdName'] }}">&#127942; {{ $ubo['topProdName'] }}</div>
                        </a>
                        <a href="{{ route('admin.tickets.index') }}" class="a2z-card bg-c-orange">
                            <i class="fas fa-ticket-alt w-icon"></i>
                            <div class="val">{{ $ubo['tickets'] }}</div>
                            <div class="lbl">{{ 'Open Tickets' }}</div>
                        </a>
                    </div>

                    <div class="ubo-sec-title">&#128176; {{ 'Billing & Income Stats' }}</div>
                    <div class="ubo-grid-3">
                        <a href="{{ route('admin.config.transactions') }}" class="a2z-card bg-c-green">
                            <i class="fas fa-wallet w-icon"></i>
                            <div class="val">{{ $ubo['income'] }}</div>
                            <div class="lbl">{{ 'Income Today' }} ({{ $ubo['paidToday'] }})</div>
                        </a>
                        <a href="{{ route('admin.invoices.index', ['status' => 'paid']) }}" class="a2z-card bg-c-teal">
                            <i class="fas fa-sync-alt w-icon"></i>
                            <div class="val">{{ $ubo['renewAmount'] }}</div>
                            <div class="lbl">{{ 'Renew Today' }} ({{ $ubo['renewCount'] }})</div>
                        </a>
                        <a href="{{ route('admin.invoices.index', ['status' => 'paid']) }}" class="a2z-card bg-c-pink">
                            <i class="fas fa-piggy-bank w-icon"></i>
                            <div class="val">{{ $ubo['addFunds'] }}</div>
                            <div class="lbl">{{ 'Add Funds Today' }}</div>
                        </a>

                        <a href="{{ route('admin.invoices.index', ['status' => 'unpaid']) }}" class="a2z-card bg-c-purple">
                            <i class="fas fa-file-invoice-dollar w-icon"></i>
                            <div class="val">{{ $ubo['due'] }}</div>
                            <div class="lbl">{{ 'Total Unpaid Due' }}</div>
                        </a>
                        <a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}" class="a2z-card bg-c-red">
                            <i class="fas fa-clock w-icon"></i>
                            <div class="val">{{ $ubo['overdue'] }}</div>
                            <div class="lbl">{{ 'Total Overdue' }}</div>
                        </a>
                        <a href="{{ route('admin.clients.index') }}" class="a2z-card bg-c-cyan">
                            <i class="fas fa-coins w-icon"></i>
                            <div class="val">{{ $ubo['totalCredit'] }}</div>
                            <div class="lbl">{{ 'Total Client Credit' }}</div>
                        </a>
                    </div>

                    <div class="ubo-sec-title">&#9889; {{ 'iTalk Recharge Today' }}</div>
                    <div class="ubo-grid-3">
                        <a href="{{ url('/admin/extensions/iptsp-recharge') }}" class="a2z-card bg-c-green">
                            <i class="fas fa-check-circle w-icon"></i>
                            <div class="sub-val">{{ 'Count:' }} {{ $ubo['rcgSuccessC'] }}</div>
                            <div class="val">{{ $ubo['rcgSuccessA'] }}</div>
                            <div class="lbl">{{ 'Success Today' }}</div>
                        </a>
                        <a href="{{ url('/admin/extensions/iptsp-recharge') }}" class="a2z-card bg-c-orange">
                            <i class="fas fa-hourglass-half w-icon"></i>
                            <div class="sub-val">{{ 'Count:' }} {{ $ubo['rcgPendingC'] }}</div>
                            <div class="val">{{ $ubo['rcgPendingA'] }}</div>
                            <div class="lbl">{{ 'Pending Today' }}</div>
                        </a>
                        <a href="{{ url('/admin/extensions/iptsp-recharge') }}" class="a2z-card bg-c-crimson">
                            <i class="fas fa-times-circle w-icon"></i>
                            <div class="sub-val">{{ 'Count:' }} {{ $ubo['rcgFailedC'] }}</div>
                            <div class="val">{{ $ubo['rcgFailedA'] }}</div>
                            <div class="lbl">{{ 'Failed Today' }}</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Two Inner Sub-Columns: [Activity + Client Activity] | [MarketConnect + To-Do + Network Status] --}}
        <div class="whmcs-sub-grid">

            {{-- Sub-Column Left --}}
            <div>
                {{-- Activity --}}
                <div class="whmcs-widget" id="w-activity">
                    <div class="whmcs-widget-head">
                        <h3 class="whmcs-widget-title">{{ 'Activity' }}</h3>
                        <div class="whmcs-widget-tools">
                            <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                            <button type="button" onclick="minimiseWhmcsWidget('w-activity', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                            <button type="button" onclick="toggleWhmcsWidget('w-activity', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <div class="whmcs-widget-body">
                        <div class="whmcs-scroll-box">
                            @forelse($activityFeed as $act)
                                <div class="whmcs-feed-row">
                                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px;">
                                        <strong style="color:#222;font-size:12px;">{{ $act['user'] }}</strong>
                                        <span style="font-size:11px;color:#64748b;white-space:nowrap;">{{ $act['ago'] }}</span>
                                    </div>
                                    <div style="color:#555;margin-top:2px;line-height:1.45;">{{ $act['description'] }}</div>
                                    @if($act['ip'] !== '')
                                        <div style="font-size:11px;color:#94a3b8;margin-top:1px;">{{ $act['ip'] }}</div>
                                    @endif
                                </div>
                            @empty
                                <div style="padding:18px 0;text-align:center;color:#999;">{{ 'No recent system activity recorded.' }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Client Activity --}}
                <div class="whmcs-widget" id="w-clientactivity">
                    <div class="whmcs-widget-head">
                        <h3 class="whmcs-widget-title">{{ 'Client Activity' }}</h3>
                        <div class="whmcs-widget-tools">
                            <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                            <button type="button" onclick="minimiseWhmcsWidget('w-clientactivity', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                            <button type="button" onclick="toggleWhmcsWidget('w-clientactivity', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <div class="whmcs-widget-body">
                        <div style="display:grid;grid-template-columns:1fr 1fr;padding:16px 18px;border-bottom:1px solid #f0f0f0;gap:10px;">
                            <a href="{{ route('admin.clients.index') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;">
                                <span style="width:36px;height:36px;border-radius:50%;border:1.5px solid #eaae53;color:#eaae53;display:inline-flex;align-items:center;justify-content:center;font-size:16px;">
                                    <i class="far fa-user"></i>
                                </span>
                                <div>
                                    <div style="font-size:11.5px;color:#666;">{{ 'Active Clients' }}</div>
                                    <div style="font-size:18px;"><span class="color-orange" style="font-weight:600;">{{ number_format($clientActivity['activeClients']) }}</span> <span style="font-size:13px;color:#888;">{{ 'Active' }}</span></div>
                                </div>
                            </a>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span style="width:36px;height:36px;border-radius:50%;border:1.5px solid #5dc560;color:#5dc560;display:inline-flex;align-items:center;justify-content:center;font-size:16px;">
                                    <i class="far fa-smile"></i>
                                </span>
                                <div>
                                    <div style="font-size:11.5px;color:#666;">{{ 'Users Online' }}</div>
                                    <div style="font-size:18px;"><span class="color-green" style="font-weight:600;">{{ number_format($clientActivity['usersOnline']) }}</span> <span style="font-size:13px;color:#888;">{{ 'Last Hour' }}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="whmcs-scroll-box" style="max-height:220px;">
                            @foreach($clientActivity['clients'] as $cl)
                                <div class="whmcs-feed-row" style="padding:7px 0;">
                                    <div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px;">
                                        <a href="{{ route('admin.clients.show', $cl['id']) }}" style="color:#333;font-weight:500;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $cl['label'] }}</a>
                                        <span style="font-size:11px;color:#999;white-space:nowrap;">{{ $cl['ago'] }}</span>
                                    </div>
                                    @if($cl['ip'] !== '')
                                        <div style="font-size:11px;color:#5bc0de;margin-top:1px;">{{ $cl['ip'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sub-Column Middle --}}
            <div>
                {{-- MarketConnect --}}
                <div class="whmcs-widget" id="w-marketconnect">
                    <div class="whmcs-widget-head">
                        <h3 class="whmcs-widget-title">{{ 'MarketConnect' }}</h3>
                        <div class="whmcs-widget-tools">
                            <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                            <button type="button" onclick="minimiseWhmcsWidget('w-marketconnect', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                            <button type="button" onclick="toggleWhmcsWidget('w-marketconnect', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <div class="whmcs-widget-body" style="padding:14px 18px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                            <span style="font-size:14px;color:#444;">{{ 'Selling Status' }}</span>
                            <a href="{{ route('admin.products.index') }}" class="btn btn-default btn-xs" style="padding:3px 12px !important;">{{ 'Manage' }}</a>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center;padding-bottom:12px;border-bottom:1px solid #eee;">
                            @foreach([
                                ['icon' => 'fas fa-bullhorn', 'bg' => '#fef3c7', 'fg' => '#d97706', 'title' => 'Social Media Management', 'by' => 'by SocialBee'],
                                ['icon' => 'fas fa-lock', 'bg' => '#dbeafe', 'fg' => '#2563eb', 'title' => 'SSL Certificates', 'by' => 'by DigiCert'],
                                ['icon' => 'fas fa-globe', 'bg' => '#f3e8ff', 'fg' => '#7c3aed', 'title' => 'Site and Server Monito..', 'by' => 'by WebPros'],
                            ] as $mc)
                                <div style="padding:6px 4px;">
                                    <div style="width:44px;height:44px;margin:0 auto 8px;border-radius:50%;background:{{ $mc['bg'] }};color:{{ $mc['fg'] }};display:flex;align-items:center;justify-content:center;font-size:18px;opacity:0.7;">
                                        <i class="{{ $mc['icon'] }}"></i>
                                    </div>
                                    <div style="font-size:11px;color:#999;line-height:1.3;height:28px;overflow:hidden;">{{ $mc['title'] }}<br>{{ $mc['by'] }}</div>
                                    <span style="display:inline-block;margin-top:6px;padding:2px 6px;font-size:9.5px;font-weight:700;background:#777;color:#fff;border-radius:3px;text-transform:uppercase;">{{ 'Not Selling' }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div style="padding-top:12px;text-align:center;font-size:12px;color:#666;line-height:1.5;">
                            {{ 'MarketConnect gives you access to resell market leading services to your customers in minutes.' }}
                            <a href="{{ route('admin.config.sslModules') }}" style="color:#337ab7;text-decoration:underline;">{{ 'Learn more »' }}</a>
                        </div>
                    </div>
                </div>

                {{-- To-Do List --}}
                <div class="whmcs-widget" id="w-todo">
                    <div class="whmcs-widget-head">
                        <h3 class="whmcs-widget-title">{{ 'To-Do List' }}</h3>
                        <div class="whmcs-widget-tools">
                            <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                            <button type="button" onclick="minimiseWhmcsWidget('w-todo', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                            <button type="button" onclick="toggleWhmcsWidget('w-todo', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <div class="whmcs-widget-body" style="padding:12px 15px;">
                        @forelse($todos as $td)
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;background:#f8f8f8;padding:6px 10px;margin-bottom:4px;border-radius:3px;font-size:12px;">
                                <label style="display:flex;align-items:center;gap:7px;margin:0;font-weight:400;cursor:pointer;overflow:hidden;">
                                    <input type="checkbox" onchange="this.closest('div').style.opacity = this.checked ? '0.45' : '1'">
                                    <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $td['title'] }}</span>
                                    <span style="background:#5cb85c;color:#fff;font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:2px;">{{ $td['status'] }}</span>
                                    <i class="fas fa-user" style="color:#888;font-size:11px;"></i>
                                </label>
                                <span style="font-size:11px;color:#999;white-space:nowrap;">{{ $td['due'] }}</span>
                            </div>
                        @empty
                            <div style="text-align:center;padding:14px 0;font-size:12.5px;color:#777;">
                                {{ 'No Incomplete To-Do Items.' }}
                                <div style="margin-top:10px;">
                                    <a href="{{ route('admin.config.todo') }}" class="btn btn-primary btn-xs">{{ 'Add a To-Do Item' }}</a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Network Status --}}
                <div class="whmcs-widget" id="w-network">
                    <div class="whmcs-widget-head">
                        <h3 class="whmcs-widget-title">{{ 'Network Status' }}</h3>
                        <div class="whmcs-widget-tools">
                            <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                            <button type="button" onclick="minimiseWhmcsWidget('w-network', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                            <button type="button" onclick="toggleWhmcsWidget('w-network', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <div class="whmcs-widget-body">
                        <div class="whmcs-scroll-box" style="max-height:240px;">
                            @forelse($servers as $srv)
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-bottom:1px solid #f3f3f3;gap:10px;">
                                    <div style="width:36%;min-width:0;">
                                        <div style="font-size:13.5px;color:#555;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $srv['name'] }}">{{ $srv['name'] }}</div>
                                        <div style="font-size:11px;color:#337ab7;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $srv['address'] }}</div>
                                    </div>
                                    <div style="width:64%;display:grid;grid-template-columns:repeat(3,1fr);text-align:center;font-size:12px;">
                                        <div>
                                            <div class="color-green" style="font-weight:600;">{{ 'N/A' }}</div>
                                            <div style="font-size:10.5px;color:#a2a6af;">{{ 'Status' }}</div>
                                        </div>
                                        <div>
                                            <div style="color:#444;">{{ 'N/A' }}</div>
                                            <div style="font-size:10.5px;color:#a2a6af;">{{ 'Uptime' }}</div>
                                        </div>
                                        <div>
                                            <div style="color:#31708f;">{{ 'N/A' }}</div>
                                            <div style="font-size:10.5px;color:#a2a6af;">{{ 'Avg. load' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div style="text-align:center;padding:16px 0;color:#999;">{{ 'No servers configured.' }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- 3. System Overview Interactive Chart --}}
        <div class="whmcs-widget" id="w-sysoverview">
            <div class="whmcs-widget-head">
                <h3 class="whmcs-widget-title">{{ 'System Overview' }}</h3>
                <div class="whmcs-widget-tools">
                    <button type="button" onclick="drawWhmcsOverviewChart(window._whmcsCurrentPeriod || 'today')" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                    <button type="button" onclick="minimiseWhmcsWidget('w-sysoverview', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                    <button type="button" onclick="toggleWhmcsWidget('w-sysoverview', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="whmcs-widget-body" style="padding:14px 18px 18px;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:16px;font-size:11.5px;color:#666;margin:0 auto;">
                        <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:26px;height:11px;background:rgba(200,200,200,0.45);border:1.5px solid #bbb;display:inline-block;"></span> {{ 'New Orders' }}</span>
                        <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:26px;height:11px;background:rgba(51,92,249,0.45);border:1.5px solid #335cf9;display:inline-block;"></span> {{ 'Activated Orders' }}</span>
                        <span style="display:inline-flex;align-items:center;gap:6px;"><span style="width:26px;height:11px;background:rgba(93,197,96,0.5);border:1.5px solid #5dc560;display:inline-block;"></span> {{ 'Income' }}</span>
                    </div>
                    <div class="whmcs-period-btns" id="whmcsPeriodBtns">
                        <button type="button" class="active" data-period="today" onclick="switchWhmcsPeriod('today', this)">{{ 'Today' }}</button>
                        <button type="button" data-period="month" onclick="switchWhmcsPeriod('month', this)">{{ 'Last 30 Days' }}</button>
                        <button type="button" data-period="year" onclick="switchWhmcsPeriod('year', this)">{{ 'Last 1 Year' }}</button>
                    </div>
                </div>
                <div style="position:relative;width:100%;height:245px;">
                    <canvas id="whmcsOverviewCanvas" style="width:100%;height:245px;display:block;"></canvas>
                </div>
            </div>
        </div>

        {{-- 4. Project Management --}}
        <div class="whmcs-widget" id="w-projects">
            <div class="whmcs-widget-head">
                <h3 class="whmcs-widget-title">{{ 'Project Management' }}</h3>
                <div class="whmcs-widget-tools">
                    <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                    <button type="button" onclick="minimiseWhmcsWidget('w-projects', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                    <button type="button" onclick="toggleWhmcsWidget('w-projects', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="whmcs-widget-body">
                <div class="whmcs-proj-tabs" id="whmcsProjTabs">
                    <button type="button" class="active" onclick="switchWhmcsProjTab('assigned', this)">{{ 'My Assigned' }}</button>
                    <button type="button" onclick="switchWhmcsProjTab('due', this)">{{ 'Due Projects' }}</button>
                    <button type="button" onclick="switchWhmcsProjTab('recent', this)">{{ 'Recent Activity' }}</button>
                </div>
                <div style="padding:12px 15px 16px;">
                    @foreach(['assigned', 'due', 'recent'] as $pKey)
                        <div class="whmcs-proj-pane" id="proj-pane-{{ $pKey }}" style="{{ $pKey === 'assigned' ? '' : 'display:none;' }}">
                            <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
                                <thead>
                                    <tr style="background:#f9f9f9;border-bottom:1px solid #e5e5e5;">
                                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#333;">{{ 'Title' }}</th>
                                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#333;">{{ 'Due Date' }}</th>
                                        <th style="padding:8px 10px;text-align:center;font-weight:700;color:#333;">{{ 'Days Left / Due In' }}</th>
                                        <th style="padding:8px 10px;text-align:center;font-weight:700;color:#333;">{{ 'Status' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($projectTabs[$pKey] ?? [] as $pr)
                                        <tr style="border-bottom:1px solid #f0f0f0;">
                                            <td style="padding:8px 10px;font-weight:600;color:#275f96;">{{ $pr['title'] }}</td>
                                            <td style="padding:8px 10px;color:#555;">{{ $pr['due_date'] }}</td>
                                            <td style="padding:8px 10px;text-align:center;color:#666;">{{ $pr['days_in'] }}</td>
                                            <td style="padding:8px 10px;text-align:center;"><span class="badge badge-open">{{ $pr['status'] }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" style="padding:16px;text-align:center;color:#666;">{{ 'No Records Found' }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════════
         RIGHT 1/3 COLUMN
         ══════════════════════════════════════════════════ --}}
    <div>

        {{-- 1. Automation Overview --}}
        <div class="whmcs-widget" id="w-automation">
            <div class="whmcs-widget-head">
                <h3 class="whmcs-widget-title">{{ 'Automation Overview' }}</h3>
                <div class="whmcs-widget-tools">
                    <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                    <button type="button" onclick="minimiseWhmcsWidget('w-automation', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                    <button type="button" onclick="toggleWhmcsWidget('w-automation', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="whmcs-widget-body">
                <div class="whmcs-auto-grid">
                    @foreach($automation['items'] ?? [] as $item)
                        @php
                            $vals = $item['series'] ?? [0,0,0,0,0,0,0];
                            $maxV = max(1, max($vals));
                            $pts = [];
                            $cnt = count($vals);
                            foreach ($vals as $idx => $v) {
                                $x = $cnt > 1 ? round(($idx / ($cnt - 1)) * 116, 1) + 2 : 60;
                                $y = round(28 - (($v / $maxV) * 24), 1);
                                $pts[] = "{$x},{$y}";
                            }
                            $linePoints = implode(' ', $pts);
                            $areaPoints = '2,30 ' . $linePoints . ' 118,30';
                        @endphp
                        <div class="whmcs-auto-cell">
                            <a href="{{ route('admin.config.automation') }}" style="text-decoration:none;display:inline-block;">
                                <svg width="120" height="32" viewBox="0 0 120 32" style="display:block;margin:0 auto;">
                                    <polygon points="{{ $areaPoints }}" fill="{{ $item['fill'] }}"></polygon>
                                    <polyline points="{{ $linePoints }}" fill="none" stroke="{{ $item['stroke'] }}" stroke-width="1.8"></polyline>
                                </svg>
                            </a>
                            <div class="whmcs-auto-title">{{ $item['label'] }}</div>
                            <div class="whmcs-auto-val {{ $item['colorClass'] }}">{{ $item['today'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="whmcs-auto-foot">
                    <i class="fas fa-check-circle" style="color:#888;margin-right:4px;"></i>
                    {{ 'Last Automation Run:' }} <strong>{{ $automation['lastRun'] ?? 'Today at 9:00 AM' }}</strong>
                </div>
            </div>
        </div>

        {{-- 2. Staff Online --}}
        <div class="whmcs-widget" id="w-staff">
            <div class="whmcs-widget-head">
                <h3 class="whmcs-widget-title">{{ 'Staff Online' }}</h3>
                <div class="whmcs-widget-tools">
                    <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                    <button type="button" onclick="minimiseWhmcsWidget('w-staff', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                    <button type="button" onclick="toggleWhmcsWidget('w-staff', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="whmcs-widget-body" style="padding:14px 18px;">
                <div style="display:flex;flex-wrap:wrap;gap:16px;">
                    @foreach($staffOnline as $st)
                        <div style="width:105px;text-align:center;">
                            <div style="width:58px;height:58px;margin:0 auto 6px;background:#d8d8d8;border-radius:3px;display:flex;align-items:flex-end;justify-content:center;overflow:hidden;color:#fff;font-size:44px;line-height:1;">
                                <i class="fas fa-user" style="margin-bottom:-4px;"></i>
                            </div>
                            <div style="font-size:12px;font-weight:700;color:#222;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $st['name'] }}</div>
                            <div style="font-size:10.5px;color:#999;">{{ $st['ago'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 3. System Health --}}
        <div class="whmcs-widget" id="w-health">
            <div class="whmcs-widget-head">
                <h3 class="whmcs-widget-title">{{ 'System Health' }}</h3>
                <div class="whmcs-widget-tools">
                    <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                    <button type="button" onclick="minimiseWhmcsWidget('w-health', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                    <button type="button" onclick="toggleWhmcsWidget('w-health', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="whmcs-widget-body" style="padding:16px 18px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <i class="far fa-life-ring" style="font-size:36px;color:#49a94d;"></i>
                        <div>
                            <div style="font-size:12px;color:#666;">{{ 'Overall Rating' }}</div>
                            <div style="font-size:21px;color:#49a94d;font-weight:500;line-height:1.15;">{{ $health['rating'] }}</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.config.automation') }}" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-right" style="font-size:11px;"></i> {{ 'View Issues' }}
                    </a>
                </div>
                <div style="height:18px;background:#f1f1f1;border-radius:3px;overflow:hidden;margin:14px 0 12px;box-shadow:inset 0 1px 2px rgba(0,0,0,0.08);">
                    <div style="height:100%;width:{{ $health['percent'] }}%;background:repeating-linear-gradient(45deg,#5cb85c,#5cb85c 12px,#68c168 12px,#68c168 24px);"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;text-align:center;font-size:12px;">
                    <div style="color:#444;"><i class="fas fa-exclamation-triangle"></i> {{ $health['warnings'] }} {{ 'Warnings' }}</div>
                    <div style="color:#c9302c;"><i class="fas fa-times"></i> {{ $health['danger'] }} {{ 'Needing Attention' }}</div>
                </div>
            </div>
        </div>

        {{-- 4. Billing --}}
        <div class="whmcs-widget" id="w-billing">
            <div class="whmcs-widget-head">
                <h3 class="whmcs-widget-title">{{ 'Billing' }}</h3>
                <div class="whmcs-widget-tools">
                    <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                    <button type="button" onclick="minimiseWhmcsWidget('w-billing', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                    <button type="button" onclick="toggleWhmcsWidget('w-billing', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="whmcs-widget-body">
                <div class="whmcs-billing-grid">
                    <div class="whmcs-billing-cell">
                        <div class="val color-green">{{ $billingSummary['today'] }}</div>
                        <div class="note">{{ 'Today' }}</div>
                    </div>
                    <div class="whmcs-billing-cell">
                        <div class="val color-orange">{{ $billingSummary['month'] }}</div>
                        <div class="note">{{ 'This Month' }}</div>
                    </div>
                    <div class="whmcs-billing-cell">
                        <div class="val color-pink">{{ $billingSummary['year'] }}</div>
                        <div class="note">{{ 'This Year' }}</div>
                    </div>
                    <div class="whmcs-billing-cell">
                        <div class="val" style="color:#222;">{{ $billingSummary['allTime'] }}</div>
                        <div class="note">{{ 'All Time' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. Support --}}
        <div class="whmcs-widget" id="w-support">
            <div class="whmcs-widget-head">
                <h3 class="whmcs-widget-title">{{ 'Support' }}</h3>
                <div class="whmcs-widget-tools">
                    <button type="button" onclick="window.location.reload()" title="{{ 'Refresh' }}"><i class="fas fa-sync-alt"></i></button>
                    <button type="button" onclick="minimiseWhmcsWidget('w-support', this)" title="{{ 'Minimise' }}"><i class="fas fa-chevron-up"></i></button>
                    <button type="button" onclick="toggleWhmcsWidget('w-support', false)" title="{{ 'Close' }}"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="whmcs-widget-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;padding:16px 18px;border-bottom:1px solid #f0f0f0;gap:10px;">
                    <a href="{{ route('admin.tickets.index') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;">
                        <i class="fas fa-tag color-blue" style="font-size:26px;opacity:0.8;"></i>
                        <div>
                            <div style="font-size:11.5px;color:#555;">{{ 'Awaiting Reply' }}</div>
                            <div style="font-size:18px;"><span class="color-blue" style="font-weight:600;">{{ $supportSummary['awaiting'] }}</span> <span style="font-size:13px;color:#888;">{{ 'Tickets' }}</span></div>
                        </div>
                    </a>
                    <a href="{{ route('admin.tickets.index') }}" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;">
                        <i class="far fa-flag color-pink" style="font-size:26px;opacity:0.85;"></i>
                        <div>
                            <div style="font-size:11.5px;color:#555;">{{ 'Assigned To You' }}</div>
                            <div style="font-size:18px;"><span class="color-pink" style="font-weight:600;">{{ $supportSummary['assigned'] }}</span> <span style="font-size:13px;color:#888;">{{ 'Tickets' }}</span></div>
                        </div>
                    </a>
                </div>
                <div style="padding:8px 18px;">
                    @forelse($supportSummary['recent'] as $tk)
                        <div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px;padding:6px 0;border-bottom:1px solid #f5f5f5;font-size:12px;">
                            <a href="{{ route('admin.tickets.show', $tk['id']) }}" style="color:#333;text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                #{{ $tk['tid'] }} - {{ $tk['title'] }}
                            </a>
                            <span class="color-blue" style="font-size:11px;white-space:nowrap;">{{ $tk['ago'] }}</span>
                        </div>
                    @empty
                        <div style="padding:10px 0;text-align:center;color:#999;font-size:12px;">{{ 'No tickets awaiting reply.' }}</div>
                    @endforelse
                </div>
                <div style="padding:10px 18px 14px;display:flex;justify-content:center;gap:14px;font-size:11.5px;border-top:1px solid #f3f3f3;">
                    <a href="{{ route('admin.tickets.index') }}" style="color:#333;text-decoration:underline;">{{ 'View All Tickets' }}</a>
                    <a href="{{ route('admin.tickets.index') }}" style="color:#333;text-decoration:underline;">{{ 'View My Tickets' }}</a>
                    <a href="{{ route('admin.tickets.create') }}" style="color:#333;text-decoration:underline;">{{ 'Open New Ticket' }}</a>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    window._whmcsChartData = @json($chartSeries);
    window._whmcsCurrentPeriod = 'today';

    function minimiseWhmcsWidget(id, btn) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('minimised');
        var icon = btn.querySelector('i');
        if (icon) {
            icon.className = el.classList.contains('minimised') ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
        }
    }

    function toggleWhmcsWidget(id, visible) {
        var el = document.getElementById(id);
        if (!el) return;
        el.style.display = visible ? '' : 'none';
    }

    function switchWhmcsProjTab(key, btn) {
        document.querySelectorAll('#whmcsProjTabs button').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        document.querySelectorAll('.whmcs-proj-pane').forEach(function (p) { p.style.display = 'none'; });
        var target = document.getElementById('proj-pane-' + key);
        if (target) target.style.display = '';
    }

    function switchWhmcsPeriod(period, btn) {
        window._whmcsCurrentPeriod = period;
        document.querySelectorAll('#whmcsPeriodBtns button').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        drawWhmcsOverviewChart(period);
    }

    function drawWhmcsOverviewChart(period) {
        var canvas = document.getElementById('whmcsOverviewCanvas');
        if (!canvas || !window._whmcsChartData) return;
        var set = window._whmcsChartData[period] || window._whmcsChartData.today;
        var dpr = window.devicePixelRatio || 1;
        var rect = canvas.parentElement.getBoundingClientRect();
        var w = Math.max(320, rect.width);
        var h = 245;
        canvas.width = w * dpr;
        canvas.height = h * dpr;
        canvas.style.width = w + 'px';
        canvas.style.height = h + 'px';

        var ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, w, h);

        var padL = 44, padR = 46, padT = 14, padB = 30;
        var plotW = w - padL - padR;
        var plotH = h - padT - padB;

        var labels = set.labels || [];
        var sNew = set.newOrders || [];
        var sAct = set.activeOrders || [];
        var sInc = set.income || [];

        var maxOrders = Math.max(1, Math.max.apply(null, sNew.concat(sAct, [1])));
        var maxIncome = Math.max(1, Math.max.apply(null, sInc.concat([1])));

        // Horizontal & vertical gridlines (exact WHMCS Chart.js look)
        var steps = 10;
        ctx.font = '10.5px "Open Sans", "Segoe UI", Arial, sans-serif';
        ctx.lineWidth = 1;
        for (var i = 0; i <= steps; i++) {
            var y = padT + (plotH / steps) * i;
            ctx.strokeStyle = '#e8e8e8';
            ctx.beginPath();
            ctx.moveTo(padL, y);
            ctx.lineTo(padL + plotW, y);
            ctx.stroke();

            var leftVal = ((steps - i) / steps * maxOrders).toFixed(maxOrders <= 2 ? 1 : 0);
            var rightVal = ((steps - i) / steps * maxIncome).toFixed(maxIncome <= 2 ? 1 : 0);

            ctx.fillStyle = '#666';
            ctx.textAlign = 'right';
            ctx.fillText(leftVal, padL - 6, y + 3.5);

            ctx.textAlign = 'left';
            ctx.fillText(rightVal, padL + plotW + 6, y + 3.5);
        }

        var n = Math.max(1, labels.length);
        for (var j = 0; j < n; j++) {
            var x = padL + (n > 1 ? (j / (n - 1)) * plotW : plotW / 2);
            ctx.strokeStyle = '#f0f0f0';
            ctx.beginPath();
            ctx.moveTo(x, padT);
            ctx.lineTo(x, padT + plotH);
            ctx.stroke();

            ctx.fillStyle = '#555';
            ctx.textAlign = 'center';
            ctx.fillText(labels[j], x, padT + plotH + 16);
        }

        // Axis titles
        ctx.save();
        ctx.fillStyle = '#666';
        ctx.translate(12, padT + plotH / 2);
        ctx.rotate(-Math.PI / 2);
        ctx.textAlign = 'center';
        ctx.fillText('Orders', 0, 0);
        ctx.restore();

        ctx.save();
        ctx.fillStyle = '#666';
        ctx.translate(w - 10, padT + plotH / 2);
        ctx.rotate(Math.PI / 2);
        ctx.textAlign = 'center';
        ctx.fillText('Income', 0, 0);
        ctx.restore();

        function plotSeries(arr, maxVal, stroke, fill) {
            if (!arr.length) return;
            ctx.beginPath();
            for (var k = 0; k < arr.length; k++) {
                var px = padL + (arr.length > 1 ? (k / (arr.length - 1)) * plotW : plotW / 2);
                var py = padT + plotH - (Math.min(maxVal, arr[k]) / maxVal) * plotH;
                if (k === 0) ctx.moveTo(px, py);
                else ctx.lineTo(px, py);
            }
            ctx.strokeStyle = stroke;
            ctx.lineWidth = 2;
            ctx.stroke();

            ctx.lineTo(padL + plotW, padT + plotH);
            ctx.lineTo(padL, padT + plotH);
            ctx.closePath();
            ctx.fillStyle = fill;
            ctx.fill();

            for (var m = 0; m < arr.length; m++) {
                var dx = padL + (arr.length > 1 ? (m / (arr.length - 1)) * plotW : plotW / 2);
                var dy = padT + plotH - (Math.min(maxVal, arr[m]) / maxVal) * plotH;
                ctx.beginPath();
                ctx.arc(dx, dy, 2.8, 0, Math.PI * 2);
                ctx.fillStyle = stroke;
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 1;
                ctx.stroke();
            }
        }

        plotSeries(sNew, maxOrders, 'rgba(180,180,180,0.9)', 'rgba(200,200,200,0.22)');
        plotSeries(sAct, maxOrders, 'rgba(51,92,249,0.95)', 'rgba(51,92,249,0.18)');
        plotSeries(sInc, maxIncome, 'rgba(93,197,96,0.95)', 'rgba(93,197,96,0.22)');
    }

    document.addEventListener('DOMContentLoaded', function () {
        drawWhmcsOverviewChart('today');
        window.addEventListener('resize', function () {
            drawWhmcsOverviewChart(window._whmcsCurrentPeriod || 'today');
        });
        document.addEventListener('click', function (e) {
            var wrap = document.querySelector('.whmcs-gear-wrap');
            var menu = document.getElementById('whmcsGearMenu');
            if (wrap && menu && !wrap.contains(e.target)) {
                menu.classList.remove('open');
            }
        });
    });
</script>
@endsection

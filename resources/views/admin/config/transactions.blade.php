@extends('admin.layouts.app')
@section('title', __('admin.transactions.title'))
@section('content')

@php
    $currencyCode = $defaultCurrencyCode ?? 'BDT';
    $canManage = auth('admin')->user()?->hasPermission('manage_invoices');
    $openTab = $errors->any() ? 'add' : (($hasActiveFilters ?? false) ? 'search' : '');

    $contrastTextFor = function (?string $hex): string {
        $hex = ltrim(trim((string) $hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#111111';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $lum = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $lum < 110 ? '#ffffff' : '#1f2937';
    };

    // Build smooth monotone cubic SVG path for 30-day Net Revenue chart
    $points = $chartPoints ?? [];
    $rawMax = 0.0;
    foreach ($points as $pt) {
        if ((float) $pt['value'] > $rawMax) {
            $rawMax = (float) $pt['value'];
        }
    }
    $stepBase = $rawMax <= 300 ? 100 : ($rawMax <= 3000 ? 1000 : (int) pow(10, floor(log10($rawMax))));
    $yMax = max($stepBase * 3, (int) (ceil(max(1, $rawMax) / $stepBase) * $stepBase));
    $yTicks = [0, (int) ($yMax / 3), (int) (($yMax * 2) / 3), $yMax];

    $svgW = 860;
    $svgH = 225;
    $padL = 62;
    $padR = 16;
    $padT = 14;
    $padB = 56;
    $plotW = $svgW - $padL - $padR;
    $plotH = $svgH - $padT - $padB;
    $countPts = max(1, count($points));

    $coords = [];
    foreach ($points as $idx => $pt) {
        $x = $countPts > 1 ? ($padL + ($idx / ($countPts - 1)) * $plotW) : ($padL + $plotW / 2);
        $y = $padT + $plotH - (min($yMax, max(0.0, (float) $pt['value'])) / $yMax) * $plotH;
        $coords[] = ['x' => round($x, 2), 'y' => round($y, 2), 'date' => $pt['date'], 'value' => (float) $pt['value']];
    }

    $linePath = '';
    $areaPath = '';
    if (count($coords) > 0) {
        $linePath = 'M '.$coords[0]['x'].' '.$coords[0]['y'];
        for ($i = 0; $i < count($coords) - 1; $i++) {
            $p0 = $coords[$i];
            $p1 = $coords[$i + 1];
            $dx = ($p1['x'] - $p0['x']) * 0.42;
            $c1x = round($p0['x'] + $dx, 2);
            $c1y = $p0['y'];
            $c2x = round($p1['x'] - $dx, 2);
            $c2y = $p1['y'];
            // Clamped spline overshoot guard near zero baseline
            if (abs($p0['value']) < 0.001 && abs($p1['value']) < 0.001) {
                $c1y = $p0['y'];
                $c2y = $p1['y'];
            }
            $linePath .= " C {$c1x} {$c1y}, {$c2x} {$c2y}, {$p1['x']} {$p1['y']}";
        }
        $baseY = round($padT + $plotH, 2);
        $areaPath = $linePath.' L '.$coords[count($coords) - 1]['x'].' '.$baseY.' L '.$coords[0]['x'].' '.$baseY.' Z';
    }
@endphp

<h1 style="margin:0 0 12px 0;">{{ __('admin.transactions.title') }}</h1>

{{-- ── WHMCS 2-Tab Collapsible Header (Search/Filter | Add Transaction) ── --}}
<ul class="admin-tabs" role="tablist">
    <li class="{{ $openTab === 'search' ? 'active' : '' }}" id="tx-tab-li-search">
        <a href="#tx-search"
           onclick="whmcsToggleTxTab('search'); return false;">
            {{ __('admin.invoices.search_filter') }}
        </a>
    </li>
    @if($canManage)
    <li class="{{ $openTab === 'add' ? 'active' : '' }}" id="tx-tab-li-add">
        <a href="#tx-add"
           onclick="whmcsToggleTxTab('add'); return false;">
            {{ __('admin.transactions.add_transaction') }}
        </a>
    </li>
    @endif
</ul>

<div class="tab-content admin-tabs" id="tx-tab-content" style="display:{{ $openTab !== '' ? 'block' : 'none' }};margin-bottom:12px;">
    {{-- Tab 1: Search/Filter --}}
    <div class="tab-pane {{ $openTab === 'search' ? 'active' : '' }}" id="tx-pane-search" style="display:{{ $openTab === 'search' ? 'block' : 'none' }};">
        <form method="GET" action="{{ route('admin.config.transactions') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('admin.clients.name') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="client" value="{{ request('client') }}" class="form-control input-250">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('admin.transactions.within') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="within" class="form-control select-inline">
                            <option value="30" @selected(($within ?? '30') === '30')>{{ __('admin.transactions.past_30_days') }}</option>
                            <option value="7" @selected(($within ?? '30') === '7')>{{ __('admin.transactions.past_7_days') }}</option>
                            <option value="month" @selected(($within ?? '30') === 'month')>{{ __('admin.transactions.this_month') }}</option>
                            <option value="365" @selected(($within ?? '30') === '365')>{{ __('admin.transactions.past_year') }}</option>
                            <option value="all" @selected(($within ?? '30') === 'all')>{{ __('admin.transactions.all_time') }}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.payment_method') }}</td>
                    <td class="fieldarea">
                        <select name="gateway" class="form-control select-inline">
                            <option value="">{{ __('admin.transactions.any_gateway') }}</option>
                            @foreach($gateways as $gwKey => $gwLabel)
                                <option value="{{ $gwKey }}" @selected(request('gateway') === $gwKey)>{{ $gwLabel }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="fieldlabel">{{ __('common.table.description') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="description" value="{{ request('description') }}" class="form-control input-250">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.transactions.transaction_id') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="transaction_id" value="{{ request('transaction_id') }}" class="form-control input-250">
                    </td>
                    <td class="fieldlabel">{{ __('common.table.amount') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="amount" value="{{ request('amount') }}" class="form-control input-150">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.transactions.date_from') }}</td>
                    <td class="fieldarea">
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control input-150">
                    </td>
                    <td class="fieldlabel">{{ __('admin.transactions.date_to') }}</td>
                    <td class="fieldarea">
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control input-150">
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.search') }}</button>
                <a href="{{ route('admin.config.transactions') }}" class="btn btn-default btn-sm">{{ __('common.actions.reset') }}</a>
            </div>
        </form>
    </div>

    {{-- Tab 2: Add Transaction --}}
    @if($canManage)
    <div class="tab-pane {{ $openTab === 'add' ? 'active' : '' }}" id="tx-pane-add" style="display:{{ $openTab === 'add' ? 'block' : 'none' }};">
        <form method="POST" action="{{ route('admin.config.transactions.store') }}">
            @csrf
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.table.client') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="client_id" class="form-control input-250">
                            <option value="">{{ __('admin.transactions.choose_client') }}</option>
                            @foreach($clients as $cli)
                                <option value="{{ $cli->id }}" @selected((string) old('client_id') === (string) $cli->id)>
                                    {{ $cli->full_name }} ({{ $cli->email }})
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('common.table.payment_method') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="gateway" class="form-control select-inline">
                            <option value="">{{ __('admin.transactions.any_gateway') }}</option>
                            @foreach($gateways as $gwKey => $gwLabel)
                                <option value="{{ $gwKey }}" @selected(old('gateway') === $gwKey)>{{ $gwLabel }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.date') }}</td>
                    <td class="fieldarea">
                        <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="form-control input-150">
                    </td>
                    <td class="fieldlabel">{{ __('common.table.description') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="description" value="{{ old('description') }}" required class="form-control input-250">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.transactions.transaction_id') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="transaction_id" value="{{ old('transaction_id') }}" class="form-control input-250">
                    </td>
                    <td class="fieldlabel">{{ __('admin.transactions.invoice_id') }}</td>
                    <td class="fieldarea">
                        <input type="number" name="invoice_id" value="{{ old('invoice_id') }}" min="1" class="form-control input-150">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.transactions.amount_in') }}</td>
                    <td class="fieldarea">
                        <input type="number" step="0.01" min="0" name="amount_in" value="{{ old('amount_in', '0.00') }}" class="form-control input-150">
                    </td>
                    <td class="fieldlabel">{{ __('admin.transactions.fees') }}</td>
                    <td class="fieldarea">
                        <input type="number" step="0.01" min="0" name="fees" value="{{ old('fees', '0.00') }}" class="form-control input-150">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.transactions.amount_out') }}</td>
                    <td class="fieldarea" colspan="3">
                        <input type="number" step="0.01" min="0" name="amount_out" value="{{ old('amount_out', '0.00') }}" class="form-control input-150">
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.transactions.add_transaction') }}</button>
            </div>
        </form>
    </div>
    @endif
</div>

{{-- ── WHMCS 30-Day Revenue Graph + Right 3 KPI Cards ── --}}
<div class="whmcs-tx-overview">
    <div class="whmcs-tx-chart-box">
        <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" width="100%" height="215" preserveAspectRatio="none" role="img" aria-label="{{ __('admin.transactions.net_revenue_axis', ['currency' => $currencyCode]) }}" style="display:block;overflow:visible;">
            <rect x="{{ $padL }}" y="{{ $padT }}" width="{{ $plotW }}" height="{{ $plotH }}" fill="#fafafa"/>
            @foreach($yTicks as $tick)
                @php
                    $ty = round($padT + $plotH - (($tick / $yMax) * $plotH), 2);
                @endphp
                <line x1="{{ $padL }}" y1="{{ $ty }}" x2="{{ $padL + $plotW }}" y2="{{ $ty }}" stroke="#e5e5e5" stroke-width="1"/>
                <text x="{{ $padL - 8 }}" y="{{ $ty + 3.5 }}" text-anchor="end" font-size="10.5" fill="#666">{{ $tick }}</text>
            @endforeach

            @foreach($coords as $idx => $pt)
                @if($idx % 2 === 0 || $idx === count($coords) - 1)
                    <line x1="{{ $pt['x'] }}" y1="{{ $padT }}" x2="{{ $pt['x'] }}" y2="{{ $padT + $plotH }}" stroke="#eeeeee" stroke-width="1"/>
                    <text x="{{ $pt['x'] }}" y="{{ $padT + $plotH + 12 }}" text-anchor="end" font-size="10" fill="#555" transform="rotate(-40 {{ $pt['x'] }} {{ $padT + $plotH + 12 }})">{{ $pt['date'] }}</text>
                @endif
            @endforeach

            <text x="15" y="{{ $padT + ($plotH / 2) }}" text-anchor="middle" font-size="11.5" fill="#444" transform="rotate(-90 15 {{ $padT + ($plotH / 2) }})">{{ __('admin.transactions.net_revenue_axis', ['currency' => $currencyCode]) }}</text>

            @if($areaPath !== '')
                <path d="{{ $areaPath }}" fill="rgba(92, 184, 92, 0.30)"/>
                <path d="{{ $linePath }}" fill="none" stroke="#5cb85c" stroke-width="2"/>
            @endif

            @foreach($coords as $pt)
                <circle cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="2.8" fill="#ffffff" stroke="#5cb85c" stroke-width="1.5">
                    <title>{{ $pt['date'] }}: {{ \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney($pt['value'], $currencyCode, true) }}</title>
                </circle>
            @endforeach
        </svg>
    </div>

    <div class="whmcs-tx-kpis">
        {{-- Total Income --}}
        <div class="whmcs-tx-kpi-card">
            <div class="whmcs-tx-kpi-icon"><i class="fas fa-database"></i></div>
            <div>
                <div class="whmcs-tx-kpi-label">{{ __('admin.transactions.total_income') }}</div>
                <div class="whmcs-tx-kpi-value">{{ \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney((float) $kpiStats['income']['value'], $currencyCode, true) }}</div>
                <div class="whmcs-tx-kpi-delta {{ $kpiStats['income']['up'] ? 'up' : 'down' }}">
                    {{ $kpiStats['income']['up'] ? '↑' : '↓' }} {{ __('admin.transactions.from_last_30', ['pct' => $kpiStats['income']['pct']]) }}
                </div>
            </div>
        </div>

        {{-- Total Fees --}}
        <div class="whmcs-tx-kpi-card">
            <div class="whmcs-tx-kpi-icon"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <div class="whmcs-tx-kpi-label">{{ __('admin.transactions.total_fees') }}</div>
                <div class="whmcs-tx-kpi-value">{{ \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney((float) $kpiStats['fees']['value'], $currencyCode, true) }}</div>
                <div class="whmcs-tx-kpi-delta {{ $kpiStats['fees']['up'] ? 'up' : 'down' }}">
                    {{ $kpiStats['fees']['up'] ? '↑' : '↓' }} {{ __('admin.transactions.from_last_30', ['pct' => $kpiStats['fees']['pct']]) }}
                </div>
            </div>
        </div>

        {{-- Total Expenditure --}}
        <div class="whmcs-tx-kpi-card">
            <div class="whmcs-tx-kpi-icon"><i class="fas fa-calculator"></i></div>
            <div>
                <div class="whmcs-tx-kpi-label">{{ __('admin.transactions.total_expenditure') }}</div>
                <div class="whmcs-tx-kpi-value">{{ \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney((float) $kpiStats['expenditure']['value'], $currencyCode, true) }}</div>
                <div class="whmcs-tx-kpi-delta {{ $kpiStats['expenditure']['up'] ? 'up' : 'down' }}">
                    {{ $kpiStats['expenditure']['up'] ? '↑' : '↓' }} {{ __('admin.transactions.from_last_30', ['pct' => $kpiStats['expenditure']['pct']]) }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Records Count & Jump to Page Bar ── --}}
<div class="whmcs-record-bar" style="margin:6px 0;">
    <div style="font-size:12px;color:#333;">
        {{ number_format($transactions->total()) }} {{ __('admin.invoices.records_found') }},
        {{ __('admin.transactions.page_of', ['current' => $transactions->currentPage(), 'last' => max(1, $transactions->lastPage())]) }}
    </div>
    <div style="display:flex;align-items:center;gap:4px;font-size:12px;color:#333;">
        <span>{{ __('admin.invoices.jump_to_page') }}</span>
        <select id="tx-jump-select"
                onchange="if(this.value) window.location.href=this.value;"
                aria-label="{{ __('admin.invoices.jump_to_page') }}"
                style="padding:2px 6px;border:1px solid #ccc;border-radius:3px;font-size:12px;background:#fff;">
            @for($pg = 1; $pg <= max(1, $transactions->lastPage()); $pg++)
                <option value="{{ $transactions->url($pg) }}" @selected($pg === $transactions->currentPage())>{{ $pg }}</option>
            @endfor
        </select>
        <button type="button"
                onclick="var s=document.getElementById('tx-jump-select');if(s&&s.value)window.location.href=s.value;"
                class="btn btn-default btn-xs"
                style="padding:2px 7px !important;font-size:11.5px !important;">{{ __('admin.transactions.go') }}</button>
    </div>
</div>

{{-- ── 8-Column WHMCS Transactions Table ── --}}
<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.clients.name') }}</th>
                <th width="95">{{ __('common.table.date') }}</th>
                <th width="175">{{ __('common.table.payment_method') }}</th>
                <th>{{ __('common.table.description') }}</th>
                <th width="85">{{ __('admin.transactions.amount_in') }}</th>
                <th width="65">{{ __('admin.transactions.fees') }}</th>
                <th width="90">{{ __('admin.transactions.amount_out') }}</th>
                <th width="54"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $tx)
                @php
                    $groupColor = trim((string) ($tx->client?->group?->color ?? ''));
                    $hasGroupColor = $groupColor !== '' && ! in_array(strtolower($groupColor), ['#ffffff', '#fff', 'white'], true);
                    $groupTextColor = $hasGroupColor ? $contrastTextFor($groupColor) : '';

                    $rawInvNum = (string) ($tx->invoice?->invoice_num ?? '');
                    $displayInvNum = str_starts_with($rawInvNum, 'INV-WHMCS-')
                        ? substr($rawInvNum, strlen('INV-WHMCS-'))
                        : ($rawInvNum !== '' ? $rawInvNum : (string) ($tx->invoice_id ?? ''));
                    $rawDesc = trim((string) ($tx->description ?? ''));
                    $descText = $rawDesc !== '' ? $rawDesc : __('admin.transactions.invoice_payment');
                    $txDateFormatted = ($tx->date ?? $tx->created_at)?->format(date_fmt()) ?? '-';
                @endphp
                <tr>
                    <td>
                        @if($tx->client)
                            @if($hasGroupColor)
                                <a href="{{ route('admin.clients.show', $tx->client) }}"
                                   class="whmcs-client-group-tag"
                                   style="background-color:{{ $groupColor }};color:{{ $groupTextColor }};">{{ $tx->client->full_name }}</a>
                            @else
                                <a href="{{ route('admin.clients.show', $tx->client) }}">{{ $tx->client->full_name }}</a>
                            @endif
                        @else
                            {{ __('admin.transactions.deleted_client') }}
                        @endif
                    </td>
                    <td style="text-align:center;white-space:nowrap;">{{ $txDateFormatted }}</td>
                    <td style="text-align:center;">{{ $tx->gateway ? payment_method_label((string) $tx->gateway) : '-' }}</td>
                    <td style="line-height:1.4;">
                        <div>
                            {{ $descText }}
                            @if($tx->invoice_id && ! str_contains($descText, '#'.$displayInvNum))
                                (<a href="{{ route('admin.invoices.show', $tx->invoice_id) }}">#{{ $displayInvNum }}</a>)
                            @elseif($tx->invoice_id && ! str_contains($descText, '#'))
                                (<a href="{{ route('admin.invoices.show', $tx->invoice_id) }}">#{{ $displayInvNum }}</a>)
                            @endif
                        </div>
                        @if(! empty($tx->transaction_id))
                            <div style="font-size:11.5px;color:#444;">{{ __('admin.transactions.trans_id_prefix') }} {{ $tx->transaction_id }}</div>
                        @endif
                    </td>
                    <td style="text-align:center;white-space:nowrap;">{{ \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney((float) $tx->amount_in, $currencyCode) }}</td>
                    <td style="text-align:center;white-space:nowrap;">{{ \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney((float) $tx->fees, $currencyCode) }}</td>
                    <td style="text-align:center;white-space:nowrap;">{{ \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney((float) $tx->amount_out, $currencyCode) }}</td>
                    <td style="text-align:center;white-space:nowrap;">
                        @if($canManage)
                            <button type="button"
                                    class="whmcs-icon-btn edit"
                                    title="{{ __('common.actions.edit') }}"
                                    data-id="{{ $tx->id }}"
                                    data-update-url="{{ route('admin.config.transactions.update', $tx) }}"
                                    data-client-id="{{ $tx->client_id ?? '' }}"
                                    data-date="{{ $tx->date?->toDateString() ?? '' }}"
                                    data-gateway="{{ $tx->gateway ?? '' }}"
                                    data-description="{{ $tx->description ?? '' }}"
                                    data-transaction-id="{{ $tx->transaction_id ?? '' }}"
                                    data-invoice-id="{{ $tx->invoice_id ?? '' }}"
                                    data-amount-in="{{ number_format((float) $tx->amount_in, 2, '.', '') }}"
                                    data-fees="{{ number_format((float) $tx->fees, 2, '.', '') }}"
                                    data-amount-out="{{ number_format((float) $tx->amount_out, 2, '.', '') }}"
                                    onclick="whmcsOpenEditTx(this)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button"
                                    class="whmcs-icon-btn del"
                                    title="{{ __('common.actions.delete') }}"
                                    data-delete-url="{{ route('admin.config.transactions.destroy', $tx) }}"
                                    onclick="whmcsDeleteTx(this)">
                                <i class="fas fa-minus-circle"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:24px;color:#888;">{{ __('admin.transactions.no_transactions') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── WHMCS Split Pager (« Previous Page | Pages | Next Page ») ── --}}
<div class="whmcs-tx-split-pager">
    <div>
        @if($transactions->onFirstPage())
            <span class="disabled">{{ __('admin.pagination.previous_page') }}</span>
        @else
            <a href="{{ $transactions->previousPageUrl() }}">{{ __('admin.pagination.previous_page') }}</a>
        @endif
    </div>
    <div>
        @include('admin.partials.whmcs-pagination', ['paginator' => $transactions])
    </div>
    <div>
        @if($transactions->hasMorePages())
            <a href="{{ $transactions->nextPageUrl() }}">{{ __('admin.pagination.next_page') }}</a>
        @else
            <span class="disabled">{{ __('admin.pagination.next_page') }}</span>
        @endif
    </div>
</div>

{{-- Balance changed by hand (#307): who, when, how much and why. Not payments, so not in the list above. --}}
<div style="margin-top:24px;">
    <div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:6px;">
        <h2 style="margin:0;font-size:15px;">{{ __('admin.transactions.adjustments_title') }}</h2>
        <span style="font-size:12px;color:#777;">{{ __('admin.transactions.adjustments_hint') }}</span>
    </div>
    <div class="tablebg">
        <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
            <thead>
                <tr>
                    <th width="100">{{ __('common.table.date') }}</th>
                    <th>{{ __('common.table.client') }}</th>
                    <th width="130">{{ __('common.table.type') }}</th>
                    <th width="120">{{ __('common.table.amount') }}</th>
                    <th>{{ __('admin.transactions.reason') }}</th>
                    <th width="150">{{ __('admin.transactions.by') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($adjustments as $adj)
                    <tr>
                        <td style="text-align:center;white-space:nowrap;">{{ ($adj->date ?? $adj->created_at)?->format(date_fmt()) }}</td>
                        <td>@if($adj->client)<a href="{{ route('admin.clients.credit', $adj->client) }}">{{ $adj->client->full_name }}</a>@else {{ __('admin.transactions.deleted_client') }} @endif</td>
                        <td style="text-align:center;">{{ (float) $adj->amount >= 0 ? __('admin.transactions.credit_added') : __('admin.transactions.credit_deducted') }}</td>
                        <td style="text-align:center;font-weight:600;color:{{ (float) $adj->amount >= 0 ? '#5cb85c' : '#d9534f' }};">{{ ((float) $adj->amount >= 0 ? '+' : '-').money_fmt(abs((float) $adj->amount)) }}</td>
                        <td>{{ $adj->description }}</td>
                        <td style="text-align:center;">{{ $adj->admin?->full_name ?: ($adj->admin?->username ?? '#'.$adj->admin_id) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:18px;color:#888;">{{ __('admin.transactions.no_adjustments') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.whmcs-pagination', ['paginator' => $adjustments])
</div>

{{-- ── Edit Transaction Modal & Delete Form ── --}}
@if($canManage)
<div class="whmcs-notes-backdrop" id="modalEditTx" onclick="if(event.target===this) whmcsCloseEditTx();">
    <form method="POST" id="edit-tx-form" class="whmcs-notes-dialog" style="max-width:640px;">
        @csrf
        @method('PUT')
        <div class="whmcs-notes-header">
            <h4>{{ __('admin.transactions.edit_transaction') }}</h4>
            <button type="button" class="whmcs-notes-close" onclick="whmcsCloseEditTx()" aria-label="{{ __('common.actions.close') }}">&times;</button>
        </div>
        <div class="whmcs-notes-body">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="28%" class="fieldlabel">{{ __('common.table.client') }}</td>
                    <td class="fieldarea">
                        <select name="client_id" id="edit-tx-client" class="form-control">
                            <option value="">{{ __('admin.transactions.choose_client') }}</option>
                            @foreach($clients as $cli)
                                <option value="{{ $cli->id }}">{{ $cli->full_name }} ({{ $cli->email }})</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.date') }}</td>
                    <td class="fieldarea"><input type="date" name="date" id="edit-tx-date" required class="form-control input-150"></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.payment_method') }}</td>
                    <td class="fieldarea">
                        <select name="gateway" id="edit-tx-gateway" class="form-control">
                            <option value="">{{ __('admin.transactions.any_gateway') }}</option>
                            @foreach($gateways as $gwKey => $gwLabel)
                                <option value="{{ $gwKey }}">{{ $gwLabel }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.description') }}</td>
                    <td class="fieldarea"><input type="text" name="description" id="edit-tx-description" required class="form-control"></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.transactions.transaction_id') }}</td>
                    <td class="fieldarea"><input type="text" name="transaction_id" id="edit-tx-trans-id" class="form-control"></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.transactions.invoice_id') }}</td>
                    <td class="fieldarea"><input type="number" name="invoice_id" id="edit-tx-invoice-id" min="1" class="form-control input-150"></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.transactions.amount_in') }}</td>
                    <td class="fieldarea"><input type="number" step="0.01" min="0" name="amount_in" id="edit-tx-in" class="form-control input-150"></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.transactions.fees') }}</td>
                    <td class="fieldarea"><input type="number" step="0.01" min="0" name="fees" id="edit-tx-fees" class="form-control input-150"></td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.transactions.amount_out') }}</td>
                    <td class="fieldarea"><input type="number" step="0.01" min="0" name="amount_out" id="edit-tx-out" class="form-control input-150"></td>
                </tr>
            </table>
        </div>
        <div class="whmcs-notes-footer">
            <button type="button" class="btn btn-default" onclick="whmcsCloseEditTx()">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('common.actions.save_changes') }}</button>
        </div>
    </form>
</div>

<form id="single-delete-tx-form" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>
@endif

<script>
function whmcsToggleTxTab(name) {
    var wrap = document.getElementById('tx-tab-content');
    var searchLi = document.getElementById('tx-tab-li-search');
    var addLi = document.getElementById('tx-tab-li-add');
    var searchPane = document.getElementById('tx-pane-search');
    var addPane = document.getElementById('tx-pane-add');
    if (!wrap || !searchPane) return;

    var currentlyOpen = (name === 'search' && searchPane.style.display === 'block')
        || (name === 'add' && addPane && addPane.style.display === 'block');

    if (currentlyOpen && wrap.style.display === 'block') {
        wrap.style.display = 'none';
        searchPane.style.display = 'none';
        if (addPane) addPane.style.display = 'none';
        if (searchLi) searchLi.classList.remove('active');
        if (addLi) addLi.classList.remove('active');
        return;
    }

    wrap.style.display = 'block';
    if (name === 'add' && addPane) {
        searchPane.style.display = 'none';
        addPane.style.display = 'block';
        if (searchLi) searchLi.classList.remove('active');
        if (addLi) addLi.classList.add('active');
    } else {
        searchPane.style.display = 'block';
        if (addPane) addPane.style.display = 'none';
        if (searchLi) searchLi.classList.add('active');
        if (addLi) addLi.classList.remove('active');
    }
}

function whmcsOpenEditTx(btn) {
    var form = document.getElementById('edit-tx-form');
    if (!form) return;
    form.setAttribute('action', btn.getAttribute('data-update-url'));
    document.getElementById('edit-tx-client').value = btn.getAttribute('data-client-id') || '';
    document.getElementById('edit-tx-date').value = btn.getAttribute('data-date') || '';
    document.getElementById('edit-tx-gateway').value = btn.getAttribute('data-gateway') || '';
    document.getElementById('edit-tx-description').value = btn.getAttribute('data-description') || '';
    document.getElementById('edit-tx-trans-id').value = btn.getAttribute('data-transaction-id') || '';
    document.getElementById('edit-tx-invoice-id').value = btn.getAttribute('data-invoice-id') || '';
    document.getElementById('edit-tx-in').value = btn.getAttribute('data-amount-in') || '0.00';
    document.getElementById('edit-tx-fees').value = btn.getAttribute('data-fees') || '0.00';
    document.getElementById('edit-tx-out').value = btn.getAttribute('data-amount-out') || '0.00';
    document.getElementById('modalEditTx').classList.add('open');
}

function whmcsCloseEditTx() {
    var m = document.getElementById('modalEditTx');
    if (m) m.classList.remove('open');
}

function whmcsDeleteTx(btn) {
    var url = btn.getAttribute('data-delete-url');
    var form = document.getElementById('single-delete-tx-form');
    if (window.pnDialog && typeof window.pnDialog.confirm === 'function') {
        window.pnDialog.confirm(@js(__('admin.transactions.confirm_delete')), { danger: true }).then(function (yes) {
            if (yes) {
                form.setAttribute('action', url);
                form.submit();
            }
        });
    }
}
</script>

@endsection

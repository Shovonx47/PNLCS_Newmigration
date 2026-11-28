@extends('admin.layouts.app')
@section('title', 'Multi-Provider Auto Recharge')

@section('content')
<style>
    .iptsp-wrap h1.iptsp-page-title {
        font-size: 26px;
        font-weight: 400;
        color: #333;
        margin: 0 0 18px 0;
    }
    .providers-container {
        background: #fff;
        border: 1px solid #e6e8ec;
        border-radius: 6px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        padding: 20px;
    }
    .providers-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #eef0f3;
    }
    .providers-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 400;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-iptsp-add {
        background: #3498db;
        color: #fff !important;
        border: none;
        padding: 8px 18px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-iptsp-add:hover { background: #2980b9; }
    .btn-iptsp-pending {
        background: #f39c12;
        color: #fff !important;
        border: none;
        padding: 8px 18px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-left: 6px;
    }
    .btn-iptsp-pending:hover { background: #e67e22; }
    .table-providers {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .table-providers th {
        text-align: left;
        padding: 11px 10px;
        background: #f8f9fa;
        color: #34495e;
        font-weight: 600;
        border-bottom: 2px solid #e9ecef;
        font-size: 13px;
    }
    .table-providers td {
        padding: 11px 10px;
        border-bottom: 1px solid #f0f2f5;
        vertical-align: middle;
    }
    .table-providers tr:hover td { background: #fafbfc; }
    .provider-logo-sm {
        height: 28px;
        width: 28px;
        object-fit: contain;
        border-radius: 4px;
        background: #f8f9fa;
        padding: 2px;
        border: 1px solid #e5e7eb;
    }
    .provider-logo-fallback {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 28px;
        width: 28px;
        border-radius: 4px;
        background: #f3f6f9;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 13px;
    }
    .iptsp-type-pill {
        display: inline-block;
        padding: 2px 7px;
        font-size: 10px;
        font-weight: 700;
        border-radius: 3px;
        color: #fff;
        background: #5bc0de;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .iptsp-status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .iptsp-status-active { background: #e8f5e9; color: #2e7d32; }
    .iptsp-status-inactive { background: #fbe9e7; color: #c62828; }
    .log-stat-badge {
        display: block;
        padding: 3px 9px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        margin-bottom: 3px;
        white-space: nowrap;
    }
    .log-stat-badge:last-child { margin-bottom: 0; }
    .stat-pending-badge { background: #fff3cd; color: #856404; }
    .stat-success-badge { background: #d4edda; color: #155724; }
    .stat-failed-badge { background: #f8d7da; color: #721c24; }
    .iptsp-action-btns { white-space: nowrap; }
    .iptsp-action-btns a {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-right: 5px;
        padding: 5px 11px;
        border-radius: 4px;
        font-size: 12px;
        color: #fff !important;
        text-decoration: none !important;
    }
    .iptsp-action-btns a:hover { opacity: 0.88; }
    .btn-iptsp-edit { background: #3498db; }
    .btn-iptsp-history { background: #2ecc71; padding: 6px 10px !important; }
    .iptsp-empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #95a5a6;
    }
</style>

<div class="iptsp-wrap">
    <h1 class="iptsp-page-title">Multi-Provider Auto Recharge</h1>

    <div class="providers-container">
        <div class="providers-header">
            <h2><i class="fas fa-cubes" style="color:#3498db;"></i> Recharge Providers</h2>
            <div>
                <a href="{{ route('admin.extensions.iptsp-recharge.index', ['action' => 'add']) }}" class="btn-iptsp-add">
                    <i class="fas fa-plus"></i> Add New
                </a>
                <a href="{{ route('admin.extensions.iptsp-recharge.index', ['action' => 'pending']) }}" class="btn-iptsp-pending">
                    <i class="far fa-clock"></i> Manage Pending
                </a>
            </div>
        </div>

        @if($providers->isEmpty())
            <div class="iptsp-empty-state">
                <i class="fas fa-inbox" style="font-size:38px;margin-bottom:10px;display:block;"></i>
                <p style="margin:0;">No providers configured yet. Click "Add New" to get started.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="table-providers">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Logo</th>
                            <th>Name</th>
                            <th>Base URL</th>
                            <th>Type</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th style="min-width:190px;">📊 Stats (Count &amp; Total BDT)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($providers as $provider)
                            @php
                                $st = $statsByProvider[$provider->id] ?? [];
                                $pCnt = (int) ($st['pending']['count'] ?? 0);
                                $pAmt = (float) ($st['pending']['amount'] ?? 0);
                                $sCnt = (int) ($st['success']['count'] ?? 0);
                                $sAmt = (float) ($st['success']['amount'] ?? 0);
                                $fCnt = (int) ($st['failed']['count'] ?? 0);
                                $fAmt = (float) ($st['failed']['amount'] ?? 0);

                                $isScraper = strtolower((string) $provider->integration_type) === 'scraper'
                                    || strtolower((string) $provider->api_type) === 'italk';
                                $typeLabel = $isScraper ? 'SCRAPER' : 'REST API';
                                $displayUrl = rtrim((string) $provider->base_url, '/')
                                    .(($provider->base_path && $provider->base_path !== '/') && ! $isScraper ? $provider->base_path : '');
                            @endphp
                            <tr>
                                <td>{{ $provider->id }}</td>
                                <td>
                                    @if(! empty($provider->logo_url))
                                        <img src="{{ asset($provider->logo_url) }}" alt="{{ $provider->name }}" class="provider-logo-sm">
                                    @else
                                        <span class="provider-logo-fallback" title="{{ $provider->name }}">
                                            <i class="far fa-image"></i>
                                        </span>
                                    @endif
                                </td>
                                <td><strong>{{ $provider->name }}</strong></td>
                                <td><span style="font-size:13px;color:#7f8c8d;">{{ $displayUrl }}</span></td>
                                <td><span class="iptsp-type-pill">{{ $typeLabel }}</span></td>
                                <td>{{ $provider->username }}</td>
                                <td>
                                    <span class="iptsp-status-badge {{ $provider->isActive() ? 'iptsp-status-active' : 'iptsp-status-inactive' }}">
                                        {{ ucfirst($provider->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="log-stat-badge stat-pending-badge">&#9203; Pending: {{ $pCnt }} (৳{{ number_format($pAmt, 2) }})</span>
                                    <span class="log-stat-badge stat-success-badge">&#9989; Success: {{ $sCnt }} (৳{{ number_format($sAmt, 2) }})</span>
                                    <span class="log-stat-badge stat-failed-badge">&#10060; Failed: {{ $fCnt }} (৳{{ number_format($fAmt, 2) }})</span>
                                </td>
                                <td class="iptsp-action-btns">
                                    <a href="{{ route('admin.extensions.iptsp-recharge.index', ['action' => 'edit', 'id' => $provider->id]) }}"
                                       class="btn-iptsp-edit">
                                        <i class="far fa-edit"></i> Edit
                                    </a>
                                    <a href="{{ route('admin.extensions.iptsp-recharge.index', ['action' => 'history', 'id' => $provider->id]) }}"
                                       class="btn-iptsp-history"
                                       title="History &amp; PINs">
                                        <i class="fas fa-history"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

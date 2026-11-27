@extends('admin.layouts.app')
@section('title', __('admin.quotes.title'))
@section('content')

<h1>{{ __('admin.quotes.title') }}</h1>

@php
    $quoteStages = [
        '' => __('common.form.all'),
        'valid' => 'Valid',
        'expired' => 'Expired',
        'Draft' => 'Draft',
        'Delivered' => 'Delivered',
        'On Hold' => 'On Hold',
        'Accepted' => 'Accepted',
        'Lost' => 'Lost',
        'Dead' => 'Dead',
    ];
    $currentQuoteStatus = (string) request('status', '');
@endphp

<ul class="admin-tabs" role="tablist">
    <li class="active">
        <a href="#quotes-search-tab" class="active" onclick="var el=document.getElementById('quotes-search-pane');if(el){el.style.display=el.style.display==='none'?'block':'none';}return false;">
            {{ __('Search/Filter') }}
        </a>
    </li>
    @foreach($quoteStages as $val => $label)
        @php $isStageActive = ($currentQuoteStatus === $val); @endphp
        <li class="{{ $isStageActive ? 'active' : '' }}">
            <a href="{{ route('admin.quotes.index', ['status' => $val, 'search' => request('search')]) }}" class="{{ $isStageActive ? 'active' : '' }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="tab-content admin-tabs" id="quotes-search-pane">
    <div class="tab-pane active">
        <form method="GET" action="{{ route('admin.quotes.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.table.subject') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.quotes.search_placeholder') }}" class="form-control input-300">
                    </td>
                    <td width="15%" class="fieldlabel">{{ 'Stage' }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            @foreach($quoteStages as $val => $label)
                                <option value="{{ $val }}" @selected($currentQuoteStatus === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default">{{ __('common.actions.search') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="context-btn-container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin:10px 0;">
    <div style="font-size:12px;color:#555;">
        <strong>{{ $quotes->total() }}</strong> {{ __('Records Found') }}, {{ __('Page') }} {{ $quotes->currentPage() }} {{ __('of') }} {{ max(1, $quotes->lastPage()) }}
    </div>
    <div>
        <a href="{{ route('admin.quotes.create') }}" class="btn btn-primary btn-sm">+ {{ 'Create New Quote' }}</a>
    </div>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th width="60">{{ 'Quote #' }}</th>
                <th>{{ __('common.table.subject') }}</th>
                <th>{{ 'Client Name' }}</th>
                <th>{{ 'Date Created' }}</th>
                <th>{{ __('admin.quotes.valid_until') }}</th>
                <th style="text-align:right;">{{ __('common.table.total') }}</th>
                <th>{{ 'Stage' }}</th>
                <th width="160">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quotes as $quote)
                @php
                    $badgeClass = match($quote->status) {
                        'Accepted' => 'badge-active',
                        'Sent', 'Delivered' => 'badge-open',
                        'Declined', 'Lost', 'Dead' => 'badge-cancelled',
                        default => 'badge-draft',
                    };
                @endphp
                <tr>
                    <td style="font-family:monospace;"><a href="{{ route('admin.quotes.edit', $quote) }}">{{ $quote->id }}</a></td>
                    <td><a href="{{ route('admin.quotes.edit', $quote) }}" style="font-weight:600;">{{ $quote->subject }}</a></td>
                    <td>
                        @if($quote->client)
                            <a href="{{ route('admin.clients.show', $quote->client) }}">{{ $quote->client->full_name }}</a>
                        @else
                            {{ $quote->recipientName() }} <span style="color:#888;font-size:11px;">({{ 'New Client' }})</span>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($quote->date)->format(date_fmt()) }}</td>
                    <td>{{ \Carbon\Carbon::parse($quote->valid_until)->format(date_fmt()) }}</td>
                    <td style="text-align:right;font-weight:600;">{{ $quote->formatMoney($quote->total) }}</td>
                    <td><span class="{{ $badgeClass }} whmcs-status {{ strtolower(str_replace(' ', '-', $quote->status)) }}">{{ status_label($quote->status) }}</span></td>
                    <td>
                        <div style="display:flex;gap:4px;align-items:center;">
                            <a href="{{ route('admin.quotes.edit', $quote) }}" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</a>
                            <a href="{{ route('admin.quotes.pdf.view', $quote) }}" target="_blank" class="btn btn-default btn-xs">{{ 'PDF' }}</a>
                            <form method="POST" action="{{ route('admin.quotes.destroy', $quote) }}" onsubmit="return pnConfirm(event, @js(__('admin.quotes.confirm_delete')))" style="display:inline;margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs">{{ __('admin.quotes.del') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;color:#999;padding:24px;">{{ __('admin.quotes.no_quotes') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">{{ $quotes->withQueryString()->links() }}</div>

@endsection

@extends('admin.layouts.app')
@section('title', __('admin.quotes.title') . ' #' . $quote->id)
@section('content')

@php
    $badgeClass = match($quote->status) {
        'Accepted' => 'badge-active',
        'Sent', 'Delivered' => 'badge-open',
        'Declined', 'Lost', 'Dead' => 'badge-cancelled',
        default => 'badge-draft',
    };
@endphp

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
    <h1 style="margin:0;">{{ __('admin.quotes.title_num', ['num' => $quote->id]) }}</h1>
    <div class="context-btn-container" style="margin:0;">
        <a href="{{ route('admin.quotes.edit', $quote) }}" class="btn btn-primary btn-sm">{{ __('admin.quotes.edit_quote') }}</a>
        <a href="{{ route('admin.quotes.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.quotes.back') }}</a>
    </div>
</div>

<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
    <tr>
        <td width="15%" class="fieldlabel">{{ __('common.table.subject') }}</td>
        <td width="35%" class="fieldarea"><strong>{{ $quote->subject }}</strong></td>
        <td width="15%" class="fieldlabel">{{ 'Stage' }}</td>
        <td width="35%" class="fieldarea"><span class="{{ $badgeClass }} whmcs-status {{ strtolower(str_replace(' ', '-', $quote->status)) }}">{{ status_label($quote->status) }}</span></td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.quotes.client') }}</td>
        <td class="fieldarea">
            @if($quote->client)
                <a href="{{ route('admin.clients.show', $quote->client) }}" style="font-weight:600;">{{ $quote->client->full_name }}</a>
            @else
                <strong>{{ $quote->recipientName() }}</strong>
                @if($quote->recipientEmail())
                    <span style="color:#666;">({{ $quote->recipientEmail() }})</span>
                @endif
            @endif
        </td>
        <td class="fieldlabel">{{ __('admin.quotes.date') }}</td>
        <td class="fieldarea">{{ \Carbon\Carbon::parse($quote->date)->format(date_fmt()) }}</td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.quotes.valid_until') }}</td>
        <td class="fieldarea">{{ \Carbon\Carbon::parse($quote->valid_until)->format(date_fmt()) }}</td>
        <td class="fieldlabel">{{ __('admin.quotes.items') }}</td>
        <td class="fieldarea">{{ $quote->items->count() }}</td>
    </tr>
    @if($quote->proposal)
    <tr>
        <td class="fieldlabel">{{ __('admin.quotes.proposal') }}</td>
        <td class="fieldarea" colspan="3" style="white-space:pre-wrap;">{{ $quote->proposal }}</td>
    </tr>
    @endif
    @if($quote->customer_notes)
    <tr>
        <td class="fieldlabel">{{ __('admin.quotes.customer_notes') }}</td>
        <td class="fieldarea" colspan="3" style="white-space:pre-wrap;">{{ $quote->customer_notes }}</td>
    </tr>
    @endif
    @if($quote->notes)
    <tr>
        <td class="fieldlabel">{{ __('admin.quotes.admin_notes') }}</td>
        <td class="fieldarea" colspan="3" style="white-space:pre-wrap;">{{ $quote->notes }}</td>
    </tr>
    @endif
</table>

<h2 style="margin:18px 0 8px;">{{ __('admin.quotes.line_items') }}</h2>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.description') }}</th>
                <th style="text-align:right;width:90px;">{{ __('admin.quotes.qty') }}</th>
                <th style="text-align:right;width:120px;">{{ __('admin.quotes.unit_price') }}</th>
                <th style="text-align:right;width:110px;">{{ __('common.table.discount') }}</th>
                <th style="text-align:right;width:120px;">{{ __('common.table.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quote->items as $item)
            <tr>
                <td style="white-space:pre-wrap;">{{ $item->description }}</td>
                <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                <td style="text-align:right;">{{ $quote->formatMoney($item->unit_price) }}</td>
                <td style="text-align:right;">
                    @if((float) ($item->discount_percent ?? 0) > 0)
                        {{ rtrim(rtrim(number_format((float) $item->discount_percent, 2), '0'), '.') }}%
                    @elseif((float) $item->discount > 0)
                        {{ $quote->formatMoney($item->discount) }}
                    @else
                        -
                    @endif
                </td>
                <td style="text-align:right;font-weight:600;font-family:monospace;">{{ $quote->formatMoney(max(0, ($item->quantity * $item->unit_price) - $item->discount)) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;color:#999;padding:20px;">{{ __('admin.quotes.no_line_items') }}</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="padding:6px 10px;text-align:right;font-weight:600;">{{ __('admin.quotes.subtotal') }}:</td>
                <td style="padding:6px 10px;text-align:right;font-weight:600;font-family:monospace;">{{ $quote->formatMoney($quote->subtotal) }}</td>
            </tr>
            @if($quote->tax > 0)
            <tr>
                <td colspan="4" style="padding:4px 10px;text-align:right;color:#666;">{{ __('admin.quotes.tax') }}:</td>
                <td style="padding:4px 10px;text-align:right;font-family:monospace;">{{ $quote->formatMoney($quote->tax) }}</td>
            </tr>
            @endif
            <tr style="background:#eef2f7;border-top:2px solid #cbd5e1;">
                <td colspan="4" style="padding:8px 10px;text-align:right;font-weight:700;font-size:14px;">{{ __('admin.quotes.total') }}:</td>
                <td style="padding:8px 10px;text-align:right;font-weight:700;font-size:14px;font-family:monospace;">{{ $quote->formatMoney($quote->total) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="btn-container" style="margin-top:16px;display:flex;flex-wrap:wrap;justify-content:center;gap:6px;">
    <a href="{{ route('admin.quotes.edit', $quote) }}" class="btn btn-primary">{{ __('admin.quotes.edit_quote') }}</a>

    <form method="POST" action="{{ route('admin.quotes.duplicate', $quote) }}" style="display:inline-block;margin:0;">
        @csrf
        <button type="submit" class="btn btn-default">{{ 'Duplicate' }}</button>
    </form>

    <a href="{{ route('admin.quotes.print', $quote) }}" target="_blank" class="btn btn-default">{{ 'Printable Version' }}</a>
    <a href="{{ route('admin.quotes.pdf.view', $quote) }}" target="_blank" class="btn btn-default">{{ 'View PDF' }}</a>
    <a href="{{ route('admin.quotes.pdf.download', $quote) }}" class="btn btn-default">{{ 'Download PDF' }}</a>

    <form method="POST" action="{{ route('admin.quotes.email-pdf', $quote) }}" style="display:inline-block;margin:0;">
        @csrf
        <button type="submit" class="btn btn-default">{{ 'Email as PDF' }}</button>
    </form>

    @if($quote->status === 'Draft')
    <form method="POST" action="{{ route('admin.quotes.send', $quote) }}" style="display:inline-block;margin:0;">
        @csrf
        <button type="submit" class="btn btn-default">{{ __('admin.quotes.send_to_client') }}</button>
    </form>
    @endif

    @if(in_array($quote->status, ['Sent', 'Delivered'], true))
    <form method="POST" action="{{ route('admin.quotes.accept', $quote) }}" style="display:inline-block;margin:0;">
        @csrf
        <button type="submit" class="btn btn-success">{{ __('admin.quotes.accept_quote') }}</button>
    </form>
    <form method="POST" action="{{ route('admin.quotes.decline', $quote) }}" style="display:inline-block;margin:0;">
        @csrf
        <button type="submit" class="btn btn-warning">{{ __('admin.quotes.decline_quote') }}</button>
    </form>
    @endif

    <form method="POST" action="{{ route('admin.quotes.convert', $quote) }}" style="display:inline-block;margin:0;">
        @csrf
        <button type="submit" class="btn btn-default">{{ __('admin.quotes.convert_to_invoice') }}</button>
    </form>

    <form method="POST" action="{{ route('admin.quotes.destroy', $quote) }}" onsubmit="return pnConfirm(event, @js(__('admin.quotes.confirm_delete')))" style="display:inline-block;margin:0;">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger">{{ __('admin.quotes.delete_quote') }}</button>
    </form>
</div>

@endsection

@extends('client.layouts.invoice')
@php
    $stageKey = strtolower((string) $quote->status);
    $stageClass = match ($stageKey) {
        'accepted' => 'paid',
        'on hold', 'sent' => 'unpaid',
        'lost', 'declined' => 'cancelled',
        'expired', 'dead' => 'collections',
        default => 'draft',
    };
    $actionable = $actionable ?? in_array($quote->status, ['Sent', 'Delivered', 'On Hold'], true);
@endphp
@section('title', __('client.quotes.quote_prefix', ['id' => $quote->id]))
@section('content')

<div class="row invoice-header">
    <div class="col-12 col-sm-7 justify-content-sm-between text-center text-sm-left invoice-col">
        <p><img src="{{ ! empty($customLogo) ? $customLogo : asset('assets/img/logo.png') }}" title="{{ company_name() }}" alt="{{ company_name() }}"></p>
        <h3>{{ __('client.quotes.quote_prefix', ['id' => $quote->id]) }}</h3>
        @if($quote->subject)
            <p class="mb-0">{{ $quote->subject }}</p>
        @endif
    </div>
    <div class="col-12 col-sm-5 invoice-col text-center">
        <div class="invoice-status">
            <span class="{{ $stageClass }}">{{ __('client.status.' . $stageKey) }}</span>
        </div>
        <div class="small-text">
            {{ __('client.quotes.issued') }} {{ $quote->date ? \Carbon\Carbon::parse($quote->date)->format(date_fmt()) : '-' }}
            @if($quote->valid_until)
                <br>{{ __('client.quotes.valid_until') }}: {{ \Carbon\Carbon::parse($quote->valid_until)->format(date_fmt()) }}
            @endif
        </div>
        @if($actionable)
            <div class="payment-btn-container d-print-none" align="center">
                <form method="POST" action="{{ route('client.quotes.accept', $quote) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="fas fa-check-circle"></i> {{ __('client.quotes.accept_button') }}</button>
                </form>
            </div>
        @endif
    </div>
</div>

<div class="row invoice-client-details">
    <div class="col-12 col-sm-6 invoice-col">
        <h5>{{ 'Client' }}</h5>
        <p class="mb-0">
            {{ auth()->user()?->full_name }}<br>
            {{ auth()->user()?->email }}
        </p>
    </div>
</div>

<div class="invoice-items">
    <table class="table table-condensed">
        <thead>
            <tr>
                <th>{{ __('common.table.description') }}</th>
                <th class="text-center">{{ __('client.quotes.qty') }}</th>
                <th class="text-right">{{ __('client.quotes.unit_price') }}</th>
                <th class="text-right">{{ __('common.table.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">{{ money_fmt((float) $item->unit_price) }}</td>
                <td class="text-right">{{ money_fmt(max(0, ($item->quantity * $item->unit_price) - $item->discount)) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="row justify-content-end invoice-summary">
    <div class="col-12 col-sm-5">
        <div class="total-row">
            <div class="row">
                <div class="col-7">{{ __('client.cart.subtotal') }}</div>
                <div class="col-5 text-right">{{ money_fmt((float) $quote->subtotal) }}</div>
            </div>
        </div>
        @if((float) $quote->tax > 0)
        <div class="total-row">
            <div class="row">
                <div class="col-7">{{ __('client.cart.tax') }}</div>
                <div class="col-5 text-right">{{ money_fmt((float) $quote->tax) }}</div>
            </div>
        </div>
        @endif
        <div class="total-row invoice-total">
            <div class="row">
                <div class="col-7"><strong>{{ __('common.table.total') }}</strong></div>
                <div class="col-5 text-right"><strong>{{ money_fmt((float) $quote->total) }}</strong></div>
            </div>
        </div>
    </div>
</div>

@if($quote->proposal)
<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title">{{ __('client.quotes.proposal') }}</h5>
        <div style="white-space:pre-wrap">{{ $quote->proposal }}</div>
    </div>
</div>
@endif

@if($actionable)
<div class="card mt-4 d-print-none">
    <div class="card-body">
        <p class="text-muted">{{ __('client.quotes.decision_hint') }}</p>
        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#declineQuoteModal">
            {{ __('client.quotes.decline_button') }}
        </button>
    </div>
</div>

<div class="modal fade" id="declineQuoteModal" tabindex="-1" role="dialog" aria-labelledby="declineQuoteModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="declineQuoteModalLabel">{{ __('client.quotes.decline_button') }}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('common.actions.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="{{ route('client.quotes.decline', $quote) }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="declineReason" class="control-label">{{ __('client.quotes.decline_reason') }}</label>
                        <textarea name="reason" id="declineReason" class="form-control" rows="3" maxlength="2000"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.actions.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('client.quotes.decline_confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="col-12 text-center mt-4 d-print-none">
    <a href="{{ route('client.quotes.index') }}" class="btn btn-default">{{ __('client.quotes.back_to_quotes') }}</a>
</div>

@endsection

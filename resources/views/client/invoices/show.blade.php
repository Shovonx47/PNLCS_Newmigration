@extends('client.layouts.invoice')
@php
    $isProforma = $invoice->isProforma();
    $invoiceTitle = __($isProforma ? 'client.invoices.proforma_prefix' : 'client.invoices.invoice_prefix', ['id' => $invoice->invoice_num ?? $invoice->id]);
    $statusKey = strtolower((string) $invoice->status);
    $statusClass = match ($statusKey) {
        'paid' => 'paid',
        'unpaid', 'payment_pending' => 'unpaid',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
        'collections' => 'collections',
        default => 'draft',
    };
    $payable = in_array($statusKey, ['unpaid', 'overdue', 'partially_paid'], true);
    $clientDetails = $invoice->client;
    $vatGroups = $invoice->vatBreakdown();
@endphp
@section('title', $invoiceTitle)
@section('content')

<div class="row invoice-header">
    <div class="col-12 col-sm-6 justify-content-sm-between text-center text-sm-left invoice-col">
        <p><img src="{{ ! empty($customLogo) ? $customLogo : asset('assets/img/logo.png') }}" title="{{ company_name() }}" alt="{{ company_name() }}"></p>
        <h3>{{ $invoiceTitle }}</h3>
    </div>
    <div class="col-12 col-sm-6 invoice-col text-center">
        <div class="invoice-status">
            <span class="{{ $statusClass }}">{{ invoice_status_label($invoice->status) }}</span>
        </div>
        @if($payable || $statusKey === 'draft')
            <div class="small-text">
                {{ __('client.invoices.due') }}: {{ $invoice->due_date?->format(date_fmt()) ?? 'N/A' }}
            </div>
            @if($payable)
            <div class="payment-btn-container d-print-none" align="center">
                <a href="#payment-options" class="btn btn-primary" onclick="document.getElementById('payment-options').scrollIntoView({behavior:'smooth'});return false;">
                    <i class="fas fa-credit-card fa-fw"></i> {{ __('client.invoices.pay_this_invoice') }} — {{ dual_money_fmt($balance ?? $invoice->amountDue(), $invoice) }}
                </a>
            </div>
            @endif
        @endif
    </div>
</div>

<hr>

@if($invoice->sourceInvoice)
<div class="alert alert-info" role="alert">
    {{ __('admin.invoices.source_proforma') }}: <strong>{{ $invoice->sourceInvoice->invoice_num }}</strong>
</div>
@endif

@if($statusKey === 'payment_pending' || (isset($pendingNotification) && $pendingNotification))
<div class="alert alert-info" role="alert">
    <strong>{{ __('client.invoices.payment_notification_pending_title') }}</strong><br>
    {{ __('client.invoices.payment_notification_pending_text') }}
    @if(isset($pendingNotification) && $pendingNotification)
    <br><small>{{ __('client.invoices.reported_on') }} {{ $pendingNotification->created_at->timezone(display_tz())->format(datetime_fmt()) }} — {{ dual_money_fmt((float) $pendingNotification->amount, $invoice) }}</small>
    @endif
</div>
@endif

{{-- What happened when we tried to take this from the card on file. --}}
@if(isset($chargeAttempt) && $chargeAttempt && in_array($statusKey, ['unpaid', 'overdue', 'partially_paid'], true))
    @if($chargeAttempt->state === \App\Enums\ChargeAttemptState::ActionRequired)
    <div class="alert alert-warning" role="alert">
        <strong>{{ __('client.invoices.charge_action_required_title') }}</strong><br>
        {{ __('client.invoices.charge_action_required_text') }}
        <div class="mt-2">
            <button type="button" id="pn-authenticate-btn" class="btn btn-primary btn-sm" onclick="pnAuthenticate({{ $invoice->id }})">
                {{ __('client.invoices.charge_authenticate_button') }}
            </button>
            <span id="pn-authenticate-error" class="small"></span>
        </div>
    </div>
    @elseif($chargeAttempt->state === \App\Enums\ChargeAttemptState::Scheduled)
    <div class="alert alert-warning" role="alert">
        <strong>{{ __('client.invoices.charge_failed_title') }}</strong><br>
        @if($chargeAttempt->next_attempt_at)
            {{ __('client.invoices.charge_retry_text', ['date' => $chargeAttempt->next_attempt_at->timezone(display_tz())->format(date_fmt())]) }}
        @else
            {{ __('client.invoices.charge_failed_text') }}
        @endif
    </div>
    @elseif($chargeAttempt->state === \App\Enums\ChargeAttemptState::Exhausted)
    <div class="alert alert-warning" role="alert">
        <strong>{{ __('client.invoices.charge_failed_title') }}</strong><br>
        {{ __('client.invoices.charge_failed_text') }}
    </div>
    @elseif($chargeAttempt->state === \App\Enums\ChargeAttemptState::NeedsReview)
    <div class="alert alert-info" role="alert">
        <strong>{{ __('client.invoices.charge_checking_title') }}</strong><br>
        {{ __('client.invoices.charge_checking_text') }}
    </div>
    @endif
@endif

<div class="row justify-content-sm-between">
    <div class="col-12 col-sm-6 order-sm-last text-sm-right invoice-col right">
        <strong>Pay To</strong>
        <address class="small-text">
            {{ company_name() }}
        </address>
    </div>
    <div class="col-12 col-sm-6 invoice-col">
        <strong>Invoiced To</strong>
        <address class="small-text">
            @if($clientDetails?->company_name){{ $clientDetails->company_name }}<br>@endif
            {{ trim(($clientDetails->first_name ?? '') . ' ' . ($clientDetails->last_name ?? '')) }}<br>
            {{ $clientDetails->address1 ?? '' }}{{ $clientDetails->address2 ? ', ' . $clientDetails->address2 : '' }}<br>
            {{ $clientDetails->city ?? '' }}{{ $clientDetails->state ? ', ' . $clientDetails->state : '' }}{{ $clientDetails->postcode ? ', ' . $clientDetails->postcode : '' }}<br>
            {{ $clientDetails->country ?? '' }}
            @if($clientDetails?->tax_id)
                <br>Tax ID: {{ $clientDetails->tax_id }}
            @endif
        </address>
    </div>
</div>

<div class="row">
    <div class="col-12 col-sm-6 order-sm-last text-sm-right invoice-col right">
        <strong>{{ __('client.invoices.payment_method') }}</strong><br>
        <span class="small-text float-sm-right" data-role="paymethod-info">
            {{ payment_method_label($invoice->displayPaymentMethod()) }}
        </span>
        <br><br>
    </div>
    <div class="col-12 col-sm-6 invoice-col">
        <strong>Invoice Date</strong><br>
        <span class="small-text">
            {{ $invoice->date?->format(date_fmt()) ?? 'N/A' }}<br><br>
        </span>
    </div>
</div>

<br>

<div class="card bg-default">
    <div class="card-header">
        <h3 class="card-title mb-0 font-size-24"><strong>{{ __('client.invoices.invoice_details') }}</strong></h3>
    </div>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
            <tr>
                <td><strong>{{ __('common.table.description') }}</strong></td>
                <td width="20%" class="text-center"><strong>{{ __('common.table.amount') }}</strong></td>
            </tr>
            </thead>
            <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}@if((int) $item->qty > 1) <span class="text-muted">({{ (int) $item->qty }} × {{ invoice_money_fmt($item->amount, $invoice) }})</span>@endif</td>
                    <td class="text-center">{{ invoice_money_fmt($item->amount * (int) $item->qty, $invoice) }}</td>
                </tr>
            @endforeach
            @if($invoice->subtotal !== null)
            <tr>
                <td class="total-row text-right"><strong>{{ __('client.cart.subtotal') }}</strong></td>
                <td class="total-row text-center">{{ invoice_money_fmt($invoice->subtotal, $invoice) }}</td>
            </tr>
            @endif
            @foreach($vatGroups as $label => $g)
            <tr>
                <td class="total-row text-right"><strong>{{ $label }}</strong></td>
                <td class="total-row text-center">{{ invoice_money_fmt($g['amount'], $invoice) }}</td>
            </tr>
            @endforeach
            @if(($invoice->tax2 ?? 0) > 0)
            <tr>
                <td class="total-row text-right"><strong>{{ __('client.cart.tax').' 2' }}{{ $invoice->tax_rate2 > 0 ? ' (' . rtrim(rtrim(number_format((float) $invoice->tax_rate2, 2), '0'), '.') . '%)' : '' }}</strong></td>
                <td class="total-row text-center">{{ invoice_money_fmt($invoice->tax2, $invoice) }}</td>
            </tr>
            @endif
            <tr>
                <td class="total-row text-right"><strong>Credit</strong></td>
                <td class="total-row text-center">{{ invoice_money_fmt($invoice->credit ?? 0, $invoice) }}</td>
            </tr>
            <tr>
                <td class="total-row text-right"><strong>{{ __('client.invoices.total_due') }}</strong></td>
                <td class="total-row text-center">{{ invoice_money_fmt($invoice->amountDue(), $invoice) }}</td>
            </tr>
            @if(billing_rate_note($invoice) && has_billing_conversion($invoice))
            <tr>
                <td class="total-row text-right"><strong>{{ __('pdf.amount_in') }}</strong></td>
                <td class="total-row text-center">{{ billing_money_fmt($invoice->amountDue(), $invoice) }}</td>
            </tr>
            @endif
            @if(isset($balance) && $balance > 0 && $balance < (float) $invoice->total && ! in_array($statusKey, \App\Enums\InvoiceStatus::settled(), true))
            <tr>
                <td class="total-row text-right"><strong>{{ __('client.invoices.amount_paid') }}</strong></td>
                <td class="total-row text-center">{{ invoice_money_fmt((float) $invoice->total - $balance, $invoice) }}</td>
            </tr>
            <tr>
                <td class="total-row text-right"><strong>{{ __('client.invoices.remaining_balance') }}</strong></td>
                <td class="total-row text-center">{{ invoice_money_fmt($balance, $invoice) }}</td>
            </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>

@if(billing_rate_note($invoice) && has_billing_conversion($invoice))
<p class="small-text text-muted">{{ billing_rate_note($invoice) }}</p>
@endif
@if($taxNotice = \App\Support\TaxNotice::forInvoice($invoice))
<p class="small-text text-muted">{{ $taxNotice }}</p>
@endif

<div class="transactions-container small-text">
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <td class="text-center"><strong>Date</strong></td>
                    <td class="text-center"><strong>Gateway</strong></td>
                    <td class="text-center"><strong>Transaction ID</strong></td>
                    <td class="text-center"><strong>Amount</strong></td>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->transactions as $transaction)
                    <tr>
                        <td class="text-center">{{ $transaction->date?->format(date_fmt()) }}</td>
                        <td class="text-center">{{ payment_method_label($transaction->gateway) }}</td>
                        <td class="text-center">{{ $transaction->transaction_id }}</td>
                        <td class="text-center">{{ invoice_money_fmt($transaction->amount_in, $invoice) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center" colspan="4">No transactions found</td>
                    </tr>
                @endforelse
                <tr>
                    <td class="text-right" colspan="3"><strong>Balance</strong></td>
                    <td class="text-center">{{ invoice_money_fmt($balance ?? $invoice->amountDue(), $invoice) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@if($payable)
<div class="card d-print-none" id="payment-options">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.invoices.pay_this_invoice') }}</h3>

        @if(($usableCredit ?? 0) > 0)
        <form method="POST" action="{{ route('client.invoices.pay-with-credit', $invoice) }}" class="mb-2">
            @csrf
            <button type="submit" class="btn btn-success">{{ __('client.invoices.pay_with_credit', ['amount' => money_fmt($usableCredit)]) }}</button>
        </form>
        @endif

        @if(isset($storedCards) && $storedCards->isNotEmpty())
        <div class="mb-3">
            <p class="text-muted small mb-1">{{ __('client.invoices.pay_with_stored_card') }}</p>
            @foreach($storedCards as $card)
            <form method="POST" action="{{ route('client.invoices.pay-with-card', $invoice) }}" class="d-inline-block mr-2 mb-2">
                @csrf
                <input type="hidden" name="payment_method_id" value="{{ $card->id }}">
                <button type="submit" class="btn {{ $loop->first ? 'btn-primary' : 'btn-default' }}">{{ __('client.invoices.pay_with_card_button', ['card' => trim(($card->card_brand ? ucfirst($card->card_brand).' ' : '').'•••• '.$card->last_four)]) }}</button>
            </form>
            @endforeach
        </div>
        @endif

        @if(!empty($gateways))
        @if(has_billing_conversion($invoice))
        <p class="text-muted small">{{ __('client.invoices.currency_charge_note', [
            'billing' => strtoupper((string) $invoice->billing_currency),
            'shop' => strtoupper((string) ($invoice->source_currency ?: currency_code_default())),
            'amount' => invoice_money_fmt($balance ?? $invoice->amountDue(), $invoice),
        ]) }}</p>
        @endif
        <p class="text-muted small">{{ __('client.invoices.select_payment_method') }}</p>
        <ul class="nav nav-tabs" role="tablist">
            @foreach($gateways as $i => $gw)
            <li class="nav-item">
                <a class="nav-link {{ $i === 0 ? 'active' : '' }}" href="#gw-{{ $gw }}" data-toggle="tab" role="tab">
                    {{ $gatewayLabels[$gw] ?? ucfirst($gw) }}
                </a>
            </li>
            @endforeach
        </ul>
        <div class="tab-content pt-3">
            @foreach($gateways as $i => $gw)
            <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="gw-{{ $gw }}" role="tabpanel">
                @if(isset($gatewayForms[$gw]) && $gatewayForms[$gw])
                    {!! $gatewayForms[$gw] !!}
                    @if($gw === 'banktransfer' && !(isset($pendingNotification) && $pendingNotification))
                        @include('client.invoices.partials.payment-notification-form')
                    @endif
                @elseif($gw === 'stripe')
                    <p class="text-muted small">{{ __('client.invoices.stripe_desc') }}</p>
                    <div id="stripe-card-element" class="form-control mb-3" style="height:auto">
                        <em class="text-muted">{{ __('client.invoices.stripe_placeholder') }}</em>
                    </div>
                    <button type="button" onclick="stripePayNow({{ $invoice->id }})" class="btn btn-primary">
                        {{ __('client.invoices.pay_with_card', ['amount' => number_format($invoice->amountDue(), 2)]) }}
                    </button>
                @elseif($gw === 'paypal')
                    <p class="text-muted small">{{ __('client.invoices.paypal_desc') }}</p>
                    <div id="paypal-button-container-{{ $invoice->id }}" style="max-width:280px"></div>
                @else
                    <p class="text-muted small">{{ __('client.invoices.payment_form_for') }} <strong>{{ $gatewayLabels[$gw] ?? ucfirst($gw) }}</strong> {{ __('client.invoices.not_configured') }}</p>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="alert alert-warning">{{ __('client.invoices.no_payment_methods') }}</div>
        @endif
    </div>
</div>
@endif

<div class="float-right btn-group btn-group-sm d-print-none">
    <a href="javascript:window.print()" class="btn btn-default"><i class="fas fa-print"></i> Print</a>
    <a href="{{ route('client.invoices.pdf', $invoice) }}" class="btn btn-default"><i class="fas fa-download"></i> {{ __('client.invoices.download_pdf') }}</a>
</div>

<p class="text-center d-print-none"><a href="{{ route('client.invoices.index') }}">{{ __('client.invoices.back_to_invoices') }}</a></p>

@endsection

@section('scripts')
<script>
// The cardholder finishing a charge their bank stopped. The intent is not
// named here: the server reads it from what the charger recorded, hands back
// only that intent's client secret, and the same confirm endpoint every other
// card payment goes through credits the invoice.
function pnAuthenticate(id) {
    var btn = document.getElementById("pn-authenticate-btn");
    var msg = document.getElementById("pn-authenticate-error");
    if (btn) { btn.disabled = true; }
    if (msg) { msg.textContent = ""; }

    function stop(text) {
        if (msg) { msg.textContent = text; }
        if (btn) { btn.disabled = false; }
    }

    fetch("/gateway/stripe/authenticate/" + id, {
        method: "POST",
        headers: {"Content-Type": "application/json", "X-CSRF-TOKEN": @json(csrf_token())}
    }).then(function (r) { return r.json(); }).then(function (d) {
        if (!d.success || !d.client_secret) {
            stop(d.message || @json(__('client.invoices.authenticate_unavailable')));
            return;
        }

        if (typeof Stripe === "undefined") {
            stop(@json(__('client.invoices.authenticate_unavailable')));
            return;
        }

        var stripe = Stripe(@json($stripePublishableKey ?? ''));

        return stripe.handleNextAction({ clientSecret: d.client_secret }).then(function (result) {
            if (result.error) {
                stop(result.error.message || @json(__('client.invoices.authenticate_unavailable')));
                return;
            }

            return fetch("/gateway/stripe/confirm/" + id, {
                method: "POST",
                headers: {"Content-Type": "application/json", "X-CSRF-TOKEN": @json(csrf_token())},
                body: JSON.stringify({ payment_intent_id: result.paymentIntent.id })
            }).then(function (r) { return r.json(); }).then(function (res) {
                if (res.success) { window.location.href = res.redirect_url || window.location.href; }
                else { stop(res.message || @json(__('client.invoices.authenticate_unavailable'))); }
            });
        });
    }).catch(function () {
        stop(@json(__('client.invoices.network_error_only')));
    });
}
function stripePayNow(id) {
    fetch("/gateway/stripe/intent/" + id, {
        method: "POST",
        headers: {"Content-Type": "application/json","X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]")?.content || ""}
    }).then(r => r.json()).then(d => {
        if (d.success) { alert(@js(__('client.invoices.payment_intent')) + ' ' + d.client_secret); }
        else { alert(@js(__('client.invoices.payment_error')) + ' ' + (d.message || @js(__('client.invoices.unknown_error')))); }
    }).catch(e => alert(@js(__('client.invoices.network_error')) + ' ' + e.message));
}
</script>
@endsection

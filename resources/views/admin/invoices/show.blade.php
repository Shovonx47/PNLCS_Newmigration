@extends('admin.layouts.app')
@section('title', __($invoice->isProforma() ? 'admin.search.proforma_title' : 'admin.search.invoice_title', ['num' => $invoice->invoice_num]))
@section('content')

@php
    $st = strtolower((string) $invoice->status);
    $itemsLocked = in_array($st, ['paid', 'cancelled', 'refunded'], true);
    $vatGroups = $invoice->vatBreakdown();
    $directPayUrl = \App\Support\DirectPayLink::url($invoice);
    $whmcsStatusColor = match($st) {
        'paid' => '#46a546',
        'unpaid', 'overdue', 'partially_paid', 'payment_pending' => '#cc0000',
        default => '#888888',
    };
@endphp

<div x-data="{ activeTab: 'summary' }">

    {{-- Top WHMCS Invoice Management Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:15px;margin-bottom:14px;">
        <div>
            <h1 style="margin:0 0 6px 0;">
                {{ __($invoice->isProforma() ? 'admin.search.proforma_title' : 'admin.search.invoice_title', ['num' => $invoice->invoice_num]) }}
            </h1>
            <div style="font-size:13px;color:#555;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                @if($invoice->client)
                    <span>
                        <strong>{{ __('admin.invoices.name') }}:</strong>
                        <a href="{{ route('admin.clients.show', $invoice->client) }}">{{ $invoice->client?->display_name ?? __('Deleted Client') }}</a>
                    </span>
                @endif
                <span><strong>{{ __('admin.invoices.date') }}:</strong> {{ $invoice->date?->format(date_fmt()) ?? '—' }}</span>
                <span>
                    <strong>{{ __('admin.invoices.due_date') }}:</strong>
                    <span style="{{ ($invoice->due_date?->isPast() && $st !== 'paid') ? 'color:#cc0000;font-weight:600;' : '' }}">{{ $invoice->due_date?->format(date_fmt()) ?? '—' }}</span>
                </span>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-default btn-xs">&larr; {{ __('admin.invoices.back') }}</a>
            </div>
        </div>

        <div style="text-align:right;">
            <div style="margin-bottom:8px;">
                <span class="whmcs-status {{ $st }} badge-{{ $st }}" style="font-size:24px;font-weight:700;text-transform:uppercase;color:{{ $whmcsStatusColor }};background:transparent;padding:0;">
                    {{ invoice_status_label($invoice->status) }}
                </span>
                @if($st === 'paid' && $invoice->date_paid)
                    <div style="font-size:12px;color:#3c763d;margin-top:2px;">&#10003; {{ __('admin.invoices.paid_on_date', ['date' => $invoice->date_paid->timezone(display_tz())->format(datetime_fmt())]) }}</div>
                @elseif($st === 'cancelled')
                    <div style="font-size:12px;color:#777;margin-top:2px;">{{ __('admin.invoices.invoice_cancelled') }}</div>
                @endif
            </div>

            <div style="display:flex;gap:5px;justify-content:flex-end;flex-wrap:wrap;align-items:center;">
                <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-default btn-sm">{{ __('admin.invoices.download_pdf_btn') }}</a>

                <form method="POST" action="{{ route('admin.invoices.send', $invoice) }}" style="display:inline;margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm">{{ __('admin.invoices.send') }}</button>
                </form>

                @if(in_array($st, ['unpaid', 'overdue', 'partially_paid', 'payment_pending']))
                <form method="POST" action="{{ route('admin.invoices.remind', $invoice) }}" style="display:inline;margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm">{{ __('admin.invoices.remind') }}</button>
                </form>
                <button type="button" class="btn btn-success btn-sm" @click="activeTab = 'add-payment'">{{ __('admin.invoices.mark_paid_btn') }}</button>
                @endif

                @if(!empty($canChargeNow))
                <form method="POST" action="{{ route('admin.invoices.charge-now', $invoice) }}" style="display:inline;margin:0;" onsubmit="return pnConfirm(event, @js(__('admin.invoices.charge_now_confirm')))">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.invoices.charge_now') }}</button>
                </form>
                @endif

                @if(!in_array($st, ['paid', 'cancelled', 'refunded']))
                <form method="POST" action="{{ route('admin.invoices.cancel', $invoice) }}" style="display:inline;margin:0;" onsubmit="return pnConfirm(event, @js(__('admin.invoices.confirm_cancel')))">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('common.actions.cancel') }}</button>
                </form>
                @endif

                @if(in_array($st, ['paid', 'partially_paid']))
                <button type="button" class="btn btn-warning btn-sm" @click="activeTab = 'refund'">{{ __('admin.invoices.refund_btn') }}</button>
                @endif
            </div>
        </div>
    </div>

    {{-- Charge Review Banner --}}
    @if(!empty($chargeReview))
    <div class="infobox" style="margin-bottom:15px;border-left:4px solid #b91c1c;background:#fef2f2;">
        <div style="margin-bottom:6px;"><strong style="color:#b91c1c;">{{ __('admin.invoices.charge_review_title') }}</strong></div>
        <p style="margin:0 0 8px;font-size:13px;">{{ __('admin.invoices.charge_review_body') }}</p>
        <ul style="margin:0 0 10px;padding-left:18px;font-size:13px;color:#444;">
            <li>{{ __('admin.invoices.charge_review_amount') }}: <strong>{{ $chargeReview->currency }} {{ number_format((float) $chargeReview->amount, 2) }}</strong></li>
            <li>{{ __('admin.invoices.charge_review_sent') }}: <strong>{{ $chargeReview->updated_at?->format(datetime_fmt()) }}</strong></li>
            @if($chargeReview->last_transaction_id)
                <li>{{ __('admin.invoices.charge_review_reference') }}: <strong>{{ $chargeReview->last_transaction_id }}</strong></li>
            @endif
            @if($chargeReview->last_message)
                <li>{{ $chargeReview->last_message }}</li>
            @endif
        </ul>
        <form method="POST" action="{{ route('admin.invoices.charge-review.release', $invoice) }}" onsubmit="return pnConfirm(event, @js(__('admin.invoices.charge_review_confirm')))" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm">{{ __('admin.invoices.charge_review_release') }}</button>
            <small class="text-muted" style="margin-left:8px;">{{ __('admin.invoices.charge_review_release_hint') }}</small>
        </form>
    </div>
    @endif

    {{-- WHMCS Admin Tabs --}}
    <ul class="admin-tabs" role="tablist">
        <li :class="{ 'active': activeTab === 'summary' }">
            <a href="#tab-summary" :class="{ 'active': activeTab === 'summary' }" @click.prevent="activeTab = 'summary'">{{ __('admin.invoices.summary') }}</a>
        </li>
        <li :class="{ 'active': activeTab === 'add-payment' }">
            <a href="#tab-add-payment" :class="{ 'active': activeTab === 'add-payment' }" @click.prevent="activeTab = 'add-payment'">{{ __('Add Payment') }}</a>
        </li>
        <li :class="{ 'active': activeTab === 'refund' }">
            <a href="#tab-refund" :class="{ 'active': activeTab === 'refund' }" @click.prevent="activeTab = 'refund'">{{ __('Credit / Refund') }}</a>
        </li>
        <li :class="{ 'active': activeTab === 'notes' }">
            <a href="#tab-notes" :class="{ 'active': activeTab === 'notes' }" @click.prevent="activeTab = 'notes'">{{ __('Options / Notes') }}</a>
        </li>
    </ul>

    <div class="tab-content admin-tabs">

        {{-- TAB 1: SUMMARY --}}
        <div class="tab-pane" :class="{ 'active': activeTab === 'summary' }" x-show="activeTab === 'summary'" id="tab-summary">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('admin.invoices.name') }}</td>
                    <td width="35%" class="fieldarea">
                        @if($invoice->client)
                            <a href="{{ route('admin.clients.show', $invoice->client) }}" style="font-weight:600;">{{ $invoice->client?->display_name ?? __('Deleted Client') }}</a>
                            <div style="font-size:12px;color:#666;">{{ $invoice->buyer('email') ?? '-' }}</div>
                        @else
                            {{ __('Deleted Client') }}
                        @endif
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('admin.invoices.invoice_hash') }}</td>
                    <td width="35%" class="fieldarea">
                        <strong style="font-family:monospace;">{{ $invoice->invoice_num }}</strong>
                        @if($invoice->sourceInvoice)
                            <span style="margin-left:8px;font-size:12px;">
                                ({{ __('admin.invoices.source_proforma') }}:
                                <a href="{{ route('admin.invoices.show', $invoice->sourceInvoice) }}" style="font-family:monospace;">{{ $invoice->sourceInvoice->invoice_num }}</a>)
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.form.address') }}</td>
                    <td class="fieldarea">
                        @if($invoice->buyer('address1'))
                            {{ $invoice->buyer('address1') }}, {{ $invoice->buyer('city') }}, {{ $invoice->buyer('state') }} {{ $invoice->buyer('postcode') }} ({{ $invoice->buyer('country') }})
                        @else
                            {{ '—' }}
                        @endif
                    </td>
                    <td class="fieldlabel">{{ __('admin.invoices.date') }}</td>
                    <td class="fieldarea">{{ $invoice->date?->format(date_fmt()) ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.invoices.buyer_type') }}</td>
                    <td class="fieldarea">
                        @if($invoice->buyer('client_type'))
                            {{ $invoice->buyer('client_type') === 'company' ? __('admin.clients.billing_type_company') : __('admin.clients.billing_type_individual') }}
                        @else
                            {{ '—' }}
                        @endif
                        @if($invoice->buyer('tax_office'))
                            <span>&middot; {{ __('admin.invoices.buyer_tax_office') }}: {{ $invoice->buyer('tax_office') }}</span>
                        @endif
                        @if($invoice->buyer('tax_id'))
                            <span>&middot; {{ __('admin.invoices.tax_id') }}: <code style="font-size:12px;">{{ $invoice->buyer('tax_id') }}</code></span>
                        @endif
                        @if($invoice->buyer('national_id'))
                            <span>&middot; {{ __('admin.invoices.buyer_national_id') }}: <code style="font-size:12px;">{{ $invoice->buyer('national_id') }}</code></span>
                        @endif
                        @foreach($invoice->buyerCustomFields() as $label => $value)
                            <div><small>{{ $label }}: {{ $value }}</small></div>
                        @endforeach
                    </td>
                    <td class="fieldlabel">{{ __('admin.invoices.due_date') }}</td>
                    <td class="fieldarea" style="{{ ($invoice->due_date?->isPast() && $st !== 'paid') ? 'color:#d9534f;font-weight:600;' : '' }}">
                        {{ $invoice->due_date?->format(date_fmt()) ?? '—' }}
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.invoices.payment') }}</td>
                    <td class="fieldarea">
                        {{ payment_method_label($invoice->displayPaymentMethod()) }}
                        @if($st === 'paid' && $invoice->date_paid)
                            <span style="color:#3c763d;font-weight:600;margin-left:8px;">({{ __('admin.invoices.paid_on') }}: {{ $invoice->date_paid->timezone(display_tz())->format(datetime_fmt()) }})</span>
                        @endif
                    </td>
                    <td class="fieldlabel">{{ __('admin.invoices.direct_pay_title') }}</td>
                    <td class="fieldarea">
                        <div style="display:flex;gap:4px;align-items:center;">
                            <input type="text" id="direct-pay-url" class="form-control input-sm" readonly value="{{ $directPayUrl }}" onfocus="this.select()" title="{{ __('admin.invoices.direct_pay_help') }}">
                            <button type="button" class="btn btn-default btn-xs" data-copy-direct-pay data-label-copy="{{ __('common.actions.copy') }}">{{ __('common.actions.copy') }}</button>
                            <a href="{{ $directPayUrl }}" target="_blank" rel="noopener" class="btn btn-primary btn-xs">{{ __('admin.addon_modules.open') }}</a>
                        </div>
                    </td>
                </tr>
            </table>

            {{-- Invoice Items --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin:16px 0 8px;">
                <h2 style="margin:0;">{{ __('admin.invoices.line_items') }}</h2>
                @unless($itemsLocked)
                <form method="POST" action="{{ route('admin.invoices.items.store', $invoice) }}" style="display:inline;margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-default btn-xs">+ {{ __('admin.invoices.add_item') }}</button>
                </form>
                @endunless
            </div>

            @foreach($invoice->items as $item)
            <form id="item-form-{{ $item->id }}" method="POST" action="{{ route('admin.invoices.items.update', [$invoice, $item]) }}" style="display:none;">
                @csrf @method('PUT')
            </form>
            @endforeach

            <div class="tablebg">
                <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                    <thead>
                        <tr>
                            <th>{{ __('common.table.description') }}</th>
                            <th style="width:70px;text-align:center;">{{ __('common.table.qty') }}</th>
                            <th style="text-align:right;width:110px;">{{ __('admin.invoices.price') }}</th>
                            <th style="width:110px;text-align:center;">{{ __('common.table.tax') }}</th>
                            <th style="text-align:right;width:110px;">{{ __('common.table.total') }}</th>
                            <th style="width:36px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                            @php
                                $effectiveRate = $item->tax_rate !== null ? (float) $item->tax_rate : ($item->taxed ? (float) $invoice->tax_rate : 0.0);
                            @endphp
                            <tr>
                                <td>
                                    @if($item->type)
                                        <div style="font-size:10px;color:#888;text-transform:uppercase;">{{ $item->type }}</div>
                                    @endif
                                    <input type="text" form="item-form-{{ $item->id }}" name="description" value="{{ $item->description }}" class="inv-inline" data-enter-submit @disabled($itemsLocked)>
                                </td>
                                <td>
                                    <input type="number" form="item-form-{{ $item->id }}" name="qty" value="{{ (int) $item->qty }}" min="1" step="1" class="inv-inline inv-num" data-enter-submit @disabled($itemsLocked)>
                                </td>
                                <td>
                                    <input type="number" form="item-form-{{ $item->id }}" name="amount" value="{{ number_format((float) $item->amount, 2, '.', '') }}" step="0.01" min="0" class="inv-inline inv-num" data-enter-submit @disabled($itemsLocked)>
                                </td>
                                <td>
                                    <select form="item-form-{{ $item->id }}" name="tax_rule_id" class="inv-inline" @disabled($itemsLocked)>
                                        <option value="">{{ '0%' }}</option>
                                        @foreach($taxRules as $rule)
                                            <option value="{{ $rule['id'] }}" @selected((int) $item->tax_rule_id === (int) $rule['id'])>{{ $rule['name'] }}</option>
                                        @endforeach
                                        @if($item->tax_rule_id === null && $effectiveRate > 0)
                                            <option value="__legacy__" selected>{{ rtrim(rtrim(number_format($effectiveRate, 2, '.', ''), '0'), '.') }}%</option>
                                        @endif
                                    </select>
                                </td>
                                <td style="text-align:right;font-family:monospace;white-space:nowrap;">{{ money_fmt($item->amount * (int) $item->qty) }}</td>
                                <td style="text-align:center;">
                                    @unless($itemsLocked)
                                    <form method="POST" action="{{ route('admin.invoices.items.destroy', [$invoice, $item]) }}" style="display:inline;margin:0;" onsubmit="return pnConfirm(event, @js(__('admin.invoices.confirm_delete_item')))">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="background:none;border:none;color:#d9534f;cursor:pointer;font-size:16px;padding:0 2px;" title="{{ __('admin.invoices.delete_item') }}">&times;</button>
                                    </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align:center;color:#999;padding:18px;">{{ __('admin.invoices.no_line_items') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align:right;padding:6px 10px;font-weight:600;">{{ __('admin.invoices.subtotal') }}:</td>
                            <td style="text-align:right;padding:6px 10px;font-weight:600;font-family:monospace;white-space:nowrap;">{{ money_fmt($invoice->subtotal) }}</td>
                            <td></td>
                        </tr>
                        @foreach($vatGroups as $label => $g)
                        <tr>
                            <td colspan="3" style="text-align:right;padding:4px 10px;color:#555;">{{ $label }}:</td>
                            <td style="text-align:right;padding:4px 10px;font-family:monospace;white-space:nowrap;color:#666;">{{ money_fmt($g['net']) }}</td>
                            <td style="text-align:right;padding:4px 10px;font-family:monospace;white-space:nowrap;">{{ money_fmt($g['amount']) }}</td>
                            <td></td>
                        </tr>
                        @endforeach
                        @if($invoice->tax2 > 0)
                        <tr>
                            <td colspan="4" style="text-align:right;padding:4px 10px;color:#555;">
                                {{ __('admin.invoices.tax_2') }}{{ $invoice->tax_rate2 > 0 ? " (" . rtrim(rtrim(number_format((float) $invoice->tax_rate2, 2), '0'), '.') . "%)" : "" }}:
                            </td>
                            <td style="text-align:right;padding:4px 10px;font-family:monospace;white-space:nowrap;">{{ money_fmt($invoice->tax2) }}</td>
                            <td></td>
                        </tr>
                        @endif
                        @if($invoice->credit > 0)
                        <tr>
                            <td colspan="4" style="text-align:right;padding:4px 10px;color:#3c763d;">{{ __('admin.invoices.credit_applied') }}:</td>
                            <td style="text-align:right;padding:4px 10px;font-family:monospace;color:#3c763d;white-space:nowrap;">-{{ money_fmt($invoice->credit) }}</td>
                            <td></td>
                        </tr>
                        @endif
                        <tr style="background:#eef2f7;border-top:2px solid #cbd5e1;">
                            <td colspan="4" style="text-align:right;padding:8px 10px;font-weight:700;font-size:14px;">{{ __('admin.invoices.total') }}:</td>
                            <td style="text-align:right;padding:8px 10px;font-weight:700;font-size:14px;font-family:monospace;white-space:nowrap;">{{ money_fmt($invoice->total) }}</td>
                            <td></td>
                        </tr>
                        <tr style="background:#f8fafc;">
                            <td colspan="4" style="text-align:right;padding:6px 10px;font-weight:700;">{{ __('admin.invoices.amount_due') }}:</td>
                            <td style="text-align:right;padding:6px 10px;font-weight:700;font-family:monospace;white-space:nowrap;">{{ money_fmt($invoice->amountDue()) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Transactions --}}
            <h2 style="margin:18px 0 8px;">{{ __('admin.invoices.payment_history') }}</h2>
            <div class="tablebg">
                <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                    <thead>
                        <tr>
                            <th>{{ __('common.table.date') }}</th>
                            <th>{{ __('admin.invoices.gateway') }}</th>
                            <th>{{ __('admin.invoices.transaction_id') }}</th>
                            <th style="text-align:right;">{{ __('common.table.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->transactions as $tx)
                        <tr>
                            <td>{{ $tx->date?->format(date_fmt()) }}</td>
                            <td>{{ payment_method_label((string) $tx->gateway) }}</td>
                            <td style="font-family:monospace;">{{ $tx->transaction_id ?? '—' }}</td>
                            @if((float) $tx->amount_out > 0)
                                <td style="text-align:right;color:#d9534f;font-weight:600;">-{{ money_fmt($tx->amount_out) }}</td>
                            @else
                                <td style="text-align:right;color:#3c763d;font-weight:600;">+{{ money_fmt($tx->amount_in) }}</td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align:center;color:#888;padding:14px;">{{ __('No Transactions Found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB 2: ADD PAYMENT --}}
        <div class="tab-pane" :class="{ 'active': activeTab === 'add-payment' }" x-show="activeTab === 'add-payment'" id="mark-paid-form">
            @if(in_array($st, ['unpaid', 'overdue', 'partially_paid', 'payment_pending']))
                <form method="POST" action="{{ route('admin.invoices.mark-paid', $invoice) }}">
                    @csrf
                    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                        <tr>
                            <td width="20%" class="fieldlabel">{{ __('admin.invoices.gateway') }}</td>
                            <td class="fieldarea">
                                <select name="gateway" class="form-control select-inline">
                                    <option value="manual">{{ __('admin.invoices.manual') }}</option>
                                    <option value="banktransfer">{{ __('admin.invoices.bank_transfer') }}</option>
                                    <option value="paypal">{{ __('admin.invoices.paypal') }}</option>
                                    <option value="stripe">{{ __('admin.invoices.stripe') }}</option>
                                    <option value="credit">{{ __('admin.invoices.credit') }}</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.invoices.transaction_id') }}</td>
                            <td class="fieldarea">
                                <input type="text" name="transaction_id" class="form-control input-250" placeholder="{{ __('admin.invoices.transaction_id_placeholder') }}">
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.invoices.amount') }}</td>
                            <td class="fieldarea">
                                <input type="number" name="amount" step="0.01" value="{{ number_format($invoice->amountDue(), 2, '.', '') }}" class="form-control input-150">
                            </td>
                        </tr>
                    </table>
                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">{{ __('admin.invoices.confirm_payment') }}</button>
                    </div>
                </form>
            @else
                <div class="infobox">{{ __('admin.invoices.mark_invoice_paid') }}: {{ invoice_status_label($invoice->status) }}</div>
            @endif
        </div>

        {{-- TAB 3: CREDIT / REFUND --}}
        <div class="tab-pane" :class="{ 'active': activeTab === 'refund' }" x-show="activeTab === 'refund'" id="refund-form">
            @if(in_array($st, ['paid', 'partially_paid']))
                <form method="POST" action="{{ route('admin.invoices.refund', $invoice) }}" onsubmit="return pnConfirm(event, @js(__('admin.invoices.confirm_refund')))">
                    @csrf
                    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                        <tr>
                            <td width="20%" class="fieldlabel">{{ __('admin.invoices.refund_amount') }}</td>
                            <td class="fieldarea">
                                <input type="number" name="amount" step="0.01" min="0.01" value="{{ number_format((float) $invoice->total, 2, '.', '') }}" class="form-control input-150">
                                <span style="font-size:11px;color:#777;margin-left:6px;">{{ __('admin.invoices.refund_amount_hint') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.invoices.refund_reason') }}</td>
                            <td class="fieldarea">
                                <input type="text" name="reason" maxlength="500" class="form-control input-400">
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldlabel">{{ __('admin.invoices.refund_via_gateway') }}</td>
                            <td class="fieldarea">
                                <label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;margin:0;cursor:pointer;">
                                    <input type="checkbox" name="gateway_refund" value="1" checked>
                                    <span>{{ __('admin.invoices.refund_via_gateway_hint') }}</span>
                                </label>
                            </td>
                        </tr>
                    </table>
                    <div class="btn-container">
                        <button type="submit" class="btn btn-warning">{{ __('admin.invoices.confirm_refund_btn') }}</button>
                    </div>
                </form>
            @else
                <div class="infobox">{{ __('No Refundable Payments') }}</div>
            @endif
        </div>

        {{-- TAB 4: OPTIONS / NOTES --}}
        <div class="tab-pane" :class="{ 'active': activeTab === 'notes' }" x-show="activeTab === 'notes'" id="tab-notes">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('admin.invoices.notes') }}</td>
                    <td class="fieldarea" style="white-space:pre-wrap;">{{ $invoice->notes ?: '—' }}</td>
                </tr>
            </table>

            <h2 style="margin:16px 0 8px;">{{ __('admin.invoices.activity_log') }}</h2>
            <div class="tablebg">
                <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                    <thead>
                        <tr>
                            <th width="180">{{ __('common.table.date') }}</th>
                            <th>{{ __('common.table.description') }}</th>
                            <th width="140">{{ __('User') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activityLog as $entry)
                        <tr>
                            <td>{{ $entry->date->timezone(display_tz())->format(datetime_fmt()) }}</td>
                            <td>{{ $entry->description }}</td>
                            <td>{{ $entry->user ?: __('System') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="text-align:center;color:#888;padding:14px;">{{ __('admin.invoices.no_activity') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
    .inv-inline { border: 1px solid transparent; background: transparent; width: 100%; font-size: 13px; font-family: inherit; padding: 2px 4px; border-radius: 3px; color: inherit; }
    .inv-inline:hover, .inv-inline:focus { border-color: #ccc; background: #fff; outline: none; }
    .inv-num { text-align: right; }
</style>
<script>
document.querySelectorAll('[data-copy-direct-pay]').forEach(function (button) {
    button.addEventListener('click', function () {
        var field = document.getElementById('direct-pay-url');
        if (! field) { return; }
        var done = function () {
            button.textContent = '✓';
            setTimeout(function () { button.textContent = button.dataset.labelCopy || 'Copy'; }, 2000);
        };
        if (navigator.clipboard) {
            navigator.clipboard.writeText(field.value).then(done, function () { field.select(); });
        } else {
            field.select();
        }
    });
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.matches('[data-enter-submit]')) {
        e.preventDefault();
        var form = document.getElementById(e.target.getAttribute('form'));
        if (form) { form.requestSubmit(); }
    }
});
</script>

@endsection

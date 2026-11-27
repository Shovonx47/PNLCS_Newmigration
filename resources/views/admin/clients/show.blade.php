@extends('admin.layouts.app')
@section('title', '#' . $client->id . ' - ' . $client->full_name)
@section('content')
<style>
    .client-summary-panels {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        align-items: start;
    }
    @media (max-width: 1180px) {
        .client-summary-panels {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 680px) {
        .client-summary-panels {
            grid-template-columns: 1fr;
        }
    }
    .clientssummarybox ul li form {
        margin: 0;
        display: inline;
    }
    .clientssummarybox ul li button.whmcs-link-btn {
        background: none;
        border: none;
        padding: 0;
        font: inherit;
        color: #1a4d80;
        cursor: pointer;
        text-align: left;
    }
    .clientssummarybox ul li button.whmcs-link-btn:hover {
        text-decoration: underline;
    }
</style>

@include('admin.clients.partials.client-tabs', ['client' => $client, 'activeTab' => $tab])

<div class="tab-content client-tabs">
    <div class="tab-pane active">
        <div class="clientsummaryactions">
            {{ __('admin.clients.status') }}:
            <span class="whmcs-status badge-{{ strtolower($client->status->value) }}">{{ status_label($client->status->value) }}</span>
            &nbsp;|&nbsp;
            {{ __('admin.clients.tax_exempt') }}:
            <strong>{{ $client->tax_exempt ? 'Yes' : 'No' }}</strong>
            &nbsp;|&nbsp;
            {{ __('admin.clients.credit_balance') }}:
            <strong style="color:#3c763d;">{{ money_fmt($client->credit) }}</strong>
            @if(auth('admin')->user()?->hasPermission('manage_invoices'))
                (<a href="{{ route('admin.clients.credit', $client) }}">{{ __('admin.clients.credit_manage') }}</a>)
            @endif
        </div>
        <div class="client-summary-name">
            #{{ $client->id }} - {{ $client->full_name }}
        </div>
        <div style="clear:both;margin-bottom:8px;"></div>

        {!! hook_output('AdminAreaClientSummaryOutput', ['client' => $client]) !!}

        @if($tab === 'summary')
        @php
            $identityType = $client->client_type;
            $identityGaps = $client->missingBillingIdentity();
            $copyLines = [$client->full_name];
            if ($identityType) {
                $copyLines[] = __('admin.clients.billing_type').': '.__('admin.clients.billing_type_'.($identityType === 'company' ? 'company' : 'individual'));
            }
            if ($identityType === 'company') {
                $copyLines[] = __('admin.clients.company').': '.($client->company_name ?: '');
                $copyLines[] = __('admin.clients.billing_tax_office').': '.($client->tax_office ?: '');
                $copyLines[] = __('common.form.tax_id').': '.($client->tax_id ?: '');
            }
            if ($identityType === 'individual') {
                $copyLines[] = __('admin.clients.billing_national_id').': '.($client->national_id ?: '');
            }
            $copyLines[] = trim(($client->address1 ?: '').' '.($client->city ?: '').' '.($client->postcode ?: '').' '.($client->country ?: ''));
            $copyLines[] = $client->full_phone;
            $copyLines[] = $client->email;
            $copyText = implode("\n", array_filter($copyLines));
            $addressCopy = trim(($client->address1 ?: '').' '.($client->city ?: '').($client->state ? ', '.$client->state : '').' '.($client->postcode ?: '').' '.($client->country ?: ''));
            $ownerLogin = $client->owner();
            $owes = (float) $unpaidInvoices > 0.009;
            $paid   = isset($invoiceSummary) ? ($invoiceSummary['paid'] ?? 0) : 0;
            $unpaid = isset($invoiceSummary) ? ($invoiceSummary['unpaid'] ?? 0) : 0;
            $ovrd   = isset($invoiceSummary) ? ($invoiceSummary['overdue'] ?? 0) : 0;
            $total  = isset($invoiceSummary) ? ($invoiceSummary['total'] ?? 0) : 0;
            $consent = \App\Models\MarketingConsent::where('client_id', $client->id)->first();
            $recentServices = method_exists($client, 'services')
                ? $client->services()->with('product')->latest('id')->take(5)->get()
                : collect();
            $recentDomains = method_exists($client, 'domains')
                ? $client->domains()->latest('id')->take(5)->get()
                : collect();
        @endphp

        <div class="client-summary-panels">
            {{-- Column 1: Clients Information, Custom Fields & Contacts --}}
            <div>
                <div class="clientssummarybox">
                    <div class="title">{{ __('admin.clients.client_information') }}</div>
                    @if($identityGaps)
                    <div style="margin:6px;padding:6px 8px;background:#fcf8e3;border:1px solid #faebcc;border-radius:3px;color:#8a6d3b;font-size:11px;">
                        {{ __('admin.clients.billing_missing', ['fields' => implode(', ', \App\Support\BillingIdentity::labels($identityGaps))]) }}
                    </div>
                    @endif
                    <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                        <tr>
                            <td>{{ __('admin.clients.name') }}</td>
                            <td><div style="display:flex;align-items:center;justify-content:space-between;gap:6px;"><strong>{{ $client->full_name }}</strong><x-copy-value :value="$client->full_name" /></div></td>
                        </tr>
                        <tr class="altrow">
                            <td>{{ __('admin.clients.billing_type') }}</td>
                            <td>{{ $identityType ? __('admin.clients.billing_type_'.($identityType === 'company' ? 'company' : 'individual')) : '-' }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('admin.clients.company') }}</td>
                            <td><div style="display:flex;align-items:center;justify-content:space-between;gap:6px;"><span>{{ $client->company_name ?: '-' }}</span><x-copy-value :value="$client->company_name" /></div></td>
                        </tr>
                        @if($identityType === 'company')
                        <tr class="altrow">
                            <td>{{ __('admin.clients.billing_tax_office') }}</td>
                            <td><div style="display:flex;align-items:center;justify-content:space-between;gap:6px;"><span>{{ $client->tax_office ?: '-' }}</span><x-copy-value :value="$client->tax_office" /></div></td>
                        </tr>
                        @endif
                        <tr>
                            <td>{{ __('common.form.tax_id') }}</td>
                            <td><div style="display:flex;align-items:center;justify-content:space-between;gap:6px;"><span>{{ $client->tax_id ?: '-' }}</span><x-copy-value :value="$client->tax_id" /></div></td>
                        </tr>
                        @if($identityType === 'individual')
                        <tr class="altrow">
                            <td>{{ __('admin.clients.billing_national_id') }}</td>
                            <td><div style="display:flex;align-items:center;justify-content:space-between;gap:6px;"><span>{{ $client->national_id ?: '-' }}</span><x-copy-value :value="$client->national_id" /></div></td>
                        </tr>
                        @endif
                        <tr>
                            <td>{{ __('admin.clients.email') }}</td>
                            <td><div style="display:flex;align-items:center;justify-content:space-between;gap:6px;"><a href="mailto:{{ $client->email }}">{{ $client->email }}</a><x-copy-value :value="$client->email" /></div></td>
                        </tr>
                        @if($ownerLogin)
                        <tr class="altrow">
                            <td>{{ __('admin.clients.email_verification') }}</td>
                            <td>
                                @if($ownerLogin->hasVerifiedEmail())
                                <span class="badge-active">{{ __('admin.clients.email_verified', ['date' => $ownerLogin->email_verified_at->format(date_fmt())]) }}</span>
                                @else
                                <span style="color:#a94442;">{{ __('admin.clients.email_unverified') }}</span>
                                @if(auth('admin')->user()?->hasPermission('edit_clients'))
                                <form method="POST" action="{{ route('admin.clients.verify-email', $client) }}" style="display:inline;margin-left:4px;">
                                    @csrf
                                    <button type="submit" class="btn btn-default btn-xs" onclick="return pnConfirm(event, @js(__('admin.clients.verify_email_confirm', ['email' => $ownerLogin->email])))">{{ __('admin.clients.verify_email') }}</button>
                                </form>
                                @endif
                                @endif
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td>{{ __('common.form.billing_email') }}</td>
                            <td><div style="display:flex;align-items:center;justify-content:space-between;gap:6px;"><span>@if($client->billing_email)<a href="mailto:{{ $client->billing_email }}">{{ $client->billing_email }}</a>@else - @endif</span><x-copy-value :value="$client->billing_email" /></div></td>
                        </tr>
                        <tr class="altrow">
                            <td>{{ __('admin.clients.phone') }}</td>
                            <td><div style="display:flex;align-items:center;justify-content:space-between;gap:6px;"><span>{{ $client->full_phone ?: '-' }}</span><x-copy-value :value="$client->full_phone" /></div></td>
                        </tr>
                        <tr>
                            <td>{{ __('admin.clients.address') }}</td>
                            <td><div style="display:flex;align-items:center;justify-content:space-between;gap:6px;"><span>{{ $client->address1 ?: '-' }}@if($client->city)<br>{{ $client->city }}{{ $client->state ? ', '.$client->state : '' }} {{ $client->postcode }}@endif</span><x-copy-value :value="$addressCopy" /></div></td>
                        </tr>
                        <tr class="altrow">
                            <td>{{ __('admin.clients.country') }}</td>
                            <td>{{ $client->country ?: '-' }}</td>
                        </tr>
                    </table>
                    <ul>
                        <li>
                            <button type="button" class="whmcs-link-btn" data-billing-copy data-copy-text="{{ $copyText }}" title="{{ __('admin.clients.billing_copy') }}" aria-label="{{ __('admin.clients.billing_copy') }}">
                                <i class="far fa-copy" aria-hidden="true"></i> {{ __('common.actions.copy') }}
                            </button>
                        </li>
                        <li>
                            <a href="{{ route('admin.clients.edit', $client) }}">
                                <img src="{{ asset('admin-assets/images/icons/clientsadd.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ __('admin.clients.edit_client') }}
                            </a>
                        </li>
                    </ul>
                </div>

                @if($customFields->isNotEmpty())
                <div class="clientssummarybox">
                    <div class="title">{{ __('admin.clients.custom_fields') }}</div>
                    <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                        @foreach($customFields as $field)
                        <tr @if($loop->even) class="altrow" @endif>
                            <td>{{ $field->field_name }}</td>
                            <td>{{ $field->values->first()?->value ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>
                @endif

                <div class="clientssummarybox">
                    <div class="title">{{ \Illuminate\Support\Facades\Lang::has('client.nav.contacts') ? __('client.nav.contacts') : 'Contacts' }}</div>
                    <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                        @forelse($client->users as $usr)
                        <tr @if($loop->even) class="altrow" @endif>
                            <td>{{ trim(($usr->first_name ?? '').' '.($usr->last_name ?? '')) ?: $usr->email }}</td>
                            <td>{{ $usr->email }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" style="text-align:center;color:#888;">-</td>
                        </tr>
                        @endforelse
                    </table>
                </div>
            </div>

            {{-- Column 2: Invoices/Billing & Other Information --}}
            <div>
                <div class="clientssummarybox">
                    <div class="title">{{ __('admin.clients.billing_summary') }}</div>
                    <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                        <tr>
                            <td>{{ __('admin.clients.paid_invoices') }}</td>
                            <td><strong style="color:#3c763d;">{{ $paid }}</strong></td>
                        </tr>
                        <tr class="altrow">
                            <td>{{ __('admin.clients.unpaid_invoices') }}</td>
                            <td>
                                <strong @if($owes) style="color:#d9534f;" @endif>{{ $unpaid }} ({{ money_fmt($unpaidInvoices) }})</strong>
                            </td>
                        </tr>
                        <tr>
                            <td>{{ __('admin.clients.overdue_invoices') }}</td>
                            <td><strong style="color:#d9534f;">{{ $ovrd }}</strong></td>
                        </tr>
                        <tr class="altrow">
                            <td>{{ __('admin.clients.total_invoices') }}</td>
                            <td><strong>{{ $invoiceCount }}</strong></td>
                        </tr>
                        <tr>
                            <td>{{ __('admin.clients.credit_balance') }}</td>
                            <td><strong style="color:#3c763d;">{{ money_fmt($client->credit) }}</strong></td>
                        </tr>
                        @if($cardRequestOffered || $storedCardCount > 0)
                        <tr class="altrow">
                            <td>{{ __('admin.clients.stored_cards') }}</td>
                            <td>
                                {{ $storedCardCount }}
                                @if($cardRequestOffered && auth('admin')->user()?->hasPermission('edit_clients'))
                                <form method="POST" action="{{ route('admin.clients.card-request', $client) }}" style="display:inline;margin-left:4px;">
                                    @csrf
                                    <button type="submit" class="btn btn-default btn-xs" title="{{ __('admin.clients.card_request_hint') }}" onclick="return pnConfirm(event, @js(__('admin.clients.card_request_confirm', ['email' => $client->email])))">{{ __('admin.clients.card_request') }}</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endif
                    </table>
                    <ul>
                        <li>
                            <a href="{{ route('admin.invoices.create', ['client_id' => $client->id]) }}">
                                <img src="{{ asset('admin-assets/images/icons/invoicesedit.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ __('admin.clients.new_invoice_link') }}
                            </a>
                        </li>
                        @if(auth('admin')->user()?->hasPermission('manage_invoices'))
                        <li>
                            <a href="{{ route('admin.clients.credit', $client) }}">
                                <img src="{{ asset('admin-assets/images/icons/income.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ __('admin.clients.credit_manage') }}
                            </a>
                        </li>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('admin.quotes.create'))
                        <li>
                            <a href="{{ route('admin.quotes.create', ['client_id' => $client->id]) }}">
                                <img src="{{ asset('admin-assets/images/icons/quotes.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ \Illuminate\Support\Facades\Lang::has('admin.quotes.create') ? __('admin.quotes.create') : 'Create Quote' }}
                            </a>
                        </li>
                        @elseif(\Illuminate\Support\Facades\Route::has('admin.quotes.index'))
                        <li>
                            <a href="{{ route('admin.quotes.index', ['client_id' => $client->id]) }}">
                                <img src="{{ asset('admin-assets/images/icons/quotes.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ \Illuminate\Support\Facades\Lang::has('admin.quotes.title') ? __('admin.quotes.title') : 'Quotes' }}
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>

                <div class="clientssummarybox">
                    <div class="title">{{ __('admin.clients.other_info') }}</div>
                    <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                        <tr>
                            <td>{{ __('admin.clients.status') }}</td>
                            <td><span class="whmcs-status badge-{{ strtolower($client->status->value) }}">{{ status_label($client->status->value) }}</span></td>
                        </tr>
                        <tr class="altrow">
                            <td>{{ __('admin.clients.registered') }}</td>
                            <td>{{ $client->created_at->format(date_fmt()) }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('admin.clients.billing_type') }}</td>
                            <td>{{ $identityType ? __('admin.clients.billing_type_'.($identityType === 'company' ? 'company' : 'individual')) : '-' }}</td>
                        </tr>
                        <tr class="altrow">
                            <td>{{ __('admin.clients.tax_exempt') }}</td>
                            <td>{{ $client->tax_exempt ? 'Yes' : 'No' }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('admin.clients.marketing_consent') }}</td>
                            <td>@if($consent?->email_opt_in){{ __('admin.clients.marketing_yes', ['date' => $consent->consented_at?->format(date_fmt()) ?? '-', 'source' => $consent->source]) }}@elseif($consent){{ __('admin.clients.marketing_withdrawn', ['date' => $consent->withdrawn_at?->format(date_fmt()) ?? '-']) }}@else{{ __('admin.clients.marketing_no') }}@endif</td>
                        </tr>
                        <tr class="altrow">
                            <td>{{ __('admin.clients.last_login') }}</td>
                            <td>{{ $client->users->max('last_login')?->diffForHumans() ?? __('admin.clients.never') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Column 3: Products/Services & Recent Services / Domains --}}
            <div>
                <div class="clientssummarybox">
                    <div class="title">{{ __('admin.clients.tab_services') }}</div>
                    <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                        <tr>
                            <td>{{ __('admin.clients.services') }}</td>
                            <td><a href="{{ route('admin.clients.show', ['client' => $client, 'tab' => 'services']) }}"><strong>{{ $serviceCount }}</strong></a></td>
                        </tr>
                        <tr class="altrow">
                            <td>{{ __('admin.clients.domains') }}</td>
                            <td><a href="{{ route('admin.clients.show', ['client' => $client, 'tab' => 'domains']) }}"><strong>{{ $domainCount }}</strong></a></td>
                        </tr>
                        <tr>
                            <td>{{ __('admin.clients.tickets') }}</td>
                            <td><a href="{{ route('admin.clients.show', ['client' => $client, 'tab' => 'tickets']) }}"><strong>{{ $ticketCount }}</strong></a></td>
                        </tr>
                        <tr class="altrow">
                            <td>{{ __('admin.clients.invoices') }}</td>
                            <td><a href="{{ route('admin.clients.show', ['client' => $client, 'tab' => 'invoices']) }}"><strong>{{ $invoiceCount }}</strong></a></td>
                        </tr>
                    </table>
                    <ul>
                        @if(\Illuminate\Support\Facades\Route::has('admin.orders.index'))
                        <li>
                            <a href="{{ route('admin.orders.index', ['client_id' => $client->id]) }}">
                                <img src="{{ asset('admin-assets/images/icons/orders.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ \Illuminate\Support\Facades\Lang::has('admin.orders.title') ? __('admin.orders.title') : 'View Orders' }}
                            </a>
                        </li>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('admin.orders.create'))
                        <li>
                            <a href="{{ route('admin.orders.create', ['client_id' => $client->id]) }}">
                                <img src="{{ asset('admin-assets/images/icons/ordersadd.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ \Illuminate\Support\Facades\Lang::has('admin.orders.add_new') ? __('admin.orders.add_new') : 'Add New Order' }}
                            </a>
                        </li>
                        @endif
                        <li>
                            <a href="{{ route('admin.tickets.create', ['client' => $client->id]) }}">
                                <img src="{{ asset('admin-assets/images/icons/ordersadd.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ __('admin.clients.new_ticket_link') }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="clientssummarybox">
                    <div class="title">{{ __('admin.clients.services') }} &amp; {{ __('admin.clients.domains') }}</div>
                    <table class="clientssummarystats" cellspacing="0" cellpadding="2">
                        @forelse($recentServices as $rSvc)
                        <tr @if($loop->even) class="altrow" @endif>
                            <td>
                                <a href="{{ route('admin.services.show', $rSvc) }}">{{ $rSvc->product?->name ?? 'N/A' }}</a>
                                @if($rSvc->domain)<div style="font-size:11px;color:#777;">{{ $rSvc->domain }}</div>@endif
                            </td>
                            <td style="text-align:right;"><span class="whmcs-status badge-{{ strtolower($rSvc->status) }}">{{ status_label($rSvc->status) }}</span></td>
                        </tr>
                        @empty
                        @endforelse
                        @forelse($recentDomains as $rDom)
                        <tr @if($loop->odd) class="altrow" @endif>
                            <td><a href="{{ route('admin.domains.show', $rDom) }}">{{ $rDom->domain }}</a></td>
                            <td style="text-align:right;"><span class="whmcs-status badge-{{ strtolower($rDom->status) }}">{{ status_label($rDom->status) }}</span></td>
                        </tr>
                        @empty
                        @endforelse
                        @if($recentServices->isEmpty() && $recentDomains->isEmpty())
                        <tr>
                            <td colspan="2" style="text-align:center;color:#888;">{{ __('admin.services.no_services') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Column 4: Other Actions & Admin Notes --}}
            <div>
                <div class="clientssummarybox">
                    <div class="title">{{ __('admin.clients.quick_actions') }}</div>
                    <ul>
                        <li>
                            <form method="POST" action="{{ route('admin.clients.impersonate', $client) }}">
                                @csrf
                                <button type="submit" class="whmcs-link-btn" onclick="return pnConfirm(event, @js(__('admin.clients.confirm_login_as')))">
                                    <img src="{{ asset('admin-assets/images/icons/clientlogin.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ __('admin.clients.login_as_client') }}
                                </button>
                            </form>
                        </li>
                        <li>
                            <a href="{{ route('admin.clients.edit', $client) }}">
                                <img src="{{ asset('admin-assets/images/icons/clientsadd.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ __('admin.clients.edit_client_btn') }}
                            </a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('admin.clients.personal-data', $client) }}" title="{{ __('admin.clients.personal_data_hint') }}">
                                @csrf
                                <button type="submit" class="whmcs-link-btn">
                                    <img src="{{ asset('admin-assets/images/icons/orders.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ __('admin.clients.personal_data') }}
                                </button>
                            </form>
                        </li>
                        <li>
                            <a href="{{ route('admin.clients.show', ['client' => $client, 'tab' => 'notes']) }}">
                                <img src="{{ asset('admin-assets/images/icons/invoicesedit.png') }}" border="0" align="absmiddle" alt="{{ '' }}" /> {{ __('admin.clients.add_note_link') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.clients.index') }}">{{ __('common.actions.close') }}</a>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" onsubmit="return pnConfirm(event, @js(__('admin.clients.confirm_delete')))">
                                @csrf @method('DELETE')
                                <button type="submit" class="whmcs-link-btn" style="color:#a94442;">
                                    {{ __('common.actions.delete') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>

                <div class="clientssummarybox">
                    <div class="title">{{ __('admin.clients.admin_notes') }}</div>
                    <div style="padding:8px;">
                        <form method="POST" action="{{ route('admin.clients.notes.store', $client) }}">
                            @csrf
                            <textarea name="note" rows="3" required placeholder="{{ __('admin.clients.type_note') }}" class="form-control" style="font-size:12px;resize:vertical;margin-bottom:6px;"></textarea>
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <label style="font-size:12px;display:inline-flex;align-items:center;gap:4px;cursor:pointer;margin:0;font-weight:400;">
                                    <input type="checkbox" name="sticky" value="1"> {{ __('admin.clients.sticky') }}
                                </label>
                                <button type="submit" class="btn btn-primary btn-xs">{{ __('admin.clients.add_note') }}</button>
                            </div>
                        </form>
                        @forelse(($notes ?? collect())->take(3) as $note)
                        <div style="margin-top:8px;padding:6px 8px;background:{{ $note->sticky ? '#fffbe6' : '#f9f9f9' }};border:1px solid {{ $note->sticky ? '#e6d200' : '#e5e5e5' }};border-radius:3px;font-size:11px;">
                            <p style="margin:0 0 3px;color:#333;">{{ $note->note }}</p>
                            <span style="color:#888;">{{ $note->created_at->timezone(display_tz())->format(datetime_fmt()) }}{{ $note->sticky ? ' — ' . __('admin.clients.pinned') : '' }}</span>
                        </div>
                        @empty
                        <p style="font-size:11px;color:#888;margin:8px 0 0;">{{ __('admin.clients.no_notes') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        @elseif($tab === 'services')

        <div class="context-btn-container" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <strong>{{ __('admin.clients.tab_services') }}</strong>
            <button type="button" class="btn btn-primary btn-sm" onclick="var f=document.getElementById('add-service-form');f.style.display=f.style.display==='none'?'block':'none';">{{ __('admin.clients.add_service') }}</button>
        </div>

        <div id="add-service-form" style="display:none;margin-bottom:16px;">
            <div class="infobox">{{ __('admin.clients.add_service_hint') }}</div>
            @if($errors->any())
            <div class="alert alert-danger" style="font-size:13px;">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('admin.clients.services.store', $client) }}">
                @csrf
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td width="15%" class="fieldlabel">{{ __('common.table.product') }} <span style="color:#d9534f;">*</span></td>
                        <td width="35%" class="fieldarea">
                            <select name="product_id" class="form-control select-inline" required>
                                <option value="">—</option>
                                @foreach($products as $p)
                                <option value="{{ $p->id }}" @selected(old('product_id')==$p->id)>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td width="15%" class="fieldlabel">{{ __('admin.clients.service_server') }}</td>
                        <td width="35%" class="fieldarea">
                            <select name="server_id" id="svc-server" class="form-control select-inline">
                                <option value="">{{ __('admin.clients.service_no_server') }}</option>
                                @foreach($servers as $s)
                                <option value="{{ $s->id }}" @selected(old('server_id')==$s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.table.domain') }}</td>
                        <td class="fieldarea">
                            <input type="text" name="domain" value="{{ old('domain') }}" class="form-control input-250" placeholder="{{ 'example.com' }}">
                        </td>
                        <td class="fieldlabel">{{ __('common.table.billing_cycle') }} <span style="color:#d9534f;">*</span></td>
                        <td class="fieldarea">
                            <select name="billing_cycle" class="form-control select-inline" required>
                                @foreach(['Monthly','Quarterly','Semi-Annually','Annually','Biennially','Triennially','One-Time'] as $c)
                                <option value="{{ $c }}" @selected(old('billing_cycle')==$c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.table.amount') }} <span style="color:#d9534f;">*</span></td>
                        <td class="fieldarea">
                            <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', '0.00') }}" class="form-control input-150" required>
                        </td>
                        <td class="fieldlabel">{{ __('admin.clients.next_due') }}</td>
                        <td class="fieldarea">
                            <input type="date" name="next_due_date" value="{{ old('next_due_date') }}" class="form-control input-200">
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.table.status') }} <span style="color:#d9534f;">*</span></td>
                        <td class="fieldarea">
                            <select name="status" class="form-control select-inline" required>
                                <option value="active" @selected(old('status','active')=='active')>{{ __('common.status.active') }}</option>
                                <option value="pending" @selected(old('status')=='pending')>{{ __('common.status.pending') }}</option>
                                <option value="suspended" @selected(old('status')=='suspended')>{{ __('common.status.suspended') }}</option>
                                <option value="cancelled" @selected(old('status')=='cancelled')>{{ __('common.status.cancelled') }}</option>
                                <option value="terminated" @selected(old('status')=='terminated')>{{ __('common.status.terminated') }}</option>
                            </select>
                        </td>
                        <td class="fieldlabel">{{ __('admin.clients.provision_now') }}</td>
                        <td class="fieldarea">
                            <label style="display:inline-flex;align-items:flex-start;gap:6px;font-size:12px;cursor:pointer;margin:0;font-weight:400;">
                                <input type="checkbox" name="provision" value="1" id="svc-provision" {{ old('provision') ? 'checked' : '' }} style="margin-top:2px;">
                                <span><strong>{{ __('admin.clients.provision_now') }}</strong><br><span style="color:#777;font-size:11px;">{{ __('admin.clients.provision_now_hint') }}</span></span>
                            </label>
                        </td>
                    </tr>
                    <tr id="link-account-row" style="display:none;">
                        <td class="fieldlabel">{{ __('admin.clients.link_existing') }}</td>
                        <td class="fieldarea" colspan="3">
                            <select name="link_user_id" id="link-user-id" class="form-control select-inline">
                                <option value="">{{ __('admin.clients.link_none') }}</option>
                            </select>
                            <div style="color:#777;font-size:11px;margin-top:3px;">{{ __('admin.clients.link_existing_hint') }}</div>
                        </td>
                    </tr>
                </table>

                <div id="svc-options" style="display:none;margin-top:10px;">
                    <div style="font-weight:600;font-size:13px;color:#1a4d80;margin-bottom:6px;">{{ __('client.cart.configurable_options') }}</div>
                    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                        <tbody id="svc-options-body"></tbody>
                    </table>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">{{ __('admin.clients.add_service') }}</button>
                </div>
            </form>
            <script>
            (function () {
                var opts = @json($productOptions ?? []);
                var product = document.querySelector('#add-service-form select[name=product_id]');
                var box = document.getElementById('svc-options'), body = document.getElementById('svc-options-body');
                if (!product || !box || !body) return;
                var old = @json((array) old('config_options', []));
                function render() {
                    var list = opts[product.value] || [];
                    box.style.display = list.length ? '' : 'none';
                    body.replaceChildren();
                    list.forEach(function (o) {
                        var tr = document.createElement('tr');
                        var tdLabel = document.createElement('td');
                        tdLabel.className = 'fieldlabel';
                        tdLabel.style.width = '15%';
                        tdLabel.textContent = o.name;
                        var tdField = document.createElement('td');
                        tdField.className = 'fieldarea';
                        var name = 'config_options[' + o.id + ']', prev = old[o.id];
                        if (o.type === 'choice') {
                            var sel = document.createElement('select');
                            sel.name = name;
                            sel.className = 'form-control select-inline';
                            (o.subs || []).forEach(function (s) {
                                var opt = document.createElement('option');
                                opt.value = s.id;
                                if (String(prev) === String(s.id)) opt.selected = true;
                                opt.textContent = s.label;
                                sel.appendChild(opt);
                            });
                            tdField.appendChild(sel);
                        } else if (o.type === 'checkbox') {
                            var lbl = document.createElement('label');
                            lbl.style.cssText = 'font-size:13px;display:inline-flex;gap:6px;align-items:center;margin:0;font-weight:400;';
                            var cb = document.createElement('input');
                            cb.type = 'checkbox';
                            cb.name = name;
                            cb.value = '1';
                            if (prev) cb.checked = true;
                            lbl.appendChild(cb);
                            lbl.appendChild(document.createTextNode(' ' + ((o.subs && o.subs[0]) ? o.subs[0].label : '')));
                            tdField.appendChild(lbl);
                        } else {
                            var inp = document.createElement('input');
                            inp.type = 'number';
                            inp.name = name;
                            inp.className = 'form-control input-150';
                            if (o.min !== undefined && o.min !== null) inp.min = o.min;
                            if (o.max) inp.max = o.max;
                            inp.value = (prev !== undefined ? prev : o.min);
                            tdField.appendChild(inp);
                        }
                        tr.appendChild(tdLabel);
                        tr.appendChild(tdField);
                        body.appendChild(tr);
                    });
                }
                product.addEventListener('change', render);
                render();
            })();
            </script>
            <script>
            (function () {
                var srv = document.getElementById('svc-server'),
                    link = document.getElementById('link-user-id'),
                    row = document.getElementById('link-account-row'),
                    prov = document.getElementById('svc-provision');
                if (!srv || !link || !row) return;
                var base = "{{ url('admin/servers') }}";
                function toggleRow() { row.style.display = (prov && prov.checked) ? 'none' : (srv.value ? '' : 'none'); }
                function resetLinkOptions() {
                    link.replaceChildren();
                    var defOpt = document.createElement('option');
                    defOpt.value = '';
                    defOpt.textContent = @json(__('admin.clients.link_none'));
                    link.appendChild(defOpt);
                }
                function loadAccounts() {
                    resetLinkOptions();
                    if (!srv.value) { toggleRow(); return; }
                    fetch(base + '/' + srv.value + '/accounts', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            (d.accounts || []).forEach(function (a) {
                                var o = document.createElement('option');
                                o.value = a.id;
                                o.textContent = a.username + (a.email ? ' (' + a.email + ')' : '') + (a.status && a.status !== 'active' ? ' — ' + a.status : '');
                                link.appendChild(o);
                            });
                        })
                        .catch(function () {});
                    toggleRow();
                }
                srv.addEventListener('change', loadAccounts);
                if (prov) prov.addEventListener('change', toggleRow);
                if (srv.value) loadAccounts();
            })();
            </script>
        </div>

        <div class="tablebg">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th>{{ __('common.table.product') }}</th>
                        <th>{{ __('common.table.domain') }}</th>
                        <th>{{ __('common.table.billing_cycle') }}</th>
                        <th>{{ __('common.table.amount') }}</th>
                        <th>{{ __('admin.clients.next_due') }}</th>
                        <th>{{ __('common.table.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($services as $service)
                    <tr>
                        <td><a href="{{ route('admin.services.show', $service) }}" style="font-weight:600;">{{ $service->product?->name ?? 'N/A' }}</a></td>
                        <td>{{ $service->domain ?? '-' }}</td>
                        <td>{{ cycle_label($service->billing_cycle) }}</td>
                        <td>{{ money_fmt($service->amount) }}</td>
                        <td>{{ $service->next_due_date?->format(date_fmt()) ?? '-' }}</td>
                        <td><span class="whmcs-status badge-{{ strtolower($service->status) }}">{{ status_label($service->status) }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:#888;padding:24px;">{{ __('admin.services.no_services') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(!$services->isEmpty())
        <div style="margin-top:10px;">{{ $services->appends(['tab' => 'services'])->links() }}</div>
        @endif

        @elseif($tab === 'domains')

        <div class="context-btn-container" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <strong>{{ __('admin.clients.tab_domains') }}</strong>
            <button type="button" class="btn btn-primary btn-sm" onclick="var f=document.getElementById('add-domain-form');f.style.display=f.style.display==='none'?'block':'none';">{{ __('admin.clients.add_domain') }}</button>
        </div>

        <div id="add-domain-form" style="display:{{ $errors->any() ? 'block' : 'none' }};margin-bottom:16px;">
            <div class="infobox">{{ __('admin.clients.add_domain_hint') }}</div>
            @if($errors->any())
            <div class="alert alert-danger" style="font-size:13px;">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('admin.clients.domains.store', $client) }}">
                @csrf
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td width="15%" class="fieldlabel">{{ __('common.table.domain') }} <span style="color:#d9534f;">*</span></td>
                        <td width="35%" class="fieldarea">
                            <input type="text" name="domain" value="{{ old('domain') }}" class="form-control input-250" placeholder="{{ 'example.com' }}" required>
                        </td>
                        <td width="15%" class="fieldlabel">{{ __('admin.clients.domain_registrar') }}</td>
                        <td width="35%" class="fieldarea">
                            <select name="registrar" class="form-control select-inline">
                                <option value="">{{ __('admin.clients.domain_registrar_placeholder') }}</option>
                                @foreach($registrars as $key => $label)
                                <option value="{{ $key }}" @selected(old('registrar') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.clients.registered') }}</td>
                        <td class="fieldarea">
                            <input type="date" name="registration_date" value="{{ old('registration_date') }}" class="form-control input-200">
                        </td>
                        <td class="fieldlabel">{{ __('admin.domains.expiry_date') }}</td>
                        <td class="fieldarea">
                            <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="form-control input-200">
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.clients.next_due') }}</td>
                        <td class="fieldarea">
                            <input type="date" name="next_due_date" value="{{ old('next_due_date') }}" class="form-control input-200">
                        </td>
                        <td class="fieldlabel">{{ __('admin.clients.domain_recurring') }} <span style="color:#d9534f;">*</span></td>
                        <td class="fieldarea">
                            <input type="number" step="0.01" min="0" name="recurring_amount" value="{{ old('recurring_amount', '0.00') }}" class="form-control input-150" required>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.clients.domain_first_payment') }}</td>
                        <td class="fieldarea">
                            <input type="number" step="0.01" min="0" name="first_payment_amount" value="{{ old('first_payment_amount') }}" class="form-control input-150">
                        </td>
                        <td class="fieldlabel">{{ __('common.table.status') }} <span style="color:#d9534f;">*</span></td>
                        <td class="fieldarea">
                            <select name="status" class="form-control select-inline" required>
                                <option value="active" @selected(old('status','active')=='active')>{{ __('common.status.active') }}</option>
                                <option value="grace" @selected(old('status')=='grace')>{{ __('admin.domain_pricing.grace') }}</option>
                                <option value="pending" @selected(old('status')=='pending')>{{ __('common.status.pending') }}</option>
                                <option value="expired" @selected(old('status')=='expired')>{{ __('common.status.expired') }}</option>
                                <option value="cancelled" @selected(old('status')=='cancelled')>{{ __('common.status.cancelled') }}</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <div style="font-size:12px;color:#777;margin:6px 0;">{{ __('admin.clients.add_domain_renewal_note') }}</div>
                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">{{ __('admin.clients.add_domain') }}</button>
                </div>
            </form>
        </div>

        <div class="tablebg">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th>{{ __('common.table.domain') }}</th>
                        <th>{{ __('common.table.registrar') }}</th>
                        <th>{{ __('admin.clients.registered') }}</th>
                        <th>{{ __('admin.domains.expiry_date') }}</th>
                        <th>{{ __('common.table.status') }}</th>
                        <th style="text-align:right;">{{ __('common.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($domains as $domain)
                    <tr>
                        <td style="font-weight:600;"><a href="{{ route('admin.domains.show', $domain) }}">{{ $domain->domain }}</a></td>
                        <td>{{ $domain->registrar ?? '-' }}</td>
                        <td>{{ $domain->registration_date?->format(date_fmt()) ?? '-' }}</td>
                        <td>{{ $domain->expiry_date?->format(date_fmt()) ?? '-' }}</td>
                        <td><span class="whmcs-status badge-{{ strtolower($domain->status) }}">{{ status_label($domain->status) }}</span></td>
                        <td style="text-align:right;">
                            <a href="{{ route('admin.domains.show', $domain) }}" class="btn btn-default btn-xs">{{ __('common.actions.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:#888;padding:24px;">{{ __('admin.domains.no_domains') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(!$domains->isEmpty())
        <div style="margin-top:10px;">{{ $domains->appends(['tab' => 'domains'])->links() }}</div>
        @endif

        @elseif($tab === 'invoices')

        <div class="context-btn-container" style="display:flex;justify-content:flex-end;margin-bottom:10px;">
            <a href="{{ route('admin.invoices.create', ['client_id' => $client->id]) }}" class="btn btn-primary btn-sm">{{ __('admin.clients.new_invoice_link') }}</a>
        </div>

        <div class="tablebg">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th>{{ __('common.table.invoice_num') }}</th>
                        <th>{{ __('common.table.date') }}</th>
                        <th>{{ __('common.table.due_date') }}</th>
                        <th>{{ __('common.table.total') }}</th>
                        <th>{{ __('common.table.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td><a href="{{ route('admin.invoices.show', $inv) }}" style="font-weight:600;">{{ $inv->invoice_num }}</a></td>
                        <td>{{ $inv->date?->format(date_fmt()) ?? '-' }}</td>
                        <td>{{ $inv->due_date?->format(date_fmt()) ?? '-' }}</td>
                        <td style="font-weight:600;">{{ money_fmt($inv->total) }}</td>
                        <td><span class="whmcs-status badge-{{ strtolower($inv->status) }}">{{ invoice_status_label($inv->status) }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;color:#888;padding:24px;">{{ __('admin.invoices.no_invoices') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(!$invoices->isEmpty())
        <div style="margin-top:10px;">{{ $invoices->appends(['tab' => 'invoices'])->links() }}</div>
        @endif

        @elseif($tab === 'tickets')

        <div class="context-btn-container" style="display:flex;justify-content:flex-end;margin-bottom:10px;">
            <a href="{{ route('admin.tickets.create', ['client' => $client->id]) }}" class="btn btn-primary btn-sm">{{ __('admin.clients.new_ticket_link') }}</a>
        </div>

        <div class="tablebg">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th>{{ __('common.table.id') }}</th>
                        <th>{{ __('common.table.department') }}</th>
                        <th>{{ __('common.table.subject') }}</th>
                        <th>{{ __('common.table.priority') }}</th>
                        <th>{{ __('common.table.last_reply') }}</th>
                        <th>{{ __('common.table.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td style="font-family:monospace;font-size:12px;">{{ $ticket->tid }}</td>
                        <td>{{ $ticket->department->name ?? '-' }}</td>
                        <td><a href="{{ route('admin.tickets.show', $ticket) }}" style="font-weight:600;">{{ $ticket->title }}</a></td>
                        <td>{{ ucfirst($ticket->priority) }}</td>
                        <td>{{ $ticket->last_reply?->diffForHumans() ?? '-' }}</td>
                        <td><span class="whmcs-status badge-{{ strtolower($ticket->status) }}">{{ status_label($ticket->status) }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:#888;padding:24px;">{{ __('admin.tickets.no_tickets') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(!$tickets->isEmpty())
        <div style="margin-top:10px;">{{ $tickets->appends(['tab' => 'tickets'])->links() }}</div>
        @endif

        @elseif($tab === 'notes')

        <form method="POST" action="{{ route('admin.clients.notes.store', $client) }}" style="margin-bottom:16px;">
            @csrf
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('admin.clients.add_note') }}</td>
                    <td class="fieldarea">
                        <textarea name="note" rows="4" required placeholder="{{ __('admin.clients.type_your_note') }}" class="form-control"></textarea>
                        <div style="margin-top:6px;">
                            <label style="font-size:12px;display:inline-flex;align-items:center;gap:5px;cursor:pointer;margin:0;font-weight:400;">
                                <input type="checkbox" name="sticky" value="1"> {{ __('admin.clients.sticky_note') }}
                            </label>
                        </div>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary">{{ __('admin.clients.add_note') }}</button>
            </div>
        </form>

        @forelse($notes as $note)
        <div class="note-card"
             data-update-url="{{ route('admin.clients.notes.update', [$client, $note]) }}"
             data-delete-url="{{ route('admin.clients.notes.destroy', [$client, $note]) }}"
             data-sticky="{{ $note->sticky ? '1' : '0' }}"
             style="background:{{ $note->sticky ? '#fffdf2' : '#ffffff' }};border:1px solid #d5dce5;{{ $note->sticky ? 'border-left:4px solid #f0ad4e;' : '' }}padding:10px 14px;margin-bottom:8px;border-radius:3px;">
            <div style="margin-bottom:6px;">
                <span style="font-size:11px;color:#6c757d;">
                    {{ __('admin.clients.author') }} <span style="color:#1a4d80;font-weight:600;">{{ $note->admin }}</span> · {{ $note->created_at->timezone(display_tz())->format(datetime_fmt()) }}
                    @if($note->sticky)<span style="color:#f0ad4e;"> · {{ __('admin.clients.pinned') }}</span>@endif
                </span>
                @if($note->edited_by)
                <div style="font-size:11px;color:#6c757d;margin-top:2px;">
                    {{ __('admin.clients.edited_by') }} <span style="color:#1a4d80;font-weight:600;">{{ $note->edited_by }}</span> · {{ $note->updated_at->timezone(display_tz())->format(datetime_fmt()) }}
                </div>
                @endif
            </div>
            <textarea class="note-edit form-control" rows="2" style="font-size:13px;">{{ $note->note }}</textarea>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
                <span class="note-status" style="font-size:11px;color:#46a546;"></span>
                <button type="button" class="note-delete btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
            </div>
        </div>
        @empty
        <div class="infobox">{{ __('admin.clients.no_notes') }}</div>
        @endforelse

        <script>
        (function () {
            var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

            function post(url, method, data) {
                return fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(data),
                }).then(function (r) { return r.json(); });
            }

            document.querySelectorAll('.note-card').forEach(function (card) {
                var area = card.querySelector('.note-edit');
                var del = card.querySelector('.note-delete');
                var status = card.querySelector('.note-status');
                var original = area.value;

                function flash(ok, text) {
                    if (!status) return;
                    status.textContent = text;
                    status.style.color = ok ? '#46a546' : '#c43c35';
                    clearTimeout(status._t);
                    status._t = setTimeout(function () { status.textContent = ''; }, 2500);
                }

                area.addEventListener('blur', function () {
                    var val = area.value.trim();
                    if (val === '') {
                        area.value = original;
                        return;
                    }
                    if (val === original) return;
                    post(card.getAttribute('data-update-url'), 'POST', { note: val })
                        .then(function (d) {
                            if (d && d.success) {
                                area.value = d.note;
                                original = d.note;
                                flash(true, @json(__('messages.success.note_updated')));
                            } else {
                                area.value = original;
                                flash(false, @json(__('messages.error.note_update_failed')));
                            }
                        })
                        .catch(function () {
                            area.value = original;
                            flash(false, @json(__('messages.error.note_update_failed')));
                        });
                });

                area.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        area.value = original;
                        area.blur();
                    }
                });

                del.addEventListener('click', function () {
                    pnDialog.confirm(@js(__('admin.confirm_delete')), { danger: true }).then(function (yes) {
                        if (!yes) return;
                        post(card.getAttribute('data-delete-url'), 'DELETE', {})
                            .then(function (d) {
                                if (d && d.success) {
                                    card.remove();
                                }
                            })
                            .catch(function () {});
                    });
                });
            });
        })();
        </script>

        @elseif($tab === 'log')

        <div class="tablebg">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                <thead>
                    <tr>
                        <th>{{ __('common.table.date') }}</th>
                        <th>{{ __('admin.clients.admin') }}</th>
                        <th>{{ __('admin.clients.action') }}</th>
                        <th>{{ __('common.table.description') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td style="font-size:12px;white-space:nowrap;">{{ $log->created_at->timezone(display_tz())->format(datetime_fmt()) }}</td>
                        <td>{{ $log->admin ?? '-' }}</td>
                        <td>{{ $log->action ?? '-' }}</td>
                        <td>{{ $log->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;color:#888;padding:24px;">{{ __('admin.clients.no_activity') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(!$logs->isEmpty())
        <div style="margin-top:10px;">{{ $logs->appends(['tab' => 'log'])->links() }}</div>
        @endif

        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    function done(button, ok) {
        var icon = ok ? 'fa-check' : 'fa-times';
        var label = ok ? @json(__('admin.clients.billing_copied')) : @json(__('common.actions.copy_failed'));

        if (button.hasAttribute('data-billing-copy')) {
            var savedNodes = Array.prototype.slice.call(button.childNodes);
            var iEl = document.createElement('i');
            iEl.className = 'fas ' + icon;
            iEl.setAttribute('aria-hidden', 'true');
            button.replaceChildren(iEl, document.createTextNode(' ' + label));
            setTimeout(function () { button.replaceChildren.apply(button, savedNodes); }, 1500);
            return;
        }

        var savedIconNodes = Array.prototype.slice.call(button.childNodes);
        var wasTitle = button.getAttribute('title');
        var iconEl = document.createElement('i');
        iconEl.className = 'fas ' + icon;
        button.replaceChildren(iconEl);
        if (! ok) { button.setAttribute('title', label); }
        setTimeout(function () {
            button.replaceChildren.apply(button, savedIconNodes);
            if (wasTitle !== null) { button.setAttribute('title', wasTitle); }
        }, 1500);
    }

    function copy(text, button) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () { done(button, true); }, function () { fallback(text, button); });
            return;
        }

        fallback(text, button);
    }

    function fallback(text, button) {
        var area = document.createElement('textarea');
        area.value = text;
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        var ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        area.remove();
        done(button, ok);
    }

    document.querySelectorAll('[data-copy-value], [data-billing-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var text = button.hasAttribute('data-copy-value')
                ? button.getAttribute('data-copy-value')
                : (button.getAttribute('data-copy-text') || '');
            copy(text, button);
        });
    });
})();
</script>
@endpush
@endsection

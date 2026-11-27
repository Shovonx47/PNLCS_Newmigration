@php
    $isEdit = isset($quote) && $quote && $quote->exists;
    $initialClientType = old('client_type', $isEdit ? ($quote->client_id ? 'existing' : 'new') : 'existing');
    $initialStage = old('stage', $isEdit ? $quote->status : 'Draft');
    $initialDate = old('date', $isEdit && $quote->date ? $quote->date->toDateString() : now()->toDateString());
    $initialValidUntil = old('valid_until', $isEdit && $quote->valid_until ? $quote->valid_until->toDateString() : now()->addDays(30)->toDateString());

    $initialCurrencyId = old(
        'currency_id',
        $isEdit
            ? ($quote->currency_id ?: ($quote->client?->currency_id ?: $defaultCurrency?->id))
            : ($selectedClientOption['currency_id'] ?? $defaultCurrency?->id)
    );
    $initialCountry = old('country', $isEdit ? ($quote->country ?: $defaultCountry) : $defaultCountry);
    $defaultPrefix = \App\Support\Countries::PHONE_PREFIXES[$initialCountry] ?? '+880';
    $initialPhonePrefix = old('phone_prefix', $isEdit ? ($quote->phone_prefix ?: $defaultPrefix) : $defaultPrefix);

    if (old('items') && is_array(old('items'))) {
        $initialItems = collect(old('items'))->map(function ($row) {
            $qty = (float) ($row['quantity'] ?? 1);
            $price = (float) ($row['unit_price'] ?? 0);
            $discPct = (float) ($row['discount_percent'] ?? 0);
            $gross = $qty * $price;
            $discAmt = $gross * ($discPct / 100);
            return [
                'description' => (string) ($row['description'] ?? ''),
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_percent' => $discPct,
                'taxable' => ! empty($row['taxable']),
                'amount' => max(0, $gross - $discAmt),
            ];
        })->values()->all();
    } elseif ($isEdit && $quote->items->isNotEmpty()) {
        $initialItems = $quote->items->map(function ($i) {
            $qty = (float) $i->quantity;
            $price = (float) $i->unit_price;
            $gross = $qty * $price;
            $discAmt = (float) $i->discount;
            $discPct = (float) ($i->discount_percent ?? 0);
            if ($discPct <= 0 && $discAmt > 0 && $gross > 0) {
                $discPct = round(($discAmt / $gross) * 100, 2);
            }
            return [
                'description' => (string) $i->description,
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_percent' => $discPct,
                'taxable' => (bool) $i->taxable,
                'amount' => max(0, $gross - $discAmt),
            ];
        })->values()->all();
    } else {
        $initialItems = [
            ['description' => '', 'quantity' => 1, 'unit_price' => '0.00', 'discount_percent' => '0.00', 'taxable' => false, 'amount' => 0],
        ];
    }

    $currenciesJs = $currencies->map(fn ($c) => [
        'id' => (int) $c->id,
        'code' => (string) $c->code,
        'prefix' => (string) $c->prefix,
        'suffix' => (string) $c->suffix,
    ])->values();

    $stageOptions = $stages ?? ['Draft', 'Delivered', 'On Hold', 'Accepted', 'Lost', 'Dead'];
    if ($isEdit && ! in_array($quote->status, $stageOptions, true)) {
        $stageOptions[] = $quote->status;
    }
@endphp

<style>
    .whmcs-quote-h2 {
        font-size: 16px !important;
        font-weight: 400 !important;
        color: #333 !important;
        margin: 14px 0 8px !important;
        padding: 0 !important;
        border: 0 !important;
    }
    .whmcs-action-bar {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        gap: 4px;
        margin: 12px 0 16px;
    }
    .whmcs-action-bar .btn {
        font-weight: 400;
        padding: 6px 12px;
        font-size: 13px;
        border-radius: 3px;
    }
    .whmcs-action-bar .btn-primary {
        background-color: #337ab7;
        border: 1px solid #2e6da4;
        color: #fff;
    }
    .whmcs-action-bar .btn-primary:hover {
        background-color: #286090;
    }
    .whmcs-action-bar .btn-default {
        background-color: #fff;
        border: 1px solid #ccc;
        color: #333;
    }
    .whmcs-action-bar .btn-default:hover:not([disabled]) {
        background-color: #e6e6e6;
        border-color: #adadad;
    }
    .whmcs-action-bar .btn[disabled] {
        opacity: 0.65;
        cursor: not-allowed;
    }
    .whmcs-date-wrap {
        display: inline-flex;
        align-items: stretch;
        border: 1px solid #ccc;
        border-radius: 3px;
        background: #fff;
        overflow: hidden;
    }
    .whmcs-date-wrap .cal-addon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 9px;
        background: #eee;
        border-right: 1px solid #ccc;
        color: #555;
        font-size: 13px;
    }
    .whmcs-date-wrap input[type="date"] {
        border: 0 !important;
        box-shadow: none !important;
        padding: 4px 8px !important;
        height: 28px;
        width: 145px !important;
        outline: none;
    }
    .whmcs-client-combobox {
        position: relative;
        width: 100%;
        margin: 5px 0 10px;
    }
    .whmcs-client-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 3px;
        min-height: 34px;
    }
    .whmcs-client-input-wrap input {
        width: 100%;
        border: 0 !important;
        box-shadow: none !important;
        padding: 6px 32px 6px 10px !important;
        font-size: 13px;
        outline: none;
        background: transparent;
    }
    .whmcs-client-caret {
        position: absolute;
        right: 10px;
        color: #555;
        pointer-events: none;
        font-size: 11px;
    }
    .whmcs-client-dropdown {
        position: absolute;
        top: calc(100% + 2px);
        left: 0;
        right: 0;
        max-height: 240px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #bbb;
        border-radius: 0 0 4px 4px;
        box-shadow: 0 6px 14px rgba(0,0,0,0.14);
        z-index: 1050;
    }
    .whmcs-client-option {
        padding: 7px 12px;
        font-size: 13px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f3f4f6;
    }
    .whmcs-client-option:hover,
    .whmcs-client-option.active {
        background: #eef4fb;
        color: #1a4d80;
    }
    .whmcs-phone-group {
        position: relative;
        display: inline-flex;
        align-items: stretch;
        border: 1px solid #ccc;
        border-radius: 3px;
        background: #fff;
        max-width: 265px;
        width: 100%;
    }
    .whmcs-phone-flag-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 7px;
        border: 0;
        border-right: 1px solid #e2e2e2;
        background: #f5f5f5;
        color: #333;
        font-size: 12.5px;
        cursor: pointer;
        white-space: nowrap;
    }
    .whmcs-phone-flag-btn:hover {
        background: #ebebeb;
    }
    .whmcs-phone-group input[type="text"] {
        border: 0 !important;
        box-shadow: none !important;
        padding: 4px 8px !important;
        flex: 1;
        min-width: 0;
        outline: none;
    }
    .whmcs-flag-dd {
        position: absolute;
        top: calc(100% + 2px);
        left: 0;
        width: 260px;
        max-height: 215px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #bbb;
        border-radius: 3px;
        box-shadow: 0 6px 14px rgba(0,0,0,0.16);
        z-index: 1080;
    }
    .whmcs-flag-opt {
        padding: 5px 9px;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f4f4f4;
    }
    .whmcs-flag-opt:hover {
        background: #eef4fb;
    }
    .whmcs-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.45);
        z-index: 2100;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    .whmcs-modal-box {
        background: #fff;
        border-radius: 5px;
        width: 100%;
        max-width: 680px;
        max-height: 82vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 12px 32px rgba(0,0,0,0.25);
        overflow: hidden;
    }
    .whmcs-modal-head {
        background: #1a4d80;
        color: #fff;
        padding: 11px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 15px;
        font-weight: 600;
    }
    [x-cloak] { display: none !important; }
</style>

<h1>{{ $isEdit ? ('Quotes #' . $quote->id) : 'Quotes' }}</h1>

@if($errors->any())
<div class="infobox" style="border-left:4px solid #c43c35;background:#f2dede;color:#a94442;margin-bottom:15px;">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

<div x-data="whmcsQuoteManage()">
    <form method="POST" action="{{ $isEdit ? route('admin.quotes.update', $quote) : route('admin.quotes.store') }}" id="quote-manage-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <h2 class="whmcs-quote-h2" style="margin-top:4px !important;">{{ 'General Information' }}</h2>

        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td width="15%" class="fieldlabel">{{ 'Subject' }}</td>
                <td width="35%" class="fieldarea">
                    <input type="text" name="subject" value="{{ old('subject', $isEdit ? $quote->subject : '') }}" required class="form-control">
                </td>
                <td width="15%" class="fieldlabel">{{ 'Stage' }}</td>
                <td width="35%" class="fieldarea">
                    <select name="stage" class="form-control select-inline">
                        @foreach($stageOptions as $st)
                            <option value="{{ $st }}" @selected($initialStage === $st)>{{ $st }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ 'Date Created' }}</td>
                <td class="fieldarea">
                    <div class="whmcs-date-wrap">
                        <span class="cal-addon"><i class="far fa-calendar-alt"></i></span>
                        <input type="date" name="date" value="{{ $initialDate }}" required>
                    </div>
                </td>
                <td class="fieldlabel">{{ 'Valid Until' }}</td>
                <td class="fieldarea">
                    <div class="whmcs-date-wrap">
                        <span class="cal-addon"><i class="far fa-calendar-alt"></i></span>
                        <input type="date" name="valid_until" value="{{ $initialValidUntil }}" required>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Top WHMCS Action Buttons Row --}}
        <div class="whmcs-action-bar">
            <button type="submit" class="btn btn-primary">{{ 'Save Changes' }}</button>
            @if($isEdit)
                <button type="submit" form="quote-duplicate-form" class="btn btn-default">{{ 'Duplicate' }}</button>
                <a href="{{ route('admin.quotes.print', $quote) }}" target="_blank" class="btn btn-default">{{ 'Printable Version' }}</a>
                <a href="{{ route('admin.quotes.pdf.view', $quote) }}" target="_blank" class="btn btn-default">{{ 'View PDF' }}</a>
                <a href="{{ route('admin.quotes.pdf.download', $quote) }}" class="btn btn-default">{{ 'Download PDF' }}</a>
                <button type="submit" form="quote-email-form" class="btn btn-default">{{ 'Email as PDF' }}</button>
                <button type="submit" form="quote-convert-form" class="btn btn-default">{{ 'Convert to Invoice' }}</button>
                <button type="submit" form="quote-delete-form" class="btn btn-danger">{{ 'Delete' }}</button>
            @else
                <button type="button" class="btn btn-default" disabled title="{{ 'Save quote first to duplicate' }}">{{ 'Duplicate' }}</button>
                <button type="button" class="btn btn-default" disabled title="{{ 'Save quote first to view printable version' }}">{{ 'Printable Version' }}</button>
                <button type="button" class="btn btn-default" disabled title="{{ 'Save quote first to view PDF' }}">{{ 'View PDF' }}</button>
                <button type="button" class="btn btn-default" disabled title="{{ 'Save quote first to download PDF' }}">{{ 'Download PDF' }}</button>
                <button type="button" class="btn btn-default" disabled title="{{ 'Save quote first to email PDF' }}">{{ 'Email as PDF' }}</button>
                <button type="button" class="btn btn-default" disabled title="{{ 'Save quote first to convert to invoice' }}">{{ 'Convert to Invoice' }}</button>
                <button type="button" class="btn btn-danger" disabled title="{{ 'Quote has not been saved yet' }}">{{ 'Delete' }}</button>
            @endif
        </div>

        <h2 class="whmcs-quote-h2">{{ 'Client Information' }}</h2>

        <div style="margin-bottom:6px;">
            <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-weight:400;margin:0;">
                <input type="radio" name="client_type" value="existing" x-model="clientType" @change="syncCurrency()">
                <span>{{ 'Quote for existing client:' }}</span>
            </label>
        </div>

        <div class="whmcs-client-combobox" @click.outside="clientDropdownOpen = false">
            <input type="hidden" name="client_id" :value="clientType === 'existing' ? (selectedClientId || '') : ''">
            <div class="whmcs-client-input-wrap" @click="openClientDropdown()">
                <input type="text"
                       x-model="clientQuery"
                       @focus="openClientDropdown()"
                       @input="onClientSearchInput()"
                       @keydown.arrow-down.prevent="moveClientHighlight(1)"
                       @keydown.arrow-up.prevent="moveClientHighlight(-1)"
                       @keydown.enter.prevent="confirmClientHighlight()"
                       @keydown.escape="clientDropdownOpen = false"
                       placeholder="{{ 'Start Typing to Search Clients' }}"
                       autocomplete="off">
                <button type="button"
                        x-show="selectedClientId"
                        x-cloak
                        @click.stop="clearSelectedClient()"
                        style="position:absolute;right:26px;border:0;background:none;color:#888;cursor:pointer;font-size:15px;line-height:1;"
                        title="{{ 'Clear selection' }}">&times;</button>
                <span class="whmcs-client-caret">&#9662;</span>
            </div>

            <div class="whmcs-client-dropdown" x-show="clientDropdownOpen" x-cloak>
                <template x-for="(c, idx) in filteredClients" :key="c.id">
                    <div class="whmcs-client-option"
                         :class="{ 'active': idx === clientHighlight }"
                         @mouseenter="clientHighlight = idx"
                         @mousedown.prevent="chooseClient(c)">
                        <div>
                            <strong x-text="c.label"></strong>
                            <span style="color:#666;margin-left:6px;" x-text="c.email ? '(' + c.email + ')' : ''"></span>
                        </div>
                    </div>
                </template>
                <div x-show="filteredClients.length === 0" style="padding:10px 12px;color:#777;font-size:12.5px;">
                    {{ 'No matching clients found.' }}
                </div>
            </div>
        </div>

        <div style="margin:8px 0 6px;">
            <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;font-weight:700;margin:0;">
                <input type="radio" name="client_type" value="new" x-model="clientType" @change="syncCurrency()">
                <span>{{ 'Quote for new client' }}</span>
            </label>
        </div>

        <div x-show="clientType === 'new'" x-cloak style="margin-bottom:14px;">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ 'First Name' }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="first_name" value="{{ old('first_name', $isEdit ? $quote->first_name : '') }}" :required="clientType === 'new'" class="form-control input-250">
                    </td>
                    <td width="15%" class="fieldlabel">{{ 'Address 1' }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="address1" value="{{ old('address1', $isEdit ? $quote->address1 : '') }}" class="form-control input-300">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ 'Last Name' }}</td>
                    <td class="fieldarea">
                        <input type="text" name="last_name" value="{{ old('last_name', $isEdit ? $quote->last_name : '') }}" class="form-control input-250">
                    </td>
                    <td class="fieldlabel">{{ 'Address 2' }}</td>
                    <td class="fieldarea">
                        <input type="text" name="address2" value="{{ old('address2', $isEdit ? $quote->address2 : '') }}" class="form-control input-300">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ 'Company Name' }}</td>
                    <td class="fieldarea">
                        <input type="text" name="company_name" value="{{ old('company_name', $isEdit ? $quote->company_name : '') }}" class="form-control input-250">
                    </td>
                    <td class="fieldlabel">{{ 'City' }}</td>
                    <td class="fieldarea">
                        <input type="text" name="city" value="{{ old('city', $isEdit ? $quote->city : '') }}" class="form-control input-250">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ 'Email Address' }}</td>
                    <td class="fieldarea">
                        <input type="email" name="email" value="{{ old('email', $isEdit ? $quote->email : '') }}" class="form-control input-250">
                    </td>
                    <td class="fieldlabel">{{ 'State/Region' }}</td>
                    <td class="fieldarea">
                        <input type="text" name="state" value="{{ old('state', $isEdit ? $quote->state : '') }}" class="form-control input-250">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ 'Phone Number' }}</td>
                    <td class="fieldarea">
                        <div class="whmcs-phone-group" @click.outside="flagDropdownOpen = false">
                            <input type="hidden" name="phone_prefix" :value="phonePrefix">
                            <button type="button" class="whmcs-phone-flag-btn" @click="flagDropdownOpen = !flagDropdownOpen">
                                <template x-if="flagCountry === 'BD'">
                                    <svg width="18" height="12" viewBox="0 0 20 14" style="border-radius:1px;flex-shrink:0;"><rect width="20" height="14" fill="#006a4e"/><circle cx="9" cy="7" r="4.2" fill="#f42a41"/></svg>
                                </template>
                                <template x-if="flagCountry === 'US'">
                                    <svg width="18" height="12" viewBox="0 0 20 14" style="border-radius:1px;flex-shrink:0;"><rect width="20" height="14" fill="#b22234"/><rect y="2" width="20" height="2" fill="#fff"/><rect y="6" width="20" height="2" fill="#fff"/><rect y="10" width="20" height="2" fill="#fff"/><rect width="9" height="8" fill="#3c3b6e"/></svg>
                                </template>
                                <template x-if="flagCountry === 'GB'">
                                    <svg width="18" height="12" viewBox="0 0 20 14" style="border-radius:1px;flex-shrink:0;"><rect width="20" height="14" fill="#012169"/><path d="M0,0 L20,14 M20,0 L0,14" stroke="#fff" stroke-width="2.6"/><path d="M10,0 V14 M0,7 H20" stroke="#fff" stroke-width="4"/><path d="M10,0 V14 M0,7 H20" stroke="#c8102e" stroke-width="2.2"/></svg>
                                </template>
                                <template x-if="flagCountry === 'IN'">
                                    <svg width="18" height="12" viewBox="0 0 20 14" style="border-radius:1px;flex-shrink:0;"><rect width="20" height="4.6" fill="#ff9933"/><rect y="4.6" width="20" height="4.8" fill="#fff"/><rect y="9.4" width="20" height="4.6" fill="#138808"/><circle cx="10" cy="7" r="1.7" fill="#000080"/></svg>
                                </template>
                                <template x-if="!['BD','US','GB','IN'].includes(flagCountry)">
                                    <span style="display:inline-block;padding:1px 3px;font-size:10px;font-weight:700;background:#1a4d80;color:#fff;border-radius:2px;line-height:1.1;" x-text="flagCountry"></span>
                                </template>
                                <span x-text="phonePrefix"></span>
                                <span style="font-size:9px;color:#666;">&#9662;</span>
                            </button>
                            <input type="text" name="phone_number" value="{{ old('phone_number', $isEdit ? $quote->phone_number : '') }}" placeholder="1812-345678">

                            <div class="whmcs-flag-dd" x-show="flagDropdownOpen" x-cloak>
                                @foreach(\App\Support\Countries::all() as $cc => $cName)
                                    @if(isset(\App\Support\Countries::PHONE_PREFIXES[$cc]))
                                        <div class="whmcs-flag-opt" @click="selectPhoneCountry('{{ $cc }}', '{{ \App\Support\Countries::PHONE_PREFIXES[$cc] }}')">
                                            <span>{{ $cName }}</span>
                                            <strong style="color:#555;">{{ \App\Support\Countries::PHONE_PREFIXES[$cc] }}</strong>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </td>
                    <td class="fieldlabel">{{ 'Postcode' }}</td>
                    <td class="fieldarea">
                        <input type="text" name="postcode" value="{{ old('postcode', $isEdit ? $quote->postcode : '') }}" class="form-control input-150">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ 'Currency' }}</td>
                    <td class="fieldarea">
                        <select name="currency_id" x-model="newClientCurrencyId" @change="syncCurrency()" class="form-control select-inline">
                            @foreach($currencies as $cur)
                                <option value="{{ $cur->id }}">{{ $cur->code }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="fieldlabel">{{ 'Country' }}</td>
                    <td class="fieldarea">
                        <select name="country" x-model="newClientCountry" @change="onCountryChange()" class="form-control select-inline">
                            @foreach(\App\Support\Countries::all() as $code => $countryName)
                                <option value="{{ $code }}">{{ $countryName }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <h2 class="whmcs-quote-h2">{{ 'Line Items' }}</h2>

        <div class="tablebg" style="margin-bottom:4px;">
            <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3" id="quote-items">
                <thead>
                    <tr>
                        <th style="width:68px;text-align:center;">{{ 'Qty' }}</th>
                        <th style="text-align:center;">{{ 'Description' }}</th>
                        <th style="width:115px;text-align:center;">{{ 'Unit Price' }}</th>
                        <th style="width:105px;text-align:center;">{{ 'Discount %' }}</th>
                        <th style="width:110px;text-align:center;">{{ 'Total' }}</th>
                        <th style="width:62px;text-align:center;">{{ 'Taxed' }}</th>
                        <th style="width:30px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, index) in items" :key="index">
                        <tr>
                            <td style="vertical-align:top;">
                                <input type="number"
                                       :name="`items[${index}][quantity]`"
                                       x-model="item.quantity"
                                       @input="calcItem(item)"
                                       min="0.01"
                                       step="any"
                                       class="form-control"
                                       style="text-align:left;width:100%;padding:4px 6px;">
                            </td>
                            <td style="vertical-align:top;">
                                <textarea :name="`items[${index}][description]`"
                                          x-model="item.description"
                                          @input="maybeEnsureTrailingRow(index)"
                                          rows="1"
                                          class="form-control"
                                          style="width:100%;min-height:30px;resize:vertical;padding:4px 8px;"></textarea>
                            </td>
                            <td style="vertical-align:top;">
                                <input type="number"
                                       :name="`items[${index}][unit_price]`"
                                       x-model="item.unit_price"
                                       @input="calcItem(item)"
                                       min="0"
                                       step="0.01"
                                       class="form-control"
                                       style="text-align:left;width:100%;padding:4px 6px;">
                            </td>
                            <td style="vertical-align:top;">
                                <input type="number"
                                       :name="`items[${index}][discount_percent]`"
                                       x-model="item.discount_percent"
                                       @input="calcItem(item)"
                                       min="0"
                                       max="100"
                                       step="0.01"
                                       class="form-control"
                                       style="text-align:left;width:100%;padding:4px 6px;">
                            </td>
                            <td style="text-align:center;vertical-align:middle;font-weight:500;" x-text="item.description || Number(item.unit_price) > 0 ? formatMoney(item.amount) : ''"></td>
                            <td style="text-align:center;vertical-align:middle;">
                                <input type="hidden" :name="`items[${index}][taxable]`" value="0">
                                <input type="checkbox" :name="`items[${index}][taxable]`" x-model="item.taxable" value="1">
                            </td>
                            <td style="text-align:center;vertical-align:middle;">
                                <button type="button"
                                        @click="removeItem(index)"
                                        style="background:none;border:none;color:#d9534f;cursor:pointer;font-size:16px;line-height:1;"
                                        title="{{ 'Remove line' }}">&times;</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:14px;">
            <tr>
                <td style="vertical-align:top;padding:5px 4px;">
                    <a href="#" @click.prevent="openProductModal()" style="color:#1a4d80;text-decoration:none;font-size:13px;margin-right:16px;display:inline-flex;align-items:center;gap:5px;">
                        <i class="fas fa-plus-circle" style="color:#5cb85c;font-size:14px;"></i>
                        <span>{{ 'Add a Predefined Product' }}</span>
                    </a>
                    <a href="#" @click.prevent="addItem()" style="color:#555;text-decoration:none;font-size:12.5px;display:inline-flex;align-items:center;gap:4px;">
                        <i class="fas fa-plus" style="color:#337ab7;font-size:11px;"></i>
                        <span>{{ 'Add Line' }}</span>
                    </a>
                </td>
                <td style="width:280px;vertical-align:top;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="text-align:right;padding:4px 12px;font-weight:700;color:#333;">{{ 'Sub Total' }}</td>
                            <td style="width:115px;text-align:right;padding:4px 10px;font-weight:700;color:#333;" x-text="formatMoney(subtotal())"></td>
                        </tr>
                        <tr x-show="taxTotal() > 0" x-cloak>
                            <td style="text-align:right;padding:4px 12px;font-weight:600;color:#555;">{{ 'Tax' }} (<span x-text="activeTaxRate + '%'"></span>)</td>
                            <td style="text-align:right;padding:4px 10px;font-weight:600;color:#555;" x-text="formatMoney(taxTotal())"></td>
                        </tr>
                        <tr>
                            <td style="text-align:right;padding:6px 12px;font-weight:700;color:#333;border-top:1px solid #e5e7eb;">{{ 'Total Due' }}</td>
                            <td style="text-align:right;padding:6px 10px;font-weight:700;color:#333;border-top:1px solid #e5e7eb;" x-text="formatMoney(grandTotal())"></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <h2 class="whmcs-quote-h2">{{ 'Notes' }}</h2>

        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td width="20%" class="fieldlabel">
                    {{ 'Proposal Text' }}
                    <div style="font-weight:400;color:#666;font-size:12px;">({{ 'Displayed at the Top of the Quote' }})</div>
                </td>
                <td class="fieldarea">
                    <textarea name="proposal" rows="4" class="form-control">{{ old('proposal', $isEdit ? $quote->proposal : '') }}</textarea>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">
                    {{ 'Customer Notes' }}
                    <div style="font-weight:400;color:#666;font-size:12px;">({{ 'Displayed as a Footer to the Quote' }})</div>
                </td>
                <td class="fieldarea">
                    <textarea name="customer_notes" rows="4" class="form-control">{{ old('customer_notes', $isEdit ? $quote->customer_notes : '') }}</textarea>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">
                    {{ 'Admin Only Notes' }}
                    <div style="font-weight:400;color:#666;font-size:12px;">({{ 'Private Notes' }})</div>
                </td>
                <td class="fieldarea">
                    <textarea name="notes" rows="4" class="form-control">{{ old('notes', $isEdit ? $quote->notes : '') }}</textarea>
                </td>
            </tr>
        </table>

        {{-- Bottom WHMCS Action Buttons Row --}}
        <div class="whmcs-action-bar" style="margin-top:16px;">
            <button type="submit" class="btn btn-primary">{{ 'Save Changes' }}</button>
            @if($isEdit)
                <button type="submit" form="quote-duplicate-form" class="btn btn-default">{{ 'Duplicate' }}</button>
                <a href="{{ route('admin.quotes.print', $quote) }}" target="_blank" class="btn btn-default">{{ 'Printable Version' }}</a>
                <a href="{{ route('admin.quotes.pdf.view', $quote) }}" target="_blank" class="btn btn-default">{{ 'View PDF' }}</a>
                <a href="{{ route('admin.quotes.pdf.download', $quote) }}" class="btn btn-default">{{ 'Download PDF' }}</a>
                <button type="submit" form="quote-email-form" class="btn btn-default">{{ 'Email as PDF' }}</button>
                <button type="submit" form="quote-convert-form" class="btn btn-default">{{ 'Convert to Invoice' }}</button>
                <button type="submit" form="quote-delete-form" class="btn btn-danger">{{ 'Delete' }}</button>
            @else
                <button type="button" class="btn btn-default" disabled>{{ 'Duplicate' }}</button>
                <button type="button" class="btn btn-default" disabled>{{ 'Printable Version' }}</button>
                <button type="button" class="btn btn-default" disabled>{{ 'View PDF' }}</button>
                <button type="button" class="btn btn-default" disabled>{{ 'Download PDF' }}</button>
                <button type="button" class="btn btn-default" disabled>{{ 'Email as PDF' }}</button>
                <button type="button" class="btn btn-default" disabled>{{ 'Convert to Invoice' }}</button>
                <button type="button" class="btn btn-danger" disabled>{{ 'Delete' }}</button>
            @endif
        </div>
    </form>

    @if($isEdit)
        <form id="quote-duplicate-form" method="POST" action="{{ route('admin.quotes.duplicate', $quote) }}" style="display:none;">@csrf</form>
        <form id="quote-email-form" method="POST" action="{{ route('admin.quotes.email-pdf', $quote) }}" style="display:none;">@csrf</form>
        <form id="quote-convert-form" method="POST" action="{{ route('admin.quotes.convert', $quote) }}" style="display:none;">@csrf</form>
        <form id="quote-delete-form" method="POST" action="{{ route('admin.quotes.destroy', $quote) }}" onsubmit="return pnConfirm(event, @js(__('admin.quotes.confirm_delete')))" style="display:none;">
            @csrf
            @method('DELETE')
        </form>
    @endif

    {{-- Predefined Product Picker Modal --}}
    <div class="whmcs-modal-backdrop" x-show="productModalOpen" x-cloak @keydown.escape.window="productModalOpen = false" @click.self="productModalOpen = false">
        <div class="whmcs-modal-box">
            <div class="whmcs-modal-head">
                <span><i class="fas fa-box-open" style="margin-right:6px;"></i> {{ 'Select a Predefined Product' }}</span>
                <button type="button" @click="productModalOpen = false" style="background:none;border:0;color:#fff;font-size:20px;cursor:pointer;line-height:1;">&times;</button>
            </div>
            <div style="padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#f8fafc;">
                <input type="text" x-model="productSearch" x-ref="productSearchInput" placeholder="{{ 'Search product or group name...' }}" class="form-control">
            </div>
            <div style="padding:0;overflow-y:auto;flex:1;">
                <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="4" style="margin:0;">
                    <thead>
                        <tr>
                            <th style="text-align:left;">{{ 'Group / Product' }}</th>
                            <th style="width:120px;text-align:right;">{{ 'Price' }}</th>
                            <th style="width:85px;text-align:center;">{{ 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="prod in filteredProducts()" :key="prod.id">
                            <tr>
                                <td>
                                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:600;" x-text="prod.group"></div>
                                    <div style="font-weight:600;color:#1e293b;" x-text="prod.name"></div>
                                </td>
                                <td style="text-align:right;font-weight:600;" x-text="formatMoney(productPrice(prod))"></td>
                                <td style="text-align:center;">
                                    <button type="button" class="btn btn-primary btn-xs" @click="insertPredefinedProduct(prod)">{{ 'Select' }}</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredProducts().length === 0">
                            <td colspan="3" style="text-align:center;padding:20px;color:#777;">{{ 'No predefined products match your search.' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div style="padding:10px 16px;border-top:1px solid #e5e7eb;text-align:right;background:#f8fafc;">
                <button type="button" class="btn btn-default btn-sm" @click="productModalOpen = false">{{ 'Close' }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function whmcsQuoteManage() {
    const initialClients = @json($clientOptions);
    const selectedOpt = @json($selectedClientOption);
    const oldClientId = @json(old('client_id'));
    const currencies = @json($currenciesJs);
    const phonePrefixes = @json(\App\Support\Countries::PHONE_PREFIXES);
    const predefinedProducts = @json($predefinedProducts);
    const clientLookupUrl = @json(route('admin.quotes.client-lookup'));
    const defaultTaxRate = Number(@json($defaultTaxRate)) || 0;
    const pricesIncludeTax = Boolean(@json($pricesIncludeTax));

    let initSelected = selectedOpt;
    if (!initSelected && oldClientId) {
        initSelected = initialClients.find(c => String(c.id) === String(oldClientId)) || null;
    }

    return {
        clientType: @json($initialClientType),
        allClients: initialClients,
        filteredClients: initialClients,
        selectedClientId: initSelected ? initSelected.id : '',
        selectedClientCurrencyId: initSelected ? initSelected.currency_id : null,
        selectedClientTaxRate: initSelected ? Number(initSelected.tax_rate || 0) : defaultTaxRate,
        clientQuery: initSelected ? (initSelected.label + (initSelected.email ? ' (' + initSelected.email + ')' : '')) : '',
        clientDropdownOpen: false,
        clientHighlight: 0,
        searchTimer: null,

        newClientCurrencyId: String(@json($initialCurrencyId ?? '')),
        newClientCountry: @json($initialCountry),
        flagCountry: @json($initialCountry),
        phonePrefix: @json($initialPhonePrefix),
        flagDropdownOpen: false,

        activeCurrency: currencies[0] || { id: 0, code: 'BDT', prefix: '৳', suffix: '' },
        activeTaxRate: initSelected ? Number(initSelected.tax_rate || 0) : defaultTaxRate,

        items: @json($initialItems),

        productModalOpen: false,
        productSearch: '',

        init() {
            this.items.forEach(i => this.calcItem(i));
            this.syncCurrency();
        },

        openClientDropdown() {
            this.clientType = 'existing';
            this.clientDropdownOpen = true;
            this.filterLocalClients();
            this.syncCurrency();
        },

        filterLocalClients() {
            const q = (this.clientQuery || '').trim().toLowerCase();
            if (!q || (this.selectedClientId && this.clientQuery.includes('#' + this.selectedClientId))) {
                this.filteredClients = this.allClients.slice(0, 50);
            } else {
                this.filteredClients = this.allClients.filter(c =>
                    (c.label && c.label.toLowerCase().includes(q)) ||
                    (c.email && c.email.toLowerCase().includes(q))
                ).slice(0, 50);
            }
            this.clientHighlight = 0;
        },

        onClientSearchInput() {
            this.clientType = 'existing';
            this.selectedClientId = '';
            this.clientDropdownOpen = true;
            this.filterLocalClients();

            clearTimeout(this.searchTimer);
            const q = (this.clientQuery || '').trim();
            if (q.length < 2) {
                return;
            }
            this.searchTimer = setTimeout(() => {
                fetch(clientLookupUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (data && Array.isArray(data.clients)) {
                        this.filteredClients = data.clients;
                        this.clientHighlight = 0;
                    }
                })
                .catch(() => {});
            }, 220);
        },

        moveClientHighlight(delta) {
            if (!this.filteredClients.length) return;
            this.clientHighlight = Math.max(0, Math.min(this.filteredClients.length - 1, this.clientHighlight + delta));
        },

        confirmClientHighlight() {
            if (this.clientDropdownOpen && this.filteredClients[this.clientHighlight]) {
                this.chooseClient(this.filteredClients[this.clientHighlight]);
            }
        },

        chooseClient(c) {
            this.clientType = 'existing';
            this.selectedClientId = c.id;
            this.selectedClientCurrencyId = c.currency_id;
            this.selectedClientTaxRate = Number(c.tax_rate || 0);
            this.clientQuery = c.label + (c.email ? ' (' + c.email + ')' : '');
            this.clientDropdownOpen = false;
            this.syncCurrency();
        },

        clearSelectedClient() {
            this.selectedClientId = '';
            this.selectedClientCurrencyId = null;
            this.selectedClientTaxRate = defaultTaxRate;
            this.clientQuery = '';
            this.openClientDropdown();
        },

        onCountryChange() {
            this.flagCountry = this.newClientCountry;
            if (phonePrefixes[this.newClientCountry]) {
                this.phonePrefix = phonePrefixes[this.newClientCountry];
            }
        },

        selectPhoneCountry(cc, prefix) {
            this.flagCountry = cc;
            this.phonePrefix = prefix;
            this.flagDropdownOpen = false;
        },

        syncCurrency() {
            let targetId = this.clientType === 'new'
                ? this.newClientCurrencyId
                : (this.selectedClientCurrencyId || this.newClientCurrencyId);
            const found = currencies.find(c => String(c.id) === String(targetId)) || currencies[0];
            if (found) {
                this.activeCurrency = found;
            }
            this.activeTaxRate = this.clientType === 'existing' && this.selectedClientId
                ? this.selectedClientTaxRate
                : defaultTaxRate;
        },

        addItem() {
            this.items.push({ description: '', quantity: 1, unit_price: '0.00', discount_percent: '0.00', taxable: false, amount: 0 });
        },

        maybeEnsureTrailingRow(index) {
            if (index === this.items.length - 1 && (this.items[index].description || '').trim().length > 0 && this.items.length < 50) {
                // Keep manual control clean while letting Enter/tab flow naturally
            }
        },

        removeItem(index) {
            if (this.items.length <= 1) {
                this.items[0] = { description: '', quantity: 1, unit_price: '0.00', discount_percent: '0.00', taxable: false, amount: 0 };
                return;
            }
            this.items.splice(index, 1);
        },

        calcItem(item) {
            const qty = Math.max(0, Number(item.quantity) || 0);
            const price = Math.max(0, Number(item.unit_price) || 0);
            const discPct = Math.min(100, Math.max(0, Number(item.discount_percent) || 0));
            const gross = qty * price;
            const disc = gross * (discPct / 100);
            item.amount = Math.max(0, gross - disc);
        },

        rawLinesSubtotal() {
            return this.items.reduce((sum, i) => sum + (Number(i.amount) || 0), 0);
        },

        taxTotal() {
            if (this.activeTaxRate <= 0) return 0;
            let tax = 0;
            this.items.forEach(i => {
                if (i.taxable) {
                    const line = Number(i.amount) || 0;
                    tax += pricesIncludeTax
                        ? (line - (line / (1 + this.activeTaxRate / 100)))
                        : (line * (this.activeTaxRate / 100));
                }
            });
            return tax;
        },

        subtotal() {
            const raw = this.rawLinesSubtotal();
            return pricesIncludeTax ? Math.max(0, raw - this.taxTotal()) : raw;
        },

        grandTotal() {
            return this.subtotal() + this.taxTotal();
        },

        formatMoney(val) {
            const num = Number(val) || 0;
            const pref = this.activeCurrency.prefix || '';
            const suff = this.activeCurrency.suffix || '';
            return pref + num.toFixed(2) + suff;
        },

        openProductModal() {
            this.productSearch = '';
            this.productModalOpen = true;
            this.$nextTick(() => {
                if (this.$refs.productSearchInput) {
                    this.$refs.productSearchInput.focus();
                }
            });
        },

        filteredProducts() {
            const q = (this.productSearch || '').trim().toLowerCase();
            if (!q) return predefinedProducts;
            return predefinedProducts.filter(p =>
                p.name.toLowerCase().includes(q) ||
                p.group.toLowerCase().includes(q)
            );
        },

        productPrice(prod) {
            const cid = String(this.activeCurrency.id || '');
            if (prod.prices && prod.prices[cid] !== undefined) {
                return Number(prod.prices[cid]) || 0;
            }
            return Number(prod.default_price) || 0;
        },

        insertPredefinedProduct(prod) {
            const price = this.productPrice(prod).toFixed(2);
            const emptyRow = this.items.find(i => !(i.description || '').trim() && (Number(i.unit_price) || 0) === 0);
            if (emptyRow) {
                emptyRow.description = prod.description;
                emptyRow.quantity = 1;
                emptyRow.unit_price = price;
                emptyRow.discount_percent = '0.00';
                emptyRow.taxable = Boolean(prod.taxable);
                this.calcItem(emptyRow);
            } else {
                const row = {
                    description: prod.description,
                    quantity: 1,
                    unit_price: price,
                    discount_percent: '0.00',
                    taxable: Boolean(prod.taxable),
                    amount: 0
                };
                this.calcItem(row);
                this.items.push(row);
            }
            this.productModalOpen = false;
        }
    };
}
</script>
@endpush

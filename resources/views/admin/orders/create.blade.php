@extends('admin.layouts.app')
@section('title', __('admin.orders.new_title'))
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
    <h1 style="margin:0;">{{ __('admin.orders.new_title') }}</h1>
    <div class="context-btn-container" style="margin:0;">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.orders.title') }}</a>
    </div>
</div>

@if($errors->any())
<div class="infobox" style="border-left:4px solid #c43c35;background:#f2dede;color:#a94442;margin-bottom:15px;">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@php
    $acceptOld = (bool) old('accept', false);
@endphp

<form method="POST" action="{{ route('admin.orders.store') }}" id="whmcs-add-order-form">
    @csrf

    <div style="display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:20px;align-items:start;">
        <div>
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="22%" class="fieldlabel"><label for="o-client">{{ __('admin.orders.new_client') }}</label></td>
                    <td class="fieldarea">
                        @if($client)
                            <strong>{{ $client->full_name }}</strong> <span style="color:#777;">({{ $client->email }})</span>
                            <input type="hidden" name="client" value="{{ $client->id }}">
                        @else
                            <input type="text" id="o-client" name="client" value="{{ old('client') }}" required class="form-control input-300">
                            <div style="font-size:11px;color:#777;margin-top:3px;">{{ __('admin.tickets.open_client_hint') }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel"><label for="o-gateway">{{ __('admin.orders.payment_method') }}</label></td>
                    <td class="fieldarea">
                        <select id="o-gateway" name="payment_method" required class="form-control select-inline">
                            @foreach($gateways as $g)
                                <option value="{{ $g }}" @selected(old('payment_method') === $g)>{{ payment_method_label((string) $g) }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel"><label for="o-promo">{{ __('admin.orders.promo_code') }}</label></td>
                    <td class="fieldarea">
                        <input type="text" id="o-promo" name="promo_code" value="{{ old('promo_code') }}" maxlength="255" class="form-control input-200">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td class="fieldarea">
                        <input type="hidden" name="accept" value="0">
                        <label style="display:inline-flex;gap:6px;align-items:center;font-weight:400;margin:0;cursor:pointer;">
                            <input type="checkbox" id="o-accept" name="accept" value="1" @checked($acceptOld)>
                            <span>{{ __('admin.orders.new_accept') }}</span>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('Setup Fee') }}</td>
                    <td class="fieldarea">
                        <input type="hidden" name="setup_fee" value="0">
                        <label style="display:inline-flex;gap:6px;align-items:center;font-weight:400;margin:0;cursor:pointer;">
                            <input type="checkbox" id="o-setup-fee" name="setup_fee" value="1" @checked((bool) old('setup_fee', true))>
                            <span>{{ __('admin.orders.new_setup_fee') }}</span>
                        </label>
                    </td>
                </tr>
            </table>

            <h2 style="margin:18px 0 8px;">{{ __('Products/Services') }}</h2>

            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="22%" class="fieldlabel"><label for="o-product">{{ __('admin.orders.new_product') }}</label></td>
                    <td class="fieldarea">
                        <select id="o-product" name="product_id" required class="form-control">
                            @foreach($products->groupBy(fn ($p) => $p->group?->name ?? '') as $groupName => $groupProducts)
                                <optgroup label="{{ $groupName }}">
                                    @foreach($groupProducts as $p)
                                        <option value="{{ $p->id }}" @selected(old('product_id') == $p->id)>{{ $p->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel"><label for="o-domain">{{ __('admin.orders.new_domain') }}</label></td>
                    <td class="fieldarea">
                        <input type="text" id="o-domain" name="domain" value="{{ old('domain') }}" maxlength="255" class="form-control input-300" autocomplete="off" spellcheck="false">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel"><label for="o-cycle">{{ __('admin.orders.new_cycle') }}</label></td>
                    <td class="fieldarea">
                        <select id="o-cycle" name="billing_cycle" required class="form-control select-inline">
                            @foreach($cycles as $c)
                                <option value="{{ $c }}" @selected(old('billing_cycle', 'monthly') === $c)>{{ __('common.billing.' . str_replace('semiannually', 'semi_annually', $c)) }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel"><label for="o-price">{{ __('admin.orders.new_price') }}</label></td>
                    <td class="fieldarea">
                        <input type="number" id="o-price" name="price" value="{{ old('price') }}" step="0.01" min="0" class="form-control input-150">
                        <span style="font-size:11px;color:#777;margin-left:6px;">{{ __('admin.orders.new_price_hint') }}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div>
            <div id="ordersummary" style="background:#f6f9fc;border:1px solid #d9e2ec;border-radius:4px;padding:15px;">
                <div style="font-size:16px;font-weight:700;color:#1a4d80;text-align:center;padding-bottom:10px;margin-bottom:10px;border-bottom:1px solid #d9e2ec;">
                    {{ __('Order Summary') }}
                </div>
                <table width="100%" style="font-size:13px;border-collapse:collapse;margin-bottom:12px;">
                    <tr>
                        <td style="padding:5px 0;color:#666;">{{ __('admin.orders.new_product') }}:</td>
                        <td id="sum-product" style="padding:5px 0;text-align:right;font-weight:600;">{{ '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666;">{{ __('admin.orders.new_domain') }}:</td>
                        <td id="sum-domain" style="padding:5px 0;text-align:right;font-family:monospace;font-size:12px;">{{ '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666;">{{ __('admin.orders.new_cycle') }}:</td>
                        <td id="sum-cycle" style="padding:5px 0;text-align:right;">{{ '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666;">{{ __('admin.orders.payment_method') }}:</td>
                        <td id="sum-gateway" style="padding:5px 0;text-align:right;">{{ '—' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#666;">{{ __('admin.orders.promo_code') }}:</td>
                        <td id="sum-promo" style="padding:5px 0;text-align:right;font-family:monospace;">{{ '—' }}</td>
                    </tr>
                    <tr style="border-top:2px solid #cbd5e1;">
                        <td style="padding:8px 0 4px;font-weight:700;font-size:14px;">{{ __('admin.orders.new_price') }}:</td>
                        <td id="sum-price" style="padding:8px 0 4px;text-align:right;font-weight:700;font-size:15px;color:#1a4d80;">{{ __('Auto') }}</td>
                    </tr>
                </table>
                <div class="btn-container" style="margin:12px 0 0;padding:0;">
                    <button type="submit" class="btn btn-success" style="width:100%;padding:8px 14px;font-weight:600;">{{ __('admin.orders.new_submit') }}</button>
                    <div style="margin-top:8px;text-align:center;">
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    var prodEl = document.getElementById('o-product');
    var domEl = document.getElementById('o-domain');
    var cycEl = document.getElementById('o-cycle');
    var gwEl = document.getElementById('o-gateway');
    var promoEl = document.getElementById('o-promo');
    var priceEl = document.getElementById('o-price');
    var autoLabel = @js(__('Auto'));

    function selectedText(sel) {
        if (!sel || sel.selectedIndex < 0) { return '—'; }
        return sel.options[sel.selectedIndex].text || '—';
    }

    function syncSummary() {
        var sp = document.getElementById('sum-product');
        var sd = document.getElementById('sum-domain');
        var sc = document.getElementById('sum-cycle');
        var sg = document.getElementById('sum-gateway');
        var spr = document.getElementById('sum-promo');
        var spc = document.getElementById('sum-price');
        if (sp) { sp.textContent = selectedText(prodEl); }
        if (sd) { sd.textContent = (domEl && domEl.value.trim()) ? domEl.value.trim() : '—'; }
        if (sc) { sc.textContent = selectedText(cycEl); }
        if (sg) { sg.textContent = selectedText(gwEl); }
        if (spr) { spr.textContent = (promoEl && promoEl.value.trim()) ? promoEl.value.trim() : '—'; }
        if (spc) { spc.textContent = (priceEl && priceEl.value !== '') ? Number(priceEl.value).toFixed(2) : autoLabel; }
    }

    [prodEl, domEl, cycEl, gwEl, promoEl, priceEl].forEach(function (el) {
        if (el) {
            el.addEventListener('change', syncSummary);
            el.addEventListener('input', syncSummary);
        }
    });
    syncSummary();
})();
</script>

@endsection

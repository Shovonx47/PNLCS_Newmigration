@extends('admin.layouts.app')
@section('title', __('admin.products.create_product'))
@section('content')
<h1>{{ __('admin.products.create_product') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <a href="{{ route('admin.products.index') }}" class="btn btn-default btn-sm">&larr; {{ __('common.actions.back') }}</a>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:15px;">
    @foreach($errors->all() as $e)<div>&bull; {{ $e }}</div>@endforeach
</div>
@endif

<style>
    .tab-content.admin-tabs > .tab-pane { display: none; }
    .tab-content.admin-tabs > .tab-pane.active { display: block; }
</style>

<form method="POST" action="{{ route('admin.products.store') }}" id="product-create-form" x-data="{ type: '{{ old('type', 'hosting') }}' }">
    @csrf

    <ul class="nav nav-tabs admin-tabs" role="tablist">
        <li class="nav-item active"><a class="nav-link active" href="#tab-details" role="tab" data-tab-target="tab-details">{{ __('Details') }}</a></li>
        <li class="nav-item"><a class="nav-link" href="#tab-pricing" role="tab" data-tab-target="tab-pricing">{{ __('admin.products.pricing') }}</a></li>
        <li class="nav-item"><a class="nav-link" href="#tab-module" role="tab" data-tab-target="tab-module">{{ __('Module Settings') }}</a></li>
    </ul>

    <div class="tab-content admin-tabs">
        <div class="tab-pane active" id="tab-details">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td class="fieldlabel" width="20%">{{ __('admin.products.product_type') }} *</td>
                    <td class="fieldarea">
                        <select name="type" x-model="type" class="form-control" style="max-width:320px;">
                            <option value="hosting">{{ __('admin.products.type_hosting') }}</option>
                            <option value="reseller">{{ __('admin.products.type_reseller') }}</option>
                            <option value="vps">{{ __('admin.products.type_vps') }}</option>
                            <option value="ssl">{{ __('admin.products.type_ssl') }}</option>
                            <option value="other">{{ __('admin.products.type_other') }}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.product_group') }} *</td>
                    <td class="fieldarea">
                        <select name="group_id" required class="form-control" style="max-width:320px;">
                            @foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.product_name') }} *</td>
                    <td class="fieldarea">
                        <input type="text" name="name" value="{{ old('name') }}" required class="form-control" style="max-width:420px;">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.product_details') }}</td>
                    <td class="fieldarea">
                        @include('admin.products.partials.description-tabs', ['product' => null, 'value' => old('description'), 'translationLanguages' => $translationLanguages ?? collect(), 'rows' => 5])
                    </td>
                </tr>
            </table>
        </div>

        <div class="tab-pane" id="tab-pricing">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom:12px;">
                <tr>
                    <td class="fieldlabel" width="20%">{{ __('admin.products.payment_type') }} *</td>
                    <td class="fieldarea">
                        <select name="pay_type" class="form-control" style="max-width:260px;">
                            <option value="recurring">{{ __('admin.products.pay_recurring') }}</option>
                            <option value="onetime">{{ __('admin.products.pay_onetime') }}</option>
                            <option value="free">{{ __('admin.products.pay_free') }}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ 'Free Trial Period' }}</td>
                    <td class="fieldarea">
                        <select name="trial_days" class="form-control" style="max-width:260px;">
                            @foreach([0, 1, 3, 7, 15, 30, 90] as $td)
                            <option value="{{ $td }}" @selected((int) old('trial_days', 0) === $td)>{{ $td === 0 ? 'Disabled (0 days)' : $td.' '.($td === 1 ? 'day' : 'days') }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ 'Compare / Regular Price' }}</td>
                    <td class="fieldarea">
                        <input type="number" step="0.01" min="0" name="compare_price" value="{{ old('compare_price') }}" class="form-control" style="max-width:200px;">
                    </td>
                </tr>
            </table>

            {{-- One price, in the shop currency. A customer who chose another
                 currency sees it converted at the daily rate; a price typed
                 against each currency would not be the one charged. --}}
            @php
                $currency = $currencies->firstWhere('is_default', true) ?? $currencies->first();
            @endphp
            @if($currency)
            <div class="tablebg">
                <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                    <thead>
                        <tr>
                            <th>{{ __('common.table.currency') }}</th>
                            @foreach(['monthly','quarterly','semiannually','annually','biennially','triennially'] as $cycle)
                            <th style="text-transform:capitalize;">{{ $cycle }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-weight:600;white-space:nowrap;">{{ $currency->code }} ({{ $currency->prefix }})</td>
                            @foreach(['monthly','quarterly','semiannually','annually','biennially','triennially'] as $cycle)
                            <td><input type="number" step="0.01" name="pricing[{{ $currency->id }}][{{ $cycle }}]" value="-1" class="form-control" style="font-size:12px;"></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
            <p style="font-size:11px;color:#777;margin:6px 0 0;">{{ __('admin.products.disable_cycle_hint') }} {{ __('admin.products.pricing_shop_currency_hint') }}</p>
            @endif
        </div>

        <div class="tab-pane" id="tab-module">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr x-show="type !== 'ssl'">
                    <td class="fieldlabel" width="20%">{{ __('admin.products.server_module') }}</td>
                    <td class="fieldarea">
                        <select name="server_type" class="form-control" style="max-width:320px;">
                            <option value="">{{ __('admin.products.server_module_none') }}</option>
                            @foreach($serverModules as $key => $label)
                            <option value="{{ $key }}" @selected(old('server_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div style="color:#777;font-size:12px;margin-top:4px;">{{ __('admin.products.server_module_hint') }}</div>
                    </td>
                </tr>
                <tr x-show="type === 'ssl'" x-cloak>
                    <td class="fieldlabel" width="20%">{{ __('admin.products.ssl_module') }}</td>
                    <td class="fieldarea">
                        <select name="ssl_module" class="form-control" style="max-width:320px;">
                            <option value="">{{ __('admin.products.ssl_none') }}</option>
                            @foreach($sslModules as $sslKey => $sslLabel)
                            <option value="{{ $sslKey }}" @selected(old('ssl_module') === $sslKey)>{{ $sslLabel }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.server_group') }}</td>
                    <td class="fieldarea">
                        <select name="server_group_id" class="form-control" style="max-width:320px;">
                            <option value="">{{ __('admin.products.server_group_any') }}</option>
                            @foreach($serverGroups as $sg)
                            <option value="{{ $sg->id }}" @selected((string) old('server_group_id') === (string) $sg->id)>{{ $sg->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr id="package-group">
                    <td class="fieldlabel">{{ __('admin.products.package') }}</td>
                    <td class="fieldarea">
                        <select name="package_name" id="package-select" class="form-control" style="max-width:320px;">
                            <option value="">{{ __('admin.products.package_default') }}</option>
                            @foreach(($packageList['packages'] ?? []) as $pkg)
                            <option value="{{ $pkg['id'] }}" @selected(($selectedPackage ?? '') === $pkg['id'])>{{ $pkg['name'] }}</option>
                            @endforeach
                        </select>
                        <div id="package-note" style="color:#777;font-size:12px;margin-top:4px;">
                            {{ $packageList['error'] ?? __('admin.products.package_hint') }}
                        </div>
                    </td>
                </tr>
                <tr id="pve-create-hint" style="display:none;">
                    <td class="fieldlabel">{{ __('proxmox.product.title') }}</td>
                    <td class="fieldarea">
                        <div class="infobox" style="margin:0;">{{ __('proxmox.product.create_hint') }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.products.auto_setup') }}</td>
                    <td class="fieldarea">
                        <select name="auto_setup" class="form-control" style="max-width:360px;">
                            <option value="payment">{{ __('admin.products.auto_setup_payment') }}</option>
                            <option value="order">{{ __('admin.products.auto_setup_order') }}</option>
                            <option value="manual">{{ __('admin.products.auto_setup_manual') }}</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ __('admin.products.create_product') }}</button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('product-create-form');
    var links = form.querySelectorAll('[data-tab-target]');
    var panes = form.querySelectorAll('.tab-content.admin-tabs > .tab-pane');
    function showTab(id) {
        links.forEach(function (a) {
            var on = a.getAttribute('data-tab-target') === id;
            a.classList.toggle('active', on);
            if (a.parentElement) a.parentElement.classList.toggle('active', on);
        });
        panes.forEach(function (p) { p.classList.toggle('active', p.id === id); });
    }
    links.forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            showTab(a.getAttribute('data-tab-target'));
        });
    });
    form.addEventListener('invalid', function (e) {
        var pane = e.target.closest('.tab-pane');
        if (pane && !pane.classList.contains('active')) showTab(pane.id);
    }, true);
})();
</script>
@endsection

@push('scripts')
<script>
(function () {
    var moduleSelect = document.querySelector('select[name="server_type"]');
    var packageSelect = document.getElementById('package-select');
    var note = document.getElementById('package-note');
    if (! moduleSelect || ! packageSelect) { return; }

    // Proxmox has no plans on the server: its resources are set on the next page.
    function proxmoxHint() {
        var pve = moduleSelect.value === 'proxmox';
        document.getElementById('package-group').style.display = pve ? 'none' : '';
        document.getElementById('pve-create-hint').style.display = pve ? '' : 'none';
    }
    moduleSelect.addEventListener('change', proxmoxHint);
    proxmoxHint();

    moduleSelect.addEventListener('change', function () {
        var chosen = packageSelect.value;
        packageSelect.innerHTML = '<option value="">@lang('admin.products.package_loading')</option>';

        fetch('{{ route('admin.products.packages') }}?module=' + encodeURIComponent(moduleSelect.value), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            packageSelect.innerHTML = '<option value="">@lang('admin.products.package_default')</option>';
            (data.packages || []).forEach(function (p) {
                var o = document.createElement('option');
                o.value = p.id;
                o.textContent = p.name;
                if (p.id === chosen) { o.selected = true; }
                packageSelect.appendChild(o);
            });
            note.textContent = data.error || '@lang('admin.products.package_hint')';
        })
        .catch(function () {
            packageSelect.innerHTML = '<option value="">@lang('admin.products.package_default')</option>';
            note.textContent = '@lang('admin.products.package_list_unreachable')';
        });
    });
})();
</script>
@endpush

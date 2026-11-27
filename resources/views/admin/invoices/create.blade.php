@extends('admin.layouts.app')
@section('title', __('admin.invoices.create_invoice'))
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
    <h1 style="margin:0;">{{ __('admin.invoices.create_invoice') }}</h1>
    <div class="context-btn-container" style="margin:0;">
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.invoices.back_to_invoices') }}</a>
    </div>
</div>

@if($errors->any())
<div class="infobox" style="border-left:4px solid #c43c35;background:#f2dede;color:#a94442;margin-bottom:15px;">
    @foreach($errors->all() as $error)
        <div>&bull; {{ $error }}</div>
    @endforeach
</div>
@endif

<div x-data="invoiceBuilder()">
<form method="POST" action="{{ route('admin.invoices.store') }}">
    @csrf

    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="15%" class="fieldlabel">{{ __('admin.invoices.select_client') }} <span style="color:#d9534f;">*</span></td>
            <td width="35%" class="fieldarea">
                <select name="client_id" required class="form-control" @change="setClient($event.target.value)">
                    <option value="">{{ __('admin.invoices.choose_client') }}</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" data-rate="{{ $client->billing_tax_rate }}" data-label="{{ $client->billing_tax_label }}" data-rates="{{ $client->billing_tax_rates->toJson() }}" {{ old('client_id', $selectedClient?->id) == $client->id ? 'selected' : '' }}>
                            {{ $client->display_name }} ({{ $client->email }})
                        </option>
                    @endforeach
                </select>
            </td>
            <td width="15%" class="fieldlabel">{{ __('admin.invoices.payment_method') }}</td>
            <td width="35%" class="fieldarea">
                <select name="payment_method" class="form-control select-inline">
                    @foreach($gateways as $gw)
                        <option value="{{ $gw }}" {{ old('payment_method', $defaultPaymentMethod ?? '') == $gw ? 'selected' : '' }}>{{ payment_method_label((string) $gw) }}</option>
                    @endforeach
                    @unless(in_array('banktransfer', $gateways, true))
                        <option value="banktransfer" {{ old('payment_method', $defaultPaymentMethod ?? '') == 'banktransfer' ? 'selected' : '' }}>{{ __('admin.invoices.bank_transfer') }}</option>
                    @endunless
                    <option value="manual" {{ old('payment_method', $defaultPaymentMethod ?? '') == 'manual' ? 'selected' : '' }}>{{ __('admin.invoices.manual') }}</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.invoices.invoice_date') }} <span style="color:#d9534f;">*</span></td>
            <td class="fieldarea">
                <input type="date" name="date" required value="{{ old('date', now()->toDateString()) }}" class="form-control input-200">
            </td>
            <td class="fieldlabel">{{ __('admin.invoices.due_date') }} <span style="color:#d9534f;">*</span></td>
            <td class="fieldarea">
                <input type="date" name="due_date" required value="{{ old('due_date', now()->addDays($dueDays ?? 14)->toDateString()) }}" class="form-control input-200">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.invoices.notes') }}</td>
            <td class="fieldarea" colspan="3">
                <textarea name="notes" rows="2" placeholder="{{ __('admin.invoices.notes_placeholder') }}" class="form-control">{{ old('notes') }}</textarea>
            </td>
        </tr>
    </table>

    <div style="display:flex;align-items:center;justify-content:space-between;margin:18px 0 8px;">
        <h2 style="margin:0;">{{ __('admin.invoices.line_items') }}</h2>
        <button type="button" @click="addItem()" class="btn btn-default btn-xs">+ {{ __('admin.invoices.add_item') }}</button>
    </div>

    <div class="tablebg">
        <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
            <thead>
                <tr>
                    <th>{{ __('common.table.description') }}</th>
                    <th style="width:74px;text-align:center;">{{ __('common.table.qty') }}</th>
                    <th style="width:74px;text-align:center;">{{ __('admin.invoices.unit') }}</th>
                    <th style="width:130px;text-align:center;">{{ __('common.table.tax') }}</th>
                    <th style="width:120px;text-align:right;">{{ __('admin.invoices.price') }}</th>
                    <th style="width:110px;text-align:right;">{{ __('common.table.total') }}</th>
                    <th style="width:34px;"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(item, index) in items" :key="index">
                    <tr>
                        <td>
                            <input type="text" :id="'desc-input-' + index" :name="`items[${index}][description]`" x-model="item.description"
                                   @input="onTyping(index, item.description)" @keydown.arrow-down.prevent="move(index, 1)"
                                   @keydown.arrow-up.prevent="move(index, -1)" @keydown.enter.prevent="pickActive(index)"
                                   @keydown.escape="item.show = false" @blur="setTimeout(() => item.show = false, 150)"
                                   placeholder="{{ __('admin.invoices.description_placeholder') }}" required class="form-control">
                            <div x-show="item.show && matchesFor(item.description).length"
                                 x-cloak :style="item.dd ? 'position:fixed;top:' + item.dd.bottom + 'px;left:' + item.dd.left + 'px;width:' + item.dd.width + 'px;z-index:9999;background:#fff;border:1px solid #ccc;border-radius:4px;box-shadow:0 4px 12px rgba(0,0,0,0.2);' : 'display:none;'">
                                <div style="overflow-y:auto;max-height:220px;">
                                    <template x-for="(p, i) in pageList(item)" :key="p.name">
                                        <div @mousedown.prevent="select(index, p)"
                                             @mouseenter="item.active = i"
                                             :style="'padding:7px 10px;cursor:pointer;font-size:13px;' + (i === item.active ? 'background:#f0f6ff;' : '')">
                                            <span x-text="p.name" style="font-weight:600;"></span>
                                            <span x-show="p.unit" style="color:#999;margin-left:6px;" x-text="p.unit"></span>
                                            <span x-show="p.amount != null" style="color:#777;float:right;" x-text="p.amount != null ? currencyPrefix + Number(p.amount).toFixed(2) + currencySuffix : ''"></span>
                                        </div>
                                    </template>
                                </div>
                                <div x-show="totalPages(item) > 1" style="display:flex;align-items:center;justify-content:space-between;padding:5px 8px;border-top:1px solid #eee;font-size:12px;background:#fafafa;">
                                    <button type="button" @click.prevent="prevPage(item)" :disabled="item.page <= 1"
                                            style="border:1px solid #ccc;background:#fff;padding:2px 8px;border-radius:3px;cursor:pointer;font-size:12px;">&laquo;</button>
                                    <span style="color:#666;" x-text="item.page + ' / ' + totalPages(item)"></span>
                                    <button type="button" @click.prevent="nextPage(item)" :disabled="item.page >= totalPages(item)"
                                            style="border:1px solid #ccc;background:#fff;padding:2px 8px;border-radius:3px;cursor:pointer;font-size:12px;">&raquo;</button>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <input type="number" :name="`items[${index}][qty]`" x-model.number="item.qty"
                                   @input="recalculate()" placeholder="{{ '1' }}" step="1" min="1"
                                   class="form-control" style="text-align:center;width:64px;">
                        </td>
                        <td style="text-align:center;">
                            <input type="text" :name="`items[${index}][unit]`" x-model="item.unit"
                                   placeholder="{{ 'jed.' }}" class="form-control" style="text-align:center;width:64px;">
                        </td>
                        <td style="text-align:center;">
                            <select :name="`items[${index}][tax_label]`" x-model="item.tax_label"
                                    @change="syncItemTax(index)" class="form-control"
                                    style="text-align:center;min-width:115px;">
                                <option value="">{{ '0%' }}</option>
                                <template x-for="(r, i) in clientRates" :key="i">
                                    <option :value="r.name" x-text="r.name"></option>
                                </template>
                            </select>
                            <input type="hidden" :name="`items[${index}][tax_rate]`" :value="item.tax_rate">
                            <input type="hidden" :name="`items[${index}][tax_rule_id]`" :value="item.tax_rule_id || ''">
                        </td>
                        <td>
                            <input type="number" :name="`items[${index}][amount]`" x-model.number="item.amount"
                                   @input="recalculate()" placeholder="{{ '0.00' }}" step="0.01" min="0" required
                                   class="form-control" style="text-align:right;">
                        </td>
                        <td style="text-align:right;white-space:nowrap;font-family:monospace;">
                            <span x-text="currencyPrefix + lineTotal(item).toFixed(2) + currencySuffix">{{ '0.00' }}</span>
                        </td>
                        <td style="text-align:center;">
                            <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                    style="background:none;border:none;color:#d9534f;cursor:pointer;font-size:16px;padding:0 2px;">&times;</button>
                        </td>
                    </tr>
                </template>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;padding:6px 10px;font-weight:600;">{{ __('admin.invoices.net') }}:</td>
                    <td style="text-align:right;padding:6px 10px;font-family:monospace;" x-text="currencyPrefix + subtotal.toFixed(2) + currencySuffix">{{ '0.00' }}</td>
                    <td></td>
                </tr>
                <template x-for="g in vatBreakdown" :key="g.label">
                    <tr>
                        <td colspan="4" style="text-align:right;padding:4px 10px;color:#555;" x-text="g.label"></td>
                        <td style="text-align:right;padding:4px 10px;font-family:monospace;color:#666;" x-text="currencyPrefix + g.net.toFixed(2) + currencySuffix">{{ '0.00' }}</td>
                        <td style="text-align:right;padding:4px 10px;font-family:monospace;" x-text="currencyPrefix + g.amount.toFixed(2) + currencySuffix">{{ '0.00' }}</td>
                        <td></td>
                    </tr>
                </template>
                <tr style="background:#eef2f7;border-top:2px solid #cbd5e1;">
                    <td colspan="5" style="text-align:right;padding:8px 10px;font-weight:700;font-size:14px;">{{ __('admin.invoices.gross') }}:</td>
                    <td style="text-align:right;padding:8px 10px;font-weight:700;font-family:monospace;font-size:14px;" x-text="currencyPrefix + grandTotal.toFixed(2) + currencySuffix">{{ '0.00' }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="btn-container" style="margin-top:16px;">
        <button type="submit" class="btn btn-primary">{{ __('admin.invoices.create_invoice') }}</button>
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
    </div>
</form>
</div>

<script>
function invoiceBuilder() {
    return {
        items: [{ description: '', qty: 1, amount: 0, unit: '', tax_rate: 0, tax_label: '', tax_rule_id: null, show: false, active: -1, dd: null, page: 1 }],
        products: @json($products),
        currencyPrefix: @json($defaultCurrency?->prefix ?? ''),
        currencySuffix: @json($defaultCurrency?->suffix ?? ''),
        taxRate: 0,
        taxMode: @json(\App\Models\Invoice::currentTaxMode()),
        taxLabel: '',
        clientRates: [],
        vatLabel: @json(__('admin.invoices.tax')),
        perPage: 6,
        subtotal: 0,
        taxAmount: 0,
        vatBreakdown: [],
        grandTotal: 0,
        init() {
            const sel = this.$el.querySelector('select[name="client_id"]');
            const opt = sel ? sel.options[sel.selectedIndex] : null;
            if (opt && opt.value) {
                this.clientRates = this.parseRates(opt);
                this.taxLabel = opt.dataset.label || '';
                this.taxRate = this.defaultRate();
            }
            this.items[0].tax_rate = this.taxRate;
            this.items[0].tax_label = this.taxLabel;
            this.items[0].tax_rule_id = this.defaultRuleId();
            this.recalculate();
        },
        setClient(value) {
            const opt = this.$el.querySelector('option[value="' + value + '"]');
            this.clientRates = value && opt ? this.parseRates(opt) : [];
            this.taxLabel = value && opt ? (opt.dataset.label || '') : '';
            this.taxRate = this.defaultRate();
            this.items.forEach(i => { i.tax_rate = this.taxRate; i.tax_label = this.taxLabel; i.tax_rule_id = this.defaultRuleId(); });
            this.recalculate();
        },
        defaultRate() {
            const d = this.clientRates.find(r => r.is_default);
            return d ? d.rate : (this.clientRates.length ? this.clientRates[0].rate : 0);
        },
        defaultRuleId() {
            const d = this.clientRates.find(r => r.is_default);
            return d ? d.id : (this.clientRates.length ? this.clientRates[0].id : null);
        },
        syncItemTax(index) {
            const item = this.items[index];
            const r = this.clientRates.find(r => r.name === item.tax_label);
            item.tax_rate = r ? r.rate : 0;
            item.tax_rule_id = r ? r.id : null;
            this.recalculate();
        },
        parseRates(opt) {
            try { return JSON.parse(opt.dataset.rates || '[]'); } catch (e) { return []; }
        },
        addItem() { this.items.push({ description: '', qty: 1, amount: 0, unit: '', tax_rate: this.taxRate, tax_label: this.taxLabel, tax_rule_id: this.defaultRuleId(), show: false, active: -1, dd: null, page: 1 }); },
        removeItem(index) { if (this.items.length > 1) { this.items.splice(index, 1); this.recalculate(); } },
        matchesFor(value) {
            const q = (value || '').trim().toLowerCase();
            return q ? this.products.filter(p => p.name.toLowerCase().includes(q)) : this.products;
        },
        totalPages(item) {
            const n = this.matchesFor(item.description).length;
            return Math.max(1, Math.ceil(n / this.perPage));
        },
        pageList(item) {
            const list = this.matchesFor(item.description);
            const start = (item.page - 1) * this.perPage;
            return list.slice(start, start + this.perPage);
        },
        prevPage(item) { if (item.page > 1) { item.page--; item.active = -1; } },
        nextPage(item) { if (item.page < this.totalPages(item)) { item.page++; item.active = -1; } },
        onTyping(index, value) {
            const item = this.items[index];
            const el = document.getElementById('desc-input-' + index);
            if (el) {
                const r = el.getBoundingClientRect();
                item.dd = { left: r.left, bottom: r.bottom + 4, width: r.width };
            }
            item.show = true;
            item.active = -1;
            item.page = 1;
            if (!this.matchesFor(value).some(p => p.name === value)) {
                this.recalculate();
            }
        },
        move(index, dir) {
            const item = this.items[index];
            const list = this.pageList(item);
            item.show = true;
            if (list.length) {
                item.active = (item.active + dir + list.length) % list.length;
            }
        },
        pickActive(index) {
            const item = this.items[index];
            const list = this.pageList(item);
            const target = item.active >= 0 ? list[item.active] : list[0];
            if (target) { this.select(index, target); }
        },
        select(index, p) {
            const item = this.items[index];
            item.description = p.name;
            item.qty = 1;
            item.amount = p.amount ?? 0;
            item.unit = p.unit || '';
            if (p.tax_rate !== undefined && p.tax_rate !== null) {
                item.tax_rate = p.tax_rate;
                const label = p.tax_label || '';
                item.tax_rule_id = p.tax_rule_id ?? this.ruleIdForLabel(label);
                if (label && !this.clientRates.some(r => r.name === label)) {
                    this.clientRates = [...this.clientRates, { id: item.tax_rule_id, name: label, rate: item.tax_rate, is_default: false }];
                }
                item.tax_label = label;
            } else {
                item.tax_rate = p.taxed ? this.taxRate : 0;
                item.tax_label = p.taxed ? this.taxLabel : '';
                item.tax_rule_id = p.taxed ? this.defaultRuleId() : null;
            }
            item.show = false;
            this.recalculate();
        },
        ruleIdForLabel(label) {
            const r = this.clientRates.find(r => r.name === label);
            return r ? r.id : null;
        },
        lineTotal(item) { return (parseFloat(item.amount) || 0) * (parseInt(item.qty, 10) || 1); },
        fmtRate(r) { return String(Math.round(r * 100) / 100); },
        recalculate() {
            const inclusive = this.taxMode === 'inclusive';
            const gross = this.items.reduce((s, i) => s + this.lineTotal(i), 0);
            const groups = {};
            this.items.forEach(i => {
                const rate = this.taxMode === 'none' ? 0 : (parseFloat(i.tax_rate) || 0);
                const label = (i.tax_label || '').trim();
                if (!label && rate <= 0) { return; }
                const key = label || (rate + '%');
                groups[key] = groups[key] || { label: key, rate: rate, amount: 0, net: 0 };
                const share = inclusive ? this.lineTotal(i) * rate / (100 + rate) : this.lineTotal(i) * rate / 100;
                groups[key].amount += share;
                groups[key].net += inclusive ? this.lineTotal(i) - share : this.lineTotal(i);
            });
            this.vatBreakdown = Object.values(groups).map(g => ({
                label: g.label,
                rate: g.rate,
                amount: Math.round(g.amount * 100) / 100,
                net: Math.round(g.net * 100) / 100
            })).sort((a, b) => b.rate - a.rate);
            this.taxAmount = Math.round(this.vatBreakdown.reduce((s, g) => s + g.amount, 0) * 100) / 100;
            this.subtotal = inclusive ? Math.round((gross - this.taxAmount) * 100) / 100 : gross;
            this.grandTotal = Math.round((this.subtotal + this.taxAmount) * 100) / 100;
        }
    };
}
</script>
@endsection

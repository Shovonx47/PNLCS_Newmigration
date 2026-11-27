@extends('admin.layouts.app')
@section('title', __('admin.tax.title'))
@section('content')

<h1>{{ __('admin.tax.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <button type="button" onclick="openAddTax()" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.tax.add_rule') }}</button>
</div>

{{-- How tax is charged at all (#305): off without deleting the rules, and prices that already include it. --}}
<form method="POST" action="{{ route('admin.settings.general.update') }}" style="margin-bottom:18px;">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td class="fieldlabel" width="22%">{{ __('admin.tax.charge_tax') }}</td>
            <td class="fieldarea">
                <label style="font-weight:400;margin:0;cursor:pointer;">
                    <input type="hidden" name="TaxEnabled" value="0"><input type="checkbox" name="TaxEnabled" value="1" @checked($taxEnabled ?? true)>
                    {{ __('admin.tax.charge_tax_hint') }}
                </label>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.tax.prices_include_tax') }}</td>
            <td class="fieldarea">
                <label style="font-weight:400;margin:0;cursor:pointer;">
                    <input type="hidden" name="TaxInclusivePrices" value="0"><input type="checkbox" name="TaxInclusivePrices" value="1" @checked($taxInclusive ?? false)>
                    {{ __('admin.tax.prices_include_tax_hint') }}
                </label>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel"><label for="TaxExemptionNotice" style="margin:0;">{{ __('admin.tax.exemption_notice') }}</label></td>
            <td class="fieldarea">
                <textarea id="TaxExemptionNotice" name="TaxExemptionNotice" rows="2" maxlength="500" class="form-control" style="max-width:600px;">{{ old('TaxExemptionNotice', $taxNotice ?? '') }}</textarea>
                <div style="font-size:12px;color:#777;margin-top:3px;">{{ __('admin.tax.exemption_notice_hint') }}</div>
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
    </div>
</form>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.tax.country') }}</th>
                <th>{{ __('admin.tax.state') }}</th>
                <th>{{ __('admin.tax.default_rate') }}</th>
                <th style="text-align:right;width:140px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($groups ?? [] as $g)
            <tr>
                <td style="font-weight:600;">{{ $g->country === '' ? __('admin.tax.all_countries') : strtoupper($g->country) }}</td>
                <td>{{ $g->state === '' ? __('admin.tax.all') : $g->state }}</td>
                <td>{{ rtrim(rtrim(number_format((float) $g->default->tax_rate, 2), '0'), '.') }}%</td>
                <td style="text-align:right;white-space:nowrap;">
                    <button type="button" class="btn btn-default btn-xs"
                        onclick='openEditTax({{ json_encode(['country'=>$g->country, 'state'=>$g->state, 'rates'=>$g->rules->map(fn($r)=>['name'=>$r->name,'rate'=>(float)$r->tax_rate,'is_default'=>(bool)$r->is_default])->values()]) }})'>{{ __('common.actions.edit') }}</button>
                    <form method="POST" action="{{ route('admin.config.tax.destroy', ['country' => $g->country === '' ? '@global' : $g->country, 'state' => $g->state === '' ? null : $g->state]) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.tax.confirm_delete')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center;padding:20px;color:#777;">{{ __('admin.tax.no_rules') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div id="modal-tax" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-tax').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:680px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 id="modal-tax-title" style="margin:0;font-size:15px;">{{ __('admin.tax.add_rule') }}</h4>
            <button type="button" onclick="document.getElementById('modal-tax').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" id="tax-form" action="{{ route('admin.config.tax.store') }}">
            @csrf
            <input type="hidden" name="_method" id="tf-method" value="POST">
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom:12px;">
                    <tr>
                        <td class="fieldlabel" width="28%">{{ __('admin.tax.country') }}</td>
                        <td class="fieldarea"><input type="text" name="country" id="tf-country" required class="form-control" style="max-width:140px;" placeholder="PL" maxlength="2"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.tax.state') }}</td>
                        <td class="fieldarea">
                            <input type="text" name="state" id="tf-state" class="form-control" style="max-width:180px;" placeholder="TX">
                            <small style="color:#777;">{{ __('admin.tax.blank_all') }}</small>
                        </td>
                    </tr>
                </table>
                <div class="tablebg" style="margin-bottom:10px;">
                    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
                        <thead><tr><th>{{ __('admin.tax.tax_name') }}</th><th>{{ __('admin.tax.rate_col') }}</th><th style="text-align:center;">{{ __('admin.tax.default') }}</th><th></th></tr></thead>
                        <tbody id="tax-rate-rows"></tbody>
                    </table>
                </div>
                <button type="button" onclick="addRateRow()" class="btn btn-default btn-sm">+ {{ __('admin.tax.add_another') }}</button>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-tax').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
let rateIndex = 0;

function addRateRow(data) {
    const i = rateIndex++;
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="rates[${i}][name]" required class="form-control" style="min-width:180px;" placeholder="VAT 23%" value="${(data && data.name) || ''}"></td>
        <td><input type="number" name="rates[${i}][tax_rate]" step="0.01" required class="form-control" style="min-width:90px;" placeholder="23" value="${(data && data.rate) || ''}"></td>
        <td style="text-align:center;"><input type="radio" name="default_index" value="${i}" ${(data && data.is_default) ? 'checked' : ''}></td>
        <td><button type="button" onclick="this.closest('tr').remove()" class="btn btn-danger btn-xs">&times;</button></td>`;
    document.getElementById('tax-rate-rows').appendChild(row);
}

function openAddTax() {
    document.getElementById('modal-tax-title').textContent = '{{ __('admin.tax.add_rule') }}';
    document.getElementById('tax-form').action = '{{ route('admin.config.tax.store') }}';
    document.getElementById('tf-method').value = 'POST';
    document.getElementById('tf-country').value = '';
    document.getElementById('tf-state').value = '';
    document.getElementById('tax-rate-rows').innerHTML = '';
    rateIndex = 0;
    addRateRow();
    document.getElementById('modal-tax').style.display = 'flex';
}

function openEditTax(d) {
    document.getElementById('modal-tax-title').textContent = '{{ __('admin.tax.edit_rule') }}';
    const country = d.country === '' ? '@global' : d.country;
    let action = '/admin/config/tax/' + encodeURIComponent(country);
    if (d.state) {
        action += '/' + encodeURIComponent(d.state);
    }
    document.getElementById('tax-form').action = action;
    document.getElementById('tf-method').value = 'PUT';
    document.getElementById('tf-country').value = d.country || '';
    document.getElementById('tf-state').value = d.state || '';
    document.getElementById('tax-rate-rows').innerHTML = '';
    rateIndex = 0;
    d.rates.forEach(function (r) { addRateRow(r); });
    document.getElementById('modal-tax').style.display = 'flex';
}
</script>
@endsection

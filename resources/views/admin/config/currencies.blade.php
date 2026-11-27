@extends('admin.layouts.app')
@section('title', __('admin.currencies.title'))
@section('content')

<h1>{{ __('admin.currencies.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;display:flex;gap:6px;flex-wrap:wrap;">
    <form method="POST" action="{{ route('admin.config.currencies.update-rates') }}" style="margin:0;display:inline;">
        @csrf
        <button type="submit" class="btn btn-default btn-sm"><i class="fas fa-sync"></i> {{ __('admin.currencies.update_rates_now') }}</button>
    </form>
    <button type="button" onclick="document.getElementById('modal-add-currency').style.display='flex'" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.currencies.add_currency') }}</button>
</div>

<div class="tablebg" style="margin-bottom:18px;">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.code') }}</th>
                <th>{{ __('admin.currencies.prefix') }}</th>
                <th>{{ __('admin.currencies.suffix') }}</th>
                <th>{{ __('common.table.rate') }}</th>
                <th>{{ __('admin.currencies.clients') }}</th>
                <th>{{ __('admin.currencies.default') }}</th>
                <th style="text-align:right;width:200px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($currencies as $currency)
            <tr>
                <td style="font-family:monospace;font-weight:600;">{{ $currency->code }}</td>
                <td style="font-family:monospace;">{{ $currency->prefix }}</td>
                <td style="font-family:monospace;">{{ $currency->suffix ?? '' }}</td>
                <td>{{ $currency->rate }}</td>
                <td>{{ (int) ($clientsPerCurrency[$currency->id] ?? 0) }}</td>
                <td>@if($currency->is_default)<span class="whmcs-status active badge-active">{{ __('admin.currencies.default') }}</span>@endif</td>
                <td style="text-align:right;white-space:nowrap;">
                    @unless($currency->is_default)
                    <form method="POST" action="{{ route('admin.config.currencies.default', $currency) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-default btn-xs">{{ __('admin.currencies.make_default') }}</button>
                    </form>
                    @endunless
                    <button type="button" class="btn btn-default btn-xs"
                        onclick="openEditCurrency({{ json_encode(['id'=>$currency->id,'code'=>$currency->code,'prefix'=>$currency->prefix,'suffix'=>$currency->suffix,'format'=>(int) $currency->format,'rate'=>$currency->rate]) }})">{{ __('common.actions.edit') }}</button>
                    @if(!$currency->is_default)
                    <form method="POST" action="{{ route('admin.config.currencies.destroy', $currency) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($currency->code), {title: @js(__('admin.currencies.confirm_delete')), danger: true})">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div id="currency-settings">
    <h2 style="font-size:15px;margin:0 0 8px;">{{ __('admin.currencies.settings_title') }}</h2>
    <form method="POST" action="{{ route('admin.config.currencies.settings') }}">
        @csrf
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td class="fieldlabel" width="24%">{{ __('admin.currencies.customer_choice') }}</td>
                <td class="fieldarea">
                    <label style="font-weight:400;margin:0;cursor:pointer;">
                        <input type="hidden" name="customer_choice" value="0">
                        <input type="checkbox" name="customer_choice" value="1" @checked($customerChoice)>
                        {{ __('admin.currencies.customer_choice_hint') }}
                    </label>
                    @if($customerChoice && $currencies->count() < 2)
                    <div style="font-size:12px;color:#c43c35;margin-top:3px;">{{ __('admin.currencies.customer_choice_needs_two') }}</div>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.currencies.by_region') }}</td>
                <td class="fieldarea">
                    <label style="font-weight:400;margin:0;cursor:pointer;">
                        <input type="hidden" name="by_region" value="0">
                        <input type="checkbox" name="by_region" value="1" @checked($currencyByRegion)>
                        {{ __('admin.currencies.by_region_hint') }}
                    </label>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.currencies.auto_update_rates') }}</td>
                <td class="fieldarea">
                    <label style="font-weight:400;margin:0;cursor:pointer;">
                        <input type="hidden" name="auto_update_rates" value="0">
                        <input type="checkbox" name="auto_update_rates" value="1" @checked($autoUpdateRates)>
                        {{ __('admin.currencies.auto_update_rates_hint') }}
                    </label>
                </td>
            </tr>
        </table>
        <div class="btn-container">
            <button type="submit" class="btn btn-primary">{{ __('common.actions.save_changes') }}</button>
        </div>
    </form>
</div>

<div id="modal-add-currency" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-currency').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:480px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.currencies.add_currency') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-currency').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.currencies.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="35%">{{ __('admin.currencies.code') }}</td>
                        <td class="fieldarea"><input type="text" name="code" required maxlength="3" class="form-control" placeholder="USD"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.currencies.prefix_symbol') }}</td>
                        <td class="fieldarea"><input type="text" name="prefix" class="form-control" placeholder="$"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.currencies.suffix') }}</td>
                        <td class="fieldarea"><input type="text" name="suffix" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.currencies.number_format') }}</td>
                        <td class="fieldarea"><select name="format" class="form-control">@foreach(array_keys(\App\Models\Currency::FORMATS) as $f)<option value="{{ $f }}">{{ (new \App\Models\Currency(['format' => $f]))->number(1234.56) }}</option>@endforeach</select></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.currencies.exchange_rate') }}</td>
                        <td class="fieldarea"><input type="number" name="rate" step="0.000001" required value="1" class="form-control"></td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-add-currency').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.currencies.add_currency') }}</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-edit-currency" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-edit-currency').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:480px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.currencies.edit_currency') }}</h4>
            <button type="button" onclick="document.getElementById('modal-edit-currency').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" id="edit-currency-form" action="">
            @csrf @method('PUT')
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="35%">{{ __('admin.currencies.code') }}</td>
                        <td class="fieldarea"><input type="text" name="code" id="ec-code" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.currencies.prefix') }}</td>
                        <td class="fieldarea"><input type="text" name="prefix" id="ec-prefix" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.currencies.suffix') }}</td>
                        <td class="fieldarea"><input type="text" name="suffix" id="ec-suffix" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.currencies.number_format') }}</td>
                        <td class="fieldarea"><select name="format" id="ec-format" class="form-control">@foreach(array_keys(\App\Models\Currency::FORMATS) as $f)<option value="{{ $f }}">{{ (new \App\Models\Currency(['format' => $f]))->number(1234.56) }}</option>@endforeach</select></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.currencies.exchange_rate') }}</td>
                        <td class="fieldarea"><input type="number" name="rate" id="ec-rate" step="0.000001" required class="form-control"></td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-edit-currency').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditCurrency(d) {
    document.getElementById('edit-currency-form').action = '/admin/config/currencies/' + d.id;
    document.getElementById('ec-code').value = d.code;
    document.getElementById('ec-rate').value = d.rate;
    document.getElementById('ec-prefix').value = d.prefix || '';
    document.getElementById('ec-suffix').value = d.suffix || '';
    document.getElementById('ec-format').value = String(d.format || 1);
    document.getElementById('modal-edit-currency').style.display = 'flex';
}
</script>
@endsection

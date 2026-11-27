@extends('admin.layouts.app')
@section('title', __('admin.promotions.title'))
@section('content')

<h1>{{ __('admin.promotions.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <button type="button" onclick="document.getElementById('modal-add-promo').style.display='flex'" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.promotions.add_promo') }}</button>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:15px;">
    <ul style="margin:0;padding-left:18px;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.code') }}</th>
                <th>{{ __('common.table.type') }}</th>
                <th>{{ __('common.table.value') }}</th>
                <th>{{ __('admin.promotions.uses') }}</th>
                <th>{{ __('admin.promotions.expires') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th style="text-align:right;width:140px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($promotions ?? [] as $promo)
            <tr>
                <td style="font-family:monospace;font-weight:600;">{{ $promo->code }}</td>
                <td style="text-transform:capitalize;">{{ $promo->type }}</td>
                <td>{{ $promo->type === 'percentage' ? $promo->value . '%' : money_fmt($promo->value) }}</td>
                <td>{{ $promo->uses ?? 0 }}{{ $promo->max_uses ? ' / ' . $promo->max_uses : '' }}</td>
                <td>{{ $promo->expiration_date?->format(date_fmt()) ?? __('admin.promotions.never') }}</td>
                <td><span class="whmcs-status {{ $promo->isValid() ? 'active badge-active' : 'suspended badge-suspended' }}">{{ $promo->isValid() ? __('common.status.active') : __('common.status.inactive') }}</span></td>
                <td style="text-align:right;white-space:nowrap;">
                    <button type="button" class="btn btn-default btn-xs" onclick="document.getElementById('modal-edit-promo-{{ $promo->id }}').style.display='flex'">{{ __('common.actions.edit') }}</button>
                    <form method="POST" action="{{ route('admin.config.promotions.destroy', $promo) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.promotions.confirm_delete')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:20px;color:#777;">{{ __('admin.promotions.no_promotions') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@foreach($promotions ?? [] as $promo)
<div id="modal-edit-promo-{{ $promo->id }}" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-edit-promo-{{ $promo->id }}').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:600px;max-width:95%;max-height:90vh;overflow-y:auto;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.promotions.edit_promo') }}</h4>
            <button type="button" onclick="document.getElementById('modal-edit-promo-{{ $promo->id }}').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.promotions.update', $promo) }}">
            @csrf @method('PUT')
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('common.table.code') }}</td>
                        <td class="fieldarea"><input type="text" name="code" value="{{ $promo->code }}" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.table.type') }}</td>
                        <td class="fieldarea">
                            <select name="type" class="form-control">
                                @foreach(['percentage', 'fixed_amount', 'free_setup', 'price_override', 'override_recurring'] as $type)
                                <option value="{{ $type }}" @selected($promo->type === $type)>{{ str_replace('_', ' ', $type) }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.table.value') }}</td>
                        <td class="fieldarea"><input type="number" step="0.01" min="0" name="value" value="{{ $promo->value }}" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.max_uses') }}</td>
                        <td class="fieldarea"><input type="number" min="0" name="max_uses" value="{{ $promo->max_uses }}" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.start_date') }}</td>
                        <td class="fieldarea"><input type="date" name="start_date" value="{{ $promo->start_date?->format('Y-m-d') }}" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.expires') }}</td>
                        <td class="fieldarea"><input type="date" name="expiration_date" value="{{ $promo->expiration_date?->format('Y-m-d') }}" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.recurring') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin:0;"><input type="checkbox" name="recurring" value="1" @checked($promo->recurring)> {{ __('admin.promotions.recurring') }}</label>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.cycles') }}</td>
                        <td class="fieldarea">
                            <input type="number" min="0" max="1000" name="cycles" value="{{ (int) $promo->cycles }}" class="form-control">
                            <div style="font-size:11px;color:#777;margin-top:2px;">{{ __('admin.promotions.cycles_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.max_products') }}</td>
                        <td class="fieldarea">
                            <input type="number" min="0" max="1000" name="max_products" value="{{ $promo->productLimit() }}" class="form-control">
                            <div style="font-size:11px;color:#777;margin-top:2px;">{{ __('admin.promotions.unlimited_hint') }} &mdash; {{ __('admin.promotions.max_products_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.billing_cycles') }}</td>
                        <td class="fieldarea">
                            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                @foreach(array_keys(\App\Models\Service::CYCLE_MONTHS) as $bc)
                                <label style="font-size:12px;font-weight:400;margin:0;"><input type="checkbox" name="billing_cycles[]" value="{{ $bc }}" @checked(in_array($bc, $promo->billingCycles(), true))> {{ __('client.cart.cycle_'.$bc) }}</label>
                                @endforeach
                            </div>
                            <div style="font-size:11px;color:#777;margin-top:2px;">{{ __('admin.promotions.billing_cycles_hint') }}</div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-edit-promo-{{ $promo->id }}').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<div id="modal-add-promo" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-promo').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:620px;max-width:95%;max-height:90vh;overflow-y:auto;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.promotions.add_promo') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-promo').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.promotions.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('admin.promotions.promo_code') }}</td>
                        <td class="fieldarea"><input type="text" name="code" value="{{ old('code') }}" required class="form-control" placeholder="SUMMER20" style="text-transform:uppercase;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.discount_type') }}</td>
                        <td class="fieldarea">
                            <select name="type" class="form-control">
                                <option value="percentage" @selected(old('type')==='percentage')>{{ __('admin.promotions.percentage') }}</option>
                                <option value="fixed_amount" @selected(old('type')==='fixed_amount')>{{ __('admin.promotions.fixed') }}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.discount_value') }}</td>
                        <td class="fieldarea"><input type="number" name="value" step="0.01" required class="form-control" placeholder="10" value="{{ old('value') }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.applies_to') }}</td>
                        <td class="fieldarea">
                            <select name="applies_to[]" multiple class="form-control" style="height:80px;">@foreach(($products ?? collect()) as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
                            <div style="font-size:11px;color:#777;margin-top:2px;">{{ __('admin.promotions.applies_to_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.requires') }}</td>
                        <td class="fieldarea">
                            <select name="requires[]" multiple class="form-control" style="height:80px;">@foreach(($products ?? collect()) as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
                            <div style="font-size:11px;color:#777;margin-top:2px;">{{ __('admin.promotions.requires_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('Options') }}</td>
                        <td class="fieldarea">
                            <div style="display:flex;gap:14px;flex-wrap:wrap;">
                                <label style="font-size:12px;font-weight:400;margin:0;"><input type="checkbox" name="apply_once" value="1"> {{ __('admin.promotions.apply_once') }}</label>
                                <label style="font-size:12px;font-weight:400;margin:0;"><input type="checkbox" name="new_signups_only" value="1"> {{ __('admin.promotions.new_signups_only') }}</label>
                                <label style="font-size:12px;font-weight:400;margin:0;"><input type="checkbox" name="existing_client" value="1"> {{ __('admin.promotions.existing_client') }}</label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.max_uses') }}</td>
                        <td class="fieldarea"><input type="number" name="max_uses" value="{{ old('max_uses', 0) }}" min="0" class="form-control"> <small style="color:#777;">{{ __('admin.promotions.unlimited_hint') }}</small></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.start_date') }}</td>
                        <td class="fieldarea"><input type="date" name="start_date" value="{{ old('start_date') }}" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.expiry_date') }}</td>
                        <td class="fieldarea"><input type="date" name="expiration_date" value="{{ old('expiration_date') }}" class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.recurring') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin-right:12px;"><input type="checkbox" name="recurring" value="1" @checked(old('recurring'))> {{ __('admin.promotions.recurring') }}</label>
                            <input type="number" name="cycles" value="{{ old('cycles', 0) }}" min="0" max="1000" class="form-control" style="max-width:120px;display:inline-block;">
                            <small style="color:#777;">{{ __('admin.promotions.cycles') }} ({{ __('admin.promotions.cycles_hint') }})</small>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.max_products') }}</td>
                        <td class="fieldarea">
                            <input type="number" name="max_products" value="{{ old('max_products', 0) }}" min="0" max="1000" class="form-control">
                            <div style="font-size:11px;color:#777;margin-top:2px;">{{ __('admin.promotions.max_products_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.promotions.billing_cycles') }}</td>
                        <td class="fieldarea">
                            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                @foreach(array_keys(\App\Models\Service::CYCLE_MONTHS) as $bc)
                                <label style="font-size:12px;font-weight:400;margin:0;"><input type="checkbox" name="billing_cycles[]" value="{{ $bc }}" @checked(in_array($bc, (array) old('billing_cycles', []), true))> {{ __('client.cart.cycle_'.$bc) }}</label>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-add-promo').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.promotions.add_promo') }}</button>
            </div>
        </form>
    </div>
</div>

@if($errors->any())
<script>document.getElementById('modal-add-promo').style.display = 'flex';</script>
@endif
@endsection

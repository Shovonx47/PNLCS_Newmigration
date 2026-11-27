@extends("admin.layouts.app")
@section("title", __("admin.product_addons"))
@section("content")

<h1>{{ __('admin.addons.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <button type="button" onclick="document.getElementById('modal-add-addon').style.display='flex'" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.addons.add_addon') }}</button>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead><tr>
            <th>{{ __('common.table.name') }}</th>
            <th>{{ __('common.table.description') }}</th>
            <th>{{ __('admin.addons.visibility') }}</th>
            <th>{{ __('common.table.status') }}</th>
            <th>{{ __('admin.addons.monthly_price') }}</th>
            <th>{{ __('admin.addons.sort') }}</th>
            <th style="text-align:right;width:140px;">{{ __('common.table.actions') }}</th>
        </tr></thead>
        <tbody>
        @forelse($addons as $addon)
        <tr>
            <td style="font-weight:600;">{{ $addon->name }}</td>
            <td style="font-size:12px;color:#666;">{{ \Illuminate\Support\Str::limit($addon->description ?? '', 60) ?: '-' }}</td>
            <td>
                @if($addon->hidden)
                    <span class="whmcs-status suspended">{{ __('admin.addons.hidden') }}</span>
                @else
                    <span class="whmcs-status active badge-active">{{ __('admin.addons.visible') }}</span>
                @endif
            </td>
            <td>
                @if($addon->retired)
                    <span class="whmcs-status terminated">{{ __('admin.addons.retired') }}</span>
                @else
                    <span class="whmcs-status active badge-active">{{ __('admin.addons.active') }}</span>
                @endif
            </td>
            <td>{{ number_format($addon->priceFor('monthly'), 2) }}</td>
            <td>{{ $addon->sort_order }}</td>
            <td style="text-align:right;white-space:nowrap;">
                <button type="button" class="btn btn-default btn-xs" onclick="editAddon({{ $addon->id }}, {{ json_encode($addon) }})">{{ __('common.actions.edit') }}</button>
                <form method="POST" action="{{ route('admin.config.addons.destroy', $addon->id) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.addons.confirm_delete')))">
                    @csrf @method("DELETE")
                    <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;padding:20px;color:#777;">{{ __('admin.addons.no_addons') }}</td>
        </tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Add Addon Modal --}}
<div id="modal-add-addon" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-addon').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:680px;max-width:96%;max-height:90vh;overflow:auto;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.addons.add_product_addon') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-addon').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.addons.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="28%">{{ __('admin.addons.name') }} *</td>
                        <td class="fieldarea"><input type="text" name="name" required class="form-control" placeholder="{{ __('admin.addons.name_placeholder') }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.description') }}</td>
                        <td class="fieldarea"><textarea name="description" rows="2" class="form-control" placeholder="{{ __('admin.addons.description_placeholder') }}"></textarea></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.addons.applicable_products') }}</td>
                        <td class="fieldarea">
                            <div style="max-height:150px;overflow:auto;border:1px solid #ddd;padding:8px 10px;background:#f9f9f9;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 12px;">
                                @foreach($products as $product)
                                    <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:400;margin:0;cursor:pointer;">
                                        <input type="checkbox" name="packages[]" value="{{ $product->id }}"> {{ $product->name }}
                                    </label>
                                @endforeach
                            </div>
                            <div style="font-size:11px;color:#777;margin-top:3px;">{{ __('admin.addons.applicable_products_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.addons.pricing') }}</td>
                        <td class="fieldarea">
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_monthly') }}</label><input type="number" step="0.01" min="-1" name="pricing[monthly]" value="0" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_quarterly') }}</label><input type="number" step="0.01" min="-1" name="pricing[quarterly]" value="0" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_semiannually') }}</label><input type="number" step="0.01" min="-1" name="pricing[semiannually]" value="0" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_annually') }}</label><input type="number" step="0.01" min="-1" name="pricing[annually]" value="0" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_biennially') }}</label><input type="number" step="0.01" min="-1" name="pricing[biennially]" value="0" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_triennially') }}</label><input type="number" step="0.01" min="-1" name="pricing[triennially]" value="0" class="form-control"></div>
                            </div>
                            <div style="font-size:11px;color:#777;margin-top:3px;">{{ __('admin.addons.pricing_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.addons.sort_order') }}</td>
                        <td class="fieldarea"><input type="number" name="sort_order" value="0" class="form-control" style="max-width:120px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('Options') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin-right:16px;"><input type="checkbox" name="hidden" value="1"> {{ __('admin.addons.hidden') }}</label>
                            <label style="font-weight:400;"><input type="checkbox" name="tax" value="1" checked> {{ __('admin.addons.apply_tax') }}</label>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-add-addon').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.addons.create_addon') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Addon Modal --}}
<div id="modal-edit-addon" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-edit-addon').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:680px;max-width:96%;max-height:90vh;overflow:auto;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.addons.edit_addon') }}</h4>
            <button type="button" onclick="document.getElementById('modal-edit-addon').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form id="edit-addon-form" method="POST" action="">
            @csrf @method("PUT")
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="28%">{{ __('admin.addons.name') }} *</td>
                        <td class="fieldarea"><input type="text" name="name" id="edit-addon-name" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.description') }}</td>
                        <td class="fieldarea"><textarea name="description" id="edit-addon-desc" rows="2" class="form-control"></textarea></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.addons.applicable_products') }}</td>
                        <td class="fieldarea">
                            <div id="edit-addon-packages" style="max-height:150px;overflow:auto;border:1px solid #ddd;padding:8px 10px;background:#f9f9f9;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 12px;">
                                @foreach($products as $product)
                                    <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:400;margin:0;cursor:pointer;">
                                        <input type="checkbox" name="packages[]" value="{{ $product->id }}"> {{ $product->name }}
                                    </label>
                                @endforeach
                            </div>
                            <div style="font-size:11px;color:#777;margin-top:3px;">{{ __('admin.addons.applicable_products_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.addons.pricing') }}</td>
                        <td class="fieldarea">
                            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_monthly') }}</label><input type="number" step="0.01" min="-1" name="pricing[monthly]" value="0" id="edit-addon-price-monthly" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_quarterly') }}</label><input type="number" step="0.01" min="-1" name="pricing[quarterly]" value="0" id="edit-addon-price-quarterly" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_semiannually') }}</label><input type="number" step="0.01" min="-1" name="pricing[semiannually]" value="0" id="edit-addon-price-semiannually" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_annually') }}</label><input type="number" step="0.01" min="-1" name="pricing[annually]" value="0" id="edit-addon-price-annually" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_biennially') }}</label><input type="number" step="0.01" min="-1" name="pricing[biennially]" value="0" id="edit-addon-price-biennially" class="form-control"></div>
                                <div><label style="font-size:11px;display:block;margin-bottom:2px;">{{ __('admin.addons.cycle_triennially') }}</label><input type="number" step="0.01" min="-1" name="pricing[triennially]" value="0" id="edit-addon-price-triennially" class="form-control"></div>
                            </div>
                            <div style="font-size:11px;color:#777;margin-top:3px;">{{ __('admin.addons.pricing_hint') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.addons.sort_order') }}</td>
                        <td class="fieldarea"><input type="number" name="sort_order" id="edit-addon-sort" class="form-control" style="max-width:120px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('Options') }}</td>
                        <td class="fieldarea">
                            <label style="font-weight:400;margin-right:14px;"><input type="checkbox" name="hidden" value="1" id="edit-addon-hidden"> {{ __('admin.addons.hidden') }}</label>
                            <label style="font-weight:400;margin-right:14px;"><input type="checkbox" name="retired" value="1" id="edit-addon-retired"> {{ __('admin.addons.retired') }}</label>
                            <label style="font-weight:400;"><input type="checkbox" name="tax" value="1" id="edit-addon-tax"> {{ __('admin.addons.apply_tax') }}</label>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-edit-addon').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.update') }}</button>
            </div>
        </form>
    </div>
</div>

@push("scripts")
<script>
function editAddon(id, data) {
    document.getElementById('edit-addon-form').action = '/admin/config/addons/' + id;
    var packages = String(data.packages || '').split(',').filter(Boolean);
    Array.prototype.forEach.call(document.querySelectorAll('#edit-addon-packages input[type="checkbox"]'), function (box) {
        box.checked = packages.indexOf(String(box.value)) !== -1;
    });
    var pricing = (data.pricing && data.pricing[0]) || {};
    ['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'].forEach(function (cycle) {
        document.getElementById('edit-addon-price-' + cycle).value = pricing[cycle] || 0;
    });
    document.getElementById('edit-addon-name').value = data.name || '';
    document.getElementById('edit-addon-desc').value = data.description || '';
    document.getElementById('edit-addon-sort').value = data.sort_order || 0;
    document.getElementById('edit-addon-hidden').checked = !!data.hidden;
    document.getElementById('edit-addon-retired').checked = !!data.retired;
    document.getElementById('edit-addon-tax').checked = !!data.tax;
    document.getElementById('modal-edit-addon').style.display = 'flex';
}
</script>
@endpush
@endsection

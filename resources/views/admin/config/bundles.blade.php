@extends("admin.layouts.app")
@section("title", __("admin.product_bundles"))
@section("content")

<h1>{{ __('admin.bundles.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;">
    <button type="button" onclick="document.getElementById('modal-add-bundle').style.display='flex'" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.bundles.create_bundle') }}</button>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead><tr>
            <th>{{ __('common.table.name') }}</th>
            <th>{{ __('admin.bundles.products') }}</th>
            <th>{{ __('common.table.discount') }}</th>
            <th>{{ __('common.table.status') }}</th>
            <th style="text-align:right;width:120px;">{{ __('common.table.actions') }}</th>
        </tr></thead>
        <tbody>
        @forelse($bundles as $bundle)
        <tr>
            <td>
                <div style="font-weight:600;">{{ $bundle->name }}</div>
                @if($bundle->description)
                    <div style="font-size:11px;color:#777;">{{ \Illuminate\Support\Str::limit($bundle->description, 60) }}</div>
                @endif
            </td>
            <td>
                @foreach($bundle->items as $item)
                    @if($item->product)
                        <span class="whmcs-status active" style="margin-bottom:2px;">{{ $item->product->name }}{{ $item->qty > 1 ? ' x'.$item->qty : '' }}</span>
                    @else
                        <span class="whmcs-status suspended" style="margin-bottom:2px;">{{ __('admin.bundles.item_badge', ['id' => $item->item_id, 'type' => $item->item_type]) }}</span>
                    @endif
                @endforeach
                @if($bundle->items->isEmpty())
                    <span style="color:#777;font-size:12px;">{{ __('admin.bundles.no_products') }}</span>
                @endif
            </td>
            <td>
                @if($bundle->discount_type === 'percentage')
                    <span style="font-weight:600;color:#2e7d32;">{{ number_format($bundle->discount_value, 0) }}{{ __('admin.bundles.percent_off') }}</span>
                @else
                    <span style="font-weight:600;color:#2e7d32;">{{ money_fmt($bundle->discount_value) }} {{ 'off' }}</span>
                @endif
            </td>
            <td>
                @if($bundle->is_active)
                    <span class="whmcs-status active badge-active">{{ __('admin.bundles.active') }}</span>
                @else
                    <span class="whmcs-status suspended">{{ __('admin.bundles.inactive') }}</span>
                @endif
            </td>
            <td style="text-align:right;">
                <form method="POST" action="{{ route('admin.config.bundles.destroy', $bundle->id) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.bundles.confirm_delete')))">
                    @csrf @method("DELETE")
                    <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="text-align:center;padding:20px;color:#777;">{{ __('admin.bundles.no_bundles') }}</td>
        </tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Create Bundle Modal --}}
<div id="modal-add-bundle" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-add-bundle').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:580px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 style="margin:0;font-size:15px;">{{ __('admin.bundles.create_product_bundle') }}</h4>
            <button type="button" onclick="document.getElementById('modal-add-bundle').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.config.bundles.store') }}">
            @csrf
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="32%">{{ __('admin.bundles.bundle_name') }} *</td>
                        <td class="fieldarea"><input type="text" name="name" required class="form-control" placeholder="{{ __('admin.bundles.name_placeholder') }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.form.description') }}</td>
                        <td class="fieldarea"><textarea name="description" rows="2" class="form-control" placeholder="{{ __('admin.bundles.description_placeholder') }}"></textarea></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.bundles.discount_type') }} *</td>
                        <td class="fieldarea">
                            <select name="discount_type" required class="form-control">
                                <option value="percentage">{{ __('admin.bundles.percentage') }}</option>
                                <option value="fixed">{{ __('admin.bundles.fixed_amount') }}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.bundles.discount_value') }} *</td>
                        <td class="fieldarea"><input type="number" name="discount_value" required step="0.01" min="0" class="form-control" placeholder="10"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.bundles.select_products') }} *</td>
                        <td class="fieldarea">
                            <div style="font-size:11px;color:#777;margin-bottom:4px;">{{ __('admin.bundles.min_two') }}</div>
                            <div style="max-height:180px;overflow-y:auto;border:1px solid #ddd;padding:6px 10px;background:#f9f9f9;">
                                @foreach($products as $product)
                                <label style="display:block;padding:2px 0;font-size:12px;font-weight:400;cursor:pointer;margin:0;">
                                    <input type="checkbox" name="product_ids[]" value="{{ $product->id }}">
                                    {{ $product->name }}
                                    @if($product->group)
                                        <span style="color:#777;font-size:11px;">({{ $product->group->name }})</span>
                                    @endif
                                </label>
                                @endforeach
                            </div>
                            @if($products->isEmpty())
                                <div style="color:#777;font-size:12px;margin-top:4px;">{{ __('admin.bundles.no_products_available') }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('common.table.status') }}</td>
                        <td class="fieldarea"><label style="font-weight:400;margin:0;"><input type="checkbox" name="is_active" value="1" checked> {{ __('admin.bundles.active') }}</label></td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-add-bundle').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.bundles.create_bundle') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection

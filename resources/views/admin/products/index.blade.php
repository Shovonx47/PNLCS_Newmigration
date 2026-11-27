@extends('admin.layouts.app')
@section('title', __('admin.products.title'))
@section('content')
<h1>{{ __('admin.products.title') }}</h1>

<div class="context-btn-container" style="margin-bottom:12px;display:flex;gap:6px;flex-wrap:wrap;">
    <a href="{{ route('admin.products.groups.create') }}" class="btn btn-default btn-sm"><i class="fas fa-plus"></i> {{ __('admin.products.new_group') }}</a>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.products.new_product') }}</a>
    <button type="button" onclick="openAddCatalog()" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> {{ __('admin.products.add_product_service') }}</button>
</div>

<div class="tablebg" style="margin-bottom:20px;">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.products.product_name') }}</th>
                <th>{{ __('common.table.type') }}</th>
                <th>{{ __('admin.products.payment') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th style="text-align:right;width:150px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($groups as $group)
            <tr style="background:#eef2f7;">
                <td colspan="4" style="padding:7px 10px;">
                    <strong>{{ __('admin.products.product_group') }}: {{ $group->name }}</strong>
                    @if($group->hidden) <span class="whmcs-status suspended badge-suspended">{{ __('admin.products.hidden') }}</span>@endif
                    <span style="font-size:11px;color:#666;margin-left:8px;">({{ trans_choice('admin.products.group_products', $group->products->count(), ['count' => $group->products->count()]) }}@if($group->headline) &mdash; {{ $group->headline }}@endif)</span>
                </td>
                <td style="text-align:right;white-space:nowrap;padding:7px 10px;">
                    <a href="{{ route('admin.products.groups.edit', $group) }}" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</a>
                    @if($group->products->isEmpty())
                    {{-- Only an empty group can go: deleting one with products would take them with it. --}}
                    <form method="POST" action="{{ route('admin.products.groups.destroy', $group) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.products.confirm_delete_group')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                    </form>
                    @endif
                </td>
            </tr>
            @forelse($group->products as $product)
            <tr>
                <td style="padding-left:22px;">
                    <a href="{{ route('admin.products.edit', $product) }}" style="font-weight:600;">{{ $product->name }}</a>
                    @if($product->is_featured) <span class="whmcs-status active" style="margin-left:4px;">{{ __('admin.products.featured') }}</span>@endif
                </td>
                <td style="text-transform:capitalize;">{{ $product->type }}</td>
                <td style="text-transform:capitalize;">{{ $product->pay_type }}</td>
                <td>
                    @if($product->hidden)<span class="whmcs-status suspended badge-suspended">{{ __('admin.products.hidden') }}</span>
                    @elseif($product->retired)<span class="whmcs-status terminated badge-terminated">{{ __('admin.products.retired') }}</span>
                    @else<span class="whmcs-status active badge-active">{{ __('admin.products.active') }}</span>@endif
                </td>
                <td style="text-align:right;white-space:nowrap;">
                    <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</a>
                    <form method="POST" action="{{ route('admin.products.copy', $product) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-default btn-xs">{{ __('common.actions.copy') }}</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="padding-left:22px;color:#777;">{{ __('admin.products.no_products_in_group') }} <a href="{{ route('admin.products.create') }}">{{ __('admin.products.add_one') }}</a></td>
            </tr>
            @endforelse
        @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:24px;">
                    <div style="margin-bottom:8px;color:#777;">{{ __('admin.products.no_groups') }}</div>
                    <a href="{{ route('admin.products.groups.create') }}" class="btn btn-primary btn-sm">{{ __('admin.products.create_first_group') }}</a>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<h2 style="font-size:15px;margin:20px 0 8px;">{{ __('admin.products.products_services') }} <span style="font-size:12px;font-weight:400;color:#777;">({{ $invoiceProducts->count() }} {{ __('admin.products.products_services') }})</span></h2>
{{-- Not products: ready-made lines for invoices made by hand. Read as
     products, they were created to sell a service and could not be (#248). --}}
<div class="infobox">{{ __('admin.products.invoice_items_hint') }}</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.products.product_name') }}</th>
                <th style="text-align:right;">{{ __('admin.invoices.price') }}</th>
                <th>{{ __('admin.products.unit') }}</th>
                <th>{{ __('admin.invoices.tax') }}</th>
                <th style="text-align:right;width:150px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($invoiceProducts as $p)
            <tr>
                <td style="font-weight:600;">{{ $p->name }}</td>
                <td style="text-align:right;font-family:monospace;white-space:nowrap;">{{ money_fmt($p->price) }}</td>
                <td>{{ $p->unit ?: '-' }}</td>
                <td>{{ $p->tax_label ?: rtrim(rtrim(number_format((float) $p->tax_rate, 2), '0'), '.').'%' }}</td>
                <td style="text-align:right;white-space:nowrap;">
                    <button type="button" class="btn btn-default btn-xs"
                        onclick='openEditCatalog({{ json_encode(['id'=>$p->id,'name'=>$p->name,'price'=>(float)$p->price,'unit'=>$p->unit,'tax_label'=>$p->tax_label]) }})'>{{ __('common.actions.edit') }}</button>
                    <form method="POST" action="{{ route('admin.products.catalog.destroy', $p) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.products.confirm_delete_product_service')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('common.actions.delete') }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align:center;color:#777;padding:15px;">{{ __('admin.products.no_products_services') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<div id="modal-catalog" style="display:none;position:fixed;inset:0;z-index:1050;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.5);" onclick="document.getElementById('modal-catalog').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:480px;max-width:95%;box-shadow:0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;justify-content:space-between;">
            <h4 id="catalog-title" style="margin:0;font-size:15px;">{{ __('admin.products.add_product_service') }}</h4>
            <button type="button" onclick="document.getElementById('modal-catalog').style.display='none'" style="background:none;border:none;font-size:22px;cursor:pointer;color:#777;">&times;</button>
        </div>
        <form method="POST" id="catalog-form" action="{{ route('admin.products.catalog.store') }}">
            @csrf
            <input type="hidden" name="_method" id="cf-method" value="POST">
            <div style="padding:15px;">
                <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                    <tr>
                        <td class="fieldlabel" width="35%">{{ __('admin.products.product_name') }}</td>
                        <td class="fieldarea"><input type="text" name="name" id="cf-name" required class="form-control" placeholder="{{ 'Usługa informatyczna' }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.invoices.price') }}</td>
                        <td class="fieldarea"><input type="number" name="price" id="cf-price" step="0.01" required class="form-control" placeholder="{{ '0.00' }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.products.unit') }}</td>
                        <td class="fieldarea"><input type="text" name="unit" id="cf-unit" class="form-control" placeholder="{{ 'szt. / godz.' }}"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ __('admin.invoices.tax') }}</td>
                        <td class="fieldarea">
                            <select name="tax_label" id="cf-tax" class="form-control">
                                <option value="">0%</option>
                                @foreach($taxRateOptions as $r)
                                <option value="{{ $r->name }}">{{ $r->name }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="btn-container" style="margin:0;padding:10px 15px;border-top:1px solid #e5e5e5;text-align:right;">
                <button type="button" onclick="document.getElementById('modal-catalog').style.display='none'" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save_changes') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
const defaultTaxLabel = @json($defaultTaxLabel);
function openAddCatalog() {
    document.getElementById('catalog-title').textContent = '{{ __('admin.products.add_product_service') }}';
    document.getElementById('catalog-form').action = '{{ route('admin.products.catalog.store') }}';
    document.getElementById('cf-method').value = 'POST';
    document.getElementById('cf-name').value = '';
    document.getElementById('cf-price').value = '';
    document.getElementById('cf-unit').value = '';
    document.getElementById('cf-tax').value = defaultTaxLabel;
    document.getElementById('modal-catalog').style.display = 'flex';
}
function openEditCatalog(d) {
    document.getElementById('catalog-title').textContent = '{{ __('common.actions.edit') }}';
    document.getElementById('catalog-form').action = '/admin/products/catalog/' + d.id;
    document.getElementById('cf-method').value = 'PUT';
    document.getElementById('cf-name').value = d.name;
    document.getElementById('cf-price').value = d.price;
    document.getElementById('cf-unit').value = d.unit || '';
    document.getElementById('cf-tax').value = d.tax_label || '';
    document.getElementById('modal-catalog').style.display = 'flex';
}
</script>
@endsection

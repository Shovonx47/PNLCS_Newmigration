@extends('admin.layouts.app')
@section('title', 'Order Statuses')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
    <h1 style="margin:0;border:0;padding:0;">{{ 'Order Statuses' }}</h1>
    <a href="{{ route('admin.config.system-settings') }}" class="btn btn-default btn-sm"><i class="fas fa-arrow-left"></i> {{ 'System Settings' }}</a>
</div>

<div class="infobox">
    {{ 'Configure order workflow statuses, badge colors, and how orders in each status are grouped in Pending, Active, and Cancelled views.' }}
</div>

<div class="tablebg" style="margin-bottom:20px;">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th class="text-left">{{ 'Status Title' }}</th>
                <th>{{ 'Include in Pending' }}</th>
                <th>{{ 'Include in Active' }}</th>
                <th>{{ 'Include in Cancelled' }}</th>
                <th>{{ 'Sort Order' }}</th>
                <th>{{ 'Orders' }}</th>
                <th width="160">{{ 'Actions' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statuses as $st)
                @php
                    $isCore = in_array(strtolower($st->title), $coreTitles, true);
                    $cnt = (int) ($orderCounts[strtolower($st->title)] ?? 0);
                @endphp
                <tr>
                    <td>
                        <span style="font-weight:700;color:{{ $st->color ?: '#333333' }};">{{ $st->title }}</span>
                        @if($isCore)
                            <span style="font-size:10px;background:#eef2f7;color:#475569;padding:1px 6px;border-radius:3px;margin-left:6px;">{{ 'Core' }}</span>
                        @endif
                    </td>
                    <td style="text-align:center;">{!! $st->show_pending ? '<i class="fas fa-check" style="color:#16a34a;"></i>' : '<span style="color:#ccc;">&mdash;</span>' !!}</td>
                    <td style="text-align:center;">{!! $st->show_active ? '<i class="fas fa-check" style="color:#16a34a;"></i>' : '<span style="color:#ccc;">&mdash;</span>' !!}</td>
                    <td style="text-align:center;">{!! $st->show_cancelled ? '<i class="fas fa-check" style="color:#16a34a;"></i>' : '<span style="color:#ccc;">&mdash;</span>' !!}</td>
                    <td style="text-align:center;">{{ $st->sort_order }}</td>
                    <td style="text-align:center;">{{ $cnt }}</td>
                    <td style="text-align:center;white-space:nowrap;">
                        <button type="button" class="btn btn-default btn-xs"
                            data-id="{{ $st->id }}"
                            data-title="{{ $st->title }}"
                            data-color="{{ $st->color ?: '#333333' }}"
                            data-pending="{{ $st->show_pending ? '1' : '0' }}"
                            data-active="{{ $st->show_active ? '1' : '0' }}"
                            data-cancelled="{{ $st->show_cancelled ? '1' : '0' }}"
                            data-sort="{{ $st->sort_order }}"
                            data-core="{{ $isCore ? '1' : '0' }}"
                            onclick="openEditOrderStatus(this)">
                            {{ 'Edit' }}
                        </button>
                        @if(!$isCore)
                            <form method="POST" action="{{ route('admin.config.order-statuses.destroy', $st) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($st->title), {title: 'Delete Order Status'})">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs">{{ 'Delete' }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<h2 style="font-size:16px;margin:18px 0 10px;color:#1a4d80;">{{ 'Add New Order Status' }}</h2>
<form method="POST" action="{{ route('admin.config.order-statuses.store') }}">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="20%" class="fieldlabel">{{ 'Title' }}</td>
            <td class="fieldarea"><input type="text" name="title" required maxlength="60" class="form-control input-300" placeholder="{{ 'e.g. Awaiting Verification' }}"></td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Status Color' }}</td>
            <td class="fieldarea">
                <input type="color" name="color" value="#2563EB" style="height:32px;width:64px;padding:2px;vertical-align:middle;">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Include in Views' }}</td>
            <td class="fieldarea">
                <label style="margin-right:16px;"><input type="checkbox" name="show_pending" value="1"> {{ 'Include in Pending' }}</label>
                <label style="margin-right:16px;"><input type="checkbox" name="show_active" value="1"> {{ 'Include in Active' }}</label>
                <label><input type="checkbox" name="show_cancelled" value="1"> {{ 'Include in Cancelled' }}</label>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Sort Order' }}</td>
            <td class="fieldarea"><input type="number" name="sort_order" value="{{ ($statuses->max('sort_order') ?? 4) + 1 }}" min="0" max="999" class="form-control input-100"></td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary">{{ 'Save Order Status' }}</button>
    </div>
</form>

<div id="modalEditOrderStatus" style="display:none;position:fixed;inset:0;z-index:1060;align-items:center;justify-content:center;">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.45);" onclick="document.getElementById('modalEditOrderStatus').style.display='none'"></div>
    <div style="position:relative;background:#fff;border-radius:4px;width:480px;max-width:95%;box-shadow:0 10px 30px rgba(0,0,0,0.25);overflow:hidden;">
        <div style="padding:12px 16px;background:#1a4d80;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <strong style="font-size:14px;">{{ 'Edit Order Status' }}</strong>
            <button type="button" onclick="document.getElementById('modalEditOrderStatus').style.display='none'" style="background:none;border:0;color:#fff;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <form method="POST" id="formEditOrderStatus" action="">
            @csrf
            @method('PUT')
            <div style="padding:15px;">
                <table class="form" width="100%">
                    <tr>
                        <td class="fieldlabel">{{ 'Title' }}</td>
                        <td class="fieldarea"><input type="text" name="title" id="editOsTitle" required class="form-control"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ 'Color' }}</td>
                        <td class="fieldarea"><input type="color" name="color" id="editOsColor" style="height:32px;width:64px;padding:2px;"></td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ 'Grouping' }}</td>
                        <td class="fieldarea">
                            <label style="display:block;margin-bottom:4px;"><input type="checkbox" name="show_pending" id="editOsPending" value="1"> {{ 'Include in Pending' }}</label>
                            <label style="display:block;margin-bottom:4px;"><input type="checkbox" name="show_active" id="editOsActive" value="1"> {{ 'Include in Active' }}</label>
                            <label style="display:block;"><input type="checkbox" name="show_cancelled" id="editOsCancelled" value="1"> {{ 'Include in Cancelled' }}</label>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldlabel">{{ 'Sort Order' }}</td>
                        <td class="fieldarea"><input type="number" name="sort_order" id="editOsSort" min="0" max="999" class="form-control input-100"></td>
                    </tr>
                </table>
            </div>
            <div style="padding:10px 15px;background:#f5f5f5;border-top:1px solid #ddd;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('modalEditOrderStatus').style.display='none'">{{ 'Cancel' }}</button>
                <button type="submit" class="btn btn-primary btn-sm">{{ 'Save Changes' }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditOrderStatus(btn) {
    var baseUrl = @js(url('/admin/config/order-statuses'));
    var form = document.getElementById('formEditOrderStatus');
    form.setAttribute('action', baseUrl + '/' + encodeURIComponent(btn.getAttribute('data-id')));
    var titleInput = document.getElementById('editOsTitle');
    titleInput.value = btn.getAttribute('data-title') || '';
    titleInput.readOnly = btn.getAttribute('data-core') === '1';
    document.getElementById('editOsColor').value = btn.getAttribute('data-color') || '#333333';
    document.getElementById('editOsPending').checked = btn.getAttribute('data-pending') === '1';
    document.getElementById('editOsActive').checked = btn.getAttribute('data-active') === '1';
    document.getElementById('editOsCancelled').checked = btn.getAttribute('data-cancelled') === '1';
    document.getElementById('editOsSort').value = btn.getAttribute('data-sort') || '0';
    document.getElementById('modalEditOrderStatus').style.display = 'flex';
}
</script>

@endsection

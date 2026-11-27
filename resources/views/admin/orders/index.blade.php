@extends("admin.layouts.app")
@section("title", __("admin.orders.title"))
@section("content")

<h1>{{ __('admin.orders.title') }}</h1>

@php
    $currentStatus = (string) request('status', '');
@endphp

<ul class="admin-tabs" role="tablist">
    <li class="active">
        <a href="#orders-search-tab" class="active" onclick="var el=document.getElementById('orders-search-pane');if(el){el.style.display=el.style.display==='none'?'block':'none';}return false;">
            {{ __('Search/Filter') }}
        </a>
    </li>
    @foreach(["", "pending", "active", "fraud", "cancelled"] as $val)
        @php
            $tabLabel = $val === "" ? __('common.form.all') : status_label($val);
            $isTabActive = ($currentStatus === $val);
        @endphp
        <li class="{{ $isTabActive ? 'active' : '' }}">
            <a href="{{ route('admin.orders.index', ['status' => $val]) }}" class="{{ $isTabActive ? 'active' : '' }}">{{ $tabLabel }}</a>
        </li>
    @endforeach
</ul>

<div class="tab-content admin-tabs" id="orders-search-pane">
    <div class="tab-pane active">
        <form method="GET" action="{{ route('admin.orders.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.table.order_num') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="order_num" value="{{ request('order_num') }}" class="form-control input-200" placeholder="{{ __('common.table.order_num') }}">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            @foreach(["", "pending", "active", "fraud", "cancelled"] as $val)
                                <option value="{{ $val }}" @selected($currentStatus === $val)>{{ $val === "" ? __('common.form.all') : status_label($val) }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.client') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control input-250" placeholder="{{ __('common.table.client') }}">
                    </td>
                    <td class="fieldlabel">{{ __('common.table.amount') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="amount" value="{{ request('amount') }}" class="form-control input-150" placeholder="{{ '0.00' }}">
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default">{{ __('common.actions.search') }}</button>
            </div>
        </form>
    </div>
</div>

<form id="order-bulk-form" method="POST" action="{{ route('admin.orders.bulk') }}">
    @csrf
    <input type="hidden" name="action" id="order-bulk-action" value="">

    <div class="context-btn-container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin:10px 0;">
        <div style="font-size:12px;color:#555;">
            <strong>{{ $orders->total() }}</strong> {{ __('Records Found') }}, {{ __('Page') }} {{ $orders->currentPage() }} {{ __('of') }} {{ max(1, $orders->lastPage()) }}
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            @if(auth('admin')->user()?->hasPermission('manage_orders'))
                <span style="font-size:12px;color:#666;margin-right:2px;">{{ __('admin.invoices.bulk_actions') }}:</span>
                <button type="submit" class="btn btn-success btn-sm" data-action="accept">{{ __('admin.orders.accept_order') }}</button>
                <button type="submit" class="btn btn-danger btn-sm" data-action="cancel">{{ __('admin.orders.cancel_order') }}</button>
                <a href="{{ route('admin.orders.create') }}" class="btn btn-primary btn-sm">+ {{ __('admin.orders.new_title') }}</a>
            @endif
        </div>
    </div>

    <div class="tablebg">
        <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
            <thead>
                <tr>
                    <th width="20"><input type="checkbox" id="order-select-all" aria-label="{{ __('admin.bulk.select_all') }}"></th>
                    <th>{{ __('ID') }}</th>
                    <th>{{ __('common.table.order_num') }}</th>
                    <th>{{ __('common.table.date') }}</th>
                    <th>{{ __('common.table.client') }}</th>
                    <th>{{ __('common.table.payment_method') }}</th>
                    <th style="text-align:right;">{{ __('common.table.total') }}</th>
                    <th>{{ __('Payment Status') }}</th>
                    <th>{{ __('common.table.status') }}</th>
                    <th width="70">{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    @php
                        $orderStatusKey = strtolower($order->status ?? "");
                        $badgeClass = match($orderStatusKey) {
                            "active"    => "badge-active",
                            "pending"   => "badge-pending",
                            "fraud"     => "badge-fraud",
                            "cancelled" => "badge-cancelled",
                            default     => "badge-cancelled",
                        };
                        $loadedInvoice = $order->relationLoaded('invoice') ? $order->invoice : null;
                        $rawPayStatus = $order->getAttributes()['payment_status'] ?? ($loadedInvoice?->status ?? null);
                    @endphp
                    <tr>
                        <td style="text-align:center;">
                            <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="order-row-checkbox" aria-label="{{ '#' . $order->order_num }}">
                        </td>
                        <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->id }}</a></td>
                        <td><a href="{{ route('admin.orders.show', $order) }}" style="font-family:monospace;">#{{ $order->order_num }}</a></td>
                        <td>{{ $order->date?->format(date_fmt()) ?? '-' }}</td>
                        <td>
                            @if($order->client)
                                <a href="{{ route('admin.clients.show', $order->client_id) }}">{{ $order->client?->full_name ?? __('Deleted Client') }}</a>
                            @else
                                {{ __('N/A') }}
                            @endif
                        </td>
                        <td>{{ $order->payment_method ? payment_method_label((string) $order->payment_method) : '-' }}</td>
                        <td style="text-align:right;font-weight:600;">{{ money_fmt($order->amount) }}</td>
                        <td>
                            @if($rawPayStatus)
                                <span class="badge badge-{{ strtolower($rawPayStatus) }} whmcs-status {{ strtolower($rawPayStatus) }}">{{ invoice_status_label($rawPayStatus) }}</span>
                            @elseif(!empty($order->invoice_id))
                                <a href="{{ route('admin.invoices.show', $order->invoice_id) }}">#{{ $order->invoice_id }}</a>
                            @else
                                <span style="color:#888;">{{ __('No Invoice Due') }}</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $badgeClass }} whmcs-status {{ $orderStatusKey }}">{{ status_label($order->status ?? "") }}</span></td>
                        <td style="text-align:center;">
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-default btn-xs">{{ __('common.actions.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:24px;color:#888;">{{ __('admin.orders.no_orders') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:10px;">
        {{ $orders->withQueryString()->links() }}
    </div>
</form>

<script>
document.getElementById('order-select-all').addEventListener('change', function () {
    document.querySelectorAll('.order-row-checkbox').forEach(function (cb) { cb.checked = this.checked; }, this);
});
document.querySelectorAll('#order-bulk-form button[data-action]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        if (document.querySelectorAll('.order-row-checkbox:checked').length === 0) {
            e.preventDefault();
            pnDialog.alert(@js(__('admin.orders.select_none')), { icon: 'warning' });
            return;
        }
        if (this.getAttribute('data-action') === 'cancel' && !pnConfirm(e, @js(__('admin.orders.confirm_cancel')), { danger: true })) {
            return;
        }
        document.getElementById('order-bulk-action').value = this.getAttribute('data-action');
    });
});
</script>

@endsection

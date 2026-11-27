@extends("admin.layouts.app")
@section("title", __("admin.invoices.title"))
@section("content")

<h1>{{ __('admin.invoices.title') }}</h1>

@php
    $statusTabs = [
        "all" => __('common.form.all'),
        "unpaid" => __('admin.invoices.filter_unpaid'),
        // An invoice waiting for transfer notification approval must be in plain sight.
        "payment_pending" => __('admin.invoices.filter_payment_pending'),
        "paid" => __('admin.invoices.filter_paid'),
        "overdue" => __('admin.invoices.filter_overdue'),
        "cancelled" => __('admin.invoices.filter_cancelled'),
        "draft" => __('admin.invoices.filter_draft'),
    ] + (\App\Support\AutoCharge::enabled()
        ? [\App\Http\Controllers\Admin\InvoiceController::CHARGE_REVIEW_FILTER => __('admin.invoices.filter_charge_review')]
        : []);
    $activeStatus = (string) request("status", "unpaid");
    $exportCsvUrl = \Illuminate\Support\Facades\Route::has('admin.invoices.export')
        ? route('admin.invoices.export', request()->query())
        : (\Illuminate\Support\Facades\Route::has('admin.invoices.export')
            ? route('admin.invoices.export', request()->query())
            : request()->fullUrlWithQuery(['export' => 'csv']));
@endphp

<ul class="admin-tabs" role="tablist">
    <li class="active">
        <a href="#invoices-search-tab" class="active" onclick="var el=document.getElementById('invoices-search-pane');if(el){el.style.display=el.style.display==='none'?'block':'none';}return false;">
            {{ __('Search/Filter') }}
        </a>
    </li>
    @foreach($statusTabs as $val => $label)
        @php $isActive = ($activeStatus === $val); @endphp
        <li class="{{ $isActive ? 'active' : '' }}">
            <a href="{{ route('admin.invoices.index', ['status' => $val]) }}" class="{{ $isActive ? 'active' : '' }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="tab-content admin-tabs" id="invoices-search-pane">
    <div class="tab-pane active">
        <form method="GET" action="{{ route('admin.invoices.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.table.invoice_num') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="invoice_num" value="{{ request('invoice_num') }}" class="form-control input-200" placeholder="{{ __('common.table.invoice_num') }}">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            @foreach($statusTabs as $val => $label)
                                <option value="{{ $val }}" @selected($activeStatus === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.client') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control input-250" placeholder="{{ __('common.table.client') }}">
                    </td>
                    <td class="fieldlabel">{{ __('common.table.due_date') }}</td>
                    <td class="fieldarea">
                        <input type="date" name="due_date" value="{{ request('due_date') }}" class="form-control input-150">
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default">{{ __('common.actions.search') }}</button>
            </div>
        </form>
    </div>
</div>

<form id="bulk-form" method="POST" action="{{ route('admin.bulk.invoice-action') }}">
    @csrf
    <input type="hidden" name="action" id="bulk-action" value="">

    <div class="context-btn-container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin:10px 0;">
        <div style="font-size:12px;color:#555;">
            <strong>{{ $invoices->total() }}</strong> {{ __('Records Found') }}, {{ __('Page') }} {{ $invoices->currentPage() }} {{ __('of') }} {{ max(1, $invoices->lastPage()) }}
        </div>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            <span style="font-size:12px;color:#666;margin-right:2px;">{{ __('admin.invoices.bulk_actions') }}:</span>
            <button type="submit" class="btn btn-success btn-sm" data-action="paid">{{ __('admin.invoices.mark_paid_btn') }}</button>
            <button type="submit" class="btn btn-danger btn-sm" data-action="cancel">{{ __('common.actions.cancel') }}</button>
            <button type="submit" class="btn btn-default btn-sm" data-action="send">{{ __('admin.invoices.send') }}</button>
            <button type="submit" class="btn btn-warning btn-sm" data-action="remind">{{ __('admin.invoices.remind') }}</button>
            <a href="{{ $exportCsvUrl }}" class="btn btn-default btn-sm">{{ __('Export CSV') }}</a>
            <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary btn-sm">+ {{ __('admin.invoices.create_invoice') }}</a>
        </div>
    </div>

    <div class="tablebg">
        <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
            <thead>
                <tr>
                    <th width="20"><input type="checkbox" id="select-all" aria-label="{{ __('admin.bulk.select_all') }}"></th>
                    <th>{{ __('common.table.invoice_num') }}</th>
                    <th>{{ __('common.table.client') }}</th>
                    <th>{{ __('common.table.date') }}</th>
                    <th>{{ __('common.table.due_date') }}</th>
                    <th style="text-align:right;">{{ __('common.table.total') }}</th>
                    <th>{{ __('common.table.payment_method') }}</th>
                    <th>{{ __('common.table.status') }}</th>
                    <th width="70">{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    @php
                        $statusKey = strtolower($invoice->status ?? "");
                        $badgeClass = match($statusKey) {
                            "active", "paid"             => "badge-paid",
                            "pending", "payment_pending" => "badge-pending",
                            "unpaid"                     => "badge-unpaid",
                            "overdue"                    => "badge-overdue",
                            "suspended"                  => "badge-suspended",
                            "terminated"                 => "badge-terminated",
                            "cancelled"                  => "badge-cancelled",
                            "fraud"                      => "badge-fraud",
                            "draft"                      => "badge-draft",
                            "refunded"                   => "badge-refunded",
                            default                      => "badge-cancelled",
                        };
                        $statusLabel = $statusKey === "overdue"
                            ? __('common.status.unpaid') . '/' . __('common.status.overdue')
                            : (__('common.status.' . $statusKey) !== 'common.status.' . $statusKey
                                ? __('common.status.' . $statusKey)
                                : status_label($invoice->status ?? ""));
                        $badgeStyle = $statusKey === "overdue"
                            ? "background:#fff3cd !important;color:#856404 !important;"
                            : "";
                    @endphp
                    <tr>
                        <td style="text-align:center;">
                            <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="row-checkbox" aria-label="{{ '#' . ($invoice->invoice_num ?? $invoice->id) }}">
                        </td>
                        <td>
                            <a href="{{ route('admin.invoices.show', $invoice) }}" style="font-family:monospace;">#{{ $invoice->invoice_num ?? $invoice->id }}</a>
                        </td>
                        <td>
                            @if($invoice->client)
                                <a href="{{ route('admin.clients.show', $invoice->client_id) }}">{{ $invoice->client->full_name }}</a>
                            @else
                                {{ __('N/A') }}
                            @endif
                        </td>
                        <td>{{ $invoice->date?->format(date_fmt()) ?? "-" }}</td>
                        <td>{{ $invoice->due_date?->format(date_fmt()) ?? "-" }}</td>
                        <td style="text-align:right;font-weight:600;">{{ dual_money_fmt($invoice->total, $invoice) }}</td>
                        <td>{{ $invoice->payment_method ? payment_method_label((string) $invoice->payment_method) : "-" }}</td>
                        <td><span class="badge {{ $badgeClass }} whmcs-status {{ $statusKey }}" style="{{ $badgeStyle }}">{{ $statusLabel }}</span></td>
                        <td style="text-align:center;">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-default btn-xs">{{ __('common.actions.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:24px;color:#888;">{{ __('admin.invoices.no_invoices') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:10px;">
        {{ $invoices->withQueryString()->links() }}
    </div>
</form>

<script>
document.getElementById('select-all').addEventListener('change', function () {
    document.querySelectorAll('.row-checkbox').forEach(function (cb) { cb.checked = this.checked; }, this);
});
document.querySelectorAll('#bulk-form button[data-action]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        var selected = document.querySelectorAll('.row-checkbox:checked');
        if (selected.length === 0) {
            e.preventDefault();
            pnDialog.alert(@js(__('admin.invoices.select_none')), { icon: 'warning' });
            return;
        }
        document.getElementById('bulk-action').value = this.getAttribute('data-action');
    });
});
</script>

@endsection

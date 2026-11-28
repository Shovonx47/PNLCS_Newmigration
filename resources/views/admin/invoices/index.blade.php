@extends("admin.layouts.app")
@section("title", __("admin.invoices.title"))
@section("content")

@php
    $statusOptions = [
        'all' => __('admin.invoices.status_any'),
        'unpaid' => __('admin.invoices.filter_unpaid'),
        'payment-pending' => __('admin.invoices.filter_payment_pending'),
        'paid' => __('admin.invoices.filter_paid'),
        'overdue' => __('admin.invoices.filter_overdue'),
        'cancelled' => __('admin.invoices.filter_cancelled'),
        'draft' => __('admin.invoices.filter_draft'),
        'refunded' => __('common.status.refunded'),
        'collections' => __('common.status.collections'),
    ] + (\App\Support\AutoCharge::enabled()
        ? [\App\Http\Controllers\Admin\InvoiceController::CHARGE_REVIEW_FILTER => __('admin.invoices.filter_charge_review')]
        : []);

    $selectedStatus = $activeStatus ?? strtolower((string) request('status', 'unpaid'));
    $selectedStatus = $selectedStatus === 'payment_pending' ? 'payment-pending' : $selectedStatus;
    $filterOpen = $hasActiveFilters ?? false;
    $exportCsvUrl = route('admin.invoices.export', request()->query());
    $currentSort = $sort ?? 'due_date';
    $currentDir = $dir ?? 'desc';

    $sortUrl = function (string $col) use ($currentSort, $currentDir): string {
        $nextDir = ($currentSort === $col && $currentDir === 'desc') ? 'asc' : 'desc';

        return route('admin.invoices.index', array_merge(request()->query(), [
            'sort' => $col,
            'dir' => $nextDir,
            'page' => 1,
        ]));
    };

    $sortCaret = function (string $col) use ($currentSort, $currentDir): string {
        if ($currentSort !== $col) {
            return '';
        }

        return $currentDir === 'asc' ? '▲' : '▼';
    };

    $contrastTextFor = function (?string $hex): string {
        $hex = ltrim(trim((string) $hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#111111';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $lum = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $lum < 110 ? '#ffffff' : '#1f2937';
    };

    $canManage = auth('admin')->user()?->hasPermission('manage_invoices');
    $canCreate = auth('admin')->user()?->hasPermission('create_invoices');
@endphp

<h1 style="margin:0 0 12px 0;">{{ __('admin.invoices.title') }}</h1>

{{-- ── WHMCS Per-Currency Totals Summary Bar ── --}}
<div class="whmcs-invoice-totals-bar">
    @foreach($currencyTotals as $tot)
        <span class="whmcs-curr-block">
            <span class="whmcs-curr-code">{{ $tot['code'] }}</span>
            {{ __('admin.invoices.summary_paid') }}
            <span class="textgreen">{{ \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney($tot['paid'], $tot['code'], true) }}</span>
            {{ __('admin.invoices.summary_unpaid') }}
            <span class="textred">{{ \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney($tot['unpaid'], $tot['code'], true) }}</span>
            {{ __('admin.invoices.summary_overdue') }}
            <strong>{{ \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney($tot['overdue'], $tot['code'], true) }}</strong>
        </span>
    @endforeach
</div>

{{-- ── Single Collapsible Search/Filter Tab (Exact WHMCS Blend Structure) ── --}}
<ul class="admin-tabs" role="tablist">
    <li class="active">
        <a href="#invoices-search-tab"
           class="active"
           onclick="var el=document.getElementById('invoices-search-pane');if(el){el.style.display=el.style.display==='none'?'block':'none';}return false;">
            {{ __('admin.invoices.search_filter') }}
        </a>
    </li>
</ul>

<div class="tab-content admin-tabs" id="invoices-search-pane" style="display:{{ $filterOpen ? 'block' : 'none' }};margin-bottom:10px;">
    <div class="tab-pane active">
        <form method="GET" action="{{ route('admin.invoices.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('admin.clients.name') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="search" value="{{ request('search', request('client_name')) }}" class="form-control input-250">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('common.table.invoice_num') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="invoice_num" value="{{ request('invoice_num') }}" class="form-control input-200">
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.invoices.line_item_description') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="line_item" value="{{ request('line_item') }}" class="form-control input-250">
                    </td>
                    <td class="fieldlabel">{{ __('common.table.payment_method') }}</td>
                    <td class="fieldarea">
                        <select name="payment_method" class="form-control select-inline">
                            <option value="">{{ __('admin.invoices.status_any') }}</option>
                            @foreach($paymentMethods as $gwKey => $gwLabel)
                                <option value="{{ $gwKey }}" @selected(request('payment_method') === $gwKey)>{{ $gwLabel }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.invoices.invoice_date') }}</td>
                    <td class="fieldarea">
                        <input type="date" name="invoice_date" value="{{ request('invoice_date') }}" class="form-control input-150">
                    </td>
                    <td class="fieldlabel">{{ __('admin.invoices.total_from') }}</td>
                    <td class="fieldarea">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <input type="number" step="0.01" name="total_from" value="{{ request('total_from') }}" class="form-control input-100">
                            <span>{{ __('admin.invoices.total_to') }}</span>
                            <input type="number" step="0.01" name="total_to" value="{{ request('total_to') }}" class="form-control input-100">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.due_date') }}</td>
                    <td class="fieldarea">
                        <input type="date" name="due_date" value="{{ request('due_date') }}" class="form-control input-150">
                    </td>
                    <td class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            @foreach($statusOptions as $val => $label)
                                <option value="{{ $val }}" @selected($selectedStatus === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.invoices.paid_on') }}</td>
                    <td class="fieldarea" colspan="3">
                        <input type="date" name="date_paid" value="{{ request('date_paid') }}" class="form-control input-150">
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.search') }}</button>
                <a href="{{ route('admin.invoices.index', ['status' => 'all']) }}" class="btn btn-default btn-sm">{{ __('admin.invoices.list_all') }}</a>
                <a href="{{ $exportCsvUrl }}" class="btn btn-default btn-sm">{{ __('common.actions.export_csv') }}</a>
                @if($canCreate)
                    <a href="{{ route('admin.invoices.create') }}" class="btn btn-default btn-sm">+ {{ __('admin.invoices.create_invoice') }}</a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- ── Records Count & Jump to Page Bar ── --}}
<div class="whmcs-record-bar" style="margin:8px 0 6px;">
    <div style="font-size:12px;color:#333;">
        {{ number_format($invoices->total()) }} {{ __('admin.invoices.records_found') }}@if($invoices->total() > 0), {{ __('admin.invoices.showing_range', ['from' => $invoices->firstItem(), 'to' => $invoices->lastItem()]) }}@endif
    </div>
    <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#333;">
        <span>{{ __('admin.invoices.jump_to_page') }}</span>
        <select onchange="if(this.value) window.location.href=this.value;"
                aria-label="{{ __('admin.invoices.jump_to_page') }}"
                style="padding:2px 6px;border:1px solid #ccc;border-radius:3px;font-size:12px;background:#fff;">
            @for($pg = 1; $pg <= max(1, $invoices->lastPage()); $pg++)
                <option value="{{ $invoices->url($pg) }}" @selected($pg === $invoices->currentPage())>{{ $pg }}</option>
            @endfor
        </select>
    </div>
</div>

<form id="bulk-form" method="POST" action="{{ route('admin.bulk.invoice-action') }}">
    @csrf
    <input type="hidden" name="action" id="bulk-action" value="">

    <div class="tablebg">
        <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
            <thead>
                <tr>
                    <th width="22"><input type="checkbox" id="select-all" aria-label="{{ __('admin.bulk.select_all') }}"></th>
                    <th class="sortable">
                        <a href="{{ $sortUrl('invoice_num') }}">
                            <span>{{ __('common.table.invoice_num') }}</span>
                            @if($sortCaret('invoice_num') !== '')<span class="whmcs-sort-caret">{{ $sortCaret('invoice_num') }}</span>@endif
                        </a>
                    </th>
                    <th class="sortable">
                        <a href="{{ $sortUrl('client_name') }}">
                            <span>{{ __('admin.clients.name') }}</span>
                            @if($sortCaret('client_name') !== '')<span class="whmcs-sort-caret">{{ $sortCaret('client_name') }}</span>@endif
                        </a>
                    </th>
                    <th class="sortable">
                        <a href="{{ $sortUrl('date') }}">
                            <span>{{ __('admin.invoices.invoice_date') }}</span>
                            @if($sortCaret('date') !== '')<span class="whmcs-sort-caret">{{ $sortCaret('date') }}</span>@endif
                        </a>
                    </th>
                    <th class="sortable">
                        <a href="{{ $sortUrl('due_date') }}">
                            <span>{{ __('common.table.due_date') }}</span>
                            @if($sortCaret('due_date') !== '')<span class="whmcs-sort-caret">{{ $sortCaret('due_date') }}</span>@endif
                        </a>
                    </th>
                    <th class="sortable" style="max-width:110px;line-height:1.25;">
                        <a href="{{ $sortUrl('last_capture') }}">
                            <span>{{ __('admin.invoices.last_capture_attempt') }}</span>
                            @if($sortCaret('last_capture') !== '')<span class="whmcs-sort-caret">{{ $sortCaret('last_capture') }}</span>@endif
                        </a>
                    </th>
                    <th class="sortable">
                        <a href="{{ $sortUrl('total') }}">
                            <span>{{ __('common.table.total') }}</span>
                            @if($sortCaret('total') !== '')<span class="whmcs-sort-caret">{{ $sortCaret('total') }}</span>@endif
                        </a>
                    </th>
                    <th class="sortable">
                        <a href="{{ $sortUrl('payment_method') }}">
                            <span>{{ __('common.table.payment_method') }}</span>
                            @if($sortCaret('payment_method') !== '')<span class="whmcs-sort-caret">{{ $sortCaret('payment_method') }}</span>@endif
                        </a>
                    </th>
                    <th class="sortable">
                        <a href="{{ $sortUrl('status') }}">
                            <span>{{ __('common.table.status') }}</span>
                            @if($sortCaret('status') !== '')<span class="whmcs-sort-caret">{{ $sortCaret('status') }}</span>@endif
                        </a>
                    </th>
                    <th width="135"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    @php
                        $rawNum = (string) ($invoice->invoice_num ?? '');
                        $displayNum = str_starts_with($rawNum, 'INV-WHMCS-')
                            ? substr($rawNum, strlen('INV-WHMCS-'))
                            : ($rawNum !== '' ? $rawNum : (string) $invoice->id);

                        $statusKey = strtolower((string) ($invoice->status ?? ''));
                        $statusClass = match($statusKey) {
                            'paid', 'active' => 'textgreen',
                            'unpaid', 'overdue', 'partially_paid' => 'textred',
                            'collections', 'payment_pending', 'payment-pending' => 'textorange',
                            default => 'textgrey',
                        };
                        $statusLabel = invoice_status_label($invoice->status ?? '');

                        $groupColor = trim((string) ($invoice->client?->group?->color ?? ''));
                        $hasGroupColor = $groupColor !== '' && ! in_array(strtolower($groupColor), ['#ffffff', '#fff', 'white'], true);
                        $groupTextColor = $hasGroupColor ? $contrastTextFor($groupColor) : '';

                        $lastCapture = $invoice->chargeAttempt?->updated_at?->timezone(display_tz())->format(datetime_fmt())
                            ?? __('admin.invoices.na_short');

                        $formattedTotal = has_billing_conversion($invoice)
                            ? dual_money_fmt($invoice->total, $invoice)
                            : \App\Http\Controllers\Admin\InvoiceController::formatWhmcsMoney((float) $invoice->total, $invoice->source_currency);
                    @endphp
                    <tr>
                        <td style="text-align:center;">
                            <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="row-checkbox" aria-label="{{ $rawNum ?: $invoice->id }}">
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" title="{{ $rawNum ?: $invoice->id }}">{{ $displayNum }}</a>
                        </td>
                        <td style="text-align:center;">
                            @if($invoice->client)
                                @if($hasGroupColor)
                                    <a href="{{ route('admin.clients.show', $invoice->client_id) }}"
                                       class="whmcs-client-group-tag"
                                       style="background-color:{{ $groupColor }};color:{{ $groupTextColor }};">{{ $invoice->client->full_name }}</a>
                                @else
                                    <a href="{{ route('admin.clients.show', $invoice->client_id) }}">{{ $invoice->client->full_name }}</a>
                                @endif
                            @else
                                {{ __('common.misc.na') }}
                            @endif
                        </td>
                        <td style="text-align:center;white-space:nowrap;">{{ $invoice->date?->format(date_fmt()) ?? '-' }}</td>
                        <td style="text-align:center;white-space:nowrap;">{{ $invoice->due_date?->format(date_fmt()) ?? '-' }}</td>
                        <td style="text-align:center;white-space:nowrap;">{{ $lastCapture }}</td>
                        <td style="text-align:center;white-space:nowrap;">{{ $formattedTotal }}</td>
                        <td style="text-align:center;">{{ $invoice->payment_method ? payment_method_label((string) $invoice->displayPaymentMethod()) : '-' }}</td>
                        <td style="text-align:center;">
                            <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="whmcs-inv-actions">
                            <div class="whmcs-btn-group">
                                <a href="{{ route('admin.invoices.view', $invoice) }}" target="_blank" rel="noopener" class="whmcs-row-btn">{{ __('common.actions.view') }}</a><a href="{{ route('admin.invoices.show', $invoice) }}" class="whmcs-row-btn">{{ __('common.actions.edit') }}</a>@if($canManage)<button type="button" class="whmcs-row-btn whmcs-row-btn-danger" data-delete-url="{{ route('admin.invoices.destroy', $invoice) }}" data-delete-confirm="{{ __('admin.invoices.confirm_delete_invoice', ['num' => $displayNum]) }}" onclick="whmcsDeleteSingleInvoice(this)">{{ __('common.actions.delete') }}</button>@endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:24px;color:#888;">{{ __('admin.invoices.no_invoices') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Bottom With Selected Bar (Exact WHMCS 6-button layout) ── --}}
    <div class="whmcs-with-selected-bar">
        <span>{{ __('admin.invoices.with_selected') }}</span>
        <button type="submit" class="btn btn-success btn-sm" data-action="paid">{{ __('admin.invoices.mark_paid') }}</button>
        <button type="submit" class="btn btn-default btn-sm" data-action="unpaid">{{ __('admin.invoices.mark_unpaid') }}</button>
        <button type="submit" class="btn btn-default btn-sm" data-action="cancel">{{ __('admin.invoices.mark_cancelled') }}</button>
        <button type="submit" class="btn btn-default btn-sm" data-action="duplicate">{{ __('admin.invoices.duplicate_invoice') }}</button>
        <button type="submit" class="btn btn-default btn-sm" data-action="remind">{{ __('admin.invoices.send_reminder') }}</button>
        <button type="submit" class="btn btn-danger btn-sm" data-action="delete">{{ __('common.actions.delete') }}</button>
    </div>

    {{-- ── Centered WHMCS Pagination ── --}}
    @include('admin.partials.whmcs-pagination', ['paginator' => $invoices])
</form>

<form id="single-delete-invoice-form" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
document.getElementById('select-all').addEventListener('change', function () {
    var checked = this.checked;
    document.querySelectorAll('.row-checkbox').forEach(function (cb) { cb.checked = checked; });
});

document.querySelectorAll('#bulk-form button[data-action]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        var selected = document.querySelectorAll('.row-checkbox:checked');
        if (selected.length === 0) {
            e.preventDefault();
            pnDialog.alert(@js(__('admin.invoices.select_none')), { icon: 'warning' });
            return;
        }
        var action = this.getAttribute('data-action');
        document.getElementById('bulk-action').value = action;
        if (action === 'delete') {
            pnConfirm(e, @js(__('admin.invoices.confirm_bulk_delete')), { danger: true });
            return;
        }
        if (action === 'cancel') {
            pnConfirm(e, @js(__('admin.invoices.confirm_bulk_cancel')), { danger: true });
            return;
        }
    });
});

function whmcsDeleteSingleInvoice(btn) {
    var url = btn.getAttribute('data-delete-url');
    var msg = btn.getAttribute('data-delete-confirm');
    var form = document.getElementById('single-delete-invoice-form');
    if (window.pnDialog && typeof window.pnDialog.confirm === 'function') {
        window.pnDialog.confirm(msg, { danger: true }).then(function (yes) {
            if (yes) {
                form.setAttribute('action', url);
                form.submit();
            }
        });
    }
}
</script>

@endsection

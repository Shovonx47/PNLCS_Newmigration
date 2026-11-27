@extends("admin.layouts.app")
@section("title", __("admin.payment_notifications.title"))
@section("content")

<h1>{{ __('admin.payment_notifications.title') }}</h1>

@php
    $pnTabs = [
        "pending"  => __('admin.payment_notifications.filter_pending'),
        "approved" => __('admin.payment_notifications.filter_approved'),
        "rejected" => __('admin.payment_notifications.filter_rejected'),
        "all"      => __('admin.payment_notifications.filter_all'),
    ];
@endphp

<ul class="admin-tabs" role="tablist">
    <li class="active">
        <a href="#pn-search-tab" class="active" onclick="var el=document.getElementById('pn-search-pane');if(el){el.style.display=el.style.display==='none'?'block':'none';}return false;">
            {{ __('Search/Filter') }}
        </a>
    </li>
    @foreach($pnTabs as $val => $label)
        @php $isPnActive = ($status == $val); @endphp
        <li class="{{ $isPnActive ? 'active' : '' }}">
            <a href="{{ route('admin.payment-notifications.index', ['status' => $val]) }}" class="{{ $isPnActive ? 'active' : '' }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

<div class="tab-content admin-tabs" id="pn-search-pane">
    <div class="tab-pane active">
        <form method="GET" action="{{ route('admin.payment-notifications.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            @foreach($pnTabs as $val => $label)
                                <option value="{{ $val }}" @selected($status == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default">{{ __('common.actions.search') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="context-btn-container" style="display:flex;align-items:center;justify-content:space-between;margin:10px 0;">
    <div style="font-size:12px;color:#555;">
        <strong>{{ $notifications->total() }}</strong> {{ __('Records Found') }}, {{ __('Page') }} {{ $notifications->currentPage() }} {{ __('of') }} {{ max(1, $notifications->lastPage()) }}
    </div>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('common.table.invoice_num') }}</th>
                <th>{{ __('common.table.client') }}</th>
                <th>{{ __('admin.payment_notifications.sender') }}</th>
                <th style="text-align:right;">{{ __('common.table.amount') }}</th>
                <th>{{ __('admin.payment_notifications.transfer_date') }}</th>
                <th>{{ __('admin.payment_notifications.receipt') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th>{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($notifications as $pn)
            <tr>
                <td>{{ $pn->id }}</td>
                <td>
                    @if($pn->invoice)
                        <a href="{{ route('admin.invoices.show', $pn->invoice) }}" style="font-family:monospace;">#{{ $pn->invoice->invoice_num ?? $pn->invoice->id }}</a>
                    @else
                        {{ '—' }}
                    @endif
                </td>
                <td>
                    @if($pn->client)
                        <a href="{{ route('admin.clients.show', $pn->client) }}">{{ $pn->client->first_name }} {{ $pn->client->last_name }}</a>
                    @else
                        {{ '—' }}
                    @endif
                </td>
                <td>
                    {{ $pn->sender_name }}
                    @if($pn->bank_name)
                        <br><small style="color:#888;">{{ $pn->bank_name }}</small>
                    @endif
                    @if($pn->reference)
                        <br><small style="color:#888;">{{ __('admin.payment_notifications.reference') }}: {{ $pn->reference }}</small>
                    @endif
                </td>
                <td style="text-align:right;"><strong>{{ money_fmt((float) $pn->amount) }}</strong></td>
                <td>{{ $pn->transfer_date?->format('Y-m-d') }}</td>
                <td>
                    @if($pn->receipt_path)
                        <a href="{{ route('admin.payment-notifications.receipt', $pn) }}" target="_blank" rel="noopener">{{ __('admin.payment_notifications.view_receipt') }}</a>
                    @else
                        {{ '—' }}
                    @endif
                </td>
                <td>
                    @php
                        $badgeColor = match($pn->status) {
                            'approved' => '#28a745',
                            'rejected' => '#dc3545',
                            default    => '#f0ad4e',
                        };
                    @endphp
                    <span class="whmcs-status {{ strtolower($pn->status) }}" style="display:inline-block;padding:2px 10px;border-radius:10px;font-size:12px;color:#fff;background:{{ $badgeColor }};">
                        {{ __('admin.payment_notifications.status_' . $pn->status) }}
                    </span>
                    @if($pn->reviewed_at)
                        <br><small style="color:#888;">{{ $pn->reviewed_at->format('Y-m-d H:i') }} — {{ $pn->admin?->username ?? '—' }}</small>
                    @endif
                    @if($pn->admin_note)
                        <br><small style="color:#888;">{{ $pn->admin_note }}</small>
                    @endif
                </td>
                <td style="white-space:nowrap;">
                    @if($pn->status === 'pending')
                        <form method="POST" action="{{ route('admin.payment-notifications.approve', $pn) }}" style="display:inline;margin:0;"
                              onsubmit="return pnConfirm(event, @js(__('admin.payment_notifications.approve_confirm')));">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-success">{{ __('admin.payment_notifications.approve') }}</button>
                        </form>
                        <button type="button" class="btn btn-xs btn-danger" onclick="document.getElementById('reject-form-{{ $pn->id }}').style.display='table-row';">
                            {{ __('admin.payment_notifications.reject') }}
                        </button>
                    @else
                        {{ '—' }}
                    @endif
                </td>
            </tr>
            @if($pn->status === 'pending')
            <tr id="reject-form-{{ $pn->id }}" style="display:none;background:#fff5f5;">
                <td colspan="9">
                    <form method="POST" action="{{ route('admin.payment-notifications.reject', $pn) }}" style="display:flex;gap:8px;align-items:center;margin:0;">
                        @csrf
                        <input type="text" name="admin_note" class="form-control" style="flex:1;" required
                               placeholder="{{ __('admin.payment_notifications.reject_reason_placeholder') }}">
                        <button type="submit" class="btn btn-xs btn-danger">{{ __('admin.payment_notifications.reject_confirm') }}</button>
                    </form>
                </td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="9" style="text-align:center;color:#888;padding:24px;">{{ __('admin.payment_notifications.none_found') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:12px;">{{ $notifications->links() }}</div>

@endsection

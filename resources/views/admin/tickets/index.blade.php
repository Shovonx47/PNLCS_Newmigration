@extends('admin.layouts.app')
@section('title', __('admin.tickets.title'))
@section('content')
<h1>{{ __('admin.tickets.title') }}</h1>

<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="active"><a href="#tabTicketFilter" role="tab" data-toggle="tab">{{ 'Search/Filter' }}</a></li>
</ul>
<div class="tab-content admin-tabs" style="margin-bottom:15px;">
    <div class="tab-pane active" id="tabTicketFilter">
        <form method="GET" action="{{ route('admin.tickets.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            @foreach(['', 'open', 'answered', 'customer-reply', 'closed', 'on hold'] as $val)
                            <option value="{{ $val }}" @selected(request('status', '') == $val)>{{ $val === '' ? __('common.form.all') : status_label($val) }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('common.table.client') }}</td>
                    <td width="35%" class="fieldarea">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="guests" value="1" @checked(request()->boolean('guests'))>
                            {{ __('admin.tickets.only_guests') }}
                        </label>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default"><i class="fas fa-search"></i> {{ __('common.actions.filter') }}</button>
            </div>
        </form>
    </div>
</div>

@php $canDelete = auth('admin')->user()?->hasPermission('manage_tickets'); @endphp

<form id="ticket-bulk-form" method="POST" action="{{ route('admin.tickets.bulk-delete') }}">
@csrf
<div class="context-btn-container" style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
    <div style="display:flex;gap:4px;flex-wrap:wrap;align-items:center;">
        @php
            $ticketTabs = [
                '' => __('common.form.all'),
                'awaiting' => 'Awaiting Reply',
                'flagged' => 'Flagged',
                'Open' => status_label('Open'),
                'Answered' => status_label('Answered'),
                'Customer-Reply' => status_label('Customer-Reply'),
                'On Hold' => status_label('On Hold'),
                'In Progress' => status_label('In Progress'),
                'Closed' => status_label('Closed'),
            ];
            $curTicketStatus = (string) request('status', '');
            if ($curTicketStatus === 'any') { $curTicketStatus = ''; }
        @endphp
        @foreach($ticketTabs as $val => $label)
        @php
            $isActive = (strcasecmp($curTicketStatus, $val) === 0);
        @endphp
        <a href="{{ route('admin.tickets.index', $val === '' ? [] : ['status' => $val]) }}"
           class="btn btn-xs {{ $isActive ? 'btn-primary' : 'btn-default' }}">{{ $label }}</a>
        @endforeach
        <a href="{{ request()->boolean('guests') ? route('admin.tickets.index', request()->except('guests', 'page')) : route('admin.tickets.index', array_merge(request()->except('page'), ['guests' => 1])) }}"
           class="btn btn-xs {{ request()->boolean('guests') ? 'btn-info' : 'btn-default' }}">
            <i class="fas fa-user-slash"></i> {{ __('admin.tickets.only_guests') }}
        </a>
    </div>
    <div style="display:flex;gap:6px;align-items:center;">
        <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> {{ __('admin.tickets.open_ticket') }}</a>
        @if($canDelete)
        <button type="submit" class="btn btn-danger btn-sm" id="ticket-bulk-delete"><i class="fas fa-trash"></i> {{ __('admin.tickets.bulk_delete') }}</button>
        @endif
    </div>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                @if($canDelete)<th style="width:28px;"><input type="checkbox" id="ticket-select-all"></th>@endif
                <th>{{ __('admin.tickets.ticket_num') }}</th>
                <th>{{ __('common.table.department') }}</th>
                <th>{{ __('common.table.subject') }}</th>
                <th>{{ __('common.table.client') }}</th>
                <th>{{ __('common.table.priority') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th>{{ __('common.table.last_reply') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $ticket)
            @php
            $statusBadge = match(strtolower($ticket->status ?? '')) {
                'open'            => 'badge-open',
                'answered'        => 'badge-answered',
                'customer-reply'  => 'badge-customer-reply',
                'closed'          => 'badge-terminated',
                'on hold'         => 'badge-suspended',
                'in progress'     => 'badge-pending',
                default           => 'badge-cancelled',
            };
            $priorityBadge = match(strtolower($ticket->priority ?? '')) {
                'high', 'critical' => 'badge-terminated',
                'medium'           => 'badge-pending',
                'low'              => 'badge-active',
                default            => 'badge-cancelled',
            };
            @endphp
            <tr>
                @if($canDelete)<td><input type="checkbox" name="ticket_ids[]" value="{{ $ticket->id }}" class="ticket-row-checkbox"></td>@endif
                <td><a href="{{ route('admin.tickets.show', $ticket) }}">{{ '#' . $ticket->tid }}</a></td>
                <td>{{ $ticket->department->name ?? 'N/A' }}</td>
                <td><a href="{{ route('admin.tickets.show', $ticket) }}"><strong>{{ Str::limit($ticket->title, 55) }}</strong></a></td>
                <td>
                    {{ $ticket->client?->full_name ?? $ticket->name ?? $ticket->email }}
                    @if(! $ticket->client_id)
                    <span class="whmcs-status badge badge-cancelled">{{ __('admin.tickets.guest') }}</span>
                    @endif
                </td>
                <td><span class="whmcs-status badge {{ $priorityBadge }}">{{ ucfirst($ticket->priority ?? '') }}</span></td>
                <td><span class="whmcs-status badge {{ $statusBadge }}">{{ status_label($ticket->status ?? '') }}</span></td>
                <td>{{ $ticket->last_reply?->diffForHumans() ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $canDelete ? 8 : 7 }}" class="text-center">{{ __('admin.tickets.no_tickets') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
</form>

<div style="margin-top:10px;">
    {{ $tickets->withQueryString()->links() }}
</div>

@if($canDelete)
<script>
document.getElementById('ticket-select-all').addEventListener('change', function () {
    document.querySelectorAll('.ticket-row-checkbox').forEach(function (cb) { cb.checked = this.checked; }, this);
});
document.getElementById('ticket-bulk-delete').addEventListener('click', function (e) {
    var count = document.querySelectorAll('.ticket-row-checkbox:checked').length;
    if (count === 0) {
        e.preventDefault();
        pnDialog.alert(@js(__('admin.tickets.select_none')), { icon: 'warning' });
        return;
    }
    return pnConfirm(e, @js(__('admin.tickets.bulk_delete_confirm')).replace(':count', count), { danger: true });
});
</script>
@endif
@endsection

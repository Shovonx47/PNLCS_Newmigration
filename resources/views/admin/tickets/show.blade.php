@extends('admin.layouts.app')
@section('title', '#' . ($ticket->tid ?? $ticket->id) . ' - ' . $ticket->title)
@section('content')
<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div>
        <h2 class="ticket-subject" style="margin-top:0;">
            {{ '#' . ($ticket->tid ?? $ticket->id) . ' - ' . $ticket->title }}
            <span class="whmcs-status badge-{{ strtolower($ticket->status) }}" style="font-size:12px;vertical-align:middle;margin-left:6px;">{{ status_label($ticket->status) }}</span>
            <span class="whmcs-status" style="font-size:12px;vertical-align:middle;margin-left:4px;">{{ ucfirst($ticket->priority) }}</span>
            @if(strcasecmp((string) $ticket->status, 'Closed') !== 0)
            <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" style="display:inline;margin-left:6px;">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="Closed">
                <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                <input type="hidden" name="department_id" value="{{ $ticket->department_id }}">
                <input type="hidden" name="flag" value="{{ $ticket->flag }}">
                <button type="submit" class="btn btn-default btn-xs close-ticket">{{ __('common.status.closed') }}</button>
            </form>
            @endif
        </h2>
        <span class="ticketlastreply">
            {{ __('common.table.last_reply') }}{{ ': ' }}{{ ($ticket->last_reply ?? $ticket->replies->last()?->created_at ?? $ticket->created_at)?->timezone(display_tz())->format(datetime_fmt()) }}
        </span>
    </div>
    <div style="display:flex;gap:6px;align-items:center;">
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.tickets.back') }}</a>
    </div>
</div>

<div class="clearfix" style="margin-bottom:10px;"></div>

@if(isset($ticket->notes) && $ticket->notes->count() > 0)
<div class="client-notes" style="margin-bottom:12px;">
    @foreach($ticket->notes as $note)
    <div class="panel panel-warning infobox" style="margin-bottom:8px;">
        <div class="panel-heading" style="display:flex;justify-content:space-between;align-items:center;">
            <strong>{{ $note->admin }} <span style="font-weight:400;">({{ __('admin.tickets.internal_notes') }})</span></strong>
            <div class="pull-right" style="display:flex;align-items:center;gap:8px;">
                <span>{{ $note->created_at->timezone(display_tz())->format(datetime_fmt()) }}</span>
                @if($note->admin === auth('admin')->user()?->username)
                <form method="POST" action="{{ route('admin.tickets.notes.destroy', [$ticket, $note]) }}" style="margin:0;display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.tickets.note_confirm_delete')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-default btn-xs">{{ __('common.actions.delete') }}</button>
                </form>
                @endif
            </div>
        </div>
        <div class="panel-body">{!! nl2br(e($note->message)) !!}</div>
    </div>
    @endforeach
</div>
@endif

@if($ticket->feedback)
<div class="infobox" style="margin-bottom:12px;">
    <strong>{{ __('admin.tickets.feedback') }}{{ ': ' }}{{ __('admin.tickets.feedback_rating', ['rating' => $ticket->feedback->rating]) }}</strong>
    @if($ticket->feedback->comments)
    <div style="margin-top:6px;">{!! nl2br(e($ticket->feedback->comments)) !!}</div>
    @endif
</div>
@endif

@php
    $canReplyNotes = auth('admin')->user()?->hasPermission('reply_tickets');
    $canManageTicket = auth('admin')->user()?->hasPermission('manage_tickets');
    $relatedService = $ticket->service ? \App\Models\Service::with('product')->find($ticket->service) : null;
    $assignee = $ticket->flag ? \App\Models\Admin::find($ticket->flag) : null;
    $predefined = \App\Models\TicketPredefinedCategory::with(['replies' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get()->filter(fn ($c) => $c->replies->isNotEmpty());
@endphp

<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="active"><a href="#tabAddReply" role="tab" data-toggle="tab" data-whmcs-tab="tabAddReply">{{ __('admin.tickets.add_reply') }}</a></li>
    @if($canReplyNotes)
    <li><a href="#tabAddNote" role="tab" data-toggle="tab" data-whmcs-tab="tabAddNote">{{ __('admin.tickets.add_note') }}</a></li>
    @endif
    <li><a href="#tabTicketOptions" role="tab" data-toggle="tab" data-whmcs-tab="tabTicketOptions">{{ 'Options / Ticket Details' }}</a></li>
</ul>

<div class="tab-content admin-tabs" style="margin-bottom:15px;">
    <div class="tab-pane active" id="tabAddReply">
        <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}">
            @csrf
            <div class="form-group" style="margin-bottom:10px;">
                <textarea name="message" rows="8" required placeholder="{{ __('admin.tickets.reply_placeholder') }}" class="form-control"></textarea>
            </div>
            <div class="ticket-reply-submit-options clearfix" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                <div class="pull-left" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    @if($predefined->isNotEmpty())
                    <select id="predefined-reply" class="form-control input-sm" style="max-width:360px;" aria-label="{{ __('admin.tickets.insert_predefined') }}"
                            onchange="if (this.value) { var t = this.form.querySelector('textarea[name=message]'); t.value = (t.value ? t.value.replace(/\s*$/, '') + '\n\n' : '') + this.options[this.selectedIndex].dataset.reply; t.focus(); this.value = ''; }">
                        <option value="">{{ __('admin.tickets.insert_predefined') }}</option>
                        @foreach($predefined as $category)
                        <optgroup label="{{ $category->name }}">
                            @foreach($category->replies as $saved)
                            <option value="{{ $saved->id }}" data-reply="{{ $saved->reply }}">{{ $saved->name }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    @endif
                </div>
                <div class="pull-right" style="margin-left:auto;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-reply"></i> {{ __('admin.tickets.send_reply') }}</button>
                </div>
            </div>
        </form>
    </div>

    @if($canReplyNotes)
    <div class="tab-pane" id="tabAddNote">
        <form method="POST" action="{{ route('admin.tickets.notes.store', $ticket) }}">
            @csrf
            <p class="text-muted" style="font-size:12px;margin:0 0 8px;">{{ __('admin.tickets.note_hint') }}</p>
            <div class="form-group" style="margin-bottom:10px;">
                <textarea name="note" rows="6" required maxlength="10000" class="form-control"></textarea>
            </div>
            <div class="clearfix" style="text-align:right;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-sticky-note"></i> {{ __('admin.tickets.save_note') }}</button>
            </div>
        </form>
    </div>
    @endif

    <div class="tab-pane" id="tabTicketOptions">
        @if($options ?? null)
        <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}">
            @csrf
            @method('PUT')
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('admin.tickets.department_label') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="department_id" class="form-control select-inline">
                            @foreach($options['departments'] as $dep)
                            <option value="{{ $dep->id }}" @selected($dep->id === $ticket->department_id)>{{ $dep->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('admin.tickets.client_label') }}</td>
                    <td width="35%" class="fieldarea">
                        @if($ticket->client)
                        <a href="{{ route('admin.clients.show', $ticket->client) }}">{{ $ticket->client?->full_name ?? $ticket->name ?? $ticket->email }}</a>
                        @else
                        {{ $ticket->name ?? $ticket->email }}
                        @endif
                        @if($ticket->email)
                        <span class="text-muted">({{ $ticket->email }})</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('common.table.status') }}</td>
                    <td class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            @foreach($options['statuses'] as $title)
                            <option value="{{ $title }}" @selected(strcasecmp($title, (string) $ticket->status) === 0)>{{ $title }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="fieldlabel">{{ __('common.table.priority') }}</td>
                    <td class="fieldarea">
                        <select name="priority" class="form-control select-inline">
                            @foreach(['Low', 'Medium', 'High'] as $p)
                            <option value="{{ $p }}" @selected(strcasecmp($p, (string) $ticket->priority) === 0)>{{ __('admin.tickets.priority_'.strtolower($p)) }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.tickets.assigned_to') }}</td>
                    <td class="fieldarea">
                        <select name="flag" class="form-control select-inline">
                            <option value="">{{ __('admin.tickets.unassigned') }}</option>
                            @foreach($options['staff'] as $staff)
                            <option value="{{ $staff->id }}" @selected((int) $ticket->flag === $staff->id)>{{ $staff->username }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="fieldlabel">{{ __('admin.tickets.created_label') }}</td>
                    <td class="fieldarea">{{ $ticket->created_at->timezone(display_tz())->format(datetime_fmt()) }}</td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('common.actions.save') }}</button>
            </div>
        </form>
        @else
        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td width="15%" class="fieldlabel">{{ __('admin.tickets.department_label') }}</td>
                <td width="35%" class="fieldarea">{{ $ticket->department->name ?? 'N/A' }}</td>
                <td width="15%" class="fieldlabel">{{ __('admin.tickets.client_label') }}</td>
                <td width="35%" class="fieldarea">
                    @if($ticket->client)
                    <a href="{{ route('admin.clients.show', $ticket->client) }}">{{ $ticket->client?->full_name ?? $ticket->name ?? $ticket->email }}</a>
                    @else
                    {{ $ticket->name ?? $ticket->email }}
                    @endif
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('admin.tickets.email_label') }}</td>
                <td class="fieldarea">{{ $ticket->email }}</td>
                <td class="fieldlabel">{{ __('admin.tickets.created_label') }}</td>
                <td class="fieldarea">{{ $ticket->created_at->timezone(display_tz())->format(datetime_fmt()) }}</td>
            </tr>
            @if($assignee)
            <tr>
                <td class="fieldlabel">{{ __('admin.tickets.assigned_to') }}</td>
                <td class="fieldarea" colspan="3">{{ $assignee->username }}</td>
            </tr>
            @endif
        </table>
        @endif

        @if($canManageTicket)
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #e5e5e5;text-align:right;">
            <form method="POST" action="{{ route('admin.tickets.destroy', $ticket) }}" style="margin:0;display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.tickets.delete_confirm')))">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> {{ __('admin.tickets.delete') }}</button>
            </form>
        </div>
        @endif
    </div>
</div>

@if($relatedService)
<div class="tablebg" style="margin-bottom:15px;">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.tickets.related_service_label') }}</th>
                <th>{{ __('common.table.domain') }}</th>
                <th>{{ __('common.table.amount') }}</th>
                <th>{{ __('common.table.billing_cycle') }}</th>
                <th>{{ __('common.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr class="rowhighlight">
                <td><a href="{{ route('admin.services.show', $relatedService) }}">{{ $relatedService->product->name ?? __('admin.tickets.service') }}{{ $relatedService->domain ? ' ('.$relatedService->domain.')' : '' }}</a></td>
                <td>{{ $relatedService->domain ?? '-' }}@if($relatedService->primaryIp()) <code>{{ $relatedService->primaryIp() }}</code>@endif</td>
                <td>{{ money_fmt($relatedService->amount) }}</td>
                <td>{{ cycle_label($relatedService->billing_cycle) }}</td>
                <td><span class="whmcs-status badge-{{ strtolower((string) $relatedService->status) }}">{{ status_label($relatedService->status) }}</span></td>
            </tr>
        </tbody>
    </table>
</div>
@endif

<div id="ticketreplies">
    @php $origIsStaff = !empty($ticket->admin); @endphp
    <div class="reply {{ $origIsStaff ? 'staff' : '' }}">
        <div class="leftcol">
            <div class="submitter">
                <div class="name">
                    @if(!$origIsStaff && $ticket->client)
                    <a href="{{ route('admin.clients.show', $ticket->client) }}">{{ $ticket->client?->full_name ?? $ticket->name ?? $ticket->email }}</a>
                    @else
                    {{ $origIsStaff ? $ticket->admin : ($ticket->name ?? $ticket->client?->full_name ?? $ticket->email) }}
                    @endif
                </div>
                <div class="title">
                    {{ $origIsStaff ? 'Staff' : 'Client' }}
                    @if(!$origIsStaff && $ticket->email)
                    <div style="font-size:11px;word-break:break-all;">{{ $ticket->email }}</div>
                    @endif
                    <div style="font-size:11px;margin-top:2px;">{{ $ticket->department->name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        <div class="rightcol">
            <div class="postedon">
                {{ $ticket->created_at->timezone(display_tz())->format(datetime_fmt()) }}
                @if($assignee)
                <span style="float:right;">{{ __('admin.tickets.assigned_to') }}{{ ' ' }}{{ $assignee->username }}</span>
                @endif
            </div>
            <div class="msgwrap">
                <div class="message">{!! nl2br(e($ticket->message)) !!}</div>
            </div>
        </div>
        <div class="clear"></div>
    </div>

    @foreach($ticket->replies as $reply)
    @php $isStaff = !empty($reply->admin); @endphp
    <div class="reply {{ $isStaff ? 'staff' : '' }}">
        <div class="leftcol">
            <div class="submitter">
                <div class="name">
                    @if($isStaff)
                    {{ $reply->admin }}
                    @elseif($ticket->client)
                    <a href="{{ route('admin.clients.show', $ticket->client) }}">{{ $ticket->client?->full_name ?? $ticket->name ?? $ticket->email }}</a>
                    @else
                    {{ $ticket->name ?? $ticket->email }}
                    @endif
                </div>
                <div class="title">{{ $isStaff ? 'Staff' : 'Client' }}</div>
            </div>
        </div>
        <div class="rightcol">
            <div class="postedon">{{ $reply->created_at->timezone(display_tz())->format(datetime_fmt()) }}</div>
            <div class="msgwrap">
                <div class="message">{!! nl2br(e($reply->message)) !!}</div>
            </div>
        </div>
        <div class="clear"></div>
    </div>
    @endforeach
</div>

<script>
document.querySelectorAll('[data-whmcs-tab]').forEach(function (link) {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        var targetId = this.getAttribute('data-whmcs-tab');
        var nav = this.closest('ul.admin-tabs');
        var content = nav ? nav.nextElementSibling : null;
        if (nav) {
            nav.querySelectorAll('li').forEach(function (li) { li.classList.remove('active'); });
            if (this.parentElement) this.parentElement.classList.add('active');
        }
        if (content) {
            content.querySelectorAll('.tab-pane').forEach(function (pane) { pane.classList.remove('active'); });
            var pane = document.getElementById(targetId);
            if (pane) pane.classList.add('active');
        }
    });
});
</script>
@endsection

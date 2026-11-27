@extends('client.layouts.twentyone')
@section('title', $ticket->title)
@section('page_heading', 'View Ticket #' . $ticket->tid)

@php
    $isClosed = strtolower((string) $ticket->status) === 'closed';
    $requestorLabel = $isClosed ? 'Closed' : 'Open';
@endphp

@section('content')

@if($isClosed)
    <div class="alert alert-warning text-center" role="alert">{{ __('client.tickets.ticket_closed') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        @foreach($errors->all() as $validationError)
            <div>{{ $validationError }}</div>
        @endforeach
    </div>
@endif

<div class="card view-ticket">
    <div class="card-body p-3">
        <h3 class="card-title">
            {{ 'View Ticket' }} #{{ $ticket->tid }}
            <div class="ticket-actions float-sm-right mt-3 mt-sm-0">
                <button id="ticketReply" type="button" class="btn btn-default btn-sm"
                        onclick="document.getElementById('ticketReplyContainer').scrollIntoView({behavior:'smooth'});">
                    <i class="fas fa-pencil-alt fa-fw"></i>
                    Reply
                </button>
                @if($isClosed)
                    <button class="btn btn-danger btn-sm" disabled="disabled">
                        <i class="fas fa-times fa-fw"></i>
                        {{ 'Closed' }}
                    </button>
                @else
                    <button class="btn btn-danger btn-sm" type="button"
                            onclick="document.getElementById('close-ticket-form').submit();">
                        <i class="fas fa-times fa-fw"></i>
                        {{ __('client.tickets.close_ticket') }}
                    </button>
                    <form id="close-ticket-form" method="POST" action="{{ route('client.tickets.close', $ticket) }}" class="d-none">@csrf</form>
                @endif
            </div>
        </h3>

        <p>
            {{ __('common.form.subject') }}:
            <strong>{{ $ticket->title }}</strong>
        </p>
        <p class="small text-muted mb-0">
            {{ $ticket->department->name ?? __('client.tickets.general_dept') }}
            &middot; {{ __('client.tickets.opened') }} {{ $ticket->created_at->timezone(display_tz())->format(datetime_fmt()) }}
            @if($ticket->last_reply) &middot; {{ __('client.tickets.last_reply') }} {{ $ticket->last_reply->diffForHumans() }}@endif
            &middot; {{ __('client.tickets.priority') }}: {{ $ticket->priority ?? 'Medium' }}
        </p>
    </div>

    {{-- The opening message, then every reply, in the posted-by / message shape
         the theme gives a ticket: gray author strip, white body. --}}
    <div class="card-body">
        <div class="ticket-reply markdown-content">
            <div class="posted-by">
                <i class="far fa-user fa-fw"></i>
                Posted by <span class="posted-by-name">{{ $ticket->name ?? auth()->user()->full_name }}</span> on <span class="posted-on">{{ $ticket->created_at->timezone(display_tz())->format(datetime_fmt()) }}</span>
                <span class="label requestor-badge requestor-type-client float-md-right">{{ 'Client' }}</span>
            </div>
            <div class="message p-3">
                {!! nl2br(e($ticket->message)) !!}
                @if($ticket->attachment)
                    <hr>
                    <i class="far fa-paperclip fa-fw"></i>
                    <a href="{{ route('client.tickets.attachment', $ticket) }}">{{ __('client.tickets.download_attachment') }}</a>
                @endif
            </div>
        </div>
    </div>

    @foreach($ticket->replies as $reply)
    <div class="card-body">
        <div class="ticket-reply markdown-content{{ $reply->admin ? ' staff' : '' }}">
            <div class="posted-by">
                @if($reply->admin)
                    Posted by <span class="posted-by-name">{{ filled($staffNames[$reply->admin] ?? null) ? $staffNames[$reply->admin] : __('client.tickets.support_staff') }}</span> on <span class="posted-on">{{ $reply->created_at->timezone(display_tz())->format(datetime_fmt()) }}</span>
                    <span class="label requestor-badge requestor-type-owner float-md-right">{{ __('client.tickets.support_staff') }}</span>
                @else
                    Posted by <span class="posted-by-name">{{ auth()->user()->full_name }}</span> on <span class="posted-on">{{ $reply->created_at->timezone(display_tz())->format(datetime_fmt()) }}</span>
                    <span class="label requestor-badge requestor-type-client float-md-right">{{ 'Client' }}</span>
                @endif
            </div>
            <div class="message p-3">
                {!! nl2br(e($reply->message)) !!}
                @if($reply->attachment)
                    <hr>
                    <i class="far fa-paperclip fa-fw"></i>
                    <a href="{{ route('client.tickets.reply.attachment', [$ticket, $reply->id]) }}">{{ __('client.tickets.download_attachment') }}</a>
                @endif

                {{-- A closed ticket asks how we did, on the last staff reply, as
                     the theme's rating block does. --}}
                @if($reply->admin && $isClosed && $loop->last)
                    <div class="clearfix">
                        @if($ticket->feedback)
                            <div class="rating-done">
                                @for($rating = 1; $rating <= 5; $rating++)
                                    <span class="star{{ (5 - $ticket->feedback->rating) < $rating ? ' active' : '' }}"></span>
                                @endfor
                                <div class="rated">{{ __('client.tickets.feedback_given', ['rating' => $ticket->feedback->rating]) }}</div>
                            </div>
                        @else
                            <div class="rating">
                                @foreach([5, 4, 3, 2, 1] as $score)
                                    <form method="POST" action="{{ route('client.tickets.feedback', $ticket) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="rating" value="{{ $score }}">
                                        <button type="submit" class="star" title="{{ __('client.tickets.feedback_score_' . $score) }}"></button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

@if($isClosed && ! $ticket->feedback)
<div class="card d-print-none">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.tickets.feedback_title') }}</h3>
        <form method="POST" action="{{ route('client.tickets.feedback', $ticket) }}">
            @csrf
            <fieldset class="form-group">
                <legend class="control-label">{{ __('client.tickets.feedback_question') }}</legend>
                <div class="d-flex gap-8 flex-wrap">
                    @foreach([1, 2, 3, 4, 5] as $score)
                    <label class="radio-inline"><input type="radio" name="rating" value="{{ $score }}" required> {{ __('client.tickets.feedback_score_' . $score) }}</label>
                    @endforeach
                </div>
            </fieldset>
            <div class="form-group">
                <label class="control-label" for="fb-comments">{{ __('client.tickets.feedback_comments') }}</label>
                <textarea id="fb-comments" name="comments" rows="3" maxlength="2000" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('client.tickets.feedback_send') }}</button>
        </form>
    </div>
</div>
@endif

<div class="card d-print-none" id="ticketReplyContainer">
    <div class="card-body">
        <h3 class="card-title">Reply</h3>

        @if(! $isClosed)
        <form method="POST" action="{{ route('client.tickets.reply', $ticket) }}" enctype="multipart/form-data" role="form" id="frmReply">
            @csrf
            <div class="row">
                <div class="form-group col-md-4">
                    <label for="inputName">{{ __('client.tickets.client_name') }}</label>
                    <input class="form-control" type="text" id="inputName" value="{{ $ticket->name ?? auth()->user()->full_name }}" disabled="disabled">
                </div>
                <div class="form-group col-md-5">
                    <label for="inputEmail">{{ __('client.tickets.client_email') }}</label>
                    <input class="form-control" type="text" id="inputEmail" value="{{ auth()->user()->email }}" disabled="disabled">
                </div>
            </div>

            <div class="form-group">
                <label for="inputMessage">{{ __('common.form.message') }}</label>
                <textarea name="message" id="inputMessage" rows="12" class="form-control" required placeholder="{{ __('client.tickets.reply_placeholder') }}"></textarea>
            </div>

            <div class="form-group">
                <label for="inputAttachment1">{{ 'Attachments' }}</label>
                <div class="input-group mb-1">
                    <div class="custom-file">
                        <input type="file" class="custom-file-input" name="attachment" id="inputAttachment1" accept=".jpg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
                        <label class="custom-file-label text-truncate" for="inputAttachment1">{{ 'Choose File' }}</label>
                    </div>
                </div>
                <div class="text-muted">
                    <small>{{ 'Allowed File Extensions' }}: .jpg, .png, .gif, .pdf, .doc, .docx, .txt, .zip ({{ __('client.tickets.max_10mb') }})</small>
                </div>
            </div>

            <div class="form-group text-center">
                <button type="submit" class="btn btn-primary">{{ __('client.tickets.post_reply_btn') }}</button>
                <a href="{{ route('client.tickets.index') }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
        @else
        <div class="alert alert-info mb-0">
            {{ __('client.tickets.ticket_closed') }} <a href="{{ route('client.tickets.create') }}">{{ __('client.tickets.open_new_ticket') }}</a> {{ __('client.tickets.need_further_assistance') }}
        </div>
        @endif
    </div>
</div>

@endsection

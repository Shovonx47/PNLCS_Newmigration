@extends('client.layouts.twentyone')
@section('title', 'WhatsApp Notifications')

@section('content')
@if($sender)
<div class="text-right mb-3">
    <a href="https://wa.me/{{ ltrim($sender, '+') }}" target="_blank" rel="noopener" class="btn btn-success btn-sm">
        <i class="fab fa-whatsapp"></i> {{ 'Message us on WhatsApp' }}
    </a>
</div>
@endif

@unless($enabled)
<div class="alert alert-info">{{ 'WhatsApp history is not available right now.' }}</div>
@endunless

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fab fa-whatsapp fa-fw"></i>&nbsp;{{ 'Messages sent to ' . ($number ? '+'.$number : 'your number') }}</h5>
    </div>
    @if($messages->isEmpty())
        <div class="card-body text-center text-muted py-4">
            <i class="fab fa-whatsapp fa-2x mb-2 d-block"></i>
            <div>{{ 'No WhatsApp messages yet.' }}</div>
            <div class="small mt-1">{{ 'Invoice reminders and service notices will appear here.' }}</div>
        </div>
    @else
        <div class="list-group list-group-flush">
            @foreach($messages as $message)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>{{ $message->created_at?->format('d M Y H:i') }}</span>
                        <span>{{ $message->template_name ?: ucfirst($message->source) }}</span>
                    </div>
                    <div style="white-space:pre-line;">{{ $message->message }}</div>
                    @if($message->status !== 'Sent')
                        <div class="small text-warning mt-1">{{ 'Delivery: ' . $message->status }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>

@if($messages->hasPages())
<div class="mt-3">{{ $messages->links() }}</div>
@endif
@endsection

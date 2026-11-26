@extends('client.layouts.app')
@section('title', 'WhatsApp')

@section('content')
<div class="pn-page-header">
    <div>
        <h1 class="pn-page-title">WhatsApp</h1>
        <p class="pn-page-subtitle">Every WhatsApp message we have sent to your number.</p>
    </div>
    @if($sender)
    <a href="https://wa.me/{{ ltrim($sender, '+') }}" target="_blank" rel="noopener" class="btn btn-primary">
        Message us on WhatsApp
    </a>
    @endif
</div>

@unless($enabled)
<div class="alert alert-info">WhatsApp history is not available right now.</div>
@endunless

<div class="pn-card">
    <div class="pn-card-header">
        <span class="pn-card-title">Messages sent to {{ $number ? '+'.$number : 'your number' }}</span>
    </div>
    <div class="pn-card-body-flush">
        @forelse($messages as $message)
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
            <div style="display:flex;justify-content:space-between;gap:10px;font-size:11px;color:var(--muted);margin-bottom:5px;">
                <span>{{ $message->created_at?->format('d M Y H:i') }}</span>
                <span>{{ $message->template_name ?: ucfirst($message->source) }}</span>
            </div>
            <div style="font-size:13.5px;color:var(--text);white-space:pre-line;">{{ $message->message }}</div>
            @if($message->status !== 'Sent')
            <div style="font-size:11px;color:#b45309;margin-top:5px;">Delivery: {{ $message->status }}</div>
            @endif
        </div>
        @empty
        <div style="text-align:center;padding:44px 20px;color:var(--muted);">
            <i class="ri-whatsapp-line" style="font-size:30px;display:block;margin-bottom:8px;opacity:.5;"></i>
            <div style="font-size:13.5px;">No WhatsApp messages yet.</div>
            <div style="font-size:12px;margin-top:4px;">Invoice reminders and service notices will appear here.</div>
        </div>
        @endforelse
    </div>
</div>

@if($messages->hasPages())
<div style="margin-top:14px;">{{ $messages->links() }}</div>
@endif
@endsection

@extends("client.layouts.twentyone")
@section("title", $email->subject)
@section("content")

<a href="{{ route('client.emails.index') }}" class="pn-back">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    {{ __('client.emails.back_to_emails') }}
</a>

<div class="pn-page-header">
    <div>
        <h1 class="h1 mb-0">{{ $email->subject }}</h1>
        <p class="text-muted">
            {{ $email->date?->timezone(display_tz())->format(datetime_fmt()) ?? $email->created_at?->timezone(display_tz())->format(datetime_fmt()) }}
            &nbsp;·&nbsp; {{ __('client.emails.sent_to') }}: {{ $email->to }}
        </p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <iframe sandbox="" srcdoc="{{ $email->message }}" style="width:100%;min-height:560px;border:0;background:var(--card);border-radius:8px"></iframe>
    </div>
</div>

@endsection

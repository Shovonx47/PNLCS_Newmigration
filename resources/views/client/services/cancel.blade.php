@extends('client.layouts.twentyone')
@section('title', __('client.services.request_cancellation'))
@section('content')

<div class="pn-page-header">
    <h1>{{ __('client.services.request_cancellation') }}</h1>
    <a href="{{ route('client.services.show', $service) }}" class="btn btn-outline btn-sm">&larr; {{ __('client.services.back_to_service') }}</a>
</div>

<div class="alert alert-warning" role="alert">
    <strong>{{ __('client.actions.warning') }}:</strong> {{ __('client.services.cancel_warning') }}: <strong>{{ $service->product?->name ?? 'Service' }}</strong>
    @if($service->domain) &mdash; {{ $service->domain }}@endif
</div>

<div class="card">
    <div class="card-header">{{ __('client.services.cancellation_request') }}</div>
    <div class="card-body">
        @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('client.services.cancel.submit', $service) }}">
            @csrf
            <div class="form-group">
                <label class="control-label">{{ __('client.services.cancellation_type') }} <span class="text-danger">*</span></label>
                <div class="radio">
                    <label>
                        <input type="radio" name="type" value="Immediate" {{ old('type') === 'Immediate' ? 'checked' : '' }} required>
                        <strong>{{ __('client.services.immediate') }}</strong>
                        <div class="text-muted">{{ __('client.services.immediate_desc') }}</div>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="type" value="End of Billing Period" {{ old('type') === 'End of Billing Period' ? 'checked' : '' }}>
                        <strong>{{ __('client.services.end_of_period') }}</strong>
                        <div class="text-muted">{{ __('client.services.end_of_period_desc') }} {{ $service->next_due_date?->format(date_fmt()) ?? __('client.services.end_of_period_fallback') }}.</div>
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label" for="reason">{{ __('client.services.cancellation_reason') }}</label>
                <textarea id="reason" name="reason" rows="4" class="form-control" placeholder="{{ __('client.services.cancellation_reason_placeholder') }}">{{ old('reason') }}</textarea>
            </div>
            <button type="submit" class="btn btn-danger">{{ __('client.services.submit_cancellation') }}</button>
            <a href="{{ route('client.services.show', $service) }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
        </form>
    </div>
</div>

@endsection

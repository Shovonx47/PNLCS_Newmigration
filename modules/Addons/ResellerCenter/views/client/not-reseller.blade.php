@extends('client.layouts.twentyone')
@section('title', 'Reseller Portal')

@section('content')
<div class="card text-center">
    <div class="card-body py-5">
        <h4 class="mb-2">{{ 'Active Reseller Membership Required' }}</h4>
        <p class="text-muted mx-auto mb-4" style="max-width:520px;">
            {{ 'Your billing account is not currently enrolled in an active reseller tier. Open a support ticket if you would like to request partner onboarding.' }}
        </p>
        <a href="{{ route('client.tickets.create') }}" class="btn btn-primary">{{ 'Contact Support' }}</a>
    </div>
</div>
@endsection

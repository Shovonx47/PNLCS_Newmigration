@extends('client.layouts.app')
@section('title', 'Reseller Portal')

@section('content')
<div class="pn-page-header">
    <div>
        <h1 class="pn-page-title">Reseller Portal</h1>
        <p class="pn-page-subtitle">Partner account management, branding, and commission reporting.</p>
    </div>
    <a href="{{ route('client.home') }}" class="btn btn-outline">&larr; Dashboard</a>
</div>

<div class="pn-card" style="padding:28px;text-align:center;">
    <h2 style="margin:0 0 8px;font-size:18px;font-weight:700;">Active Reseller Membership Required</h2>
    <p style="margin:0 auto 18px;max-width:520px;color:var(--muted);font-size:14px;">
        Your billing account is not currently enrolled in an active reseller tier. Open a support ticket if you would like to request partner onboarding.
    </p>
    <a href="{{ route('client.tickets.create') }}" class="btn btn-primary">Contact Support</a>
</div>
@endsection

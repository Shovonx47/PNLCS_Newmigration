@php
    $status = $verification?->status ?? 'not_started';
@endphp
<div class="alert {{ $status === 'failed' ? 'alert-danger' : ($status === 'pending' ? 'alert-info' : 'alert-warning') }} d-flex align-items-center justify-content-between flex-wrap mb-4">
    <div class="mr-3">
        <strong><i class="fas fa-id-card fa-fw"></i> {{ 'Account Identity Verification' }}</strong>
        <div class="small mt-1">
            @if($status === 'pending')
                {{ 'Your verification submission is currently under review.' }}
            @elseif($status === 'failed')
                {{ 'Your previous verification attempt requires attention' . ($verification?->decline_reason ? ': '.$verification->decline_reason : '.') }}
            @else
                {{ 'Verify your identity to ensure uninterrupted access to all services and orders.' }}
            @endif
        </div>
    </div>
    <a href="{{ route('client.kyc.index') }}" class="btn {{ $status === 'pending' ? 'btn-default' : 'btn-primary' }} btn-sm mt-2 mt-sm-0">
        {{ $status === 'pending' ? 'View Status' : 'Verify Identity' }}
    </a>
</div>

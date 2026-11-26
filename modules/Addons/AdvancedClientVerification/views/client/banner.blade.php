@php
    $status = $verification?->status ?? 'not_started';
@endphp
<div class="pn-card" style="padding:18px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
    <div>
        <div class="pn-section-title" style="margin:0;">Account Identity Verification</div>
        <div style="font-size:13px;color:var(--muted);margin-top:4px;">
            @if($status === 'pending')
                Your verification submission is currently under review.
            @elseif($status === 'failed')
                Your previous verification attempt requires attention{{ $verification?->decline_reason ? ': '.$verification->decline_reason : '.' }}
            @else
                Verify your identity to ensure uninterrupted access to all services and orders.
            @endif
        </div>
    </div>
    <a href="{{ route('client.kyc.index') }}" class="btn {{ $status === 'pending' ? 'btn-outline' : 'btn-primary' }}">
        {{ $status === 'pending' ? 'View Status' : 'Verify Identity' }}
    </a>
</div>

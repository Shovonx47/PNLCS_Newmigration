@extends("client.layouts.app")
@section("title", "Security — " . ($service->domain ?: "Hosting"))
@section("content")

@include("bhpanel::partials.nav")

<div class="db-head">
    <div class="db-head-ic"><i class="ri-lock-password-line"></i></div>
    <div>
        <h1>Panel Security &amp; Firewall</h1>
        <div class="sub">Manage hosting panel two-factor authentication, passkeys, and firewall IP blocks.</div>
    </div>
</div>

@if(!empty($recoveryCodes) && is_array($recoveryCodes))
<div class="pn-alert pn-alert-warning" style="margin-bottom:18px;">
    <strong><i class="ri-key-fill"></i> Save Your One-Time Recovery Codes Now:</strong>
    <p style="margin:6px 0 8px;font-size:13px;">These codes are shown only once. Store them in a safe place:</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @foreach($recoveryCodes as $code)
            <code style="padding:4px 10px;background:var(--card);border:1px solid var(--border);border-radius:6px;">{{ $code }}</code>
        @endforeach
    </div>
</div>
@endif

<div class="db-card">
    <div class="db-ch">
        <span><i class="ri-smartphone-line"></i> Control Panel Two-Factor Authentication (TOTP)</span>
        @if($sec)
            <span class="badge {{ !empty($sec['two_fa_on']) ? 'badge-active' : 'badge-cancelled' }}">
                {{ !empty($sec['two_fa_on']) ? 'Enabled' : 'Off' }}
            </span>
        @endif
    </div>
    <div style="padding:18px;">
        @if($sec && !empty($sec['two_fa_on']))
            <form method="POST" action="{{ route('client.services.bhpanel.security', $service) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                @csrf
                <input type="hidden" name="bhp_action" value="disable">
                <div class="db-fld" style="max-width:260px;">
                    <label class="db-lbl">Authenticator or Recovery Code</label>
                    <input type="text" name="otp" required placeholder="6-digit code" class="db-inp">
                </div>
                <button type="submit" class="db-act">Turn Off 2FA</button>
            </form>
        @elseif($setup)
            <p style="margin:0 0 10px;font-size:13.5px;color:var(--muted);">
                Enter this secret key into Google Authenticator, Authy, or 1Password, then enter the 6-digit code below:
            </p>
            <div style="margin-bottom:14px;"><span class="sv-code" style="font-size:14px;padding:6px 12px;">{{ $setup['secret'] }}</span></div>
            <form method="POST" action="{{ route('client.services.bhpanel.security', $service) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                @csrf
                <input type="hidden" name="bhp_action" value="enable">
                <div class="db-fld" style="max-width:240px;">
                    <label class="db-lbl">6-Digit Verification Code</label>
                    <input type="text" name="otp" required placeholder="123456" class="db-inp">
                </div>
                <button type="submit" class="db-btn"><i class="ri-shield-check-line"></i> Confirm &amp; Enable 2FA</button>
            </form>
        @else
            <p style="margin:0 0 12px;font-size:13.5px;color:var(--muted);">
                Protect direct sign-ins to your hosting control panel with an authenticator app.
            </p>
            <a href="{{ route('client.services.bhpanel.security', ['service' => $service, 'setup' => '1']) }}" class="db-btn">
                <i class="ri-qr-code-line"></i> Set Up Two-Factor Authentication
            </a>
        @endif
    </div>
</div>

<div class="db-card">
    <div class="db-ch"><span><i class="ri-fingerprint-line"></i> Passkeys</span></div>
    <div style="padding:18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div style="font-size:13.5px;color:var(--muted);">
            Register hardware security keys or biometric passkeys inside your hosting control panel.
        </div>
        <form method="POST" action="{{ route('client.services.bhpanel.signin', $service) }}" target="_blank" style="margin:0;">
            @csrf
            <input type="hidden" name="to" value="passkeys">
            <button type="submit" class="db-act"><i class="ri-external-link-line"></i> Manage Passkeys in Panel</button>
        </form>
    </div>
</div>

@if($fwOffered)
<div class="db-card">
    <div class="db-ch"><span><i class="ri-shield-flash-line"></i> Hosting Firewall Unblock</span></div>
    <div style="padding:18px;">
        @if($fw && !empty($fw['blocked']))
            <div class="pn-alert pn-alert-danger" style="margin-bottom:14px;">
                IP address <code>{{ $fwIp }}</code> is currently blocked on the server firewall.
            </div>
        @endif
        <form method="POST" action="{{ route('client.services.bhpanel.security', $service) }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <input type="hidden" name="bhp_action" value="unblock">
            <div class="db-fld" style="max-width:280px;">
                <label class="db-lbl">IP Address to Check / Unblock</label>
                <input type="text" name="fw_ip" value="{{ $fwIp }}" required class="db-inp">
            </div>
            <button type="submit" class="db-btn"><i class="ri-lock-unlock-line"></i> Unblock IP Address</button>
        </form>
    </div>
</div>
@endif

@endsection

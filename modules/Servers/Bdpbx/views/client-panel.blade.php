@if($ipBlocked)
<div class="pn-alert pn-alert-danger" style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
    <div>
        <strong><i class="ri-shield-keyhole-line"></i> Network Restriction Detected</strong><br>
        <span>Your current IP address (<code>{{ $visitorIp }}</code>) is temporarily restricted after failed device sign-in attempts. Unblock it instantly to reconnect your softphones.</span>
    </div>
    <form method="POST" action="{{ route('client.services.bdpbx.unblock-ip', $service) }}" style="margin:0;">
        @csrf
        <input type="hidden" name="ip_address" value="{{ $visitorIp }}">
        <button type="submit" class="pn-btn pn-btn-sm pn-btn-primary"><i class="ri-lock-unlock-line"></i> Unblock My IP</button>
    </form>
</div>
@endif

@if($isSuspended)
<div class="pn-alert pn-alert-info" style="margin-bottom:20px;">
    <strong><i class="ri-volume-up-line"></i> Voice Broadcast Extension Active:</strong>
    While standard call extensions are paused until your open invoice is settled, <strong>Extension 999 (Voice SMS &amp; OTP)</strong> remains active without interruption.
</div>
@endif

<div class="sv-g2">
    <div class="sv-panel">
        <div class="sv-ph"><i class="ri-phone-line"></i> Cloud PBX Portal Access</div>
        <ul class="sv-dl">
            <li>
                <span class="k">PBX Domain</span>
                <span class="v">
                    @if($portalUrl)
                        <a href="{{ $portalUrl }}" target="_blank" rel="noopener" class="sv-code">{{ $domain }} <i class="ri-external-link-line"></i></a>
                    @else
                        <span class="text-muted">Provisioning</span>
                    @endif
                </span>
            </li>
            <li>
                <span class="k">Portal Username</span>
                <span class="v"><span class="sv-code">{{ $username }}</span></span>
            </li>
            <li>
                <span class="k">Portal Password</span>
                <span class="v" style="display:inline-flex;align-items:center;gap:6px;">
                    @if($password !== '')
                        <input type="password" readonly value="{{ $password }}" id="bdpbx-portal-pw" class="form-control" style="width:140px;height:28px;padding:2px 8px;font-family:ui-monospace,Menlo,monospace;font-size:12.5px;">
                        <button type="button" class="pn-btn pn-btn-sm" data-toggle-pw="bdpbx-portal-pw" title="Show or hide password"><i class="ri-eye-line"></i></button>
                        <button type="button" class="pn-btn pn-btn-sm" data-copy-pw="bdpbx-portal-pw" title="Copy password"><i class="ri-file-copy-line"></i></button>
                    @else
                        <span class="text-muted">One-Click Sign-In Active</span>
                    @endif
                </span>
            </li>
            <li>
                <span class="k">30-Day Call Recordings</span>
                <span class="v">{{ number_format($recordingsCount) }} stored</span>
            </li>
        </ul>
    </div>

    <div class="sv-panel">
        <div class="sv-ph"><i class="ri-customer-service-2-line"></i> Softphone &amp; IP Phone Setup</div>
        <ul class="sv-dl">
            <li><span class="k">SIP Server / Domain</span><span class="v"><span class="sv-code">{{ $domain ?: '—' }}</span></span></li>
            <li><span class="k">Transport &amp; Port</span><span class="v"><span class="sv-code">UDP 5060</span></span></li>
            <li><span class="k">SIP Username</span><span class="v">Extension Number (e.g. 101)</span></li>
            <li><span class="k">Call Recording Retention</span><span class="v">30 Days Automatic Rolling</span></li>
        </ul>
    </div>
</div>

<div class="sv-panel" style="margin-bottom:22px;">
    <div class="sv-ph" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <span><i class="ri-Sip-line ri-git-branch-line"></i> Provisioned Extensions</span>
        <span class="text-muted" style="font-size:12px;font-weight:600;">{{ count($extensions) }} extension(s)</span>
    </div>
    @if(count($extensions) > 0)
    <div style="overflow-x:auto;">
        <table class="pn-table">
            <thead>
                <tr>
                    <th>Extension</th>
                    <th>Caller Label</th>
                    <th>SIP Password</th>
                    <th>SIP Realm</th>
                    <th>Status</th>
                    <th style="text-align:right;">Manage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($extensions as $idx => $ext)
                    @php
                        $extNum = (string) ($ext['extension'] ?? '');
                        $is999 = $extNum === '999';
                        $extLabel = (string) ($ext['effective_caller_id_name'] ?? '');
                        if ($is999 && ($extLabel === '' || stripos($extLabel, 'support') !== false)) {
                            $extLabel = 'Voice SMS & OTP';
                        }
                        $extPass = (string) ($ext['password'] ?? '');
                        $enabled = ! empty($ext['enabled']);
                        $pwInputId = 'bdpbx-ext-pw-' . $idx;
                    @endphp
                    <tr>
                        <td><span class="sv-code">{{ $extNum }}</span></td>
                        <td>{{ $extLabel !== '' ? $extLabel : 'Extension ' . $extNum }}</td>
                        <td>
                            @if($is999)
                                <span class="text-muted" style="font-size:12.5px;"><i class="ri-lock-line"></i> System Managed</span>
                            @elseif($extPass !== '')
                                <div style="display:inline-flex;align-items:center;gap:6px;">
                                    <input type="password" readonly value="{{ $extPass }}" id="{{ $pwInputId }}" class="form-control" style="width:130px;height:28px;padding:2px 8px;font-family:ui-monospace,Menlo,monospace;font-size:12px;">
                                    <button type="button" class="pn-btn pn-btn-sm" data-toggle-pw="{{ $pwInputId }}" title="Reveal"><i class="ri-eye-line"></i></button>
                                    <button type="button" class="pn-btn pn-btn-sm" data-copy-pw="{{ $pwInputId }}" title="Copy"><i class="ri-file-copy-line"></i></button>
                                </div>
                            @else
                                <span class="text-muted">Set new password &rarr;</span>
                            @endif
                        </td>
                        <td><span class="text-muted" style="font-family:ui-monospace,Menlo,monospace;font-size:12.5px;">{{ $domain }}</span></td>
                        <td>
                            @if($is999)
                                <span class="badge badge-active">Always Active</span>
                            @elseif($enabled)
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge badge-suspended">Paused</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            @if($is999)
                                <span class="text-muted" style="font-size:12px;"><i class="ri-shield-star-line"></i> Protected</span>
                            @else
                                <details style="display:inline-block;text-align:left;">
                                    <summary class="pn-btn pn-btn-sm" style="cursor:pointer;list-style:none;">
                                        <i class="ri-key-2-line"></i> Change Password
                                    </summary>
                                    <form method="POST" action="{{ route('client.services.bdpbx.extension-password', $service) }}" style="margin-top:8px;padding:10px;background:var(--bg);border:1px solid var(--border);border-radius:10px;display:flex;gap:6px;align-items:center;min-width:250px;">
                                        @csrf
                                        <input type="hidden" name="extension" value="{{ $extNum }}">
                                        <input type="password" name="new_password" required minlength="6" maxlength="64" placeholder="New SIP password" class="form-control" style="height:32px;font-size:12.5px;">
                                        <button type="submit" class="pn-btn pn-btn-sm pn-btn-primary">Save</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div style="padding:20px;color:var(--muted);font-size:13.5px;">
        Extensions are being prepared for your account. Refresh in a moment or sign in to your Cloud PBX portal above.
    </div>
    @endif
</div>

<div class="sv-panel" style="margin-bottom:22px;">
    <div class="sv-ph"><i class="ri-shield-check-line"></i> Network Access Protection</div>
    <div style="padding:16px 20px;">
        <p style="margin:0 0 14px;font-size:13.5px;color:var(--muted);">
            If an office router or softphone device was temporarily blocked after mistyped credentials, unblock your current connection or enter a branch office IP address below.
        </p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;">
            <form method="POST" action="{{ route('client.services.bdpbx.unblock-ip', $service) }}" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border:1px solid var(--border);border-radius:12px;background:var(--bg);">
                @csrf
                <input type="hidden" name="ip_address" value="{{ $visitorIp }}">
                <div>
                    <div style="font-size:11.5px;color:var(--muted);font-weight:600;">Detected Connection IP</div>
                    <div class="sv-code" style="margin-top:3px;display:inline-block;">{{ $visitorIp ?: 'Unavailable' }}</div>
                </div>
                <button type="submit" class="pn-btn pn-btn-sm" @disabled($visitorIp === '')><i class="ri-lock-unlock-line"></i> Unblock My IP</button>
            </form>

            <form method="POST" action="{{ route('client.services.bdpbx.unblock-ip', $service) }}" style="display:flex;align-items:center;gap:8px;padding:12px 14px;border:1px solid var(--border);border-radius:12px;background:var(--bg);">
                @csrf
                <input type="text" name="ip_address" required placeholder="Branch IP (e.g. 103.15.140.10)" class="form-control" style="flex:1;height:34px;font-family:ui-monospace,Menlo,monospace;font-size:13px;">
                <button type="submit" class="pn-btn pn-btn-sm pn-btn-primary"><i class="ri-lock-unlock-line"></i> Unblock</button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('[data-toggle-pw]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-toggle-pw'));
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });
    document.querySelectorAll('[data-copy-pw]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-copy-pw'));
            if (!input || !navigator.clipboard) return;
            navigator.clipboard.writeText(input.value).then(function () {
                var original = btn.textContent;
                btn.textContent = 'Copied';
                setTimeout(function () { btn.textContent = original || 'Copy'; }, 1500);
            });
        });
    });
})();
</script>

<div class="pn-card" style="padding:16px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;" x-data="{ revealed: false }">
    <div>
        <div class="pn-section-title" style="margin:0;">Support Verification PIN</div>
        <div style="font-size:12.5px;color:var(--muted);margin-top:3px;">
            Share this 6-digit PIN only with official support staff when verifying your account. Expires {{ $expiresAt?->diffForHumans() }}.
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <code style="font-size:18px;font-weight:800;letter-spacing:3px;padding:6px 12px;border-radius:8px;background:var(--bg);border:1px solid var(--border);"
              x-text="revealed ? @js($pin) : '••••••'">••••••</code>
        <button type="button" class="btn btn-outline btn-sm" x-on:click="revealed = !revealed" x-text="revealed ? 'Hide' : 'Show PIN'">Show PIN</button>
        <form method="POST" action="{{ route('client.support-pin.regenerate') }}" style="margin:0;">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm">Regenerate</button>
        </form>
    </div>
</div>

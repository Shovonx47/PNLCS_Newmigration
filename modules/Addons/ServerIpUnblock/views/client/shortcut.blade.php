<div class="pn-card" style="padding:16px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div>
        <div class="pn-section-title" style="margin:0;">Connection / Firewall IP Unblocker</div>
        <div style="font-size:12.5px;color:var(--muted);margin-top:3px;">
            Having trouble connecting from <code>{{ $visitorIp }}</code>? Check and clear firewall blocks across your {{ $count }} eligible {{ $count === 1 ? 'service' : 'services' }}.
        </div>
    </div>
    <a href="{{ route('client.unblock-ip.index') }}" class="btn btn-outline btn-sm">Open IP Unblocker</a>
</div>

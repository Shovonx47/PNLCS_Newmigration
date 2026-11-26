<div class="card" style="margin-bottom:15px;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
        <strong>BHPanel Hosting Account</strong>
        <div style="display:flex;gap:6px;">
            @if(strtolower((string) $service->status) === 'active')
                <a href="{{ route('admin.bhpanel.service-sso', $service) }}" target="_blank" rel="noopener" class="btn btn-primary btn-xs">Customer Panel SSO</a>
            @endif
            @if($server)
                <a href="{{ route('admin.bhpanel.server-sso', $server) }}" target="_blank" rel="noopener" class="btn btn-default btn-xs">Admin Server SSO</a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;font-size:13px;">
            <div><span class="text-muted">Panel Username:</span><br><code>{{ $service->username ?: '—' }}</code></div>
            <div><span class="text-muted">Primary Domain:</span><br><code>{{ $service->domain ?: '—' }}</code></div>
            @if($usage)
                <div><span class="text-muted">Disk Usage:</span><br><strong>{{ number_format((float) ($usage['disk_used_mb'] ?? 0), 1) }} / {{ (float) ($usage['disk_limit_mb'] ?? 0) > 0 ? number_format((float) $usage['disk_limit_mb'], 0).' MB' : '∞' }}</strong></div>
                <div><span class="text-muted">Bandwidth Usage:</span><br><strong>{{ number_format((float) ($usage['bandwidth_used_mb'] ?? 0), 1) }} MB</strong></div>
            @endif
            @if($summary)
                <div><span class="text-muted">Sites / Mailboxes / DBs:</span><br><strong>{{ (int) ($summary['domains'] ?? 0) }} / {{ (int) ($summary['mailboxes'] ?? 0) }} / {{ (int) ($summary['databases'] ?? 0) }}</strong></div>
            @endif
        </div>
    </div>
</div>

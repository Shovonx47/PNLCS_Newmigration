<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Softcents Voice SMS Account</strong></div>
    <div class="card-body">
        @if($account)
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;font-size:13px;">
                <div><span class="text-muted">Voice SMS User ID:</span><br><strong>#{{ $account['id'] ?? $userId }}</strong></div>
                <div><span class="text-muted">Username:</span><br><code>{{ $account['username'] ?? '—' }}</code></div>
                <div><span class="text-muted">Role / Status:</span><br><strong>{{ strtoupper((string) ($account['type'] ?? 'user')) }} / {{ ucfirst((string) ($account['status'] ?? 'active')) }}</strong></div>
                <div><span class="text-muted">Live Wallet Balance:</span><br><strong style="color:#059669;">{{ number_format((float) ($account['balance'] ?? 0), 2) }} BDT</strong></div>
                <div><span class="text-muted">Pulse Rate:</span><br>{{ number_format((float) ($account['pulse_rate'] ?? 0), 2) }} BDT / {{ (int) ($account['pulse_duration'] ?? 30) }}s</div>
            </div>
        @else
            <span class="text-muted">{{ $userId ? "Linked to remote Voice SMS User #{$userId} (live details unreachable)." : 'Not provisioned or linked yet.' }}</span>
        @endif
    </div>
</div>

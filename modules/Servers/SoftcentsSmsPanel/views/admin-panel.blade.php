<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Softcents Bulk SMS Account</strong></div>
    <div class="card-body">
        @if($account)
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;font-size:13px;">
                <div><span class="text-muted">SMS User ID:</span><br><strong>#{{ $account['id'] ?? $userId }}</strong></div>
                <div><span class="text-muted">Username:</span><br><code>{{ $account['username'] ?? '—' }}</code></div>
                <div><span class="text-muted">Role / Status:</span><br><strong>{{ strtoupper((string) ($account['role'] ?? 'user')) }} / {{ ucfirst((string) ($account['status'] ?? 'active')) }}</strong></div>
                <div><span class="text-muted">Live Wallet Balance:</span><br><strong style="color:#059669;">{{ number_format((float) ($account['balance'] ?? 0), 2) }} BDT</strong></div>
                <div><span class="text-muted">Rates (NM / Masking):</span><br>{{ number_format((float) ($account['non_masking_rate'] ?? 0), 2) }} / {{ number_format((float) ($account['masking_rate'] ?? 0), 2) }} BDT</div>
            </div>
        @else
            <span class="text-muted">{{ $userId ? "Linked to remote SMS User #{$userId} (live details unreachable)." : 'Not provisioned or linked yet.' }}</span>
        @endif
    </div>
</div>

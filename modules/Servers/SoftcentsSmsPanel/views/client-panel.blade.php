<div class="sv-g2">
    <div class="sv-panel">
        <div class="sv-ph"><i class="ri-message-2-line"></i> Bulk SMS Account Overview</div>
        <ul class="sv-dl">
            <li>
                <span class="k">SMS Portal</span>
                <span class="v"><a href="{{ $portalUrl }}" target="_blank" rel="noopener" class="sv-code">Open Portal <i class="ri-external-link-line"></i></a></span>
            </li>
            <li>
                <span class="k">Portal Username</span>
                <span class="v"><span class="sv-code">{{ $account['username'] ?? ($service->username ?: '—') }}</span></span>
            </li>
            @if($account)
                <li>
                    <span class="k">Account Tier</span>
                    <span class="v">{{ match((string) ($account['role'] ?? 'user')) { 'sp' => 'Reseller', 'ssp' => 'Sub-Reseller', default => 'Customer' } }}</span>
                </li>
                <li>
                    <span class="k">Available Wallet Balance</span>
                    <span class="v" style="color:var(--primary);font-size:15px;">{{ number_format((float) ($account['balance'] ?? 0), 2) }} BDT</span>
                </li>
                <li>
                    <span class="k">SMS Tariff (Non-Masking / Masking)</span>
                    <span class="v">{{ number_format((float) ($account['non_masking_rate'] ?? 0), 2) }} / {{ number_format((float) ($account['masking_rate'] ?? 0), 2) }} BDT</span>
                </li>
            @endif
        </ul>
    </div>

    <div class="sv-panel">
        <div class="sv-ph"><i class="ri-key-2-line"></i> Sender IDs &amp; Developer API</div>
        <ul class="sv-dl">
            <li>
                <span class="k">Assigned Sender IDs</span>
                <span class="v">
                    @if(!empty($account['senderids']) && is_array($account['senderids']))
                        @foreach($account['senderids'] as $sid)
                            <span class="sv-code" style="margin-left:4px;">{{ $sid['sender'] ?? '' }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">Default / Portal Managed</span>
                    @endif
                </span>
            </li>
            @if(!empty($account['allow_api']) && !empty($account['client_id']))
                <li>
                    <span class="k">API Client ID</span>
                    <span class="v"><span class="sv-code">{{ $account['client_id'] }}</span></span>
                </li>
                @if(!empty($account['client_secret']))
                <li>
                    <span class="k">API Secret</span>
                    <span class="v" style="display:inline-flex;align-items:center;gap:6px;">
                        <input type="password" readonly value="{{ $account['client_secret'] }}" id="sms-api-secret" class="form-control" style="width:145px;height:28px;padding:2px 8px;font-family:ui-monospace,Menlo,monospace;font-size:12px;">
                        <button type="button" class="pn-btn pn-btn-sm" onclick="var i=document.getElementById('sms-api-secret');if(i)i.type=i.type==='password'?'text':'password';"><i class="ri-eye-line"></i></button>
                    </span>
                </li>
                @endif
            @else
                <li>
                    <span class="k">API Access</span>
                    <span class="v">{{ !empty($account['allow_api']) ? 'Enabled in SMS Portal' : 'Standard Portal Access' }}</span>
                </li>
            @endif
        </ul>
    </div>
</div>

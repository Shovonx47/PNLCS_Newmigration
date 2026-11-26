<div class="sv-g2">
    <div class="sv-panel">
        <div class="sv-ph"><i class="ri-mic-line"></i> Voice Broadcast Account Overview</div>
        <ul class="sv-dl">
            <li>
                <span class="k">Voice SMS Portal</span>
                <span class="v"><a href="{{ $portalUrl }}" target="_blank" rel="noopener" class="sv-code">Open Portal <i class="ri-external-link-line"></i></a></span>
            </li>
            <li>
                <span class="k">Portal Username</span>
                <span class="v"><span class="sv-code">{{ $account['username'] ?? ($service->username ?: '—') }}</span></span>
            </li>
            @if($account)
                <li>
                    <span class="k">Account Tier</span>
                    <span class="v">{{ match((string) ($account['type'] ?? 'user')) { 'sp' => 'Reseller', 'ssp' => 'Sub-Reseller', default => 'Customer' } }}</span>
                </li>
                <li>
                    <span class="k">Available Wallet Balance</span>
                    <span class="v" style="color:var(--primary);font-size:15px;">{{ number_format((float) ($account['balance'] ?? 0), 2) }} BDT</span>
                </li>
                <li>
                    <span class="k">Voice Tariff</span>
                    <span class="v">{{ number_format((float) ($account['pulse_rate'] ?? 0), 2) }} BDT / {{ (int) ($account['pulse_duration'] ?? 30) }}s @if(!empty($account['tariff_plan']['name']))({{ $account['tariff_plan']['name'] }})@endif</span>
                </li>
            @endif
        </ul>
    </div>

    <div class="sv-panel">
        <div class="sv-ph"><i class="ri-phone-find-line"></i> Assigned Caller CLIs</div>
        <ul class="sv-dl">
            <li>
                <span class="k">Outbound Caller Numbers</span>
                <span class="v">
                    @if(!empty($account['callers']) && is_array($account['callers']))
                        @foreach($account['callers'] as $caller)
                            <span class="sv-code" style="margin-left:4px;">{{ $caller['caller_number'] ?? ($caller['caller_name'] ?? '') }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">Portal Default CLI</span>
                    @endif
                </span>
            </li>
            <li>
                <span class="k">Campaign Audio Review</span>
                <span class="v">Ready in Voice Portal</span>
            </li>
        </ul>
    </div>
</div>

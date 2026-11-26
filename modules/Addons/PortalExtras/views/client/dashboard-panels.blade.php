<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:24px;">
    <div class="pn-card" style="padding:18px 22px;">
        <div class="pn-section-title" style="margin:0 0 10px;">Account Summary</div>
        <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--muted);">Account Holder</span>
                <strong>{{ trim($client->first_name.' '.$client->last_name) }}</strong>
            </div>
            @if($client->company_name)
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--muted);">Organization</span>
                <strong>{{ $client->company_name }}</strong>
            </div>
            @endif
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--muted);">Account Status</span>
                <strong style="color:{{ strtolower($stats['status'] ?? 'active') === 'active' ? '#16a34a' : 'var(--text)' }};">{{ $stats['status'] ?? 'Active' }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--muted);">Member Since</span>
                <strong>{{ $stats['member_since'] ? $stats['member_since']->format('d M Y').' ('.($stats['tenure'] ?? '—').')' : '—' }}</strong>
            </div>
            @if(! empty($stats['last_login']))
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--muted);">Last Sign-In</span>
                <strong>{{ $stats['last_login']->format('d M Y, H:i') }}</strong>
            </div>
            @endif
            <div style="display:flex;justify-content:space-between;">
                <span style="color:var(--muted);">Available Credit</span>
                <strong style="color:var(--primary);">{{ display_money_fmt($stats['credit_balance']) }}</strong>
            </div>
        </div>
    </div>

    <div class="pn-card" style="padding:18px 22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
            <div class="pn-section-title" style="margin:0;">Recent Credit Activity</div>
            <a href="{{ route('client.funds.index') }}" style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none;">Add Funds</a>
        </div>
        @if($stats['recent_credits']->isEmpty())
            <div style="font-size:13px;color:var(--muted);padding:8px 0;">No credit transactions recorded yet.</div>
        @else
            <div style="display:flex;flex-direction:column;gap:6px;font-size:12.5px;">
                @foreach($stats['recent_credits'] as $cr)
                <div style="display:flex;justify-content:space-between;gap:10px;padding-bottom:6px;border-bottom:1px solid var(--border);">
                    <div style="min-width:0;">
                        <div style="color:var(--text);">{{ \Illuminate\Support\Str::limit($cr->description, 44) }}</div>
                        @if($cr->date)
                            <div style="font-size:11px;color:var(--muted);">{{ $cr->date->format('d M Y') }}</div>
                        @endif
                    </div>
                    <strong style="white-space:nowrap;color:{{ (float)$cr->amount >= 0 ? '#16a34a' : '#dc2626' }};">
                        {{ (float)$cr->amount >= 0 ? '+' : '' }}{{ display_money_fmt((float) $cr->amount) }}
                    </strong>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

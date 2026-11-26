@extends('client.layouts.app')
@section('title', 'Reseller Portal')

@section('content')
@php
    $branding = is_array($reseller->branding_settings) ? $reseller->branding_settings : [];
    $smtp = is_array($reseller->smtp_settings) ? $reseller->smtp_settings : [];
    $gatewaysByKey = $reseller->gateways->keyBy('gateway');
@endphp

<div class="pn-page-header">
    <div>
        <h1 class="pn-page-title">Reseller Portal</h1>
        <p class="pn-page-subtitle">
            {{ $reseller->company_name ?: 'Reseller Workspace' }}
            &middot; Tier: <strong>{{ $reseller->group?->name ?: 'Standard' }}</strong>
            @if($reseller->cname) &middot; Hostname: <code>{{ $reseller->cname }}</code>@endif
        </p>
    </div>
    <a href="{{ route('client.home') }}" class="btn btn-outline">&larr; Dashboard</a>
</div>

@if($errors->any())
    <div class="pn-alert pn-alert-error" style="margin-bottom:16px;">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
    <div class="pn-card" style="padding:16px 20px;">
        <div style="font-size:11.5px;color:var(--muted);text-transform:uppercase;font-weight:700;">End Clients</div>
        <div style="font-size:24px;font-weight:800;margin-top:4px;">{{ $metrics['end_clients'] }}</div>
    </div>
    <div class="pn-card" style="padding:16px 20px;">
        <div style="font-size:11.5px;color:var(--muted);text-transform:uppercase;font-weight:700;">Reseller Balance</div>
        <div style="font-size:24px;font-weight:800;margin-top:4px;color:var(--primary);">{{ money_fmt((float) $reseller->balance) }}</div>
    </div>
    <div class="pn-card" style="padding:16px 20px;">
        <div style="font-size:11.5px;color:var(--muted);text-transform:uppercase;font-weight:700;">Total End-Client Sales</div>
        <div style="font-size:24px;font-weight:800;margin-top:4px;">{{ money_fmt($metrics['total_sales']) }}</div>
    </div>
    <div class="pn-card" style="padding:16px 20px;">
        <div style="font-size:11.5px;color:var(--muted);text-transform:uppercase;font-weight:700;">Pending Commissions</div>
        <div style="font-size:24px;font-weight:800;margin-top:4px;">{{ money_fmt($metrics['pending_payouts']) }}</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:18px;margin-bottom:20px;">
    <div class="pn-card" style="padding:20px 24px;">
        <div class="pn-section-title" style="margin:0 0 12px;">White-Label Branding &amp; Contact</div>
        <form method="POST" action="{{ route('client.reseller.branding') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
                <div class="form-group">
                    <label class="form-label">Company / Brand Name</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $reseller->company_name) }}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Support Email</label>
                    <input type="email" name="email_address" value="{{ old('email_address', $reseller->email_address) }}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">White-Label CNAME</label>
                    <input type="text" name="cname" value="{{ old('cname', $reseller->cname) }}" class="form-control" placeholder="billing.yourbrand.com">
                </div>
                <div class="form-group">
                    <label class="form-label">PayPal Payout Email</label>
                    <input type="email" name="paypal_email" value="{{ old('paypal_email', $reseller->paypal_email) }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Logo URL</label>
                    <input type="url" name="logo_url" value="{{ old('logo_url', $reseller->logo_url) }}" class="form-control" placeholder="https://...">
                </div>
                <div class="form-group">
                    <label class="form-label">Accent Hex Color</label>
                    <input type="text" name="theme_color" value="{{ old('theme_color', $branding['theme_color'] ?? '#2563eb') }}" class="form-control" placeholder="#2563eb">
                </div>
            </div>

            <div class="form-group" style="margin-top:10px;">
                <label class="form-label">Email Signature</label>
                <textarea name="email_signature" rows="2" class="form-control">{{ old('email_signature', $branding['email_signature'] ?? '') }}</textarea>
            </div>

            @if($reseller->group?->allow_own_smtp)
            <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border);">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:var(--muted);margin-bottom:8px;">Custom Outbound SMTP (Encrypted)</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;">
                    <div class="form-group">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" value="{{ old('smtp_host', $smtp['host'] ?? '') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Port</label>
                        <input type="number" name="smtp_port" value="{{ old('smtp_port', $smtp['port'] ?? 587) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="smtp_username" value="{{ old('smtp_username', $smtp['username'] ?? '') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_password" class="form-control" autocomplete="new-password" placeholder="{{ filled($smtp['password'] ?? '') ? '•••••••• (leave blank to keep)' : 'Enter password' }}">
                    </div>
                </div>
            </div>
            @endif

            <div style="margin-top:14px;">
                <button type="submit" class="btn btn-primary">Save Branding</button>
            </div>
        </form>
    </div>

    <div class="pn-card" style="padding:20px 24px;">
        <div class="pn-section-title" style="margin:0 0 12px;">End-Client Payment Gateways</div>
        <p style="font-size:12.5px;color:var(--muted);margin:0 0 14px;">
            Gateways configured here are used automatically when your assigned end clients choose Reseller Gateway at invoice checkout.
        </p>

        @foreach(['banktransfer' => 'Bank Transfer / bKash Manual', 'piprapay' => 'PipraPay Merchant', 'paypal' => 'PayPal Checkout'] as $gwKey => $gwLabel)
        @php
            $gw = $gatewaysByKey->get($gwKey);
            $gwCfg = is_array($gw?->settings) ? $gw->settings : [];
        @endphp
        <details style="border:1px solid var(--border);border-radius:8px;padding:10px 14px;margin-bottom:10px;" @if($gw?->enabled) open @endif>
            <summary style="cursor:pointer;font-weight:600;display:flex;justify-content:space-between;align-items:center;">
                <span>{{ $gwLabel }}</span>
                <span class="badge {{ $gw?->enabled ? 'badge-active' : 'badge-cancelled' }}">{{ $gw?->enabled ? 'Enabled' : 'Disabled' }}</span>
            </summary>
            <form method="POST" action="{{ route('client.reseller.gateway.save') }}" style="margin-top:12px;">
                @csrf
                <input type="hidden" name="gateway" value="{{ $gwKey }}">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div class="form-group">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="display_name" value="{{ $gwCfg['display_name'] ?? $gwLabel }}" class="form-control">
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:6px;">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" name="enabled" value="1" @checked((bool) ($gw?->enabled))>
                            Enable for end clients
                        </label>
                    </div>
                    @if($gwKey === 'banktransfer')
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Payment Instructions</label>
                        <textarea name="instructions" rows="2" class="form-control">{{ $gwCfg['instructions'] ?? '' }}</textarea>
                    </div>
                    @elseif($gwKey === 'piprapay')
                    <div class="form-group">
                        <label class="form-label">PipraPay Base URL</label>
                        <input type="url" name="base_url" value="{{ $gwCfg['base_url'] ?? '' }}" class="form-control" placeholder="https://pay.yourbrand.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">API Key</label>
                        <input type="password" name="api_key" class="form-control" autocomplete="new-password" placeholder="{{ filled($gwCfg['api_key'] ?? '') ? '•••••••• (leave blank to keep)' : 'Enter API key' }}">
                    </div>
                    @elseif($gwKey === 'paypal')
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">PayPal Business Email</label>
                        <input type="email" name="paypal_email" value="{{ $gwCfg['paypal_email'] ?? '' }}" class="form-control">
                    </div>
                    @endif
                </div>
                <button type="submit" class="btn btn-outline btn-sm" style="margin-top:8px;">Save {{ $gwLabel }}</button>
            </form>
        </details>
        @endforeach
    </div>
</div>

@if($reseller->group?->allow_promos)
<div class="pn-card" style="padding:20px 24px;margin-bottom:20px;">
    <div class="pn-section-title" style="margin:0 0 12px;">Reseller Promo Codes</div>
    <form method="POST" action="{{ route('client.reseller.promos.store') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
        @csrf
        <div>
            <label class="form-label">Promo Code</label>
            <input type="text" name="code" class="form-control" required placeholder="PARTNER20" style="text-transform:uppercase;">
        </div>
        <div>
            <label class="form-label">Type</label>
            <select name="type" class="form-control" required>
                <option value="percent">Percent (%)</option>
                <option value="fixed">Fixed Discount</option>
            </select>
        </div>
        <div>
            <label class="form-label">Value</label>
            <input type="number" step="0.01" min="0.01" name="value" class="form-control" required>
        </div>
        <div>
            <label class="form-label">Max Uses (0 = unlimited)</label>
            <input type="number" min="0" name="max_uses" value="0" class="form-control">
        </div>
        <div>
            <label class="form-label">Expires At</label>
            <input type="date" name="expires_at" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Create Promo</button>
    </form>

    @if($reseller->promos->isEmpty())
        <div style="color:var(--muted);font-size:13px;">No promo codes created yet.</div>
    @else
        <table class="pn-table">
            <thead>
                <tr><th>Code</th><th>Discount</th><th>Uses</th><th>Expires</th><th style="text-align:right;">Action</th></tr>
            </thead>
            <tbody>
                @foreach($reseller->promos as $promo)
                <tr>
                    <td><code>{{ $promo->code }}</code></td>
                    <td>{{ $promo->type === 'percent' ? number_format((float) $promo->value, 1).'%' : money_fmt((float) $promo->value) }}</td>
                    <td>{{ $promo->uses }} / {{ $promo->max_uses ?: '∞' }}</td>
                    <td>{{ $promo->expires_at?->format('Y-m-d') ?: 'Never' }}</td>
                    <td style="text-align:right;">
                        <form method="POST"
                              action="{{ route('client.reseller.promos.destroy', $promo) }}"
                              onsubmit="return pnConfirm(event, @js('Delete promo code '.$promo->code.'?'));">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:18px;">
    <div class="pn-card" style="padding:20px 24px;">
        <div class="pn-section-title" style="margin:0 0 12px;">End Clients &amp; Managed Services</div>
        @if($services->isEmpty())
            <div style="color:var(--muted);font-size:13px;">No end-client services found yet.</div>
        @else
            <table class="pn-table">
                <thead><tr><th>Service</th><th>Client</th><th>Status</th><th style="text-align:right;">Control</th></tr></thead>
                <tbody>
                    @foreach($services as $svc)
                    <tr>
                        <td>
                            <strong>{{ $svc->product?->name ?: 'Service #'.$svc->id }}</strong>
                            @if($svc->domain)<div style="font-size:11.5px;color:var(--muted);">{{ $svc->domain }}</div>@endif
                        </td>
                        <td>{{ trim(($svc->client?->first_name ?? '').' '.($svc->client?->last_name ?? '')) }}</td>
                        <td><span class="badge {{ strtolower((string) $svc->status) === 'active' ? 'badge-active' : 'badge-pending' }}">{{ $svc->status }}</span></td>
                        <td style="text-align:right;">
                            @if($reseller->group?->allow_suspend)
                                @if(strtolower((string) $svc->status) === 'active')
                                    <form method="POST"
                                          action="{{ route('client.reseller.services.suspend', $svc) }}"
                                          onsubmit="return pnConfirm(event, @js('Suspend end-client service #'.$svc->id.'?'));">
                                        @csrf
                                        <button type="submit" class="btn btn-outline btn-sm">Suspend</button>
                                    </form>
                                @elseif(strtolower((string) $svc->status) === 'suspended')
                                    <form method="POST"
                                          action="{{ route('client.reseller.services.unsuspend', $svc) }}"
                                          onsubmit="return pnConfirm(event, @js('Reactivate end-client service #'.$svc->id.'?'));">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Unsuspend</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="pn-card" style="padding:20px 24px;">
        <div class="pn-section-title" style="margin:0 0 12px;">Recent Commission Payouts</div>
        @if($payouts->isEmpty())
            <div style="color:var(--muted);font-size:13px;">No commissions recorded yet.</div>
        @else
            <table class="pn-table">
                <thead><tr><th>Date</th><th>End Client</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($payouts as $payout)
                    <tr>
                        <td>{{ $payout->created_at?->format('Y-m-d') }}</td>
                        <td>{{ $payout->client ? trim($payout->client->first_name.' '.$payout->client->last_name) : '—' }}</td>
                        <td><strong>{{ money_fmt((float) $payout->amount) }}</strong></td>
                        <td>
                            <span class="badge {{ $payout->status === 'paid' ? 'badge-active' : 'badge-pending' }}">
                                {{ ucfirst($payout->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

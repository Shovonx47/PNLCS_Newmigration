@extends('client.layouts.twentyone')
@section('title', 'Reseller Portal')

@section('content')
@php
    $branding = is_array($reseller->branding_settings) ? $reseller->branding_settings : [];
    $smtp = is_array($reseller->smtp_settings) ? $reseller->smtp_settings : [];
    $gatewaysByKey = $reseller->gateways->keyBy('gateway');
@endphp

<div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
    <div class="text-muted small">
        <strong>{{ $reseller->company_name ?: 'Reseller Workspace' }}</strong>
        &middot; {{ 'Tier:' }} <strong>{{ $reseller->group?->name ?: 'Standard' }}</strong>
        @if($reseller->cname) &middot; {{ 'Hostname:' }} <code>{{ $reseller->cname }}</code>@endif
    </div>
    <a href="{{ route('client.home') }}" class="btn btn-default btn-sm">&laquo; {{ 'Dashboard' }}</a>
</div>

<div class="tiles clearfix mb-4">
    <div class="row">
        <div class="col-sm-3 col-6 tile">
            <a href="#">
                <div class="icon"><i class="fas fa-users"></i></div>
                <div class="stat">{{ $metrics['end_clients'] }}</div>
                <div class="title">{{ 'End Clients' }}</div>
                <div class="highlight bg-color-blue"></div>
            </a>
        </div>
        <div class="col-sm-3 col-6 tile">
            <a href="#">
                <div class="icon"><i class="fas fa-wallet"></i></div>
                <div class="stat">{{ money_fmt((float) $reseller->balance) }}</div>
                <div class="title">{{ 'Reseller Balance' }}</div>
                <div class="highlight bg-color-green"></div>
            </a>
        </div>
        <div class="col-sm-3 col-6 tile">
            <a href="#">
                <div class="icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat">{{ money_fmt($metrics['total_sales']) }}</div>
                <div class="title">{{ 'Total End-Client Sales' }}</div>
                <div class="highlight bg-color-gold"></div>
            </a>
        </div>
        <div class="col-sm-3 col-6 tile">
            <a href="#">
                <div class="icon"><i class="fas fa-coins"></i></div>
                <div class="stat">{{ money_fmt($metrics['pending_payouts']) }}</div>
                <div class="title">{{ 'Pending Commissions' }}</div>
                <div class="highlight bg-color-red"></div>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-paint-brush fa-fw"></i>&nbsp;{{ 'White-Label Branding & Contact' }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('client.reseller.branding') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{ 'Company / Brand Name' }}</label>
                            <input type="text" name="company_name" value="{{ old('company_name', $reseller->company_name) }}" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ 'Support Email' }}</label>
                            <input type="email" name="email_address" value="{{ old('email_address', $reseller->email_address) }}" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ 'White-Label CNAME' }}</label>
                            <input type="text" name="cname" value="{{ old('cname', $reseller->cname) }}" class="form-control" placeholder="{{ 'billing.yourbrand.com' }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ 'PayPal Payout Email' }}</label>
                            <input type="email" name="paypal_email" value="{{ old('paypal_email', $reseller->paypal_email) }}" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ 'Logo URL' }}</label>
                            <input type="url" name="logo_url" value="{{ old('logo_url', $reseller->logo_url) }}" class="form-control" placeholder="{{ 'https://...' }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ 'Accent Hex Color' }}</label>
                            <input type="text" name="theme_color" value="{{ old('theme_color', $branding['theme_color'] ?? '#2563eb') }}" class="form-control" placeholder="{{ '#2563eb' }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ 'Email Signature' }}</label>
                        <textarea name="email_signature" rows="2" class="form-control">{{ old('email_signature', $branding['email_signature'] ?? '') }}</textarea>
                    </div>

                    @if($reseller->group?->allow_own_smtp)
                    <hr>
                    <h6 class="text-muted text-uppercase small font-weight-bold mb-3">{{ 'Custom Outbound SMTP (Encrypted)' }}</h6>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{ 'SMTP Host' }}</label>
                            <input type="text" name="smtp_host" value="{{ old('smtp_host', $smtp['host'] ?? '') }}" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ 'Port' }}</label>
                            <input type="number" name="smtp_port" value="{{ old('smtp_port', $smtp['port'] ?? 587) }}" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ 'Username' }}</label>
                            <input type="text" name="smtp_username" value="{{ old('smtp_username', $smtp['username'] ?? '') }}" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ 'SMTP Password' }}</label>
                            <input type="password" name="smtp_password" class="form-control" autocomplete="new-password" placeholder="{{ filled($smtp['password'] ?? '') ? '•••••••• (leave blank to keep)' : 'Enter password' }}">
                        </div>
                    </div>
                    @endif

                    <button type="submit" class="btn btn-primary">{{ 'Save Branding' }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-credit-card fa-fw"></i>&nbsp;{{ 'End-Client Payment Gateways' }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    {{ 'Gateways configured here are used automatically when your assigned end clients choose Reseller Gateway at invoice checkout.' }}
                </p>

                @foreach(['banktransfer' => 'Bank Transfer / bKash Manual', 'piprapay' => 'PipraPay Merchant', 'paypal' => 'PayPal Checkout'] as $gwKey => $gwLabel)
                @php
                    $gw = $gatewaysByKey->get($gwKey);
                    $gwCfg = is_array($gw?->settings) ? $gw->settings : [];
                @endphp
                <div class="card mb-3">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <strong>{{ $gwLabel }}</strong>
                        <span class="label {{ $gw?->enabled ? 'label-success' : 'label-default' }}">{{ $gw?->enabled ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                    <div class="card-body py-3">
                        <form method="POST" action="{{ route('client.reseller.gateway.save') }}">
                            @csrf
                            <input type="hidden" name="gateway" value="{{ $gwKey }}">
                            <div class="row">
                                <div class="col-md-7 form-group mb-2">
                                    <label class="small">{{ 'Display Name' }}</label>
                                    <input type="text" name="display_name" value="{{ $gwCfg['display_name'] ?? $gwLabel }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-5 form-group mb-2 d-flex align-items-end">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="gw-en-{{ $gwKey }}" name="enabled" value="1" @checked((bool) ($gw?->enabled))>
                                        <label class="form-check-label small" for="gw-en-{{ $gwKey }}">{{ 'Enable for end clients' }}</label>
                                    </div>
                                </div>
                                @if($gwKey === 'banktransfer')
                                <div class="col-12 form-group mb-2">
                                    <label class="small">{{ 'Payment Instructions' }}</label>
                                    <textarea name="instructions" rows="2" class="form-control form-control-sm">{{ $gwCfg['instructions'] ?? '' }}</textarea>
                                </div>
                                @elseif($gwKey === 'piprapay')
                                <div class="col-md-6 form-group mb-2">
                                    <label class="small">{{ 'PipraPay Base URL' }}</label>
                                    <input type="url" name="base_url" value="{{ $gwCfg['base_url'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ 'https://pay.yourbrand.com' }}">
                                </div>
                                <div class="col-md-6 form-group mb-2">
                                    <label class="small">{{ 'API Key' }}</label>
                                    <input type="password" name="api_key" class="form-control form-control-sm" autocomplete="new-password" placeholder="{{ filled($gwCfg['api_key'] ?? '') ? '••••••••' : 'Enter API key' }}">
                                </div>
                                @elseif($gwKey === 'paypal')
                                <div class="col-12 form-group mb-2">
                                    <label class="small">{{ 'PayPal Business Email' }}</label>
                                    <input type="email" name="paypal_email" value="{{ $gwCfg['paypal_email'] ?? '' }}" class="form-control form-control-sm">
                                </div>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-default btn-sm mt-1">{{ 'Save ' . $gwLabel }}</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if($reseller->group?->allow_promos)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-tags fa-fw"></i>&nbsp;{{ 'Reseller Promo Codes' }}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('client.reseller.promos.store') }}" class="mb-3">
            @csrf
            <div class="form-row align-items-end">
                <div class="col-md-3 form-group mb-2">
                    <label class="small">{{ 'Promo Code' }}</label>
                    <input type="text" name="code" class="form-control text-uppercase" required placeholder="{{ 'PARTNER20' }}">
                </div>
                <div class="col-md-2 form-group mb-2">
                    <label class="small">{{ 'Type' }}</label>
                    <select name="type" class="form-control" required>
                        <option value="percent">{{ 'Percent (%)' }}</option>
                        <option value="fixed">{{ 'Fixed Discount' }}</option>
                    </select>
                </div>
                <div class="col-md-2 form-group mb-2">
                    <label class="small">{{ 'Value' }}</label>
                    <input type="number" step="0.01" min="0.01" name="value" class="form-control" required>
                </div>
                <div class="col-md-2 form-group mb-2">
                    <label class="small">{{ 'Max Uses' }}</label>
                    <input type="number" min="0" name="max_uses" value="0" class="form-control">
                </div>
                <div class="col-md-2 form-group mb-2">
                    <label class="small">{{ 'Expires At' }}</label>
                    <input type="date" name="expires_at" class="form-control">
                </div>
                <div class="col-md-1 form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-block">{{ 'Add' }}</button>
                </div>
            </div>
        </form>

        @if($reseller->promos->isEmpty())
            <p class="text-muted mb-0">{{ 'No promo codes created yet.' }}</p>
        @else
            <div class="table-responsive">
                <table class="table table-list mb-0">
                    <thead>
                        <tr><th>{{ 'Code' }}</th><th>{{ 'Discount' }}</th><th>{{ 'Uses' }}</th><th>{{ 'Expires' }}</th><th class="text-right">{{ 'Action' }}</th></tr>
                    </thead>
                    <tbody>
                        @foreach($reseller->promos as $promo)
                        <tr>
                            <td><code>{{ $promo->code }}</code></td>
                            <td>{{ $promo->type === 'percent' ? number_format((float) $promo->value, 1).'%' : money_fmt((float) $promo->value) }}</td>
                            <td>{{ $promo->uses }} / {{ $promo->max_uses ?: '∞' }}</td>
                            <td>{{ $promo->expires_at?->format('Y-m-d') ?: 'Never' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('client.reseller.promos.destroy', $promo) }}" class="d-inline" onsubmit="return pnConfirm(event, @js('Delete promo code '.$promo->code.'?'));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">{{ 'Delete' }}</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-server fa-fw"></i>&nbsp;{{ 'End Clients & Managed Services' }}</h5>
            </div>
            @if($services->isEmpty())
                <div class="card-body text-muted">{{ 'No end-client services found yet.' }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-list mb-0">
                        <thead><tr><th>{{ 'Service' }}</th><th>{{ 'Client' }}</th><th>{{ 'Status' }}</th><th class="text-right">{{ 'Control' }}</th></tr></thead>
                        <tbody>
                            @foreach($services as $svc)
                            <tr>
                                <td>
                                    <strong>{{ $svc->product?->name ?: 'Service #'.$svc->id }}</strong>
                                    @if($svc->domain)<div class="small text-muted">{{ $svc->domain }}</div>@endif
                                </td>
                                <td>{{ trim(($svc->client?->first_name ?? '').' '.($svc->client?->last_name ?? '')) }}</td>
                                <td><span class="label {{ strtolower((string) $svc->status) === 'active' ? 'label-success' : 'label-warning' }}">{{ $svc->status }}</span></td>
                                <td class="text-right">
                                    @if($reseller->group?->allow_suspend)
                                        @if(strtolower((string) $svc->status) === 'active')
                                            <form method="POST" action="{{ route('client.reseller.services.suspend', $svc) }}" class="d-inline" onsubmit="return pnConfirm(event, @js('Suspend end-client service #'.$svc->id.'?'));">
                                                @csrf
                                                <button type="submit" class="btn btn-default btn-sm">{{ 'Suspend' }}</button>
                                            </form>
                                        @elseif(strtolower((string) $svc->status) === 'suspended')
                                            <form method="POST" action="{{ route('client.reseller.services.unsuspend', $svc) }}" class="d-inline" onsubmit="return pnConfirm(event, @js('Reactivate end-client service #'.$svc->id.'?'));">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm">{{ 'Unsuspend' }}</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-money-check-alt fa-fw"></i>&nbsp;{{ 'Recent Commission Payouts' }}</h5>
            </div>
            @if($payouts->isEmpty())
                <div class="card-body text-muted">{{ 'No commissions recorded yet.' }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-list mb-0">
                        <thead><tr><th>{{ 'Date' }}</th><th>{{ 'End Client' }}</th><th>{{ 'Amount' }}</th><th>{{ 'Status' }}</th></tr></thead>
                        <tbody>
                            @foreach($payouts as $payout)
                            <tr>
                                <td>{{ $payout->created_at?->format('Y-m-d') }}</td>
                                <td>{{ $payout->client ? trim($payout->client->first_name.' '.$payout->client->last_name) : '—' }}</td>
                                <td><strong>{{ money_fmt((float) $payout->amount) }}</strong></td>
                                <td><span class="label {{ $payout->status === 'paid' ? 'label-success' : 'label-warning' }}">{{ ucfirst($payout->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

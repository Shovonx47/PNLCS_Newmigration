@extends('admin.layouts.app')
@section('title', 'Reseller #'.$reseller->id.' — Resellers Center')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Reseller #{{ $reseller->id }}: {{ $reseller->company_name ?: trim(($reseller->client?->first_name ?? '').' '.($reseller->client?->last_name ?? '')) }}</h1>
    <div style="display:flex;gap:8px;">
        @if($reseller->client)
            <a href="{{ route('admin.clients.show', $reseller->client) }}" class="btn btn-default btn-sm">View Host Client</a>
        @endif
        <a href="{{ route('admin.extensions.reseller-center.resellers') }}" class="btn btn-default btn-sm">&larr; All Resellers</a>
    </div>
</div>

@include('resellercenter::admin._nav')

@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:14px;">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px;">
    <div class="card"><div class="card-body" style="padding:12px 16px;"><div style="font-size:11px;color:#777;text-transform:uppercase;">End Clients</div><div style="font-size:20px;font-weight:700;">{{ $metrics['end_clients'] }}</div></div></div>
    <div class="card"><div class="card-body" style="padding:12px 16px;"><div style="font-size:11px;color:#777;text-transform:uppercase;">Account Balance</div><div style="font-size:20px;font-weight:700;">{{ money_fmt((float) $reseller->balance) }}</div></div></div>
    <div class="card"><div class="card-body" style="padding:12px 16px;"><div style="font-size:11px;color:#777;text-transform:uppercase;">Total End-Client Sales</div><div style="font-size:20px;font-weight:700;">{{ money_fmt($metrics['total_sales']) }}</div></div></div>
    <div class="card"><div class="card-body" style="padding:12px 16px;"><div style="font-size:11px;color:#777;text-transform:uppercase;">Last Month Sales</div><div style="font-size:20px;font-weight:700;">{{ money_fmt($metrics['last_month_sales']) }}</div></div></div>
    <div class="card"><div class="card-body" style="padding:12px 16px;"><div style="font-size:11px;color:#777;text-transform:uppercase;">Pending Payouts</div><div style="font-size:20px;font-weight:700;color:#b45309;">{{ money_fmt($metrics['pending_payouts']) }}</div></div></div>
    <div class="card"><div class="card-body" style="padding:12px 16px;"><div style="font-size:11px;color:#777;text-transform:uppercase;">Paid Payouts</div><div style="font-size:20px;font-weight:700;color:#15803d;">{{ money_fmt($metrics['paid_payouts']) }}</div></div></div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:16px;">
    <div class="card">
        <div class="card-header"><strong>Configuration &amp; Branding</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.extensions.reseller-center.resellers.update', $reseller) }}">
                @csrf
                @method('PUT')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Pricing Tier</label>
                        <select name="group_id" class="form-control" required>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" @selected((int) $reseller->group_id === (int) $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="active" @selected($reseller->status === 'active')>Active</option>
                            <option value="inactive" @selected($reseller->status !== 'active')>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $reseller->company_name) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Brand Email</label>
                        <input type="email" name="email_address" value="{{ old('email_address', $reseller->email_address) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">CNAME</label>
                        <input type="text" name="cname" value="{{ old('cname', $reseller->cname) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PayPal Payout Email</label>
                        <input type="email" name="paypal_email" value="{{ old('paypal_email', $reseller->paypal_email) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Store Template</label>
                        <select name="store_template" class="form-control">
                            <option value="standard_cart" @selected($reseller->store_template === 'standard_cart')>Standard Cart</option>
                            <option value="modern" @selected($reseller->store_template === 'modern')>Modern</option>
                            <option value="classic" @selected($reseller->store_template === 'classic')>Classic</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Logo URL</label>
                        <input type="url" name="logo_url" value="{{ old('logo_url', $reseller->logo_url) }}" class="form-control">
                    </div>
                </div>
                <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;">
                    <button type="submit" class="btn btn-primary btn-sm">Save Reseller Settings</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Assigned End Clients ({{ $reseller->endClients->count() }})</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.extensions.reseller-center.resellers.assign-client', $reseller) }}" style="display:flex;gap:8px;margin-bottom:14px;">
                @csrf
                <select name="client_id" class="form-control" required>
                    <option value="">— Assign Existing Client —</option>
                    @foreach($availableClients as $c)
                        <option value="{{ $c->id }}">#{{ $c->id }} — {{ trim($c->first_name.' '.$c->last_name) }} ({{ $c->email }})</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Assign</button>
            </form>

            @if($reseller->endClients->isEmpty())
                <div style="color:#888;font-size:13px;">No end clients assigned to this reseller.</div>
            @else
                <table class="data-table">
                    <thead><tr><th>Client</th><th>Status</th><th style="text-align:right;">Action</th></tr></thead>
                    <tbody>
                        @foreach($reseller->endClients as $link)
                        <tr>
                            <td>
                                @if($link->client)
                                    <a href="{{ route('admin.clients.show', $link->client) }}">
                                        #{{ $link->client_id }} — {{ trim($link->client->first_name.' '.$link->client->last_name) }}
                                    </a>
                                    <div style="font-size:11.5px;color:#777;">{{ $link->client->email }}</div>
                                @else
                                    Client #{{ $link->client_id }}
                                @endif
                            </td>
                            <td>{{ $link->client?->status ?: 'Active' }}</td>
                            <td style="text-align:right;">
                                <form method="POST"
                                      action="{{ route('admin.extensions.reseller-center.resellers.unassign-client', [$reseller, $link]) }}"
                                      onsubmit="return pnConfirm(event, @js('Unlink this end client from reseller #'.$reseller->id.'?'));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-default btn-sm">Unlink</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:16px;">
    <div class="card">
        <div class="card-header"><strong>End-Client Services ({{ $services->count() }})</strong></div>
        <div class="card-body">
            @if($services->isEmpty())
                <div style="color:#888;font-size:13px;">No active or historical services for assigned end clients.</div>
            @else
                <table class="data-table">
                    <thead><tr><th>Service</th><th>Client</th><th>Amount</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($services as $svc)
                        <tr>
                            <td>
                                <a href="{{ route('admin.services.show', $svc) }}">#{{ $svc->id }} — {{ $svc->product?->name ?: 'Service' }}</a>
                                @if($svc->domain)<div style="font-size:11.5px;color:#777;">{{ $svc->domain }}</div>@endif
                            </td>
                            <td>{{ trim(($svc->client?->first_name ?? '').' '.($svc->client?->last_name ?? '')) }}</td>
                            <td>{{ money_fmt((float) $svc->amount) }}</td>
                            <td><span class="badge {{ strtolower((string) $svc->status) === 'active' ? 'badge-active' : 'badge-pending' }}">{{ $svc->status }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Reseller Promos &amp; Proxy Gateways</strong></div>
        <div class="card-body">
            <div style="margin-bottom:14px;">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#666;margin-bottom:6px;">Promo Codes ({{ $reseller->promos->count() }})</div>
                @if($reseller->promos->isEmpty())
                    <div style="color:#888;font-size:13px;">None created.</div>
                @else
                    <table class="data-table">
                        <thead><tr><th>Code</th><th>Discount</th><th>Uses</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($reseller->promos as $promo)
                            <tr>
                                <td><code>{{ $promo->code }}</code></td>
                                <td>{{ $promo->type === 'percent' ? number_format((float) $promo->value, 1).'%' : money_fmt((float) $promo->value) }}</td>
                                <td>{{ $promo->uses }} / {{ $promo->max_uses ?: '∞' }}</td>
                                <td><span class="badge {{ $promo->active ? 'badge-active' : 'badge-cancelled' }}">{{ $promo->active ? 'Active' : 'Disabled' }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div>
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#666;margin-bottom:6px;">Configured Gateways ({{ $reseller->gateways->count() }})</div>
                @if($reseller->gateways->isEmpty())
                    <div style="color:#888;font-size:13px;">No custom gateways configured by this reseller.</div>
                @else
                    <table class="data-table">
                        <thead><tr><th>Gateway</th><th>Display Label</th><th>State</th></tr></thead>
                        <tbody>
                            @foreach($reseller->gateways as $gw)
                            <tr>
                                <td><code>{{ $gw->gateway }}</code></td>
                                <td>{{ $gw->settings['display_name'] ?? ucfirst($gw->gateway) }}</td>
                                <td><span class="badge {{ $gw->enabled ? 'badge-active' : 'badge-cancelled' }}">{{ $gw->enabled ? 'Enabled' : 'Disabled' }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@extends('admin.layouts.app')
@section('title', 'Resellers — Resellers Center')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Resellers</h1>
    <a href="{{ route('admin.extensions.reseller-center.groups') }}" class="btn btn-default btn-sm">Manage Pricing Groups</a>
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

<details class="card" style="margin-bottom:16px;" @if($resellers->isEmpty() || $errors->any()) open @endif>
    <summary class="card-header" style="cursor:pointer;"><strong>Create New Reseller Account</strong></summary>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.reseller-center.resellers.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                <div class="form-group">
                    <label class="form-label">Host Client Account <span style="color:#dc2626;">*</span></label>
                    <select name="client_id" class="form-control" required>
                        <option value="">— Select Client —</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected((int) old('client_id') === (int) $client->id)>
                                #{{ $client->id }} — {{ trim($client->first_name.' '.$client->last_name) }} ({{ $client->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Pricing Group / Tier <span style="color:#dc2626;">*</span></label>
                    <select name="group_id" class="form-control" required>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) old('group_id') === (int) $group->id)>
                                {{ $group->name }} ({{ number_format((float) $group->default_commission_percent, 1) }}%)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Reseller Brand / Company</label>
                    <input type="text" name="company_name" value="{{ old('company_name') }}" class="form-control" placeholder="e.g. Cloud Partner BD">
                </div>
                <div class="form-group">
                    <label class="form-label">Support / Brand Email</label>
                    <input type="email" name="email_address" value="{{ old('email_address') }}" class="form-control" placeholder="billing@partner.example">
                </div>
                <div class="form-group">
                    <label class="form-label">White-label CNAME Hostname</label>
                    <input type="text" name="cname" value="{{ old('cname') }}" class="form-control" placeholder="portal.partner.example">
                </div>
                <div class="form-group">
                    <label class="form-label">PayPal Payout Email</label>
                    <input type="email" name="paypal_email" value="{{ old('paypal_email') }}" class="form-control" placeholder="payouts@partner.example">
                </div>
            </div>
            <div style="margin-top:12px;">
                <button type="submit" class="btn btn-primary btn-sm">Create Reseller</button>
            </div>
        </form>
    </div>
</details>

<div class="card">
    <div class="card-header"><strong>Registered Resellers ({{ $resellers->total() }})</strong></div>
    <div class="card-body">
        @if($resellers->isEmpty())
            <div style="color:#888;font-size:13px;">No resellers created yet.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company / Contact</th>
                        <th>Tier</th>
                        <th>CNAME</th>
                        <th>End Clients</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resellers as $reseller)
                    <tr>
                        <td>#{{ $reseller->id }}</td>
                        <td>
                            <a href="{{ route('admin.extensions.reseller-center.resellers.show', $reseller) }}" style="font-weight:600;">
                                {{ $reseller->company_name ?: trim(($reseller->client?->first_name ?? '').' '.($reseller->client?->last_name ?? '')) }}
                            </a>
                            <div style="font-size:11.5px;color:#777;">
                                @if($reseller->client)
                                    Client #{{ $reseller->client_id }} &middot; {{ $reseller->client->email }}
                                @endif
                            </div>
                        </td>
                        <td>{{ $reseller->group?->name ?: 'Standard' }}</td>
                        <td><code>{{ $reseller->cname ?: '—' }}</code></td>
                        <td>{{ $reseller->end_clients_count }}</td>
                        <td>{{ money_fmt((float) $reseller->balance) }}</td>
                        <td>
                            <span class="badge {{ $reseller->isActive() ? 'badge-active' : 'badge-cancelled' }}">
                                {{ ucfirst($reseller->status) }}
                            </span>
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <a href="{{ route('admin.extensions.reseller-center.resellers.show', $reseller) }}" class="btn btn-default btn-sm">Details</a>
                            <form method="POST"
                                  action="{{ route('admin.extensions.reseller-center.resellers.destroy', $reseller) }}"
                                  style="display:inline;"
                                  onsubmit="return pnConfirm(event, @js('Remove reseller #'.$reseller->id.' and unlink its end clients?'));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="margin-top:12px;">{{ $resellers->links() }}</div>
        @endif
    </div>
</div>
@endsection

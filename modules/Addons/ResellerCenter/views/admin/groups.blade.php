@extends('admin.layouts.app')
@section('title', 'Pricing Groups — Resellers Center')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Reseller Pricing Groups</h1>
    <a href="{{ route('admin.extensions.reseller-center.resellers') }}" class="btn btn-default btn-sm">View Resellers</a>
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

<details class="card" style="margin-bottom:16px;" @if($groups->isEmpty() || $errors->any()) open @endif>
    <summary class="card-header" style="cursor:pointer;"><strong>Create Pricing Group / Tier</strong></summary>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.reseller-center.groups.store') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
                <div class="form-group">
                    <label class="form-label">Group Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" required placeholder="e.g. Gold Partners">
                </div>
                <div class="form-group">
                    <label class="form-label">Payout Method</label>
                    <select name="payout_method" class="form-control" required>
                        <option value="credit_balance">Client Credit Balance</option>
                        <option value="paypal">PayPal Payouts</option>
                        <option value="manual">Manual Settlement</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Invoicing Method</label>
                    <select name="invoicing_method" class="form-control" required>
                        <option value="provider">Provider Issues Invoices</option>
                        <option value="reseller">Reseller Issues Invoices</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Default Commission (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="default_commission_percent" value="{{ old('default_commission_percent', '10') }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Credit Limit</label>
                    <input type="number" step="0.01" min="0" name="credit_limit" value="{{ old('credit_limit', '1000') }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" class="form-control">
                </div>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:14px;margin:10px 0;">
                <label><input type="checkbox" name="allow_promos" value="1" checked> Allow Promo Codes</label>
                <label><input type="checkbox" name="allow_own_smtp" value="1"> Allow Custom SMTP</label>
                <label><input type="checkbox" name="allow_suspend" value="1" checked> Allow End-Client Suspend</label>
                <label><input type="checkbox" name="allow_deferred" value="1"> Allow Deferred Billing</label>
                <label><input type="checkbox" name="allow_global_search" value="1" checked> Global Search</label>
                <input type="hidden" name="p_clients" value="1">
                <input type="hidden" name="p_orders" value="1">
                <input type="hidden" name="p_billing" value="1">
                <input type="hidden" name="p_promos" value="1">
                <input type="hidden" name="p_suspend" value="1">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Create Tier</button>
        </form>
    </div>
</details>

@forelse($groups as $group)
<div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div>
            <strong>{{ $group->name }}</strong>
            <span style="font-size:12px;color:#666;margin-left:8px;">
                {{ $group->resellers_count }} reseller(s) &middot; Default {{ number_format((float) $group->default_commission_percent, 2) }}% &middot; Payout: {{ $group->payout_method }}
            </span>
        </div>
        @if($group->resellers_count === 0)
            <form method="POST"
                  action="{{ route('admin.extensions.reseller-center.groups.destroy', $group) }}"
                  onsubmit="return pnConfirm(event, @js('Delete pricing tier '.$group->name.' and its rules?'));">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Delete Group</button>
            </form>
        @endif
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.reseller-center.groups.update', $group) }}" style="margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #e5e7eb;">
            @csrf
            @method('PUT')
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;">
                <div class="form-group">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ $group->name }}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Payout Method</label>
                    <select name="payout_method" class="form-control">
                        <option value="credit_balance" @selected($group->payout_method === 'credit_balance')>Credit Balance</option>
                        <option value="paypal" @selected($group->payout_method === 'paypal')>PayPal</option>
                        <option value="manual" @selected($group->payout_method === 'manual')>Manual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Invoicing</label>
                    <select name="invoicing_method" class="form-control">
                        <option value="provider" @selected($group->invoicing_method === 'provider')>Provider</option>
                        <option value="reseller" @selected($group->invoicing_method === 'reseller')>Reseller</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Default %</label>
                    <input type="number" step="0.01" min="0" max="100" name="default_commission_percent" value="{{ $group->default_commission_percent }}" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Credit Limit</label>
                    <input type="number" step="0.01" min="0" name="credit_limit" value="{{ $group->credit_limit }}" class="form-control">
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:14px;margin:8px 0;">
                <label><input type="checkbox" name="allow_promos" value="1" @checked($group->allow_promos)> Allow Promos</label>
                <label><input type="checkbox" name="allow_own_smtp" value="1" @checked($group->allow_own_smtp)> Own SMTP</label>
                <label><input type="checkbox" name="allow_suspend" value="1" @checked($group->allow_suspend)> Allow Suspend</label>
                <label><input type="checkbox" name="allow_deferred" value="1" @checked($group->allow_deferred)> Deferred Billing</label>
                <label><input type="checkbox" name="allow_global_search" value="1" @checked($group->allow_global_search)> Global Search</label>
            </div>
            <button type="submit" class="btn btn-default btn-sm">Update Tier</button>
        </form>

        <div style="font-size:12px;font-weight:700;text-transform:uppercase;color:#666;margin-bottom:8px;">
            Product Commission Rules ({{ $group->pricingRules->count() }})
        </div>

        <form method="POST" action="{{ route('admin.extensions.reseller-center.groups.pricing.store', $group) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:12px;">
            @csrf
            <div style="min-width:220px;flex:1;">
                <label class="form-label">Product</label>
                <select name="rel_id" class="form-control" required>
                    <option value="">— Select Product —</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}">#{{ $prod->id }} — {{ $prod->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Cycle (optional)</label>
                <input type="text" name="billing_cycle" class="form-control" placeholder="monthly / annually">
            </div>
            <div>
                <label class="form-label">Payout Type</label>
                <select name="payout_type" class="form-control" required>
                    <option value="percent">Percent (%)</option>
                    <option value="fixed">Fixed Amount</option>
                    <option value="difference">Margin Over Min Price</option>
                </select>
            </div>
            <div>
                <label class="form-label">Value</label>
                <input type="number" step="0.01" min="0" name="payout_value" class="form-control" required value="15">
            </div>
            <div>
                <label class="form-label">Min Price</label>
                <input type="number" step="0.01" min="0" name="min_price" class="form-control" placeholder="Optional">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Add Rule</button>
        </form>

        @if($group->pricingRules->isNotEmpty())
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Cycle</th>
                        <th>Commission Rule</th>
                        <th>Min / Max Price</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group->pricingRules as $rule)
                    <tr>
                        <td>{{ $rule->product?->name ?: 'Product #'.$rule->rel_id }}</td>
                        <td>{{ $rule->billing_cycle ?: 'All cycles' }}</td>
                        <td>
                            {{ ucfirst($rule->payout_type) }}:
                            <strong>{{ $rule->payout_type === 'percent' ? number_format((float) $rule->payout_value, 2).'%' : money_fmt((float) $rule->payout_value) }}</strong>
                        </td>
                        <td>
                            {{ $rule->min_price !== null ? money_fmt((float) $rule->min_price) : '—' }}
                            /
                            {{ $rule->max_price !== null ? money_fmt((float) $rule->max_price) : '—' }}
                        </td>
                        <td style="text-align:right;">
                            <form method="POST"
                                  action="{{ route('admin.extensions.reseller-center.pricing.destroy', $rule) }}"
                                  onsubmit="return pnConfirm(event, @js('Remove this pricing rule?'));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@empty
    <div class="card"><div class="card-body" style="color:#888;">No pricing tiers configured yet.</div></div>
@endforelse
@endsection

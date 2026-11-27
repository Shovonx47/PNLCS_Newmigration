@extends('client.layouts.twentyone')
@section('title', __('client.services.upgrade_downgrade_title'))
@section('content')

<div class="pn-page-header">
    <h1>{{ __('client.services.upgrade_downgrade') }}</h1>
    <a href="{{ route('client.services.show', $service) }}" class="btn btn-outline btn-sm">&larr; {{ __('client.services.back_to_service') }}</a>
</div>

@if(!isset($upgrades) || $upgrades->isEmpty())
<div class="card">
    <div class="card-body" style="text-align:center; padding:40px; color:var(--muted);">
        <p style="margin:0 0 16px;">{{ __('client.services.no_upgrades') }}</p>
        <a href="{{ route('client.services.show', $service) }}" class="btn btn-outline btn-sm">&larr; {{ __('client.services.back_to_service') }}</a>
    </div>
</div>
@else
<div class="card">
    <div class="card-header">{{ 'Current Configuration' }}</div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-6">
                <h5>{{ __('client.cart.product') }}</h5>
                <span>{{ $service->product?->name ?? 'Service' }}</span>
            </div>
            <div class="col-sm-6">
                <h5>{{ __('common.table.billing_cycle') }}</h5>
                <span>{{ display_money_fmt($service->amount) }} / {{ cycle_label($service->billing_cycle) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">{{ __('client.services.select_new_plan') }}</div>
    <div class="card-body">
        <form method="POST" action="{{ route('client.services.upgrade.process', $service) }}">
            @csrf
            @if($errors->any())
            <div style="background:#f2dede;border:1px solid #ebccd1;color:#a94442;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:16px;">
                <ul style="margin:0; padding-left:18px;">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
            @endif
            <div class="table-container clearfix mb-4">
            <table class="table table-list">
                <thead>
                    <tr>
                        <th>{{ __('client.cart.product') }}</th>
                        <th>{{ __('common.table.description') }}</th>
                        <th class="text-right">{{ __('common.table.price') }}</th>
                        <th class="text-center">{{ __('client.services.select_new_plan') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($upgrades as $product)
                @php
                    // The price for the term this customer is on, worked out
                    // where the customer is known rather than guessed at here.
                    $price = $upgradePrices[$product->id] ?? null;
                    $cycle = strtolower($upgradeCycle ?? '');
                    // What the account has that this package could not hold
                    // (#276): such a package is shown, with what to remove
                    // first, and cannot be chosen until it fits.
                    $fit = $packageFits[$product->id] ?? ['problems' => [], 'warnings' => []];
                    $blocked = $fit['problems'] !== [];
                @endphp
                <tr class="pn-upgrade-option" style="{{ $blocked ? 'opacity:.6;' : '' }}">
                    <td><strong>{{ $product->name }}</strong></td>
                    <td>
                        @if($product->hasDescription())
                            <span class="text-muted">{{ Str::limit($product->descriptionText(), 100) }}</span>
                        @endif
                        @if($blocked)
                        <div class="alert alert-danger" role="note" style="margin:8px 0 0; font-size:12.5px;">
                            <div style="font-weight:600; margin-bottom:4px;">{{ __('client.services.package_fit_intro') }}</div>
                            <ul style="margin:0; padding-left:18px;">@foreach($fit['problems'] as $problem)<li>{{ $problem }}</li>@endforeach</ul>
                        </div>
                        @endif
                        @foreach($fit['warnings'] as $warning)
                        <div class="alert alert-warning" role="note" style="margin:8px 0 0; font-size:12.5px;">{{ $warning }}</div>
                        @endforeach
                    </td>
                    <td class="text-right">
                        @if($price)
                            <strong>{{ display_money_fmt($price) }}</strong><br>
                            <small>{{ __('client.services.per_cycle', ['cycle' => $cycle]) }}</small>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        <input type="radio" name="new_product_id" value="{{ $product->id }}" required style="margin:0; cursor:{{ $blocked ? 'not-allowed' : 'pointer' }};" @disabled($blocked)>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary">{{ __('client.services.request_change') }}</button>
                <a href="{{ route('client.services.show', $service) }}" class="btn btn-outline">{{ __('common.actions.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endif

@if(($optionGroups ?? collect())->flatMap->options->filter(fn ($o) => $o->subs->isNotEmpty())->isNotEmpty())
{{-- Raise the options on this same package: more RAM, a bigger disk. Charged for the days left in the cycle. --}}
<div class="card" style="margin-top:16px;">
    <div class="card-header">{{ __('client.services.options_title') }}</div>
    <div class="card-body">
        <p style="font-size:13px; color:var(--muted); margin-top:0;">{{ __('client.services.options_hint') }}</p>
        <form method="POST" action="{{ route('client.services.options.upgrade', $service) }}">
            @csrf
            @foreach($optionGroups as $group)
                @foreach($group->options as $option)
                    @continue($option->subs->isEmpty())
                    <div class="form-group" style="margin-bottom:12px;">
                        <label class="form-label" for="uopt-{{ $option->id }}">{{ $option->displayName() }}</label>
                        @if($option->isQuantity())
                            <input type="number" id="uopt-{{ $option->id }}" name="config_options[{{ $option->id }}]" class="form-control" style="max-width:160px;"
                                   value="{{ $currentOptions[$option->id] ?? ($option->qty_minimum ?? 0) }}"
                                   min="{{ $currentOptions[$option->id] ?? ($option->qty_minimum ?? 0) }}" @if($option->qty_maximum) max="{{ $option->qty_maximum }}" @endif>
                            <small style="color:var(--muted);">{{ display_money_fmt($option->subs->first()?->priceFor($upgradeCycle) ?? 0) }} {{ __('client.cart.per_unit') }}</small>
                        @elseif($option->isCheckbox())
                            <label style="display:flex; align-items:center; gap:8px; font-size:13px;">
                                <input type="checkbox" id="uopt-{{ $option->id }}" name="config_options[{{ $option->id }}]" value="1" @checked(isset($currentOptions[$option->id]))>
                                <span>{{ $option->subs->first()?->displayName() ?? $option->displayName() }} (+{{ display_money_fmt($option->subs->first()?->priceFor($upgradeCycle) ?? 0) }})</span>
                            </label>
                        @else
                            <select id="uopt-{{ $option->id }}" name="config_options[{{ $option->id }}]" class="form-control" style="max-width:320px;">
                                @foreach($option->subs as $sub)
                                <option value="{{ $sub->id }}" @selected((string) ($currentOptions[$option->id] ?? '') === (string) $sub->id)>{{ $sub->displayName() }} · {{ display_money_fmt($sub->priceFor($upgradeCycle)) }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                @endforeach
            @endforeach
            <button type="submit" class="btn btn-primary btn-sm">{{ __('client.services.options_submit') }}</button>
        </form>
    </div>
</div>
@endif

@endsection

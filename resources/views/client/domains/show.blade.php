@extends('client.layouts.twentyone')
@section('title', $domain->domain ?? 'Domain')
@push('styles')
<style>
    .domain-overview h5 { font-size: 13px; font-weight: 600; color: #333; margin: 0 0 4px; }
    .domain-overview .row { border-bottom: 1px solid var(--border); padding-bottom: 12px; }
    .domain-overview .row:last-of-type { border-bottom: 0; }
</style>
@endpush
@section('content')

@if($restoreAmount ?? null)
<div class="alert alert-warning clearfix">
    <div class="float-right">
        <form method="POST" action="{{ route('client.domains.restore', $domain) }}" style="margin:0;">@csrf
            <button type="submit" class="btn btn-xs btn-default">{{ __('client.domains.restore_now') }}</button>
        </form>
    </div>
    {{ __('client.domains.restore_notice', ['amount' => display_money_fmt($restoreAmount)]) }}
</div>
@endif

<div class="card domain-overview">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.domains.domain_info') }}</h3>

        @if($locked === false)
            <div class="alert alert-danger text-center" role="alert">
                <strong>{{ __('client.domains.registrar_lock') }}: {{ __('client.status.unlocked') }}</strong><br>
                {{ __('client.domains.ns_change_hint') }}
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-lg-6">
                <h5>{{ __('client.domains.domain_name') }}:</h5>
                <a href="http://{{ $domain->domain }}" target="_blank" rel="noopener">{{ $domain->domain }}</a>
            </div>
            <div class="col-lg-6">
                <h5>{{ __('client.services.status') }}:</h5>
                <span class="badge badge-{{ strtolower($domain->status ?? 'active') }}">{{ status_label($domain->status ?? 'active') }}</span>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-lg-6">
                <h5>{{ __('client.services.registration_date') }}:</h5>
                <span>{{ $domain->registration_date?->format(date_fmt()) ?? 'N/A' }}</span>
            </div>
            <div class="col-lg-6">
                <h5>{{ __('client.domains.expiry_date') }}:</h5>
                <span>{{ $domain->expiry_date?->format(date_fmt()) ?? 'N/A' }}</span>
                @if(strtolower((string) $domain->status) === 'active' && (float) $domain->recurring_amount > 0)
                    <form method="POST" action="{{ route('client.domains.renew', $domain) }}" style="display:inline;margin-left:8px;">
                        @csrf
                        <button type="submit" class="btn btn-xs btn-default">{{ __('client.domains.renew_now') }}</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-lg-6">
                <h5>{{ __('client.services.auto_renew') }}:</h5>
                <span>{{ $domain->auto_renew ? __('client.status.enabled') : __('client.status.disabled') }}</span>
                <form method="POST" action="{{ route('client.domains.autorenew', $domain) }}" style="display:inline;margin-left:8px;">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-default">{{ $domain->auto_renew ? __('client.domains.turn_off') : __('client.domains.turn_on') }}</button>
                </form>
            </div>
            <div class="col-lg-6">
                <h5>{{ __('client.domains.id_protection') }}:</h5>
                <span>{{ ($domain->id_protection ?? false) ? __('client.status.enabled') : __('client.status.disabled') }}</span>
                @if($canTogglePrivacy ?? false)
                    <form method="POST" action="{{ route('client.domains.privacy', $domain) }}" style="display:inline;margin-left:8px;">
                        @csrf
                        <button type="submit" class="btn btn-xs btn-default">{{ $domain->id_protection ? __('client.domains.turn_off') : __('client.domains.turn_on') }}</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-lg-6">
                <h5>{{ __('client.domains.registrar_lock') }}:</h5>
                <span>{{ $locked === null ? __('client.status.unknown') : ($locked ? __('client.status.locked') : __('client.status.unlocked')) }}</span>
            </div>
            <div class="col-lg-6">
                <h5>{{ __('client.domains.nameservers') }}:</h5>
                <span>{{ implode(', ', $domain->nameserverList()) ?: '&mdash;' }}</span>
            </div>
        </div>

        <h3 class="card-title">{{ __('client.domains.manage_domain') }}</h3>
        <ul>
            @if($canEditContacts ?? false)
                <li><a href="{{ route('client.domains.contacts', $domain) }}">{{ __('client.domains.contacts_title') }}</a></li>
            @endif
            @if($canManageGlue ?? false)
                <li><a href="{{ route('client.domains.glue', $domain) }}">{{ __('client.domains.glue_title') }}</a></li>
            @endif
            <li><a href="{{ route('client.domains.epp', $domain) }}">{{ __('client.domains.get_epp_code') }}</a></li>
        </ul>

        @if(session('epp_code'))
        <div class="alert alert-info" style="font-family:monospace;">{{ session('epp_code') }}</div>
        @endif
    </div>
</div>

<h3 class="card-title">{{ __('client.domains.nameservers') }}</h3>
<div class="card">
    <div class="card-body">
        {{-- The route and DomainController::updateNameservers() existed, but no
             screen posted to them: the customer could read the nameservers and
             not change them. Reported by ENA Hosting. --}}
        @php $ns = $domain->nameserverList(); @endphp
        <form method="POST" action="{{ route('client.domains.nameservers', $domain) }}">
            @csrf
            @method('PUT')
            @for($i = 1; $i <= 5; $i++)
            <div class="form-group row">
                <label for="ns{{ $i }}" class="col-sm-3 col-form-label">{{ __('client.domains.nameservers') }} {{ $i }}</label>
                <div class="col-sm-9">
                    <input type="text" id="ns{{ $i }}" name="ns{{ $i }}" value="{{ old('ns'.$i, $ns[$i-1] ?? '') }}"
                           class="form-control" autocomplete="off" spellcheck="false"
                           @if($i <= 2) required @else placeholder="{{ __('client.form.optional') }}" @endif>
                    @error('ns'.$i)<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>
            @endfor
            <p class="text-muted text-sm">{{ __('client.domains.ns_change_hint') }}</p>
            <button type="submit" class="btn btn-primary">{{ __('client.domains.update_nameservers') }}</button>
        </form>
    </div>
</div>

@if(($hostings ?? collect())->isNotEmpty())
@php
    $setUp = $hostings->firstWhere('set_up', true);
    $first = $setUp ?? $hostings->first();
@endphp
<h3 class="card-title">{{ __('client.domains.attach_to_hosting') }}</h3>
<div class="card">
    <div class="card-body">
        {{-- One step: put the domain on one of the customer's hosting accounts
             and point it there. Running it again is harmless. --}}
        @if($setUp)
        <p class="text-success"><strong>&#10003; {{ __('client.domains.attach_already') }}</strong></p>
        @endif
        <form method="POST" action="{{ route('client.domains.attach-hosting', $domain) }}">
            @csrf
            @if($hostings->count() > 1)
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="service_id">{{ __('client.domains.attach_choose_service') }}</label>
                <div class="col-sm-9">
                    <select id="service_id" name="service_id" class="form-control">
                        @foreach($hostings as $h)
                        <option value="{{ $h['service']->id }}" @selected($h['service']->id === $first['service']->id)>{{ $h['service']->product?->name }}{{ $h['service']->domain ? ' - '.$h['service']->domain : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @else
            <input type="hidden" name="service_id" value="{{ $first['service']->id }}">
            @endif
            <button type="submit" class="btn btn-success">{{ $setUp ? __('client.domains.attach_to_hosting_again') : __('client.domains.attach_to_hosting') }}</button>
            <p class="text-muted text-sm">
                @if($setUp)
                    {{ __('client.domains.attach_again_hint') }}
                @elseif($first['nameservers'] !== [])
                    {{ __('client.domains.attach_to_hosting_hint', ['ns' => implode(', ', $first['nameservers'])]) }}
                @else
                    {{ __('client.domains.attach_to_hosting_hint_no_ns') }}
                @endif
            </p>
        </form>
    </div>
</div>
@endif

@if($domain->dns_management ?? false)
<h3 class="card-title">{{ __('client.domains.dns_management') }}</h3>
<div class="card">
    <div class="card-body">
        <p>{{ __('client.domains.dns_enabled') }}</p>
        <a href="#" class="btn btn-primary">{{ __('client.domains.manage_dns') }} &rarr;</a>
    </div>
</div>
@endif

<h3 class="card-title">{{ __('client.domain_move.title') }}</h3>
<div class="card">
    <div class="card-body">
        @if($outgoingMove ?? null)
        <p>{{ __('client.domain_move.waiting', ['email' => $outgoingMove->toClient?->email, 'date' => $outgoingMove->expires_at->format(date_fmt())]) }}</p>
        <form method="POST" action="{{ route('client.domain-moves.cancel', $outgoingMove) }}">@csrf<button type="submit" class="btn btn-default">{{ __('client.domain_move.cancel') }}</button></form>
        @else
        <p class="text-muted">{{ __('client.domain_move.hint') }}</p>
        <form method="POST" action="{{ route('client.domains.move', $domain) }}">
            @csrf
            <div class="form-group row">
                <label for="moveEmail" class="col-sm-3 col-form-label">{{ __('client.domain_move.email') }}</label>
                <div class="col-sm-6">
                    <input type="email" id="moveEmail" name="email" required maxlength="255" class="form-control" value="{{ old('email') }}">
                    @error('email')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="col-sm-3">
                    <button type="submit" class="btn btn-default">{{ __('client.domain_move.offer') }}</button>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>

@endsection

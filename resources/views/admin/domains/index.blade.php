@extends('admin.layouts.app')
@section('title', __('admin.domains.title'))
@section('content')
<h1>{{ __('admin.domains.title') }}</h1>

<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="active"><a href="#tabDomainFilter" role="tab" data-toggle="tab">{{ 'Search/Filter' }}</a></li>
</ul>
<div class="tab-content admin-tabs" style="margin-bottom:15px;">
    <div class="tab-pane active" id="tabDomainFilter">
        <form method="GET" action="{{ route('admin.domains.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.actions.search') }}</td>
                    <td width="35%" class="fieldarea">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.domains.search_placeholder') }}" class="form-control input-300">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('admin.domains.status_label') }}</td>
                    <td width="35%" class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            <option value="">{{ __('common.misc.all_statuses') }}</option>
                            @foreach($statuses as $s)
                            <option value="{{ $s }}" @selected(request('status') == $s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.domains.registrar_label') }}</td>
                    <td class="fieldarea">
                        @if($registrars->count() > 0)
                        <select name="registrar" class="form-control select-inline">
                            <option value="">{{ __('admin.domains.all_registrars') }}</option>
                            @foreach($registrars as $r)
                            <option value="{{ $r }}" @selected(request('registrar') == $r)>{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                        @else
                        {{ '-' }}
                        @endif
                    </td>
                    <td class="fieldlabel">{{ __('admin.domains.sort') }}</td>
                    <td class="fieldarea">
                        <select name="sort" class="form-control select-inline">
                            <option value="created_at" @selected(request('sort', 'created_at') == 'created_at')>{{ __('admin.domains.sort_created') }}</option>
                            <option value="expiry_date" @selected(request('sort') == 'expiry_date')>{{ __('admin.domains.sort_expiry') }}</option>
                            <option value="registration_date" @selected(request('sort') == 'registration_date')>{{ __('admin.domains.sort_registration') }}</option>
                            <option value="domain" @selected(request('sort') == 'domain')>{{ __('admin.domains.sort_domain') }}</option>
                        </select>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default"><i class="fas fa-search"></i> {{ __('common.actions.filter') }}</button>
                @if(request()->hasAny(['search', 'status', 'registrar', 'sort']))
                <a href="{{ route('admin.domains.index') }}" class="btn btn-default">{{ __('common.actions.reset') }}</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="context-btn-container" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
    <span class="text-muted">{{ __('admin.domains.total_count', ['count' => $domains->total()]) }}</span>
    <a href="{{ route('admin.domains.searches') }}" class="btn btn-default btn-sm">{{ __('admin.domain_searches.title') }}</a>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.domain') }}</th>
                <th>{{ __('common.table.client') }}</th>
                <th>{{ __('common.table.registrar') }}</th>
                <th>{{ __('admin.domains.registered') }}</th>
                <th>{{ __('admin.domains.expires') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th>{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($domains as $domain)
            @php
            $expirySoon = $domain->expiry_date && $domain->expiry_date->diffInDays(now()) <= 30 && $domain->expiry_date->isFuture();
            $expired = $domain->expiry_date && $domain->expiry_date->isPast();
            $badgeClass = match(strtolower($domain->status ?? '')) {
                'active'  => 'badge-active',
                'pending' => 'badge-pending',
                'expired' => 'badge-terminated',
                default   => 'badge-cancelled',
            };
            @endphp
            <tr>
                <td><a href="{{ route('admin.domains.show', $domain) }}"><strong>{{ $domain->domain }}</strong></a></td>
                <td>
                    @if($domain->client)
                    <a href="{{ route('admin.clients.show', $domain->client_id) }}">{{ $domain->client->full_name }}</a>
                    @else
                    {{ 'N/A' }}
                    @endif
                </td>
                <td>{{ ucfirst($domain->registrar ?? '-') }}</td>
                <td>{{ $domain->registration_date?->format(date_fmt()) ?? '-' }}</td>
                <td style="color:{{ $expired ? '#c43c35' : ($expirySoon ? '#d68100' : 'inherit') }};font-weight:{{ ($expired || $expirySoon) ? '600' : '400' }};">
                    {{ $domain->expiry_date?->format(date_fmt()) ?? '-' }}
                    @if($expirySoon) <small>{{ __('admin.domains.expiry_soon') }}</small> @endif
                    @if($expired) <small>{{ __('admin.domains.expiry_past') }}</small> @endif
                </td>
                <td><span class="whmcs-status badge {{ $badgeClass }}">{{ status_label($domain->status ?? '') }}</span></td>
                <td>
                    <a href="{{ route('admin.domains.show', $domain) }}" class="btn btn-default btn-xs">{{ __('common.actions.view') }}</a>
                    <form method="POST" action="{{ route('admin.domains.destroy', $domain) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.domains.delete_confirm', ['domain' => $domain->domain])))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('admin.domains.delete') }}</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">{{ __('admin.domains.no_domains') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">
    {{ $domains->withQueryString()->links() }}
</div>
@endsection

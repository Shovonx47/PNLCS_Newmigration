@extends('admin.layouts.app')
@section('title', __('admin.clients.title'))
@section('content')
<h1>{{ __('admin.clients.title') }}</h1>

<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="active">
        <a href="#tab-search" onclick="var p=document.getElementById('tab-search');p.style.display=p.style.display==='none'?'block':'none';return false;">
            {{ __('common.actions.search') }}/{{ __('common.actions.filter') }}
        </a>
    </li>
</ul>
<div class="tab-content admin-tabs">
    <div class="tab-pane active" id="tab-search">
        <form method="GET" action="{{ route('admin.clients.index') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.actions.search') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="{{ __('common.placeholder.search_clients') }}"
                            class="form-control input-300">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('admin.clients.status_label') }}</td>
                    <td class="fieldarea">
                        <select name="status" class="form-control select-inline">
                            <option value="">{{ __('common.misc.all_statuses') }}</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('admin.clients.active') }}</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('admin.clients.inactive') }}</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>{{ __('admin.clients.closed') }}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="fieldlabel">{{ __('admin.clients.group_label') }}</td>
                    <td class="fieldarea" colspan="3">
                        <select name="group_id" class="form-control select-inline">
                            <option value="">{{ __('common.misc.all_groups') }}</option>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default">{{ __('common.actions.filter') }}</button>
                @if(request()->hasAny(['search','status','group_id']))
                <a href="{{ route('admin.clients.index') }}" class="btn btn-default">{{ __('common.actions.reset') }}</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="context-btn-container whmcs-record-bar" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin:10px 0;">
    <div style="font-size:12px;color:#555;">
        <strong>{{ $clients->total() }}</strong> {{ __('admin.clients.title') }}
    </div>
    <div style="display:flex;gap:6px;align-items:center;">
        <a href="{{ route('admin.clients.index') }}?export=csv" class="btn btn-default btn-sm">{{ __('common.actions.export_csv') }}</a>
        <a href="{{ route('admin.clients.create') }}" class="btn btn-primary btn-sm">{{ __('admin.clients.add_new') }}</a>
    </div>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.id') }}</th>
                <th>{{ __('common.table.name') }}</th>
                <th>{{ __('common.table.email') }}</th>
                <th>{{ __('common.table.company') }}</th>
                <th>{{ __('admin.clients.tab_domains') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th>{{ __('common.table.created') }}</th>
                <th>{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clients as $client)
            @php
            $statusVal = is_object($client->status) ? $client->status->value : $client->status;
            $badgeClass = match(strtolower($statusVal)) {
                'active'   => 'badge-active',
                'inactive' => 'badge-cancelled',
                'closed'   => 'badge-terminated',
                default    => 'badge-cancelled',
            };
            @endphp
            <tr>
                <td><a href="{{ route('admin.clients.show', $client) }}">{{ $client->id }}</a></td>
                <td><a href="{{ route('admin.clients.show', $client) }}" style="font-weight:600;">{{ $client->full_name }}</a></td>
                <td><a href="mailto:{{ $client->email }}">{{ $client->email }}</a></td>
                <td>{{ $client->company_name ?? '-' }}</td>
                <td style="max-width:240px;">
                    @php($knownDomains = $client->knownDomains())
                    @if($knownDomains->isNotEmpty())
                        @foreach($knownDomains->take(2) as $knownDomain)
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px;">{{ $knownDomain }}</div>
                        @endforeach
                        @if($knownDomains->count() > 2)
                            <span style="color:#888;" title="{{ $knownDomains->implode(', ') }}">+{{ $knownDomains->count() - 2 }}</span>
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td><span class="whmcs-status badge {{ $badgeClass }}">{{ status_label($statusVal) }}</span></td>
                <td>{{ $client->created_at->format(date_fmt()) }}</td>
                <td style="white-space:nowrap;">
                    <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-default btn-xs">{{ __('common.actions.view') }}</a>
                    <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-default btn-xs">{{ __('common.actions.edit') }}</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;padding:24px;color:#888;">{{ __('admin.clients.no_clients') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">
    {{ $clients->withQueryString()->links() }}
</div>
@endsection

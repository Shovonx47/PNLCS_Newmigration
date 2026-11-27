@extends('admin.layouts.app')
@section('title', __('admin.clients.billing_identity'))
@section('content')
<h1>{{ __('admin.clients.billing_identity') }}</h1>

@if($missingCount > 0)
<div class="infobox">
    {{ __('admin.clients.billing_missing_count', ['count' => $missingCount]) }}
</div>
@endif

<ul class="nav nav-tabs admin-tabs" role="tablist">
    <li class="active">
        <a href="#tab-billing-filter" onclick="var p=document.getElementById('tab-billing-filter');p.style.display=p.style.display==='none'?'block':'none';return false;">
            {{ __('common.actions.search') }}/{{ __('common.actions.filter') }}
        </a>
    </li>
</ul>
<div class="tab-content admin-tabs">
    <div class="tab-pane active" id="tab-billing-filter">
        <form method="GET" action="{{ route('admin.clients.billing') }}">
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
                <tr>
                    <td width="15%" class="fieldlabel">{{ __('common.actions.search') }}</td>
                    <td class="fieldarea">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control input-300">
                    </td>
                    <td width="15%" class="fieldlabel">{{ __('admin.clients.billing_missing_column') }}</td>
                    <td class="fieldarea">
                        <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;margin:0;font-weight:400;">
                            <input type="checkbox" name="only_missing" value="1" {{ request()->boolean('only_missing') ? 'checked' : '' }}>
                            {{ __('admin.clients.billing_only_missing') }}
                        </label>
                    </td>
                </tr>
            </table>
            <div class="btn-container">
                <button type="submit" class="btn btn-default">{{ __('common.actions.search') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="context-btn-container whmcs-record-bar" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin:10px 0;">
    <div style="font-size:12px;color:#555;">
        <strong>{{ method_exists($clients, 'total') ? $clients->total() : count($clients) }}</strong> {{ __('admin.clients.title') }}
    </div>
    <div style="display:flex;gap:6px;align-items:center;">
        <a href="{{ route('admin.clients.billing.csv', request()->only('search', 'only_missing')) }}" class="btn btn-default btn-sm">{{ __('common.actions.export_csv') }}</a>
        <a href="{{ route('admin.clients.index') }}" class="btn btn-default btn-sm">{{ __('admin.clients.title') }}</a>
    </div>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('admin.clients.name') }}</th>
                <th>{{ __('admin.clients.billing_type') }}</th>
                <th>{{ __('client.form.company_title') }}</th>
                <th>{{ __('admin.clients.billing_tax_office') }}</th>
                <th>{{ __('common.form.tax_id') }}</th>
                <th>{{ __('admin.clients.billing_national_id') }}</th>
                <th>{{ __('common.form.address') }}</th>
                <th>{{ __('common.form.phone') }}</th>
                <th>{{ __('admin.clients.billing_missing_column') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($clients as $client)
            @php $gaps = $missing[$client->id] ?? []; @endphp
            <tr>
                <td>
                    <a href="{{ route('admin.clients.show', $client) }}" style="font-weight:600;">{{ $client->display_name }}</a>
                    <div style="color:#888;font-size:11px;">{{ $client->email }}</div>
                </td>
                <td>{{ $client->client_type ? __('admin.clients.billing_type_'.($client->client_type === 'company' ? 'company' : 'individual')) : '—' }}</td>
                <td>{{ $client->client_type === 'company' ? ($client->company_name ?: '—') : '—' }}</td>
                <td>{{ $client->client_type === 'company' ? ($client->tax_office ?: '—') : '—' }}</td>
                <td style="font-family:monospace;">{{ $client->client_type === 'company' ? ($client->tax_id ?: '—') : '—' }}</td>
                <td style="font-family:monospace;">{{ $client->client_type === 'individual' ? ($client->national_id ?: '—') : '—' }}</td>
                <td>{{ $client->address1 ?: '—' }}@if($client->city)<div style="color:#888;font-size:11px;">{{ $client->city }} / {{ $client->country }}</div>@endif</td>
                <td>{{ $client->full_phone ?: '—' }}</td>
                <td>
                    @if($gaps)
                        <span style="color:#a94442;font-size:12px;">{{ implode(', ', \App\Support\BillingIdentity::labels($gaps)) }}</span>
                    @else
                        <span style="color:#3c763d;font-size:12px;">{{ '✓' }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="9" style="padding:20px;text-align:center;color:#888;">{{ __('common.empty.no_records') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if(method_exists($clients, 'links'))
<div style="margin-top:10px;">{{ $clients->withQueryString()->links() }}</div>
@endif
@endsection

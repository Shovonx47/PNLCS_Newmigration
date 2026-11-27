@extends('admin.layouts.app')
@section('title', 'Domain Resolver')
@section('content')

<h1>{{ 'Domain Resolver' }}</h1>

<div class="infobox">
    {{ 'Verify which active hosting domains resolve to their assigned server IP address.' }}
</div>

<form method="GET" action="{{ route('admin.utilities.domain-resolver') }}" style="margin-bottom:16px;">
    <input type="hidden" name="check" value="1">
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="18%" class="fieldlabel">{{ 'Server Filter' }}</td>
            <td class="fieldarea">
                <select name="server_id" class="form-control select-inline">
                    <option value="">{{ '— All Servers —' }}</option>
                    @foreach($servers as $srv)
                        <option value="{{ $srv->id }}" @selected((string) $selectedServerId === (string) $srv->id)>{{ $srv->name }}{{ $srv->ip_address ? ' (' . $srv->ip_address . ')' : '' }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-left:8px;">{{ 'Check Domains' }}</button>
            </td>
        </tr>
    </table>
</form>

@if($runCheck)
    <div class="tablebg">
        <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
            <thead>
                <tr>
                    <th class="text-left">{{ 'Domain' }}</th>
                    <th class="text-left">{{ 'Product / Service' }}</th>
                    <th class="text-left">{{ 'Client' }}</th>
                    <th>{{ 'Assigned Server' }}</th>
                    <th>{{ 'Resolved IP' }}</th>
                    <th>{{ 'Status' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $row)
                    <tr>
                        <td><a href="{{ route('admin.services.show', $row['service_id']) }}" style="font-weight:600;">{{ $row['domain'] }}</a></td>
                        <td>{{ $row['product'] }}</td>
                        <td>
                            @if($row['client'])
                                <a href="{{ route('admin.clients.show', $row['client']) }}">{{ $row['client']->full_name }}</a>
                            @else
                                {{ '—' }}
                            @endif
                        </td>
                        <td style="text-align:center;">{{ $row['server_name'] }}{{ $row['expected_ip'] ? ' (' . $row['expected_ip'] . ')' : '' }}</td>
                        <td style="text-align:center;font-family:monospace;">{{ $row['resolved_ip'] ?: 'Unresolved' }}</td>
                        <td style="text-align:center;">
                            @if($row['matches'])
                                <span class="whmcs-status active">{{ 'Match' }}</span>
                            @elseif(!$row['resolved_ip'])
                                <span class="whmcs-status terminated">{{ 'No A Record' }}</span>
                            @else
                                <span class="whmcs-status pending">{{ 'External / Mismatch' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:#777;padding:22px;">{{ 'No active service domains matched the selected filter.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@endsection

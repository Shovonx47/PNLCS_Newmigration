@extends('admin.layouts.app')
@section('title', 'PHP Version Compatibility')
@section('content')

<h1>{{ 'PHP Version Compatibility' }}</h1>

<div class="successbox">
    <strong>{{ 'Running PHP ' . $phpVersion }}</strong> ({{ strtoupper($sapi) }}, Zend Engine {{ $zendVersion }}) — {{ 'Fully compatible with this installation.' }}
</div>

<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom:16px;">
    <tr>
        <td width="20%" class="fieldlabel">{{ 'Active PHP Version' }}</td>
        <td width="30%" class="fieldarea"><strong>{{ $phpVersion }}</strong></td>
        <td width="20%" class="fieldlabel">{{ 'OPcache Status' }}</td>
        <td width="30%" class="fieldarea">
            <span class="whmcs-status {{ $opcacheEnabled ? 'active' : 'draft' }}">{{ $opcacheEnabled ? 'Enabled' : 'Disabled' }}</span>
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ 'Minimum Required PHP' }}</td>
        <td class="fieldarea"><code>8.4.0+</code></td>
        <td class="fieldlabel">{{ 'Memory Limit' }}</td>
        <td class="fieldarea"><code>{{ ini_get('memory_limit') }}</code></td>
    </tr>
</table>

<h2 style="font-size:15px;font-weight:600;color:#1a4d80;margin:14px 0 6px;">{{ 'Extension Compatibility Checks' }}</h2>
<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th class="text-left">{{ 'Extension' }}</th>
                <th class="text-left">{{ 'Purpose' }}</th>
                <th width="140">{{ 'Status' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($extensionChecks as $chk)
                <tr>
                    <td style="font-family:monospace;font-weight:600;">{{ $chk['extension'] }}</td>
                    <td>{{ $chk['purpose'] }}</td>
                    <td style="text-align:center;">
                        <span class="whmcs-status {{ $chk['loaded'] ? 'active' : 'terminated' }}">{{ $chk['loaded'] ? 'Loaded' : 'Missing' }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection

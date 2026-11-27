@extends('admin.layouts.app')
@section('title', 'Link Tracking')
@section('content')

<h1>{{ 'Link Tracking' }}</h1>

<form method="POST" action="{{ route('admin.utilities.link-tracking.store') }}" style="margin-bottom:16px;">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="15%" class="fieldlabel">{{ 'Name' }}</td>
            <td width="30%" class="fieldarea"><input type="text" name="name" required class="form-control" placeholder="{{ 'e.g. Facebook Spring Promo' }}"></td>
            <td width="15%" class="fieldlabel">{{ 'Forward To URL' }}</td>
            <td width="40%" class="fieldarea">
                <div style="display:flex;gap:6px;">
                    <input type="url" name="url" value="{{ url('/') }}" required class="form-control">
                    <button type="submit" class="btn btn-primary btn-sm">{{ 'Add Link' }}</button>
                </div>
            </td>
        </tr>
    </table>
</form>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ 'ID' }}</th>
                <th class="text-left">{{ 'Name' }}</th>
                <th class="text-left">{{ 'Tracking Link / Destination' }}</th>
                <th>{{ 'Clicks' }}</th>
                <th>{{ 'Conversions' }}</th>
                <th>{{ 'Conversion Rate' }}</th>
                <th width="100">{{ 'Actions' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($links as $lnk)
                @php
                    $clicks = (int) ($lnk['clicks'] ?? 0);
                    $conv = (int) ($lnk['conversions'] ?? 0);
                    $rate = $clicks > 0 ? number_format(($conv / $clicks) * 100, 1) . '%' : '0.0%';
                @endphp
                <tr>
                    <td style="text-align:center;">{{ $lnk['id'] }}</td>
                    <td><strong>{{ $lnk['name'] }}</strong></td>
                    <td>
                        <div style="font-family:monospace;font-size:12px;color:#1a4d80;">{{ url('/?trk=' . $lnk['id']) }}</div>
                        <div style="font-size:11.5px;color:#666;">&rarr; {{ $lnk['url'] }}</div>
                    </td>
                    <td style="text-align:center;">{{ $clicks }}</td>
                    <td style="text-align:center;">{{ $conv }}</td>
                    <td style="text-align:center;">{{ $rate }}</td>
                    <td style="text-align:center;">
                        <form method="POST" action="{{ route('admin.utilities.link-tracking.destroy', $lnk['id']) }}" style="display:inline;" onsubmit="return pnConfirm(event, 'Remove this tracking link?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">{{ 'Delete' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:#777;padding:22px;">{{ 'No tracking links created yet.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

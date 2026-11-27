@extends('admin.layouts.app')
@section('title', 'Registrar TLD Sync')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
    <h1 style="margin:0;border:0;padding:0;">{{ 'Registrar TLD Sync' }}</h1>
    <a href="{{ route('admin.config.domain-pricing') }}" class="btn btn-default btn-sm">{{ 'Domain Pricing Matrix' }}</a>
</div>

<form method="POST" action="{{ route('admin.utilities.tld-sync.run') }}" style="margin-bottom:16px;">
    @csrf
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="18%" class="fieldlabel">{{ 'Domain Registrar' }}</td>
            <td width="32%" class="fieldarea">
                <select name="registrar" required class="form-control select-inline">
                    @forelse($registrars as $reg)
                        <option value="{{ $reg }}" @selected($selectedRegistrar === $reg)>{{ ucfirst($reg) }}</option>
                    @empty
                        <option value="manual">{{ 'Manual / Default' }}</option>
                    @endforelse
                </select>
            </td>
            <td width="18%" class="fieldlabel">{{ 'Profit Margin %' }}</td>
            <td width="32%" class="fieldarea">
                <input type="number" name="margin_percent" value="20" min="0" max="500" step="0.5" class="form-control input-100">
                <button type="submit" class="btn btn-primary btn-sm" style="margin-left:8px;">{{ 'Sync / Verify TLDs' }}</button>
            </td>
        </tr>
    </table>
</form>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th class="text-left">{{ 'TLD' }}</th>
                <th>{{ 'Auto-Registrar' }}</th>
                <th style="text-align:right;">{{ 'Register (1 Yr)' }}</th>
                <th style="text-align:right;">{{ 'Renew (1 Yr)' }}</th>
                <th style="text-align:right;">{{ 'Transfer' }}</th>
                <th>{{ 'EPP Required' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tlds as $row)
                <tr>
                    <td><strong>{{ str_starts_with((string) $row->extension, '.') ? $row->extension : ('.' . $row->extension) }}</strong></td>
                    <td style="text-align:center;">{{ $row->auto_registrar ? ucfirst($row->auto_registrar) : 'None' }}</td>
                    <td style="text-align:right;">{{ money_fmt($row->register_price ?? 0) }}</td>
                    <td style="text-align:right;">{{ money_fmt($row->renew_price ?? 0) }}</td>
                    <td style="text-align:right;">{{ money_fmt($row->transfer_price ?? 0) }}</td>
                    <td style="text-align:center;">{{ !empty($row->epp_code) ? 'Yes' : 'No' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:#777;padding:22px;">{{ 'No TLDs configured yet in Domain Pricing.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

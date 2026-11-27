@extends('admin.layouts.app')
@section('title', __('admin.system_database.title'))
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
    <h1 style="margin:0;border:0;padding:0;">{{ __('admin.system_database.title') }}</h1>
    <form method="POST" action="{{ route('admin.config.system-database.optimize') }}" style="margin:0;">
        @csrf
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-bolt"></i> {{ 'Optimize Tables' }}</button>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:15px;">
    <div class="stat-card">
        <div class="stat-value">{{ $tableCount ?? 0 }}</div>
        <div class="stat-label">{{ __('admin.system_database.total_tables') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ number_format($totalRows ?? 0) }}</div>
        <div class="stat-label">{{ 'Total Records' }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $dbSize ?? '0 KB' }}</div>
        <div class="stat-label">{{ __('admin.system_database.database_size') }}</div>
    </div>
</div>

@if(isset($tables) && count($tables) > 0)
<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th class="text-left">{{ __('admin.system_database.table_name') }}</th>
                <th>{{ __('admin.system_database.engine') }}</th>
                <th style="text-align:right;">{{ __('admin.system_database.rows') }}</th>
                <th style="text-align:right;">{{ __('admin.system_database.data_size') }}</th>
                <th style="text-align:right;">{{ __('admin.system_database.index_size') }}</th>
                <th>{{ __('common.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($tables as $table)
        <tr>
            <td style="font-family:monospace;">{{ $table->Name }}</td>
            <td style="text-align:center;">{{ $table->Engine }}</td>
            <td style="text-align:right;">{{ number_format((int) ($table->Rows ?? 0)) }}</td>
            <td style="text-align:right;">{{ number_format(((int) ($table->Data_length ?? 0)) / 1024, 1) }} KB</td>
            <td style="text-align:right;">{{ number_format(((int) ($table->Index_length ?? 0)) / 1024, 1) }} KB</td>
            <td style="text-align:center;"><span class="whmcs-status active">{{ 'OK' }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection

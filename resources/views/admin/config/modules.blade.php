@extends("admin.layouts.app")
@section("title", __("admin.modules.title"))
@section("content")

@php
    $configure = [
        'server' => route('admin.config.servers'),
        'gateway' => route('admin.config.gateways'),
        'registrar' => route('admin.config.registrars'),
        'ssl' => route('admin.config.sslModules'),
        'addon' => route('admin.config.addons.modules'),
    ];
@endphp

<h1>{{ __('admin.modules.title') }}</h1>
<div class="infobox">{{ __('admin.modules.description') }}</div>

@foreach($groups as $type => $rows)
<div style="margin-bottom:20px;" id="modules-{{ $type }}">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
        <h2 style="font-size:15px;margin:0;">{{ __('admin.modules.type_'.$type) }}</h2>
        <a href="{{ $configure[$type] }}" class="btn btn-default btn-xs">{{ __('admin.modules.configure') }}</a>
    </div>
    <div class="tablebg">
        <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
            <thead>
                <tr>
                    <th>{{ __('admin.modules.col_module') }}</th>
                    <th>{{ __('admin.modules.col_source') }}</th>
                    <th>{{ __('common.table.status') }}</th>
                    @if(in_array($type, ['server', 'ssl'], true))<th>{{ __('admin.modules.col_usage') }}</th>@endif
                    <th style="text-align:right;width:140px;">{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr data-module="{{ $type }}/{{ $row->key }}">
                    <td><span style="font-weight:600;">{{ $row->label }}</span> <span style="font-family:monospace;font-size:11px;color:#777;">{{ $row->key }}</span></td>
                    <td>{{ $row->third_party ? __('admin.modules.source_third_party') : __('admin.modules.source_core') }}</td>
                    <td><span class="whmcs-status {{ $row->active ? 'active badge-active' : 'suspended badge-suspended' }}">{{ $row->active ? __('common.status.active') : __('common.status.disabled') }}</span></td>
                    @if(in_array($type, ['server', 'ssl'], true))<td>{{ $row->in_use }}</td>@endif
                    <td style="text-align:right;white-space:nowrap;">
                        <form method="POST" action="{{ route('admin.config.modules.toggle', [$type, $row->key]) }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="active" value="{{ $row->active ? '0' : '1' }}">
                            @if($row->active)
                                <button type="submit" class="btn btn-default btn-xs" @if($row->in_use > 0) disabled title="{{ __('admin.modules.in_use_cannot_disable', ['count' => $row->in_use]) }}" @endif>{{ __('admin.modules.switch_off') }}</button>
                            @else
                                <button type="submit" class="btn btn-primary btn-xs">{{ __('admin.modules.switch_on') }}</button>
                            @endif
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ in_array($type, ['server', 'ssl'], true) ? 5 : 4 }}" style="text-align:center;padding:18px;color:#777;">{{ __('admin.modules.none') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endforeach

@endsection

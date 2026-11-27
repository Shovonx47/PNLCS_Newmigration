@extends("admin.layouts.app")
@section("title", __("admin.addon_modules.title"))
@section("content")

<h1>{{ __('admin.addon_modules.title') }}</h1>
<div class="infobox">{{ __('admin.addon_modules.description') }}</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.name') }}</th>
                <th>{{ __('Version') }}</th>
                <th>{{ __('admin.addon_modules.author') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th style="text-align:right;width:220px;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($addons as $name => $addon)
            <tr>
                <td>
                    <div style="font-weight:700;">{{ $addon->getDisplayName() }}</div>
                    <div style="font-size:12px;color:#666;">{{ $addon->getDescription() }}</div>
                    @if(isset($pending[$name]))
                    <div class="alert alert-warning" style="font-size:12px;margin:6px 0 0;padding:6px 10px;">{{ __('admin.addon_modules.upgrade_pending', ['from' => $pending[$name]['from'], 'to' => $pending[$name]['to']]) }} <code>{{ 'php artisan pnlcs:addons-upgrade' }}</code></div>
                    @endif
                </td>
                <td>{{ 'v'.$addon->getVersion() }}</td>
                <td>{{ $addon->getAuthor() }}</td>
                <td>
                    <span class="whmcs-status {{ ($statuses[$name] ?? false) ? 'active badge-active' : 'suspended' }}">
                        {{ ($statuses[$name] ?? false) ? __('admin.addon_modules.status_active') : __('admin.addon_modules.status_inactive') }}
                    </span>
                </td>
                <td style="text-align:right;white-space:nowrap;">
                    <form method="POST" action="{{ route('admin.config.addons.modules.toggle', $name) }}" style="display:inline;">
                        @csrf
                        @if($statuses[$name] ?? false)
                            <a href="{{ route('admin.config.addons.modules.show', $name) }}" class="btn btn-xs btn-primary">{{ __('admin.addon_modules.open') }}</a>
                            <button type="submit" class="btn btn-xs btn-danger">{{ __('admin.addon_modules.deactivate') }}</button>
                        @else
                            <button type="submit" class="btn btn-xs btn-success">{{ __('admin.addon_modules.activate') }}</button>
                        @endif
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align:center;padding:20px;color:#777;">{{ __('admin.addon_modules.no_modules') }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@endsection

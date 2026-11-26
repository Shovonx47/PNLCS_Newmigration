<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <a href="{{ route('admin.extensions.reseller-center.index') }}"
           class="btn btn-sm {{ request()->routeIs('admin.extensions.reseller-center.index') ? 'btn-primary' : 'btn-default' }}">
            Dashboard
        </a>
        <a href="{{ route('admin.extensions.reseller-center.resellers') }}"
           class="btn btn-sm {{ request()->routeIs('admin.extensions.reseller-center.resellers*') ? 'btn-primary' : 'btn-default' }}">
            Resellers
        </a>
        <a href="{{ route('admin.extensions.reseller-center.groups') }}"
           class="btn btn-sm {{ request()->routeIs('admin.extensions.reseller-center.groups*') ? 'btn-primary' : 'btn-default' }}">
            Pricing Groups
        </a>
        <a href="{{ route('admin.extensions.reseller-center.payouts') }}"
           class="btn btn-sm {{ request()->routeIs('admin.extensions.reseller-center.payouts*') ? 'btn-primary' : 'btn-default' }}">
            Payouts
        </a>
        <a href="{{ route('admin.extensions.reseller-center.utilities') }}"
           class="btn btn-sm {{ request()->routeIs('admin.extensions.reseller-center.utilities*') ? 'btn-primary' : 'btn-default' }}">
            Utilities &amp; Logs
        </a>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <a href="{{ route('admin.extensions.reseller-center.export', ['type' => 'resellers']) }}" class="btn btn-default btn-sm">Export Resellers CSV</a>
        <a href="{{ route('admin.extensions.reseller-center.export', ['type' => 'payouts']) }}" class="btn btn-default btn-sm">Export Payouts CSV</a>
    </div>
</div>

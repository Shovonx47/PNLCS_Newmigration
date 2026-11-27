{{-- WHMCS Blend Client Profile Tab Strip --}}
@php
    $activeClientTab = $activeTab ?? 'summary';
    $cTabs = [
        ['key' => 'summary',  'label' => __('admin.clients.tab_summary'),  'url' => route('admin.clients.show', ['client' => $client, 'tab' => 'summary'])],
        ['key' => 'profile',  'label' => 'Profile',                        'url' => route('admin.clients.edit', $client)],
        ['key' => 'services', 'label' => __('admin.clients.tab_services'), 'url' => route('admin.clients.show', ['client' => $client, 'tab' => 'services'])],
        ['key' => 'domains',  'label' => __('admin.clients.tab_domains'),  'url' => route('admin.clients.show', ['client' => $client, 'tab' => 'domains'])],
        ['key' => 'invoices', 'label' => __('admin.clients.tab_invoices'), 'url' => route('admin.clients.show', ['client' => $client, 'tab' => 'invoices'])],
        ['key' => 'quotes',   'label' => __('admin.nav.quotes'),           'url' => route('admin.quotes.index', ['client_id' => $client->id])],
        ['key' => 'credit',   'label' => 'Credit',                         'url' => route('admin.clients.credit', $client)],
        ['key' => 'tickets',  'label' => __('admin.clients.tab_tickets'),  'url' => route('admin.clients.show', ['client' => $client, 'tab' => 'tickets'])],
        ['key' => 'notes',    'label' => __('admin.clients.tab_notes'),    'url' => route('admin.clients.show', ['client' => $client, 'tab' => 'notes'])],
        ['key' => 'log',      'label' => __('admin.clients.tab_log'),      'url' => route('admin.clients.show', ['client' => $client, 'tab' => 'log'])],
    ];
@endphp
<ul class="client-tabs" style="margin-bottom:0;">
    @foreach($cTabs as $ct)
        <li class="{{ $activeClientTab === $ct['key'] ? 'active' : '' }}">
            <a href="{{ $ct['url'] }}">{{ $ct['label'] }}</a>
        </li>
    @endforeach
</ul>

{{--
    Quick links to a service's hosting tools, for rows in listing pages.

    Which tools exist is the service's own answer (hostingFeatureKeys), so a
    container plan shows Apps alone and a non-hosting service shows nothing at
    all. Only a short, fixed set is linked here - the service page still lists
    everything - because a row is not the place for nine links.
--}}
@php
    $svcTools = $svc->hostingFeatureKeys();
    $svcShortcuts = [
        'containers' => ['client.services.containers', 'fas fa-cubes', __('client.hosting.tools.apps')],
        'files' => ['client.services.files', 'far fa-folder', __('client.hosting.tools.files')],
        'emails' => ['client.services.emails', 'far fa-envelope', __('client.hosting.tools.emails')],
        'databases' => ['client.services.databases', 'fas fa-database', __('client.hosting.tools.databases')],
        'laravel' => ['client.services.laravel', 'fab fa-laravel', __('client.hosting.tools.laravel')],
        'nodejs' => ['client.services.nodejs', 'fab fa-node-js', __('client.hosting.tools.nodejs')],
        'python' => ['client.services.python', 'fab fa-python', __('client.hosting.tools.python')],
    ];
    $svcShown = array_filter($svcShortcuts, fn ($v, $k) => in_array($k, $svcTools, true), ARRAY_FILTER_USE_BOTH);
@endphp
{{-- Inline, like the hosting pages do: the client layout has no styles stack,
     and the CSS bundle is built by Vite, which a blade-only change must not
     depend on. @once keeps it to one copy per page however many rows there are. --}}
@once
<style>
    .svc-tools{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}
    .svc-tool{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;line-height:1;
        padding:4px 8px;border:1px solid #ddd;border-radius:3px;color:var(--muted);
        background:#f8f8f8;text-decoration:none;transition:color .14s,border-color .14s,background .14s}
    .svc-tool:hover{color:#337ab7;border-color:#337ab7;background:var(--card);text-decoration:none}
    .svc-tool i{font-size:11px}
</style>
@endonce
@if($svcShown)
<div class="svc-tools">
    @foreach($svcShown as $key => [$route, $icon, $label])
    <a href="{{ route($route, $svc) }}" class="svc-tool" title="{{ $label }}"><i class="{{ $icon }}"></i>{{ $label }}</a>
    @endforeach
</div>
@endif

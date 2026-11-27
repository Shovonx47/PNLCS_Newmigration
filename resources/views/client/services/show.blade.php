@extends('client.layouts.twentyone')
@section('title', $service->product?->name ?? __('client.services.title'))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('client.services.index') }}">{{ __('client.nav.my_services') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $service->domain ?: ($service->product?->name ?? __('client.services.title')) }}</li>
@endsection

@php
    $st = strtolower((string) $service->status);
    $isPanelica = (($service->server?->type ?? $service->product?->server_type ?? '') === 'panelica');
    $panelModule = $service->server_id ? app(\App\Services\ProvisioningService::class)->resolveModule($service) : null;
    $canPanelLogin = $isPanelica || ($panelModule && method_exists($panelModule, 'ssoLogin'));
    $hasLiveUsage = $panelModule && method_exists($panelModule, 'liveUsage');
    $feat = $hostingFeatures ?? [];
    $onHypervisor = strtolower((string) $service->server?->type) === 'proxmox';
    $tools = [
        ['k' => 'files', 'name' => __('client.hosting.files.title'), 'route' => route('client.services.files', $service)],
        ['k' => 'emails', 'name' => __('client.hosting.email.title'), 'route' => route('client.services.emails', $service)],
        ['k' => 'databases', 'name' => __('client.hosting.databases.title'), 'route' => route('client.services.databases', $service)],
        ['k' => 'ftp', 'name' => __('client.hosting.ftp.title'), 'route' => route('client.services.ftp', $service)],
        ['k' => 'subdomains', 'name' => __('client.hosting.subdomains.title'), 'route' => route('client.services.subdomains', $service)],
        ['k' => 'dns', 'name' => __('client.hosting.dns.title'), 'route' => route('client.services.dns', $service)],
        ['k' => 'cron', 'name' => __('client.hosting.cron.title'), 'route' => route('client.services.cron', $service)],
        ['k' => 'backups', 'name' => __('client.hosting.backups.title'), 'route' => route('client.services.backups', $service)],
        ['k' => 'containers', 'name' => __('client.hosting.containers.title'), 'route' => route('client.services.containers', $service)],
        ['k' => 'laravel', 'name' => __('client.hosting.laravel.title'), 'route' => route('client.services.laravel', $service)],
        ['k' => 'nodejs', 'name' => __('client.hosting.nodejs.title'), 'route' => route('client.services.nodejs', $service)],
        ['k' => 'python', 'name' => __('client.hosting.python.title'), 'route' => route('client.services.python', $service)],
    ];
    $liveTools = array_values(array_filter($tools, fn ($t) => in_array($t['k'], $feat, true)));
    $hasTabs = filled($service->domain) || filled($modulePanel) || ($orderAnswers ?? collect())->isNotEmpty() || $hasLiveUsage || $liveTools !== [] || filled($service->username);
@endphp

@section('content')

@if($setupDelayed ?? false)
<div class="alert alert-warning text-center" role="status">{{ __('client.services.setup_delayed') }}</div>
@endif

@if($st === 'suspended')
<div class="alert alert-danger" role="status">
    {{ $service->customerSuspensionReason() !== null ? __('client.services.suspended_because', ['reason' => $service->customerSuspensionReason()]) : __('client.services.suspended_ask_support') }}
</div>
@endif

<div class="tab-content margin-bottom">
    <div class="tab-pane fade show active" role="tabpanel" id="tabOverview">

        <div class="card">
            <div class="card-body">

                <div class="product-details">

                    <div class="row">
                        <div class="col-md-6">

                            <div class="product-status product-status-{{ $st }} mb-3">
                                <div class="product-icon text-center">
                                    <span class="fa-stack fa-lg">
                                        <i class="fas fa-circle fa-stack-2x"></i>
                                        <i class="fas fa-hdd fa-stack-1x fa-inverse"></i>
                                    </span>
                                    <h3>{{ $service->product?->name ?? __('client.services.title') }}</h3>
                                    <h4>{{ $service->domain ?: cycle_label($service->billing_cycle) }}</h4>
                                </div>
                                <div class="product-status-text">
                                    {{ ucfirst($st) }}
                                </div>
                            </div>

                            <div class="row product-actions-wrapper">
                                @if($canPanelLogin && $st === 'active')
                                <div class="col-12">
                                    <a href="{{ route('client.services.login', $service) }}" target="_blank" rel="noopener" class="btn btn-block btn-primary">
                                        <i class="fas fa-external-link-alt"></i>
                                        {{ __('client.services.login_to_panel') }}
                                    </a>
                                </div>
                                @endif
                                @if($upgradeOffered ?? true)
                                <div class="col-12">
                                    <a href="{{ route('client.services.upgrade', $service) }}" class="btn btn-block btn-success">
                                        <i class="fas fa-level-up-alt"></i>
                                        Upgrade
                                    </a>
                                </div>
                                @endif
                                @if($canRenewEarly ?? false)
                                <div class="col-12">
                                    <form method="POST" action="{{ route('client.services.renew', $service) }}">@csrf
                                        <button type="submit" class="btn btn-block btn-primary">
                                            <i class="fas fa-sync"></i>
                                            {{ __('client.services.renew_now') }}
                                        </button>
                                    </form>
                                </div>
                                @endif
                                <div class="col-12">
                                    <a href="{{ route('client.services.cancel', $service) }}" class="btn btn-block btn-danger">
                                        <i class="fas fa-ban"></i>
                                        {{ __('client.services.request_cancellation') }}
                                    </a>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-6 text-center">

                            <h4>{{ __('client.services.registration_date') }}</h4>
                            {{ $service->registration_date?->format(date_fmt()) ?? '—' }}

                            <h4>{{ __('client.services.amount') }}</h4>
                            {{ display_money_fmt($service->amount) }}

                            <h4>Billing Cycle</h4>
                            {{ cycle_label($service->billing_cycle) ?: '—' }}
                            @if(! empty($cycleOptions))
                            <form method="POST" action="{{ route('client.services.cycle', $service) }}" class="form-inline justify-content-center mt-2">@csrf
                                <select name="billing_cycle" class="form-control form-control-sm mr-2" aria-label="{{ __('client.services.change_cycle') }}">
                                    @foreach($cycleOptions as $cycle => $amount)
                                    <option value="{{ $cycle }}">{{ cycle_label($cycle) }} · {{ display_money_fmt($amount) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-default btn-sm">{{ __('client.services.change_cycle') }}</button>
                            </form>
                            @endif

                            <h4>{{ __('client.services.next_due_date') }}</h4>
                            {{ $service->next_due_date?->format(date_fmt()) ?? '—' }}

                            <h4>{{ __('client.services.auto_renew') }}</h4>
                            <form method="POST" action="{{ route('client.services.autorenew', $service) }}" class="d-inline">@csrf
                                <button type="submit" class="btn btn-sm {{ $service->auto_renew ? 'btn-success' : 'btn-default' }}">
                                    {{ $service->auto_renew ? __('client.status.enabled') : __('client.status.disabled') }}
                                </button>
                            </form>

                            @if($service->suspend_reason ?? null)
                            <h4>{{ 'Suspension Reason' }}</h4>
                            {{ $service->suspend_reason }}
                            @endif

                        </div>
                    </div>

                </div>
            </div>
        </div>

        @if($hasTabs)
        <ul class="nav nav-tabs responsive-tabs-sm">
            @if(filled($service->domain) || filled($service->username))
            <li class="nav-item">
                <a href="#domain" data-toggle="tab" class="nav-link active"><i class="fas fa-globe fa-fw"></i> {{ __('client.services.server_info') }}</a>
            </li>
            @elseif(! empty($modulePanel) || $liveTools !== [])
            <li class="nav-item">
                <a href="#manage" data-toggle="tab" class="nav-link active"><i class="fas fa-globe fa-fw"></i> Manage</a>
            </li>
            @endif
            @if(($orderAnswers ?? collect())->isNotEmpty())
            <li class="nav-item">
                <a href="#configoptions" data-toggle="tab" class="nav-link{{ filled($service->domain) ? '' : ' active' }}"><i class="fas fa-cubes fa-fw"></i> {{ __('client.cart.order_questions') }}</a>
            </li>
            @endif
            @if($hasLiveUsage && $st === 'active')
            <li class="nav-item">
                <a href="#resourceusage" data-toggle="tab" class="nav-link{{ filled($service->domain) ? '' : ' active' }}"><i class="fas fa-inbox fa-fw"></i> Resource Usage</a>
            </li>
            @endif
            @if(! empty($modulePanel) || $liveTools !== [])
            <li class="nav-item">
                <a href="#manage" data-toggle="tab" class="nav-link{{ filled($service->domain) ? '' : ' active' }}"><i class="fas fa-cogs fa-fw"></i> Manage</a>
            </li>
            @endif
            @if(!empty($vpsFeatures))
            <li class="nav-item">
                <a href="#vps" data-toggle="tab" class="nav-link"><i class="fas fa-server fa-fw"></i> {{ 'VPS Details' }}</a>
            </li>
            @endif
        </ul>

        <div class="tab-content bg-white product-details-tab-container">

            <div class="tab-pane fade show active text-center" role="tabpanel" id="domain">
                @if(filled($service->domain))
                <div class="row">
                    <div class="col-sm-5 text-right"><strong>{{ 'Domain' }}</strong></div>
                    <div class="col-sm-7 text-left">{{ $service->domain }}</div>
                </div>
                @endif
                @if(filled($service->username))
                <div class="row">
                    <div class="col-sm-5 text-right"><strong>{{ __('client.services.username') }}</strong></div>
                    <div class="col-sm-7 text-left">{{ $service->username }}</div>
                </div>
                @endif
                @unless($onHypervisor)
                @if($service->server?->hostname)
                <div class="row">
                    <div class="col-sm-5 text-right"><strong>{{ __('client.services.server') }}</strong></div>
                    <div class="col-sm-7 text-left">{{ $service->server->hostname }}</div>
                </div>
                @endif
                @endunless
                @if($service->primaryIp())
                <div class="row">
                    <div class="col-sm-5 text-right"><strong>{{ __('client.services.ip_address') }}</strong></div>
                    <div class="col-sm-7 text-left">{{ $service->primaryIp() }}</div>
                </div>
                @endif
                @if(filled($service->domain))
                <br>
                <p>
                    <a href="http://{{ $service->domain }}" class="btn btn-default" target="_blank" rel="noopener">{{ 'Visit Website' }}</a>
                </p>
                @endif
            </div>

            <div class="tab-pane fade" role="tabpanel" id="configoptions">
                @foreach(($orderAnswers ?? collect()) as $row)
                <div class="row">
                    <div class="col-sm-5"><strong>{{ $row['name'] }}</strong></div>
                    <div class="col-sm-7 text-left">{{ $row['value'] === '1' ? __('common.yes') : $row['value'] }}</div>
                </div>
                @endforeach
            </div>

            <div class="tab-pane fade" role="tabpanel" id="resourceusage">
                <div class="row">
                    <div class="col-sm-10 offset-sm-1">
                        <div class="row">
                            <div class="col-sm-3">
                                <h4>CPU</h4>
                                <p><span id="m-cpu-v">—</span>%</p>
                            </div>
                            <div class="col-sm-3">
                                <h4>Memory</h4>
                                <p><span id="m-ram-v">—</span></p>
                            </div>
                            <div class="col-sm-3">
                                <h4>{{ 'Disk Usage' }}</h4>
                                <p><span id="m-disk-v">—</span></p>
                            </div>
                            <div class="col-sm-3">
                                <h4>Bandwidth</h4>
                                <p><span id="m-bw-v">—</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-muted mb-0">Last Updated: {{ now()->timezone(display_tz())->format(datetime_fmt()) }}</p>
            </div>

            <div class="tab-pane fade text-center" role="tabpanel" id="manage">
                @if(! empty($modulePanel))
                    {!! $modulePanel !!}
                @endif
                @if($liveTools !== [])
                <div class="row justify-content-center">
                    @foreach($liveTools as $t)
                    <div class="col-6 col-md-3 mb-2">
                        <a href="{{ $t['route'] }}" class="btn btn-default btn-block">{{ $t['name'] }}</a>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            @if(!empty($vpsFeatures))
            <div class="tab-pane fade" role="tabpanel" id="vps">
                @include('client.services.partials.vps')
            </div>
            @endif

        </div>
        @endif

        @php($productDetailsOutput = hook_output('ClientAreaProductDetailsOutput', ['service' => $service]))
        @if($productDetailsOutput !== '')
        <div class="mb-3">{!! $productDetailsOutput !!}</div>
        @endif

        @if(($downloads ?? collect())->isNotEmpty())
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">{{ __('client.downloads.title') }}</h3>
                <div class="alert alert-info text-center">{{ 'The following downloads are available for this product.' }}</div>
                @foreach($downloads as $download)
                <div class="row">
                    <div class="col-10 offset-1">
                        <h4>{{ $download->title }}</h4>
                        @if($download->description)<p>{{ $download->description }}</p>@endif
                        <p><a href="{{ route('client.downloads.download', $download) }}" class="btn btn-default"><i class="fas fa-download"></i> {{ __('common.actions.download') }}</a></p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($service->addons && $service->addons->count())
        <h3 class="mt-3">{{ __('client.services.addons') }}</h3>
        <div class="row">
            @foreach($service->addons as $addon)
            <div class="col-10 offset-1">
                <div class="card bg-default card-accent-blue">
                    <div class="card-header">
                        {{ $addon->label() }}
                        <div class="float-right label status-{{ strtolower((string) $addon->status) }}">{{ ucfirst(strtolower((string) $addon->status)) }}</div>
                    </div>
                    <div class="row card-body">
                        <div class="col-md-6">
                            <p>{{ display_money_fmt($addon->amount) }} {{ cycle_label($addon->billing_cycle) }}</p>
                            <p>Next Due Date: {{ $addon->next_due_date?->format(date_fmt()) ?? '—' }}</p>
                        </div>
                        @if($addon->addon?->description)
                        <div class="col-md-6">
                            <p class="text-muted">{{ \Illuminate\Support\Str::limit($addon->addon->description, 160) }}</p>
                        </div>
                        @endif
                    </div>
                    @if(in_array(strtolower((string) $addon->status), ['active', 'pending'], true))
                    <div class="card-footer">
                        <form method="POST" action="{{ route('client.services.addons.cancel', [$service, $addon]) }}" onsubmit="return pnConfirm(event, @js(__('client.services.addon_cancel_confirm')))">@csrf
                            <button type="submit" class="btn btn-default btn-sm">{{ __('client.services.addon_cancel') }}</button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if(($availableAddons ?? collect())->isNotEmpty())
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">{{ 'Available Addons' }}</h3>
                <div class="alert alert-info text-center">{{ 'The following addons can be purchased with this product.' }}</div>
                @foreach($availableAddons as $available)
                    @php($quote = $addonQuotes[$available->id] ?? null)
                    <form method="POST" action="{{ route('client.services.addons.store', $service) }}" class="d-flex justify-content-between align-items-center border-bottom py-2">@csrf
                        <input type="hidden" name="addon_id" value="{{ $available->id }}">
                        <span>
                            <strong>{{ $available->name }}</strong>
                            @if($available->description)<br><small class="text-muted">{{ $available->description }}</small>@endif
                        </span>
                        <span class="text-nowrap ml-3">
                            @if($quote && $quote['prorated'])
                                {{ __('client.services.addon_first_payment', ['first' => display_money_fmt($quote['first_amount']), 'days' => $quote['remaining_days'], 'cycle_days' => $quote['cycle_days'], 'full' => display_money_fmt($quote['full_amount']), 'cycle' => cycle_label($quote['cycle'])]) }}
                            @elseif($quote)
                                {{ display_money_fmt($quote['full_amount']) }}/{{ cycle_label($quote['cycle']) }}
                            @endif
                            <button type="submit" class="btn btn-primary btn-sm">{{ __('client.services.addon_order') }}</button>
                        </span>
                    </form>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

@if($hasLiveUsage && $st === 'active')
<script>
(function () {
    function set(id, text) {
        var el = document.getElementById(id);
        if (el) el.textContent = text;
    }
    fetch("{{ route('client.services.usage', $service) }}", {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(function (r) { return r.json(); })
        .then(function (u) {
            if (!u || !u.available) {
                ['m-cpu-v', 'm-ram-v', 'm-disk-v', 'm-bw-v'].forEach(function (id) { set(id, 'Not reported'); });
                return;
            }
            if (u.cpu) set('m-cpu-v', (u.cpu.percent || 0).toFixed(2));
            if (u.ram) set('m-ram-v', (u.ram.used_mb || 0).toLocaleString() + ' MB / ' + (u.ram.limit_mb || 0).toLocaleString() + ' MB');
            if (u.disk) set('m-disk-v', (u.disk.used_mb || 0).toLocaleString() + ' MB / ' + ((u.disk.quota_mb || 0) > 0 ? (u.disk.quota_mb).toLocaleString() + ' MB' : '∞'));
            if (u.bandwidth) set('m-bw-v', (u.bandwidth.used_mb || 0).toLocaleString() + ' MB');
        })
        .catch(function () {
            ['m-cpu-v', 'm-ram-v', 'm-disk-v', 'm-bw-v'].forEach(function (id) { set(id, 'Not reported'); });
        });
})();
</script>
@endif

@endsection

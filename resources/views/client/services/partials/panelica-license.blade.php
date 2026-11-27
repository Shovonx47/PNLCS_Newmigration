{{-- Panelica license section of the customer's service page
     (Modules\Servers\PanelicaLicense, drawn through ModuleScreens). --}}
@php
    $fmt = fn ($iso) => $iso ? \Illuminate\Support\Carbon::parse($iso)->timezone(config('app.timezone'))->format(date_fmt()) : '—';
    $active = strtolower((string) $service->status) === 'active';
    $seenServer = $install && (filled($install['hostname'] ?? null) || filled($install['ip_address'] ?? null));
    $needsHelp = filled($data['last_error'] ?? null);
    $badgeClass = match ($state) {
        'licensed' => 'badge-success',
        'waiting' => 'badge-warning',
        default => 'badge-secondary',
    };
@endphp

<div class="card mb-4" id="panelica-license">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title h5 mb-0">
            <i class="fas fa-key mr-2"></i>{{ __('panelica_license.client.title') }}
        </h3>
        <span class="badge {{ $badgeClass }}">{{ __('panelica_license.state.'.$state) }}</span>
    </div>

    @if($state === 'waiting')
        <div class="card-body">
            <p class="text-muted">{{ __('panelica_license.client.waiting_lead') }}</p>
            <ol class="pl-3 mb-3">
                <li class="mb-3">
                    <strong class="d-block">{{ __('panelica_license.client.step_server') }}</strong>
                    <small class="text-muted">{{ __('panelica_license.client.step_server_hint') }}</small>
                </li>
                <li class="mb-3">
                    <strong class="d-block">{{ __('panelica_license.client.step_run') }}</strong>
                    @if($active && $command)
                        <div class="input-group mt-2 mb-1">
                            <code id="pl-command" class="form-control bg-dark text-light text-monospace h-auto py-2">{{ $command }}</code>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-default btn-sm" data-pl-copy="pl-command" data-copied="{{ __('panelica_license.client.copied') }}">
                                    {{ __('panelica_license.client.copy') }}
                                </button>
                            </div>
                        </div>
                        <small class="text-muted d-block">{{ __('panelica_license.client.step_run_hint') }}</small>
                    @else
                        <small class="text-muted">{{ __('panelica_license.client.link_paused') }}</small>
                    @endif
                </li>
                <li>
                    <strong class="d-block">{{ __('panelica_license.client.step_wizard') }}</strong>
                    <small class="text-muted">{{ __('panelica_license.client.step_wizard_hint') }}</small>
                </li>
            </ol>

            @if($needsHelp)
                <div class="alert alert-warning">{{ __('panelica_license.client.needs_help') }}</div>
            @elseif($seenServer)
                <div class="alert alert-info">{{ __('panelica_license.client.server_seen', ['server' => trim(($install['hostname'] ?? '').' '.(($install['ip_address'] ?? '') !== '' ? '('.$install['ip_address'].')' : ''))]) }}</div>
            @elseif(! empty($data['script_fetched_at']))
                <div class="alert alert-info">{{ __('panelica_license.client.install_started', ['time' => $fmt($data['script_fetched_at'])]) }}</div>
            @endif

            @if($active)
                <div class="d-flex flex-wrap">
                    <form method="POST" action="{{ route('client.services.module-function', [$service, 'checkNow']) }}" class="mr-2 mb-2">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-sync-alt mr-1"></i>{{ __('panelica_license.action.check_now') }}
                        </button>
                    </form>
                    @if($canChangeLink)
                        <form method="POST" action="{{ route('client.services.module-function', [$service, 'newInstallLink']) }}"
                              onsubmit="return pnConfirm(event, @js(__('panelica_license.client.new_link_confirm')))" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-default btn-sm">{{ __('panelica_license.action.new_link') }}</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    @elseif($state === 'licensed')
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <tbody>
                    <tr>
                        <th class="text-muted">{{ __('panelica_license.field.key') }}</th>
                        <td class="text-right">
                            <code id="pl-key" class="text-monospace mr-2">{{ $data['license_key'] ?? '—' }}</code>
                            @if(! empty($data['license_key']))
                                <button type="button" class="btn btn-default btn-sm" data-pl-copy="pl-key" data-copied="{{ __('panelica_license.client.copied') }}">
                                    {{ __('panelica_license.client.copy') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">{{ __('panelica_license.field.plan') }}</th>
                        <td class="text-right">{{ $data['license_plan'] ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">{{ __('panelica_license.field.status') }}</th>
                        <td class="text-right">{{ __('panelica_license.license_status.'.(in_array($data['license_status'] ?? '', ['active', 'suspended', 'expired', 'cancelled', 'terminated', 'pending'], true) ? $data['license_status'] : 'unknown')) }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">{{ __('panelica_license.field.valid_until') }}</th>
                        <td class="text-right">{{ $fmt($data['license_expires_at'] ?? null) }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">{{ __('panelica_license.field.server') }}</th>
                        <td class="text-right">{{ trim(($data['license_server_hostname'] ?? '').' '.(($data['license_server_ip'] ?? '') !== '' ? '('.$data['license_server_ip'].')' : '')) ?: '—' }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">{{ __('panelica_license.field.last_seen') }}</th>
                        <td class="text-right">{{ $fmt($data['license_heartbeat_at'] ?? null) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            {{ __('panelica_license.client.licensed_hint') }}
        </div>
    @elseif($state === 'ended')
        <div class="card-body">
            <p class="text-muted mb-0">{{ __('panelica_license.client.ended') }}</p>
        </div>
    @else
        <div class="card-body">
            <p class="text-muted mb-0">{{ __('panelica_license.client.preparing') }}</p>
        </div>
    @endif
</div>

<script>
document.querySelectorAll('#panelica-license [data-pl-copy]').forEach(function (button) {
    button.addEventListener('click', function () {
        var target = document.getElementById(button.dataset.plCopy);
        if (!target || !navigator.clipboard) return;
        navigator.clipboard.writeText(target.textContent.trim()).then(function () {
            button.textContent = button.dataset.copied;
        });
    });
});
</script>

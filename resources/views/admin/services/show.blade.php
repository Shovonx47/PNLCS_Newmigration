@extends('admin.layouts.app')
@section('title', ($service->product?->name ?? 'Service') . ($service->domain ? ' - ' . $service->domain : ''))
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
    <h1 style="margin:0;">
        {{ $service->product?->name ?? ('Service #' . $service->id) }}
        @if($service->domain) &mdash; <span>{{ $service->domain }}</span>@endif
        <span class="whmcs-status badge-{{ strtolower($service->status) }}" style="font-size:12px;vertical-align:middle;margin-left:6px;">{{ status_label($service->status) }}</span>
    </h1>
    <a href="{{ route('admin.services.index') }}" class="btn btn-default btn-sm">&larr; {{ __('admin.services.back') }}</a>
</div>

@if($service->client)
    @include('admin.clients.partials.client-tabs', ['client' => $service->client, 'activeTab' => 'services'])
@endif
<div class="{{ $service->client ? 'tab-content client-tabs' : '' }}">
<div class="{{ $service->client ? 'tab-pane active' : '' }}">

@if($decision = session('server_decision'))
@php
    $decisionStatus = (string) ($decision['status'] ?? '');
    $decisionDelete = ($decision['action'] ?? '') === 'delete';
    $decisionServer = $service->server?->name ?: ($service->server?->hostname ?: __('admin.services.server_decision_the_server'));
@endphp
<div class="infobox" id="server-decision" style="margin-bottom:15px;border-color:#d9534f;background:#fdf2f2;">
    <strong style="color:#a12a1f;">{{ __('admin.services.server_decision_title', ['server' => $decisionServer]) }}</strong>
    <p style="margin:6px 0;">
        @if(($decision['state'] ?? '') === 'unknown')
            {{ __('admin.services.server_decision_unknown', ['server' => $decisionServer]) }}
        @else
            {{ __('admin.services.server_decision_live', ['account' => $service->username ?: '-', 'domain' => $service->domain ?: '-', 'server' => $decisionServer]) }}
        @endif
    </p>
    <p style="margin:0 0 10px;color:#666;">{{ $decisionDelete ? __('admin.services.server_decision_delete_why') : __('admin.services.server_decision_status_why', ['status' => status_label($decisionStatus)]) }}</p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <form method="POST" action="{{ $decisionDelete ? route('admin.services.destroy', $service) : route('admin.services.status', $service) }}" style="display:inline;">
            @csrf
            @method($decisionDelete ? 'DELETE' : 'PUT')
            @unless($decisionDelete)<input type="hidden" name="status" value="{{ $decisionStatus }}">@endunless
            <input type="hidden" name="server" value="terminate">
            <button type="submit" class="btn btn-danger btn-sm" autofocus>{{ $decisionDelete ? __('admin.services.decision_terminate_delete') : __('admin.services.decision_terminate_status', ['status' => status_label($decisionStatus)]) }}</button>
        </form>
        <form method="POST" action="{{ $decisionDelete ? route('admin.services.destroy', $service) : route('admin.services.status', $service) }}" style="display:inline;">
            @csrf
            @method($decisionDelete ? 'DELETE' : 'PUT')
            @unless($decisionDelete)<input type="hidden" name="status" value="{{ $decisionStatus }}">@endunless
            <input type="hidden" name="server" value="keep">
            <button type="submit" class="btn btn-default btn-sm">{{ $decisionDelete ? __('admin.services.decision_keep_delete') : __('admin.services.decision_keep_status', ['status' => status_label($decisionStatus)]) }}</button>
        </form>
        <a href="{{ route('admin.services.show', $service) }}" class="btn btn-link btn-sm">{{ __('admin.services.decision_cancel') }}</a>
    </div>
</div>
@endif

<form method="POST" action="{{ route('admin.services.update', $service) }}">
    @csrf
    @method('PUT')
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        <tr>
            <td width="20%" class="fieldlabel">{{ __('admin.services.order') }}</td>
            <td width="30%" class="fieldarea">
                @if($service->order_id)
                <a href="{{ route('admin.orders.show', $service->order_id) }}">{{ '#' . $service->order_id }}</a>
                @else
                {{ '-' }}
                @endif
                @if($service->client)
                <span class="text-muted">&mdash; <a href="{{ route('admin.clients.show', $service->client_id) }}">{{ $service->client->full_name }}</a></span>
                @endif
            </td>
            <td width="20%" class="fieldlabel">{{ __('admin.services.registered') }}</td>
            <td width="30%" class="fieldarea">{{ $service->registration_date?->format(date_fmt()) ?? '-' }}</td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.services.product') }}</td>
            <td class="fieldarea">
                <select name="product_id" class="form-control select-inline">
                    @foreach($products as $p)
                    <option value="{{ $p->id }}" @selected((int) old('product_id', $service->product_id) === $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
                <div><small class="text-muted">{{ __('admin.services.product_hint') }}</small></div>
            </td>
            <td class="fieldlabel">{{ __('admin.services.first_payment') }}</td>
            <td class="fieldarea">{{ money_fmt($service->first_payment_amount) }}</td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.services.server') }}</td>
            <td class="fieldarea">
                <strong>{{ $service->server->name ?? __('admin.services.none_assigned') }}</strong>
                <span class="text-muted">({{ __('admin.services.module') }}{{ ': ' }}{{ $service->product?->server_type ?: ($service->product?->ssl_module ?: __('admin.services.none_label')) }})</span>
            </td>
            <td class="fieldlabel">{{ __('admin.services.recurring_amount') }}</td>
            <td class="fieldarea">
                <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $service->amount) }}" class="form-control input-150">
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.services.domain') }}</td>
            <td class="fieldarea">
                <input type="text" name="domain" value="{{ old('domain', $service->domain) }}" class="form-control input-300">
            </td>
            <td class="fieldlabel">{{ __('admin.services.billing_cycle') }}</td>
            <td class="fieldarea">
                <select name="billing_cycle" class="form-control select-inline">
                    @foreach($cycles as $cycle)
                    <option value="{{ $cycle }}" @selected(old('billing_cycle', $service->billing_cycle) === $cycle)>{{ $cycle }}</option>
                    @endforeach
                </select>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ 'Dedicated IP' }}</td>
            <td class="fieldarea">{{ $service->primaryIp() ?: '-' }}</td>
            <td class="fieldlabel">{{ __('admin.services.payment') }}</td>
            <td class="fieldarea">{{ $service->payment_method ? payment_method_label((string) $service->payment_method) : '-' }}</td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.services.username') }}</td>
            <td class="fieldarea">
                <input type="text" name="username" value="{{ old('username', $service->username) }}" class="form-control input-200">
            </td>
            <td class="fieldlabel">{{ __('common.table.status') }}</td>
            <td class="fieldarea">
                <span class="whmcs-status badge-{{ strtolower($service->status) }}">{{ status_label($service->status) }}</span>
                @if($service->suspension_date)
                <div style="font-size:12px;color:#d9534f;margin-top:3px;">{{ __('admin.services.suspended') }}{{ ': ' }}{{ $service->suspension_date->format(date_fmt()) }}@if($service->suspension_reason) ({{ $service->suspension_reason }})@endif</div>
                @endif
                @if($service->termination_date)
                <div style="font-size:12px;color:#d9534f;margin-top:3px;">{{ __('admin.services.terminated') }}{{ ': ' }}{{ $service->termination_date->format(date_fmt()) }}</div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.services.no_suspend_until') }}</td>
            <td class="fieldarea" colspan="3">
                <input type="date" name="override_auto_suspend_date" value="{{ old('override_auto_suspend_date', $service->override_auto_suspend_date ? \Illuminate\Support\Carbon::parse($service->override_auto_suspend_date)->format('Y-m-d') : '') }}" class="form-control input-200" style="display:inline-block;width:auto;">
                <small class="text-muted">{{ __('admin.services.no_suspend_hint') }}</small>
            </td>
        </tr>
        <tr>
            <td class="fieldlabel">{{ __('admin.services.notes') }}</td>
            <td class="fieldarea" colspan="3">
                <textarea name="notes" rows="3" class="form-control">{{ old('notes', $service->notes) }}</textarea>
            </td>
        </tr>
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ __('admin.services.save') }}</button>
    </div>
</form>

<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-top:10px;">
    <tr>
        <td width="20%" class="fieldlabel">{{ __('admin.services.next_due') }}</td>
        <td width="30%" class="fieldarea">
            <form method="POST" action="{{ route('admin.services.next-due', $service) }}" style="display:inline-flex;gap:6px;align-items:center;">
                @csrf
                @method('PUT')
                <input type="date" name="next_due_date" value="{{ $service->next_due_date?->format('Y-m-d') ?? '' }}" class="form-control input-150" style="{{ $service->next_due_date?->isPast() ? 'border-color:#d9534f;' : '' }}" @disabled($service->status === 'terminated')>
                @if($service->status !== 'terminated')
                <button type="submit" class="btn btn-primary btn-xs">{{ __('admin.services.save') }}</button>
                @endif
            </form>
        </td>
        <td width="20%" class="fieldlabel">{{ __('common.form.password') }}</td>
        <td width="30%" class="fieldarea">
            @if($service->product?->server_type && ($moduleScreen['change_password'] ?? true))
            <form method="POST" action="{{ route('admin.services.module-action', [$service, 'changepassword']) }}" style="display:inline-flex;gap:6px;align-items:center;">
                @csrf
                <input type="password" name="password" placeholder="{{ __('admin.services.new_password') }}" required minlength="6" class="form-control input-150">
                <button type="submit" class="btn btn-default btn-xs">{{ __('admin.services.change_password') }}</button>
            </form>
            @else
            {{ '-' }}
            @endif
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ 'Module Commands' }}</td>
        <td class="fieldarea" colspan="3">
            @if(!$service->product?->server_type && !$service->product?->ssl_module)
            <span class="text-muted">{{ __('admin.services.no_module') }}</span>
            @elseif($service->product?->ssl_module)
            <span>{{ __('admin.products.ssl_module') }}{{ ': ' }}<code>{{ $service->product->ssl_module }}</code></span>
            @if($service->sslOrder)
                <a href="{{ route('admin.ssl.show', $service->sslOrder) }}" class="btn btn-default btn-sm" style="margin-left:8px;">{{ __('admin.ssl.order_details') }}</a>
            @else
                <span class="text-muted" style="margin-left:8px;">{{ __('admin.services.no_ssl_order') }}</span>
            @endif
            @else
            @if($pendingSetup)
            <div class="infobox" style="margin-bottom:8px;">
                <strong>{{ __('admin.services.setup_pending_title') }}</strong>
                <div style="font-family:monospace;font-size:12px;word-break:break-word;">{{ $pendingSetup->last_error ?: '-' }}</div>
                <div>
                    @if($pendingSetup->status === 'failed')
                        {{ __('admin.services.setup_gave_up', ['button' => __('admin.services.create_account')]) }}
                    @elseif($pendingSetup->next_attempt_at)
                        {{ __('admin.services.setup_next_try', ['time' => $pendingSetup->next_attempt_at->timezone(config('app.timezone'))->format(date_fmt().' H:i'), 'button' => __('admin.services.create_account')]) }}
                    @endif
                </div>
            </div>
            @endif
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                @unless(($moduleScreen['provisioned'] ?? null) ?? (strtolower((string) $service->status) === 'active' && (string) $service->username !== ''))
                <form method="POST" action="{{ route('admin.services.module-action', [$service, 'create']) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.services.confirm_create')))">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm">{{ __('admin.services.create_account') }}</button>
                </form>
                @endunless
                <form method="POST" action="{{ route('admin.services.module-action', [$service, 'suspend']) }}" onsubmit="return pnConfirm(event, @js(__('admin.services.confirm_suspend')))" style="display:inline-flex;gap:4px;align-items:center;">
                    @csrf
                    <input type="text" name="reason" maxlength="255" class="form-control input-sm" style="width:190px;" placeholder="{{ __('admin.services.suspend_reason_placeholder') }}" aria-label="{{ __('admin.services.suspend_reason_placeholder') }}">
                    <button type="submit" class="btn btn-default btn-sm">{{ __('admin.services.suspend') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.services.module-action', [$service, 'unsuspend']) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.services.confirm_unsuspend')))">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm">{{ __('admin.services.unsuspend') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.services.module-action', [$service, 'terminate']) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.services.confirm_terminate')))">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('admin.services.terminate') }}</button>
                </form>
                @if(!array_key_exists('changepackage', $moduleScreen['buttons'] ?? []))
                <form method="POST" action="{{ route('admin.services.module-action', [$service, 'changepackage']) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm">{{ 'Change Package' }}</button>
                </form>
                @endif
                @foreach(($moduleScreen['buttons'] ?? []) as $function => $label)
                <form method="POST" action="{{ route('admin.services.module-action', [$service, $function]) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-default btn-sm">{{ $label }}</button>
                </form>
                @endforeach
            </div>
            @endif
        </td>
    </tr>
    <tr>
        <td class="fieldlabel">{{ __('admin.services.manage_status') }}</td>
        <td class="fieldarea" colspan="3">
            <div style="margin-bottom:6px;font-size:12px;color:#777;">{{ __('admin.services.manage_status_hint', ['actions' => __('admin.services.module_actions')]) }}</div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                @foreach(['active', 'suspended', 'terminated', 'pending', 'cancelled'] as $btn)
                <form method="POST" action="{{ route('admin.services.status', $service) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js(__('admin.services.confirm_status', ['status' => status_label($btn)])))">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="{{ $btn }}">
                    @if($service->status === $btn)
                    <button type="submit" class="btn btn-default btn-xs" disabled>{{ __('admin.services.status_'.$btn) }}</button>
                    @else
                    <button type="submit" class="btn btn-{{ $btn === 'terminated' ? 'danger' : ($btn === 'suspended' ? 'warning' : ($btn === 'active' ? 'success' : 'info')) }} btn-xs">{{ __('admin.services.status_'.$btn) }}</button>
                    @endif
                </form>
                @endforeach
                <form method="POST" action="{{ route('admin.services.destroy', $service) }}" style="display:inline;margin-left:8px;" onsubmit="return pnConfirm(event, @js(__('admin.services.confirm_delete')))">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-xs">{{ __('admin.services.delete') }}</button>
                </form>
            </div>
        </td>
    </tr>
    @if($service->disk_limit > 0 || $service->bw_limit > 0 || $service->disk_usage > 0 || $service->bw_usage > 0)
    <tr>
        <td class="fieldlabel">{{ __('admin.services.resource_usage') }}</td>
        <td class="fieldarea" colspan="3">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                @php $diskPct = $service->disk_limit > 0 ? min(100, ($service->disk_usage / $service->disk_limit) * 100) : 0; @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                        <span>{{ __('admin.services.disk_usage') }}</span>
                        <strong>{{ mb_fmt($service->disk_usage) . ' / ' . ($service->disk_limit > 0 ? mb_fmt($service->disk_limit) : '∞') }}</strong>
                    </div>
                    <div style="background:#e9e9e9;border-radius:3px;height:10px;">
                        <div style="height:10px;border-radius:3px;background:{{ $diskPct > 85 ? '#d9534f' : '#337ab7' }};width:{{ $diskPct }}%;"></div>
                    </div>
                </div>
                @php $bwPct = $service->bw_limit > 0 ? min(100, ($service->bw_usage / $service->bw_limit) * 100) : 0; @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">
                        <span>{{ __('admin.services.bandwidth') }}</span>
                        <strong>{{ mb_fmt($service->bw_usage) . ' / ' . ($service->bw_limit > 0 ? mb_fmt($service->bw_limit) : '∞') }}</strong>
                    </div>
                    <div style="background:#e9e9e9;border-radius:3px;height:10px;">
                        <div style="height:10px;border-radius:3px;background:{{ $bwPct > 85 ? '#d9534f' : '#5cb85c' }};width:{{ $bwPct }}%;"></div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    @endif
</table>

@if(! empty($moduleScreen['panel']))
<div style="margin:15px 0;">{{ $moduleScreen['panel'] }}</div>
@endif

@if(strtolower((string) ($service->server?->type ?? $service->product?->server_type)) === 'proxmox')
@php $pveData = $service->module_data ?? []; @endphp
<div style="margin:15px 0;" id="pve-admin">
    <div style="display:flex;justify-content:space-between;align-items:center;margin:4px 0 10px;">
        <strong style="font-size:15px;">{{ __('proxmox.admin.vm_title') }}</strong>
        @if(empty($pveData['proxmox_vmid']))<span style="font-size:12px;color:#666;">{{ __('proxmox.admin.no_vm') }}</span>@endif
    </div>
    @if(!empty($pveData['proxmox_vmid']))
    @php
        $vpsUrls = [];
        foreach (['status', 'graphs', 'power', 'password', 'reinstall', 'snapshots', 'backups'] as $name) {
            $vpsUrls[$name] = route('admin.services.vps.'.$name, $service);
        }
        $vpsUrls['snapshotAction'] = route('admin.services.vps.snapshots.action', $service);
        $vpsUrls['backupAction'] = route('admin.services.vps.backups.action', $service);
        $vpsCanChange = in_array(strtolower((string) $service->status), ['active', 'suspended'], true);
    @endphp
    <x-vps-panel :service="$service" :admin="true" :features="$vpsFeatures" :choices="$reinstallChoices" :can-change="$vpsCanChange" :urls="$vpsUrls">
        <div class="vps-sec">
            @include('admin.services.partials.pve-claim')
        </div>
    </x-vps-panel>
    @else
    <div class="card"><div class="card-body">@include('admin.services.partials.pve-claim')</div></div>
    @endif
</div>
@endif

@if(($productFields ?? collect())->isNotEmpty())
<h2 style="margin-top:18px;">{{ __('admin.products.fields_title') }}</h2>
<form method="POST" action="{{ route('admin.services.fields', $service) }}">
    @csrf
    @method('PUT')
    <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
        @foreach($productFields as $field)
        @php($value = old("custom_fields.{$field->id}", $field->valueFor($service->id)))
        <tr>
            <td width="20%" class="fieldlabel">
                <label for="pf-{{ $field->id }}">{{ $field->field_name }}@if($field->admin_only) <span class="text-muted">({{ __('admin.products.fields_admin_only') }})</span>@endif</label>
            </td>
            <td class="fieldarea">
                @if($field->field_type === 'select')
                <select id="pf-{{ $field->id }}" name="custom_fields[{{ $field->id }}]" class="form-control select-inline">
                    <option value="">{{ '—' }}</option>
                    @foreach($field->options() as $opt)<option value="{{ $opt }}" @selected($value === $opt)>{{ $opt }}</option>@endforeach
                </select>
                @elseif($field->field_type === 'textarea')
                <textarea id="pf-{{ $field->id }}" name="custom_fields[{{ $field->id }}]" rows="2" class="form-control">{{ $value }}</textarea>
                @elseif($field->field_type === 'checkbox')
                <label class="checkbox-inline"><input type="checkbox" id="pf-{{ $field->id }}" name="custom_fields[{{ $field->id }}]" value="1" @checked($value)> {{ __('common.yes') }}</label>
                @else
                <input type="{{ in_array($field->field_type, ['number', 'date'], true) ? $field->field_type : 'text' }}" id="pf-{{ $field->id }}" name="custom_fields[{{ $field->id }}]" value="{{ $value }}" class="form-control input-300">
                @endif
                @error("custom_fields.{$field->id}")<div style="color:#a94442;font-size:12px;">{{ $message }}</div>@enderror
            </td>
        </tr>
        @endforeach
    </table>
    <div class="btn-container">
        <button type="submit" class="btn btn-primary btn-sm">{{ __('common.actions.save') }}</button>
    </div>
</form>
@endif

<h2 style="margin-top:18px;">{{ __('admin.addons.title') }}</h2>
@if($service->addons->isNotEmpty())
<div class="tablebg" style="margin-bottom:12px;">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ __('common.table.name') }}</th>
                <th>{{ __('common.table.amount') }}</th>
                <th>{{ __('common.table.billing_cycle') }}</th>
                <th>{{ __('client.services.next_due_date') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th style="text-align:right;">{{ __('common.table.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($service->addons as $addon)
            <tr>
                <td>{{ $addon->label() }}@if($addon->addon?->description)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($addon->addon->description, 80) }}</small>@endif</td>
                <td>{{ number_format((float) $addon->amount, 2) }}</td>
                <td>{{ cycle_label($addon->billing_cycle) }}</td>
                <td>{{ $addon->next_due_date?->format(date_fmt()) ?? '-' }}</td>
                <td><span class="whmcs-status badge badge-{{ strtolower($addon->status) }}">{{ status_label($addon->status) }}</span></td>
                <td style="text-align:right;white-space:nowrap;">
                    @unless($addon->isEnded())
                    <a class="btn btn-default btn-xs" href="{{ route('admin.services.addons.edit', [$service, $addon]) }}">{{ __('admin.addons.service_edit') }}</a>
                    @endunless

                    @if($addon->isDraft())
                    <form method="POST" action="{{ route('admin.services.addons.accept', [$service, $addon]) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-success btn-xs">{{ __('admin.addons.service_accept') }}</button>
                    </form>
                    @endif

                    @if($addon->isActive())
                    <form method="POST" action="{{ route('admin.services.addons.suspend', [$service, $addon]) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-xs">{{ __('admin.addons.service_suspend') }}</button>
                    </form>
                    @elseif($addon->isSuspended())
                    <form method="POST" action="{{ route('admin.services.addons.resume', [$service, $addon]) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-success btn-xs">{{ __('admin.addons.service_resume') }}</button>
                    </form>
                    @endif

                    @if(in_array(strtolower($addon->status), ['active', 'pending'], true))
                    <form method="POST" action="{{ route('admin.services.addons.cancel', [$service, $addon]) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('client.services.addon_cancel') }}</button>
                    </form>
                    @endif

                    @if($addon->hasBlockingInvoiceLines())
                    <button type="button" class="btn btn-default btn-xs" disabled title="{{ __('admin.addons.service_has_invoices') }}">{{ __('admin.addons.service_delete') }}</button>
                    @else
                    <form method="POST" action="{{ route('admin.services.addons.destroy', [$service, $addon]) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js($addon->hasInvoiceLines() ? __('admin.addons.service_delete_with_invoice_confirm') : __('admin.addons.service_delete_confirm')))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-xs">{{ __('admin.addons.service_delete') }}</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div style="margin-bottom:15px;">
    @if($canAddAddons)
        @if(($availableAddons ?? collect())->isNotEmpty())
        <form method="POST" action="{{ route('admin.services.addons.store', $service) }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @csrf
            <select name="addon_id" class="form-control" style="max-width:480px;">
                @foreach($availableAddons as $available)
                    @php($quote = $addonQuotes[$available->id] ?? null)
                    <option value="{{ $available->id }}" @disabled(! ($quote['available'] ?? false))>
                        {{ $available->name }}@if($available->description) &mdash; {{ \Illuminate\Support\Str::limit($available->description, 60) }}@endif
                        @if($quote && $quote['available'])
                            @if($quote['prorated'])
                                &mdash; {{ __('admin.addons.first_payment_short', [
                                    'first' => money_fmt($quote['first_amount']),
                                    'days' => $quote['remaining_days'],
                                    'cycle_days' => $quote['cycle_days'],
                                    'full' => money_fmt($quote['full_amount']),
                                    'cycle' => cycle_label($quote['cycle']),
                                ]) }}
                            @else
                                &mdash; {{ money_fmt($quote['full_amount']) . '/' . cycle_label($quote['cycle']) }}
                            @endif
                        @else
                            &mdash; {{ __('admin.addons.service_period_unpaid') }}
                        @endif
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">{{ __('client.services.addon_order') }}</button>
        </form>
        <p class="text-muted" style="margin:8px 0 0;">{{ __('admin.addons.service_add_hint') }}</p>
        <p class="text-muted" style="margin:4px 0 0;">{{ __('admin.addons.service_add_proration_hint', ['cycle' => cycle_label($service->billing_cycle ?: 'Monthly')]) }}</p>
        @else
            <p class="text-muted">{{ __('admin.addons.no_addons') }}</p>
        @endif
    @else
        <p class="text-muted">{{ __('admin.addons.service_only_active') }}</p>
    @endif
</div>

</div>
</div>
@endsection

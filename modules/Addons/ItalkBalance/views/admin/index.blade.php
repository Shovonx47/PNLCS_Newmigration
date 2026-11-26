@extends('admin.layouts.app')
@section('title', 'iTalk / ICC Balance')

@section('content')

<div class="page-header">
    <h1>iTalk / ICC Balance</h1>
</div>

<p style="color:#666;font-size:13px;margin-bottom:15px;">
    Map a customer service to the IP number (PIN) it holds and the endpoint that balance belongs to.
    The customer then sees a live BDT balance card on the service page with a rate-limited refresh.
</p>

{{-- Product selection --}}
<div class="card" style="margin-bottom:15px;">
    <div class="card-header">
        <strong>IPTSP products</strong>
        <span style="font-size:12px;color:#666;">Only services of the selected products show a balance.</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.italk-balance.settings') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;">
                @foreach(['italk' => 'iTalk products', 'icc' => 'ICC Talk products'] as $program => $label)
                @php($selected = $program === 'italk' ? $selectedItalk : $selectedIcc)
                <div>
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#666;margin-bottom:8px;">{{ $label }}</div>
                    <div style="max-height:190px;overflow:auto;border:1px solid #e5e7eb;border-radius:6px;padding:8px 10px;">
                        @forelse($products as $product)
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;padding:3px 0;cursor:pointer;">
                            <input type="checkbox" name="products_{{ $program }}[]" value="{{ $product->id }}" {{ in_array($product->id, $selected, true) ? 'checked' : '' }}>
                            {{ $product->name }}
                            <span style="color:#9ca3af;font-size:11px;">#{{ $product->id }}</span>
                        </label>
                        @empty
                        <div style="color:#999;font-size:13px;">No products yet.</div>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>

            <div class="form-group" style="margin-top:14px;max-width:240px;">
                <label style="font-size:12px;font-weight:600;">Refresh cooldown (seconds)</label>
                <input type="number" name="refresh_cooldown" min="0" max="3600" value="{{ $cooldown }}" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Save settings</button>
        </form>
    </div>
</div>

{{-- Endpoints --}}
<div class="card" style="margin-bottom:15px;">
    <div class="card-header"><strong>Balance endpoints</strong></div>
    <div class="card-body">
        @if($endpoints->isEmpty())
        <div style="color:#999;font-size:13px;margin-bottom:12px;">No endpoints yet. Add one below.</div>
        @else
        <table class="data-table" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Program</th>
                    <th>Base URL</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($endpoints as $endpoint)
                <tr>
                    <td>{{ $endpoint->label }}</td>
                    <td>{{ $programs[$endpoint->program] ?? $endpoint->program }}</td>
                    <td style="font-family:ui-monospace,Menlo,monospace;font-size:11.5px;word-break:break-all;">{{ $endpoint->base_url }}</td>
                    <td><span class="badge {{ $endpoint->is_active ? 'badge-active' : 'badge-cancelled' }}">{{ $endpoint->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td style="text-align:right;white-space:nowrap;">
                        <form method="POST" action="{{ route('admin.extensions.italk-balance.endpoints.update', $endpoint) }}" style="display:inline-flex;gap:6px;align-items:center;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="label" value="{{ $endpoint->label }}">
                            <input type="hidden" name="base_url" value="{{ $endpoint->base_url }}">
                            <input type="hidden" name="program" value="{{ $endpoint->program }}">
                            <input type="hidden" name="is_active" value="{{ $endpoint->is_active ? '0' : '1' }}">
                            <button type="submit" class="btn btn-default btn-xs">{{ $endpoint->is_active ? 'Disable' : 'Enable' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.extensions.italk-balance.endpoints.destroy', $endpoint) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js('Remove this endpoint? Mapped services keep their IP number but stop showing a balance.'))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <form method="POST" action="{{ route('admin.extensions.italk-balance.endpoints.store') }}" style="display:grid;grid-template-columns:2fr 2fr 1fr auto;gap:10px;align-items:end;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Label</label>
                <input type="text" name="label" class="form-control" placeholder="e.g. iTalk API" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Base URL (https)</label>
                <input type="url" name="base_url" class="form-control" placeholder="https://…/getclientbalance.do?pin=" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Program</label>
                <select name="program" class="form-control">
                    @foreach($programs as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Add endpoint</button>
        </form>
    </div>
</div>

{{-- Mappings --}}
<div class="card">
    <div class="card-header"><strong>Service IP number mappings</strong></div>
    <div class="card-body">
        @if($mappings->isEmpty())
        <div style="color:#999;font-size:13px;margin-bottom:12px;">No service has an IP number yet.</div>
        @else
        <table class="data-table" style="margin-bottom:16px;">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Customer</th>
                    <th>IP number</th>
                    <th>Endpoint</th>
                    <th>Balance</th>
                    <th>Last check</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mappings as $mapping)
                <tr>
                    <td>
                        #{{ $mapping->service_id }}
                        <span style="color:#666;font-size:12px;">{{ $mapping->service?->domain ?: $mapping->service?->product?->name }}</span>
                    </td>
                    <td>{{ $mapping->client ? trim($mapping->client->first_name.' '.$mapping->client->last_name) : '—' }}</td>
                    <td style="font-family:ui-monospace,Menlo,monospace;">{{ $mapping->ip_number }}</td>
                    <td>{{ $mapping->endpoint?->label ?? '—' }}</td>
                    <td>
                        @if($mapping->balanceAmount() !== null)
                            ৳{{ number_format($mapping->balanceAmount(), 2) }}
                        @else
                            <span style="color:#9ca3af;">—</span>
                        @endif
                        @if($mapping->last_error)
                        <div style="font-size:11px;color:#b91c1c;max-width:240px;">{{ $mapping->last_error }}</div>
                        @endif
                    </td>
                    <td style="font-size:12px;color:#666;">{{ $mapping->last_checked_at?->format('d M Y, H:i') ?? 'never' }}</td>
                    <td style="text-align:right;white-space:nowrap;">
                        <form method="POST" action="{{ route('admin.extensions.italk-balance.mappings.refresh', $mapping) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-default btn-xs">Refresh</button>
                        </form>
                        <form method="POST" action="{{ route('admin.extensions.italk-balance.mappings.destroy', $mapping) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js('Remove this IP number mapping?'))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <form method="POST" action="{{ route('admin.extensions.italk-balance.mappings.store') }}" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end;">
            @csrf
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Service</label>
                <select name="service_id" class="form-control" required>
                    <option value="">Select a service…</option>
                    @foreach($services as $service)
                    <option value="{{ $service->id }}">
                        #{{ $service->id }} — {{ $service->domain ?: ($service->product?->name ?? 'service') }}
                        ({{ $service->client ? trim($service->client->first_name.' '.$service->client->last_name) : 'client #'.$service->client_id }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">IP number / PIN</label>
                <input type="text" name="ip_number" class="form-control" placeholder="096XXXXXXXX" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label style="font-size:12px;font-weight:600;">Endpoint</label>
                <select name="endpoint_id" class="form-control" required>
                    @foreach($endpoints as $endpoint)
                    <option value="{{ $endpoint->id }}">{{ $endpoint->label }} ({{ $programs[$endpoint->program] ?? $endpoint->program }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Save mapping</button>
        </form>
    </div>
</div>

@endsection

@extends('admin.layouts.app')
@section('title', 'Recharge Requests')

@section('content')

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Recharge Requests</h1>
</div>

<p style="color:#666;font-size:13px;margin-bottom:12px;">
    IP number balance top-ups requested from the client area. Each request carries the invoice it raised;
    a paid invoice with a matching client PIN is topped up automatically by the IPTSP Recharge addon.
</p>

<div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;">
    @foreach($statuses as $state)
    <a href="{{ route('admin.extensions.recharge-requests.index', ['status' => $state]) }}"
       class="btn {{ $status === $state ? 'btn-primary' : 'btn-default' }} btn-sm">
        {{ $state }} ({{ (int) ($counts[$state] ?? 0) }})
    </a>
    @endforeach
</div>

<div class="card">
    <div class="card-body">
        @if($requests->isEmpty())
        <div style="text-align:center;padding:36px;color:#999;">No {{ strtolower($status) }} requests.</div>
        @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>IP number</th>
                    <th>Amount</th>
                    <th>Invoice</th>
                    <th>Requested</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                <tr>
                    <td>{{ $request->id }}</td>
                    <td>
                        @if($request->client)
                        <a href="{{ route('admin.clients.show', $request->client) }}">{{ trim($request->client->first_name.' '.$request->client->last_name) }}</a>
                        @else
                        #{{ $request->client_id }}
                        @endif
                    </td>
                    <td style="font-family:ui-monospace,Menlo,monospace;">{{ $request->mobile_number }}</td>
                    <td>৳{{ number_format((float) $request->amount, 2) }}</td>
                    <td>
                        @if($request->invoice)
                        <a href="{{ route('admin.invoices.show', $request->invoice) }}">{{ $request->invoice->invoice_num }}</a>
                        <span class="badge {{ strtolower($request->invoice->status) === 'paid' ? 'badge-active' : 'badge-pending' }}">{{ ucfirst($request->invoice->status) }}</span>
                        @else
                        —
                        @endif
                    </td>
                    <td style="font-size:12px;color:#666;">{{ $request->requested_at?->format('d M Y, H:i') }}</td>
                    <td style="text-align:right;white-space:nowrap;">
                        {{-- Only a pending request has a decision left to make; a
                             finished one would only be re-opened or contradicted. --}}
                        @if($request->status === 'Pending')
                        <form method="POST" action="{{ route('admin.extensions.recharge-requests.update', $request) }}" style="display:inline;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="Completed">
                            <button type="submit" class="btn btn-default btn-xs">Mark completed</button>
                        </form>
                        <form method="POST" action="{{ route('admin.extensions.recharge-requests.update', $request) }}" style="display:inline;" onsubmit="return pnConfirm(event, @js('Cancel this recharge request? The invoice is not cancelled automatically.'))">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="Cancelled">
                            <button type="submit" class="btn btn-danger btn-xs">Cancel</button>
                        </form>
                        @else
                        <span style="font-size:12px;color:#9ca3af;">No action needed</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:14px;">{{ $requests->links() }}</div>
        @endif
    </div>
</div>

@endsection

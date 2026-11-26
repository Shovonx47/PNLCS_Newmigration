@extends('admin.layouts.app')
@section('title', 'Service Ownership Transfers')

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;">
    <h1>Service Ownership Transfers</h1>
</div>

<div style="display:flex;gap:6px;margin-bottom:14px;">
    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $lbl)
        <a href="{{ route('admin.extensions.service-transfers.index', ['status' => $k]) }}"
           class="btn {{ $status === $k ? 'btn-primary' : 'btn-default' }} btn-sm">{{ $lbl }}</a>
    @endforeach
</div>

<div class="card">
    <div class="card-body">
        @if($requests->isEmpty())
            <div style="text-align:center;padding:30px;color:#888;">No {{ $status }} service transfer requests.</div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Service</th>
                        <th>From Client</th>
                        <th>Recipient</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                    <tr>
                        <td>{{ $req->id }}</td>
                        <td>
                            @if($req->service)
                                <a href="{{ route('admin.services.show', $req->service) }}">#{{ $req->service->id }} {{ $req->service->product?->name }}</a>
                                @if($req->service->domain)<div style="font-size:11.5px;color:#666;">{{ $req->service->domain }}</div>@endif
                            @else
                                Service #{{ $req->service_id }}
                            @endif
                        </td>
                        <td>
                            @if($req->fromClient)
                                <a href="{{ route('admin.clients.show', $req->fromClient) }}">{{ trim($req->fromClient->first_name.' '.$req->fromClient->last_name) }}</a>
                            @else
                                Client #{{ $req->from_client_id }}
                            @endif
                        </td>
                        <td>
                            <div><strong>{{ $req->to_name }}</strong></div>
                            <div style="font-size:12px;color:#666;">{{ $req->to_email }}</div>
                        </td>
                        <td>{{ $req->reason }}</td>
                        <td><span class="badge {{ $req->status === 'approved' ? 'badge-active' : ($req->status === 'pending' ? 'badge-pending' : 'badge-cancelled') }}">{{ ucfirst($req->status) }}</span></td>
                        <td style="text-align:right;white-space:nowrap;">
                            @if($req->status === 'pending')
                                <form method="POST" action="{{ route('admin.extensions.service-transfers.approve', $req) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-xs">Approve Transfer</button>
                                </form>
                                <form method="POST" action="{{ route('admin.extensions.service-transfers.reject', $req) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-xs">Reject</button>
                                </form>
                            @else
                                <span style="font-size:12px;color:#888;">{{ $req->processed_at?->format('d M Y') }}</span>
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

@extends('admin.layouts.app')
@section('title', 'Cancellation Requests')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
    <h1 style="margin:0;border:0;padding:0;">{{ 'Cancellation Requests' }}</h1>
    <div style="display:flex;gap:6px;">
        <a href="{{ route('admin.cancellation-requests.index') }}" class="btn {{ !$showCompleted ? 'btn-primary' : 'btn-default' }} btn-sm">{{ 'Show Open Requests' }}</a>
        <a href="{{ route('admin.cancellation-requests.index', ['completed' => 1]) }}" class="btn {{ $showCompleted ? 'btn-primary' : 'btn-default' }} btn-sm">{{ 'Show Completed Requests' }}</a>
    </div>
</div>

<div style="font-size:13px;color:#333;margin-bottom:6px;">
    {{ $requests->total() }} {{ 'Records Found' }}
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th style="width:140px;">{{ 'Date' }}</th>
                <th class="text-left">{{ 'Product/Service' }}</th>
                <th class="text-left">{{ 'Reason' }}</th>
                <th>{{ 'Type' }}</th>
                <th>{{ 'Cancellation By' }}</th>
                <th width="120">{{ 'Actions' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($requests as $req)
                <tr>
                    <td style="text-align:center;">{{ $req->created_at?->format(datetime_fmt()) ?? '—' }}</td>
                    <td>
                        @if($req->service)
                            <a href="{{ route('admin.services.show', $req->service) }}" style="font-weight:600;">
                                {{ $req->service->product?->name ?? 'Service #' . $req->service->id }}
                            </a>
                            @if($req->service->domain)
                                <div><small style="color:#666;">{{ $req->service->domain }}</small></div>
                            @endif
                            @if($req->service->client)
                                <div><a href="{{ route('admin.clients.show', $req->service->client) }}" style="font-size:11.5px;">{{ $req->service->client->full_name }}</a></div>
                            @endif
                        @else
                            {{ 'Service #' . $req->service_id }}
                        @endif
                    </td>
                    <td style="white-space:pre-wrap;">{{ $req->reason ?: 'No reason given' }}</td>
                    <td style="text-align:center;">
                        <span class="whmcs-status {{ $req->isImmediate() ? 'terminated' : 'pending' }}">
                            {{ $req->isImmediate() ? 'Immediate' : 'End of Billing Period' }}
                        </span>
                    </td>
                    <td style="text-align:center;">
                        @if($req->processed_at)
                            {{ $req->processed_at->format(date_fmt()) }}
                        @elseif($req->isImmediate())
                            {{ 'Immediate' }}
                        @else
                            {{ $req->service?->next_due_date?->format(date_fmt()) ?? 'End of Cycle' }}
                        @endif
                    </td>
                    <td style="text-align:center;white-space:nowrap;">
                        @if($req->service)
                            <a href="{{ route('admin.services.show', $req->service) }}" class="btn btn-default btn-xs">{{ 'Service' }}</a>
                        @endif
                        <form method="POST" action="{{ route('admin.cancellation-requests.destroy', $req) }}" style="display:inline;" onsubmit="return pnConfirm(event, 'Delete this cancellation request?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs">&times;</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:#777;padding:24px;">{{ 'No cancellation requests found.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">{{ $requests->withQueryString()->links() }}</div>

@endsection

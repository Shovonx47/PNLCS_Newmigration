@extends('admin.layouts.app')
@section('title', 'Module Queue')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
    <h1 style="margin:0;border:0;padding:0;">{{ 'Module Queue' }}</h1>
    <div style="display:flex;gap:6px;">
        <a href="{{ route('admin.utilities.module-queue', ['status' => 'open']) }}" class="btn {{ ($statusFilter ?? 'open') === 'open' ? 'btn-primary' : 'btn-default' }} btn-xs">{{ 'Incomplete / Queued' }}</a>
        <a href="{{ route('admin.utilities.module-queue', ['status' => 'completed']) }}" class="btn {{ ($statusFilter ?? 'open') === 'completed' ? 'btn-primary' : 'btn-default' }} btn-xs">{{ 'Completed' }}</a>
        <a href="{{ route('admin.logs.module') }}" class="btn btn-default btn-xs">{{ 'Module Debug Log' }}</a>
    </div>
</div>

<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th>{{ 'ID' }}</th>
                <th class="text-left">{{ 'Client / Service' }}</th>
                <th>{{ 'Action' }}</th>
                <th>{{ 'Attempts' }}</th>
                <th class="text-left">{{ 'Last Error' }}</th>
                <th>{{ 'Status' }}</th>
                <th width="150">{{ 'Actions' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $q)
                <tr>
                    <td style="text-align:center;">#{{ $q->id }}</td>
                    <td>
                        @if($q->service)
                            <a href="{{ route('admin.services.show', $q->service) }}" style="font-weight:600;">
                                {{ $q->service->product?->name ?? 'Service #' . $q->service->id }}
                            </a>
                            @if($q->service->domain)
                                <span style="color:#666;">({{ $q->service->domain }})</span>
                            @endif
                            @if($q->service->client)
                                <div style="font-size:11.5px;color:#666;">{{ $q->service->client->full_name }}</div>
                            @endif
                        @else
                            {{ 'Service #' . ($q->service_id ?? '—') }}
                        @endif
                    </td>
                    <td style="text-align:center;text-transform:capitalize;font-weight:600;">{{ $q->action }}</td>
                    <td style="text-align:center;">{{ $q->attempts }} / {{ $q->max_attempts }}</td>
                    <td style="font-size:12px;color:#a94442;">{{ \Illuminate\Support\Str::limit((string) $q->last_error, 90) ?: '—' }}</td>
                    <td style="text-align:center;">
                        <span class="whmcs-status {{ $q->status === 'completed' ? 'active' : ($q->status === 'failed' ? 'terminated' : 'pending') }}">{{ ucfirst($q->status) }}</span>
                    </td>
                    <td style="text-align:center;white-space:nowrap;">
                        @if($q->status !== 'completed')
                            <form method="POST" action="{{ route('admin.utilities.module-queue.retry', $q) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-xs">{{ 'Retry' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.utilities.module-queue.dismiss', $q) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-default btn-xs">{{ 'Resolve' }}</button>
                            </form>
                        @else
                            <span style="color:#888;font-size:11.5px;">{{ $q->completed_at?->format(datetime_fmt()) ?? 'Resolved' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:#777;padding:24px;">{{ 'No queued or failed module actions waiting for retry.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:10px;">{{ $items->withQueryString()->links() }}</div>

@endsection

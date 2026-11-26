@extends('admin.layouts.app')
@section('title', 'KYC Review #'.$verification->id)

@section('content')
<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <h1>Verification Review #{{ $verification->id }}</h1>
    <a href="{{ route('admin.extensions.kyc.index') }}" class="btn btn-default btn-sm">&larr; Back to List</a>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">
    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><strong>Submission Details</strong></div>
            <div class="card-body">
                <table class="data-table">
                    <tbody>
                        <tr><th>Client</th><td>@if($verification->client)<a href="{{ route('admin.clients.show', $verification->client) }}">{{ trim($verification->client->first_name.' '.$verification->client->last_name) }}</a> ({{ $verification->client->email }})@else #{{ $verification->client_id }} @endif</td></tr>
                        <tr><th>Status</th><td><span class="badge {{ $verification->status === 'verified' ? 'badge-active' : ($verification->status === 'pending' ? 'badge-pending' : 'badge-cancelled') }}">{{ ucfirst($verification->status) }}</span></td></tr>
                        <tr><th>Method</th><td>{{ strtoupper($verification->verification_method) }}</td></tr>
                        <tr><th>Session ID</th><td><code>{{ $verification->session_id ?: '—' }}</code></td></tr>
                        <tr><th>Document</th><td>{{ $verification->document_type ?: '—' }} {{ $verification->document_number ? '· '.$verification->document_number : '' }}</td></tr>
                        <tr><th>Business / Trade License</th><td>{{ $verification->business_type ?: 'individual' }} {{ $verification->trade_license_number ? '· License #'.$verification->trade_license_number : '' }}</td></tr>
                        @if($verification->decline_reason)
                        <tr><th>Decline Reason</th><td style="color:#b91c1c;">{{ $verification->decline_reason }}</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><strong>Private Documents ({{ $verification->documents->count() }})</strong></div>
            <div class="card-body">
                @if($verification->documents->isEmpty())
                    <div style="color:#888;font-size:13px;">No manual documents uploaded for this record.</div>
                @else
                    <table class="data-table">
                        <thead><tr><th>Slot</th><th>Original Filename</th><th>MIME</th><th>Size</th><th style="text-align:right;">Download</th></tr></thead>
                        <tbody>
                            @foreach($verification->documents as $doc)
                            <tr>
                                <td>{{ ucwords(str_replace('_', ' ', $doc->file_type)) }}</td>
                                <td>{{ $doc->original_name }}</td>
                                <td><code>{{ $doc->mime_type }}</code></td>
                                <td>{{ number_format($doc->file_size / 1024, 1) }} KB</td>
                                <td style="text-align:right;"><a href="{{ route('admin.extensions.kyc.documents.download', $doc) }}" class="btn btn-default btn-xs">Download Attachment</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Activity Log</strong></div>
            <div class="card-body">
                @if($logs->isEmpty())
                    <div style="color:#888;font-size:13px;">No activity logged yet.</div>
                @else
                    <table class="data-table">
                        <thead><tr><th>When</th><th>Action</th><th>Details</th><th>Actor / IP</th></tr></thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td style="font-size:12px;color:#666;">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                                <td><code>{{ $log->action }}</code></td>
                                <td>{{ $log->description }}</td>
                                <td style="font-size:12px;color:#666;">{{ $log->admin_name ?: ($log->ip_address ?: 'system') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><strong>Review Decision</strong></div>
            <div class="card-body">
                @if($verification->status !== 'verified')
                    <form method="POST" action="{{ route('admin.extensions.kyc.approve', $verification) }}" style="margin-bottom:14px;">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="width:100%;">Approve &amp; Mark Verified</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.extensions.kyc.decline', $verification) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Decline Reason</label>
                        <textarea name="decline_reason" rows="3" required class="form-control" placeholder="Explain clearly what the customer needs to re-submit">{{ $verification->decline_reason }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm" style="width:100%;">Decline Verification</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Staff Notes</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.extensions.kyc.notes', $verification) }}" style="margin-bottom:12px;">
                    @csrf
                    <div class="form-group">
                        <textarea name="note" rows="2" required class="form-control" placeholder="Add internal review note..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-default btn-sm">Add Note</button>
                </form>

                @foreach($verification->notes as $note)
                    <div style="border-top:1px solid #eee;padding-top:8px;margin-top:8px;font-size:12.5px;">
                        <div style="color:#666;font-size:11.5px;"><strong>{{ $note->admin_name ?: 'Admin' }}</strong> &middot; {{ $note->created_at?->format('d M Y, H:i') }}</div>
                        <div style="margin-top:3px;">{{ $note->note }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

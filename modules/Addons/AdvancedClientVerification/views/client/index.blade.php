@extends('client.layouts.app')
@section('title', 'Identity Verification')

@section('content')
@php
    $status = $verification?->status ?? 'not_started';
    $maskedDoc = $verification?->document_number
        ? (strlen($verification->document_number) > 4
            ? str_repeat('*', strlen($verification->document_number) - 4).substr($verification->document_number, -4)
            : '****')
        : null;
@endphp

<div class="pn-page-header">
    <div>
        <h1 class="pn-page-title">Identity Verification</h1>
        <p class="pn-page-subtitle">Confirm your account identity to keep services active and unlock restricted orders.</p>
    </div>
    <a href="{{ route('client.home') }}" class="btn btn-outline">&larr; Dashboard</a>
</div>

<div class="pn-card" style="padding:20px 24px;margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <div style="font-size:12px;color:var(--muted);text-transform:uppercase;font-weight:700;letter-spacing:.4px;">Current Status</div>
            <div style="font-size:17px;font-weight:700;margin-top:4px;">
                @if($status === 'verified')
                    Verified
                @elseif($status === 'pending')
                    Pending Review
                @elseif($status === 'failed')
                    Action Required (Declined)
                @else
                    Not Verified
                @endif
            </div>
            @if($verification?->verified_at)
                <div style="font-size:12.5px;color:var(--muted);margin-top:3px;">Verified on {{ $verification->verified_at->format('d M Y, H:i') }}</div>
            @elseif($maskedDoc)
                <div style="font-size:12.5px;color:var(--muted);margin-top:3px;">Document on file: {{ $maskedDoc }}</div>
            @endif
        </div>
        <span class="badge {{ $status === 'verified' ? 'badge-active' : ($status === 'pending' ? 'badge-pending' : 'badge-cancelled') }}">
            {{ ucfirst(str_replace('_', ' ', $status)) }}
        </span>
    </div>

    @if($status === 'failed' && $verification?->decline_reason)
        <div class="pn-alert pn-alert-error" style="margin-top:14px;">
            <strong>Reason:</strong> {{ $verification->decline_reason }}
        </div>
    @endif
</div>

@if($status !== 'verified')
    @if($allowsApi)
        <div class="pn-card" style="padding:20px 24px;margin-bottom:20px;">
            <div class="pn-section-title" style="margin:0 0 6px;">Instant Automated Verification</div>
            <p style="font-size:13px;color:var(--muted);margin:0 0 14px;">
                Complete quick camera and document verification in a few minutes.
            </p>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <form method="POST" action="{{ route('client.kyc.session') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Start Instant Verification</button>
                </form>
                @if($verification?->session_id && $status === 'pending')
                    <form method="POST" action="{{ route('client.kyc.refresh') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline">Check Verification Status</button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if($allowsManual)
        <div class="pn-card" style="padding:20px 24px;margin-bottom:20px;" x-data="{ accountKind: '{{ old('business_type', $verification?->business_type ?: 'individual') }}', docType: '{{ old('document_type', $verification?->document_type ?: 'national_id') }}', businessList: @js($businessTypes) }">
            <div class="pn-section-title" style="margin:0 0 6px;">Manual Document Submission</div>
            <p style="font-size:13px;color:var(--muted);margin:0 0 16px;">
                Upload clear JPG, PNG, or PDF copies (maximum {{ (int) ($settings['max_file_size'] ?? 5) }} MB per file). Files are stored privately and reviewed only by authorized verification staff.
            </p>

            <form method="POST" action="{{ route('client.kyc.manual') }}" enctype="multipart/form-data">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
                    <div class="form-group">
                        <label class="form-label">Account Category</label>
                        <select name="business_type" x-model="accountKind" class="form-control">
                            <option value="individual">Individual / Personal</option>
                            <option value="sole_proprietorship">Sole Proprietorship</option>
                            <option value="partnership">Partnership</option>
                            <option value="limited_company">Limited Company</option>
                            <option value="pvt_ltd">Private Limited (Pvt. Ltd.)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Document Type</label>
                        <select name="document_type" x-model="docType" class="form-control" required>
                            <option value="national_id">National ID (NID)</option>
                            <option value="passport">Passport</option>
                            <option value="driving_license">Driving License</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ID / Passport Number</label>
                        <input type="text" name="document_number" value="{{ old('document_number') }}" required class="form-control" placeholder="Enter document number">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-top:12px;">
                    <div class="form-group">
                        <label class="form-label">ID Front Side *</label>
                        <input type="file" name="id_front" accept=".jpg,.jpeg,.png,.pdf" required class="form-control">
                    </div>
                    @if(($settings['require_document_back'] ?? '1') !== '0')
                    <div class="form-group" x-show="docType !== 'passport'">
                        <label class="form-label">ID Back Side *</label>
                        <input type="file" name="id_back" accept=".jpg,.jpeg,.png,.pdf" x-bind:required="docType !== 'passport'" class="form-control">
                    </div>
                    @endif
                    @if(($settings['require_selfie'] ?? '1') !== '0')
                    <div class="form-group">
                        <label class="form-label">Selfie Holding ID *</label>
                        <input type="file" name="selfie" accept=".jpg,.jpeg,.png,.pdf" required class="form-control">
                    </div>
                    @endif
                </div>

                @if(($settings['require_trade_license'] ?? '1') !== '0')
                <div x-show="businessList.includes(accountKind)" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);">
                    <div class="form-group">
                        <label class="form-label">Trade License Number *</label>
                        <input type="text" name="trade_license_number" value="{{ old('trade_license_number', $verification?->trade_license_number) }}" x-bind:required="businessList.includes(accountKind)" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trade License Copy (JPG/PNG/PDF) *</label>
                        <input type="file" name="trade_license" accept=".jpg,.jpeg,.png,.pdf" x-bind:required="businessList.includes(accountKind)" class="form-control">
                    </div>
                </div>
                @endif

                <div style="margin-top:16px;">
                    <button type="submit" class="btn btn-primary">Submit Documents for Review</button>
                </div>
            </form>
        </div>
    @endif
@endif

@if($verification && $verification->documents->isNotEmpty())
<div class="pn-card" style="padding:20px 24px;">
    <div class="pn-section-title" style="margin:0 0 12px;">Submitted Documents</div>
    <table class="pn-table">
        <thead>
            <tr>
                <th>Type</th>
                <th>File</th>
                <th>Status</th>
                <th style="text-align:right;">Download</th>
            </tr>
        </thead>
        <tbody>
            @foreach($verification->documents as $doc)
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $doc->file_type)) }}</td>
                <td>{{ $doc->original_name }} ({{ number_format($doc->file_size / 1024, 1) }} KB)</td>
                <td><span class="badge {{ $doc->status === 'approved' ? 'badge-active' : ($doc->status === 'rejected' ? 'badge-cancelled' : 'badge-pending') }}">{{ ucfirst($doc->status) }}</span></td>
                <td style="text-align:right;"><a href="{{ route('client.kyc.documents.download', $doc) }}" class="btn btn-outline btn-sm">Download</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection

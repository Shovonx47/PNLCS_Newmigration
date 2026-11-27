@extends('client.layouts.twentyone')
@section('title', 'Identity Verification')

@section('content')
@php
    $status = $verification?->status ?? 'not_started';
    $maskedDoc = $verification?->document_number
        ? (strlen($verification->document_number) > 4
            ? str_repeat('*', strlen($verification->document_number) - 4).substr($verification->document_number, -4)
            : '****')
        : null;
    $labelClass = $status === 'verified' ? 'label-success' : ($status === 'pending' ? 'label-warning' : ($status === 'failed' ? 'label-danger' : 'label-default'));
@endphp

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0"><i class="fas fa-id-card fa-fw"></i>&nbsp;{{ 'Verification Status' }}</h5>
        <span class="label {{ $labelClass }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
    </div>
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <h5 class="mb-1">
                    @if($status === 'verified')
                        {{ 'Verified' }}
                    @elseif($status === 'pending')
                        {{ 'Pending Review' }}
                    @elseif($status === 'failed')
                        {{ 'Action Required (Declined)' }}
                    @else
                        {{ 'Not Verified' }}
                    @endif
                </h5>
                @if($verification?->verified_at)
                    <p class="text-muted mb-0">{{ 'Verified on ' . $verification->verified_at->format('d M Y, H:i') }}</p>
                @elseif($maskedDoc)
                    <p class="text-muted mb-0">{{ 'Document on file: ' . $maskedDoc }}</p>
                @else
                    <p class="text-muted mb-0">{{ 'Confirm your account identity to keep services active and unlock restricted orders.' }}</p>
                @endif
            </div>
            <a href="{{ route('client.home') }}" class="btn btn-default btn-sm mt-2 mt-sm-0">&laquo; {{ 'Back to Client Area' }}</a>
        </div>

        @if($status === 'failed' && $verification?->decline_reason)
            <div class="alert alert-danger mt-3 mb-0">
                <strong>{{ 'Reason:' }}</strong> {{ $verification->decline_reason }}
            </div>
        @endif
    </div>
</div>

@if($status !== 'verified')
    @if($allowsApi)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt fa-fw"></i>&nbsp;{{ 'Instant Automated Verification' }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">{{ 'Complete quick camera and document verification in a few minutes.' }}</p>
                <div class="d-flex flex-wrap">
                    <form method="POST" action="{{ route('client.kyc.session') }}" class="mr-2 mb-2">
                        @csrf
                        <button type="submit" class="btn btn-primary">{{ 'Start Instant Verification' }}</button>
                    </form>
                    @if($verification?->session_id && $status === 'pending')
                        <form method="POST" action="{{ route('client.kyc.refresh') }}" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-default">{{ 'Check Verification Status' }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($allowsManual)
        <div class="card mb-4" id="kycManualCard" data-business-types="{{ json_encode($businessTypes) }}">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-file-upload fa-fw"></i>&nbsp;{{ 'Manual Document Submission' }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    {{ 'Upload clear JPG, PNG, or PDF copies (maximum ' . ((int) ($settings['max_file_size'] ?? 5)) . ' MB per file). Files are stored privately and reviewed only by authorized verification staff.' }}
                </p>

                <form method="POST" action="{{ route('client.kyc.manual') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>{{ 'Account Category' }}</label>
                            <select name="business_type" id="kycAccountKind" class="form-control">
                                @php $oldKind = old('business_type', $verification?->business_type ?: 'individual'); @endphp
                                <option value="individual" @selected($oldKind === 'individual')>{{ 'Individual / Personal' }}</option>
                                <option value="sole_proprietorship" @selected($oldKind === 'sole_proprietorship')>{{ 'Sole Proprietorship' }}</option>
                                <option value="partnership" @selected($oldKind === 'partnership')>{{ 'Partnership' }}</option>
                                <option value="limited_company" @selected($oldKind === 'limited_company')>{{ 'Limited Company' }}</option>
                                <option value="pvt_ltd" @selected($oldKind === 'pvt_ltd')>{{ 'Private Limited (Pvt. Ltd.)' }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>{{ 'Document Type' }}</label>
                            @php $oldDocType = old('document_type', $verification?->document_type ?: 'national_id'); @endphp
                            <select name="document_type" id="kycDocType" class="form-control" required>
                                <option value="national_id" @selected($oldDocType === 'national_id')>{{ 'National ID (NID)' }}</option>
                                <option value="passport" @selected($oldDocType === 'passport')>{{ 'Passport' }}</option>
                                <option value="driving_license" @selected($oldDocType === 'driving_license')>{{ 'Driving License' }}</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>{{ 'ID / Passport Number' }}</label>
                            <input type="text" name="document_number" value="{{ old('document_number') }}" required class="form-control" placeholder="{{ 'Enter document number' }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>{{ 'ID Front Side *' }}</label>
                            <input type="file" name="id_front" accept=".jpg,.jpeg,.png,.pdf" required class="form-control">
                        </div>
                        @if(($settings['require_document_back'] ?? '1') !== '0')
                        <div class="col-md-4 form-group" id="kycIdBackWrap">
                            <label>{{ 'ID Back Side *' }}</label>
                            <input type="file" name="id_back" id="kycIdBackInput" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                        </div>
                        @endif
                        @if(($settings['require_selfie'] ?? '1') !== '0')
                        <div class="col-md-4 form-group">
                            <label>{{ 'Selfie Holding ID *' }}</label>
                            <input type="file" name="selfie" accept=".jpg,.jpeg,.png,.pdf" required class="form-control">
                        </div>
                        @endif
                    </div>

                    @if(($settings['require_trade_license'] ?? '1') !== '0')
                    <div class="row pt-3 border-top" id="kycTradeWrap">
                        <div class="col-md-6 form-group">
                            <label>{{ 'Trade License Number *' }}</label>
                            <input type="text" name="trade_license_number" id="kycTradeNum" value="{{ old('trade_license_number', $verification?->trade_license_number) }}" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ 'Trade License Copy (JPG/PNG/PDF) *' }}</label>
                            <input type="file" name="trade_license" id="kycTradeFile" accept=".jpg,.jpeg,.png,.pdf" class="form-control">
                        </div>
                    </div>
                    @endif

                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary">{{ 'Submit Documents for Review' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endif

@if($verification && $verification->documents->isNotEmpty())
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-folder-open fa-fw"></i>&nbsp;{{ 'Submitted Documents' }}</h5>
    </div>
    <div class="table-container clearfix">
        <table class="table table-list mb-0">
            <thead>
                <tr>
                    <th>{{ 'Type' }}</th>
                    <th>{{ 'File' }}</th>
                    <th>{{ 'Status' }}</th>
                    <th class="text-right">{{ 'Download' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($verification->documents as $doc)
                <tr>
                    <td>{{ ucwords(str_replace('_', ' ', $doc->file_type)) }}</td>
                    <td>{{ $doc->original_name }} ({{ number_format($doc->file_size / 1024, 1) }} {{ 'KB' }})</td>
                    <td><span class="label {{ $doc->status === 'approved' ? 'label-success' : ($doc->status === 'rejected' ? 'label-danger' : 'label-warning') }}">{{ ucfirst($doc->status) }}</span></td>
                    <td class="text-right"><a href="{{ route('client.kyc.documents.download', $doc) }}" class="btn btn-default btn-sm"><i class="fas fa-download"></i> {{ 'Download' }}</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<script>
(function () {
    var card = document.getElementById('kycManualCard');
    if (!card) return;
    var businessList = [];
    try { businessList = JSON.parse(card.getAttribute('data-business-types') || '[]'); } catch (e) {}
    var kindSel = document.getElementById('kycAccountKind');
    var docSel = document.getElementById('kycDocType');
    var backWrap = document.getElementById('kycIdBackWrap');
    var backInput = document.getElementById('kycIdBackInput');
    var tradeWrap = document.getElementById('kycTradeWrap');
    var tradeNum = document.getElementById('kycTradeNum');
    var tradeFile = document.getElementById('kycTradeFile');

    function syncKycFields() {
        if (docSel && backWrap) {
            var needBack = docSel.value !== 'passport';
            backWrap.style.display = needBack ? '' : 'none';
            if (backInput) backInput.required = needBack;
        }
        if (kindSel && tradeWrap) {
            var isBiz = businessList.indexOf(kindSel.value) !== -1;
            tradeWrap.style.display = isBiz ? '' : 'none';
            if (tradeNum) tradeNum.required = isBiz;
            if (tradeFile) tradeFile.required = isBiz;
        }
    }
    if (kindSel) kindSel.addEventListener('change', syncKycFields);
    if (docSel) docSel.addEventListener('change', syncKycFields);
    syncKycFields();
})();
</script>
@endsection

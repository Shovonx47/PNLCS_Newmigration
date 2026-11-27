@extends('client.layouts.twentyone')
@section('title', __('client.contact.title'))

@section('page_heading', __('client.contact.heading'))
@section('page_subheading', __('client.contact.subtitle'))

@section('content')

<div class="card mb-4">
    <div class="card-body extra-padding">

        <div class="mb-4">
            <h6 class="h3">{{ __('client.contact.send_message') }}</h6>
            <p class="text-muted mb-0">{{ __('client.contact.how_can_we_help') }}</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success text-center" role="alert">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach($errors->all() as $validationError)
                    <div>{{ $validationError }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('client.contact.submit') }}" role="form">
            @csrf

            <div class="form-group row">
                <label for="name" class="col-sm-3 col-form-label text-right">{{ __('client.contact.your_name') }}</label>
                <div class="col-sm-7">
                    <input type="text" id="name" name="name" value="{{ old('name', auth()->user()?->full_name) }}" required maxlength="100" class="form-control">
                </div>
            </div>

            <div class="form-group row">
                <label for="email" class="col-sm-3 col-form-label text-right">{{ __('common.form.email_address') }}</label>
                <div class="col-sm-7">
                    <input type="email" id="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required maxlength="200" class="form-control">
                </div>
            </div>

            <div class="form-group row">
                <label for="department_id" class="col-sm-3 col-form-label text-right">{{ __('client.contact.department') }}</label>
                <div class="col-sm-7">
                    <select id="department_id" name="department_id" required class="form-control">
                        <option value="">-- {{ __('client.contact.select_department') }} --</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group row">
                <label for="subject" class="col-sm-3 col-form-label text-right">{{ __('common.form.subject') }}</label>
                <div class="col-sm-7">
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="200" class="form-control">
                </div>
            </div>

            <div class="form-group row">
                <label for="message" class="col-sm-3 col-form-label text-right">{{ __('common.form.message') }}</label>
                <div class="col-sm-9">
                    <textarea id="message" name="message" rows="7" required maxlength="5000" class="form-control">{{ old('message') }}</textarea>
                </div>
            </div>

            <div class="text-center margin-bottom">
                @include('client.partials.recaptcha', ['form' => 'contact'])
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary">{{ __('client.contact.send_message_btn') }}</button>
            </div>
        </form>

    </div>
</div>

{{-- Who the customer is dealing with: e-commerce law in most places wants the
     seller's trading name, address, telephone and tax details reachable on the
     site itself. Read from the settings the invoice and the legal documents
     also read, so the three can never disagree. --}}
@php
    $sellerLines = array_values(array_filter([
        trim((string) \App\Models\Setting::get('CompanyLegalName', '')),
        trim((string) \App\Models\Setting::get('Address', '')),
        trim(implode(' ', array_filter([
            trim((string) \App\Models\Setting::get('Postcode', '')),
            trim((string) \App\Models\Setting::get('CompanyCity', '')),
        ]))),
        trim((string) \App\Models\Setting::get('Country', '')),
    ]));
    $sellerPhone = trim((string) \App\Models\Setting::get('PhoneNumber', ''));
    $sellerEmail = trim((string) \App\Models\Setting::get('Email', ''));
    $sellerTaxOffice = trim((string) \App\Models\Setting::get('TaxOffice', ''));
    $sellerTaxId = trim((string) \App\Models\Setting::get('TaxID', ''));
    $sellerRegistry = trim((string) (\App\Models\Setting::get('MersisNo', '') ?: \App\Models\Setting::get('TradeRegistryNo', '')));
@endphp

<div class="row">
    @if($sellerLines || $sellerPhone || $sellerEmail)
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">{{ __('client.contact.company_details') }}</h3>
                <div class="text-muted small" style="line-height:1.75">
                    @foreach($sellerLines as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                    @if($sellerPhone)<div><a href="tel:{{ preg_replace('/[^0-9+]/', '', $sellerPhone) }}">{{ $sellerPhone }}</a></div>@endif
                    @if($sellerEmail)<div><a href="mailto:{{ $sellerEmail }}">{{ $sellerEmail }}</a></div>@endif
                    @if($sellerTaxOffice || $sellerTaxId)
                        <div>
                            @if($sellerTaxOffice){{ __('client.contact.tax_office') }}: {{ $sellerTaxOffice }}@endif
                            @if($sellerTaxOffice && $sellerTaxId) &middot; @endif
                            @if($sellerTaxId){{ __('common.form.tax_id') }}: {{ $sellerTaxId }}@endif
                        </div>
                    @endif
                    @if($sellerRegistry)<div>{{ __('client.contact.registry_no') }}: {{ $sellerRegistry }}</div>@endif
                    <div><a href="{{ route('legal.index') }}">{{ __('client.contact.legal_documents') }}</a></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">{{ __('client.contact.prefer_tickets') }}</h3>
                <p class="text-muted small">{{ __('client.contact.ticket_benefit') }}</p>
                <a href="{{ route('client.tickets.create') }}" class="btn btn-primary btn-block">
                    <i class="fas fa-ticket-alt fa-fw"></i> {{ __('client.nav.open_ticket') }}
                </a>
                @if(kb_enabled())
                <p class="text-muted small mt-3 mb-2">{{ __('client.contact.kb_benefit') }}</p>
                <a href="{{ route('client.kb.index') }}" class="btn btn-default btn-block">{{ __('client.contact.browse_articles') }}</a>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

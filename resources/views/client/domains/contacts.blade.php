@extends('client.layouts.twentyone')
@section('title', __('client.domains.contacts_title').' - '.$domain->domain)
@section('content')

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title h5 mb-0">{{ __('client.domains.contacts_title') }} &mdash; {{ $domain->domain }}</h3>
        <a href="{{ route('client.domains.show', $domain) }}" class="btn btn-default btn-sm">{{ __('common.actions.back') }}</a>
    </div>
    <div class="card-body">
        <p class="text-muted">{{ __('client.domains.contacts_hint') }}</p>
        @if($fromProfile)
            <div class="alert alert-warning">{{ __('client.domains.contacts_from_profile') }}</div>
        @endif
        <form method="POST" action="{{ route('client.domains.contacts.update', $domain) }}">
            @csrf
            @method('PUT')
            @foreach(['first_name', 'last_name', 'company_name', 'email', 'phone', 'address1', 'city', 'state', 'postcode'] as $field)
                <div class="form-group row">
                    <label class="col-md-3 col-form-label" for="c-{{ $field }}">
                        {{ __('common.form.'.($field === 'address1' ? 'address' : $field)) }}
                        @if(! in_array($field, ['company_name', 'state'], true))
                            <span class="text-danger">*</span>
                        @endif
                    </label>
                    <div class="col-md-7">
                        <input id="c-{{ $field }}" name="{{ $field }}"
                               type="{{ $field === 'email' ? 'email' : ($field === 'phone' ? 'tel' : 'text') }}"
                               class="form-control"
                               value="{{ old($field, $contact[$field] ?? '') }}"
                               @if(! in_array($field, ['company_name', 'state'], true)) required @endif>
                        @error($field)
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endforeach
            <div class="form-group row">
                <label class="col-md-3 col-form-label" for="c-country">
                    {{ __('common.form.country') }} <span class="text-danger">*</span>
                </label>
                <div class="col-md-7">
                    <select id="c-country" name="country" class="form-control custom-select" required>
                        <option value="">{{ __('common.form.select_country') }}</option>
                        @foreach($countries as $code => $name)
                            <option value="{{ $code }}" @selected(old('country', $contact['country'] ?? '') === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('country')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="form-group row mb-0">
                <div class="col-md-7 offset-md-3">
                    <button type="submit" class="btn btn-primary">{{ __('common.actions.save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

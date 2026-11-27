@extends('client.layouts.twentyone')
@section('title', __('client.contacts.title'))
@section('content')

<div id="addContactForm" class="card mb-4 d-none">
    <div class="card-header">
        <h3 class="card-title h5 mb-0">{{ __('client.contacts.add_new_contact') }}</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('client.account.contacts.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('common.form.first_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" required class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('common.form.last_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" required class="form-control">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('common.form.email') }} <span class="text-danger">*</span></label>
                        <input type="email" name="email" required class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('common.form.phone') }}</label>
                        <input type="text" name="phone_number" class="form-control">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('client.contacts.save_contact') }}</button>
            <button type="button" class="btn btn-default ml-1" onclick="document.getElementById('addContactForm').classList.add('d-none')">
                {{ __('common.actions.cancel') }}
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title h5 mb-0">{{ __('client.contacts.title') }}</h3>
        <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('addContactForm').classList.toggle('d-none')">
            <i class="fas fa-plus mr-1"></i>{{ __('client.contacts.add_contact') }}
        </button>
    </div>
    <div class="table-container clearfix">
        <table class="table table-list mb-0">
            <thead>
                <tr>
                    <th>{{ __('common.table.name') }}</th>
                    <th>{{ __('common.table.email') }}</th>
                    <th>{{ __('common.form.phone') }}</th>
                    <th>{{ __('client.contacts.receives') }}</th>
                    <th>{{ __('common.table.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts ?? [] as $contact)
                    <tr>
                        <td class="font-weight-bold">{{ $contact->first_name }} {{ $contact->last_name }}</td>
                        <td>{{ $contact->email }}</td>
                        <td class="text-muted">{{ $contact->phone_number ?: '-' }}</td>
                        <td class="text-muted small">
                            @php
                                $kinds = collect([
                                    'general_emails' => __('client.contacts.kind_general'),
                                    'invoice_emails' => __('client.contacts.kind_invoice'),
                                    'product_emails' => __('client.contacts.kind_product'),
                                    'domain_emails' => __('client.contacts.kind_domain'),
                                    'support_emails' => __('client.contacts.kind_support'),
                                ])->filter(fn ($label, $field) => $contact->{$field})->values();
                            @endphp
                            {{ $kinds->isEmpty() ? __('client.contacts.receives_nothing') : $kinds->implode(', ') }}
                        </td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-default btn-sm" onclick="openModal('edit-contact-{{ $contact->id }}')">
                                {{ __('common.actions.edit') }}
                            </button>
                            <form method="POST" action="{{ route('client.account.contacts.destroy', $contact) }}" class="d-inline"
                                  onsubmit="return pnConfirm(event, @js(__('client.contacts.confirm_remove')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">{{ __('common.actions.remove') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">{{ __('client.contacts.no_contacts') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($contacts as $contact)
    <x-modal name="edit-contact-{{ $contact->id }}" title="{{ __('client.contacts.edit_contact') }}" maxWidth="md">
        <form method="POST" action="{{ route('client.account.contacts.update', $contact) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('common.form.first_name') }}</label>
                        <input type="text" name="first_name" value="{{ $contact->first_name }}" required class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('common.form.last_name') }}</label>
                        <input type="text" name="last_name" value="{{ $contact->last_name }}" required class="form-control">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>{{ __('common.form.email_address') }}</label>
                <input type="email" name="email" value="{{ $contact->email }}" required class="form-control">
            </div>
            <div class="form-group">
                <label>{{ __('common.form.company_name') }}</label>
                <input type="text" name="company_name" value="{{ $contact->company_name }}" class="form-control">
            </div>
            <div class="form-group">
                <label>{{ __('common.form.phone_number') }}</label>
                <input type="text" name="phone_number" value="{{ $contact->phone_number }}" class="form-control">
            </div>
            <div class="form-group">
                <label class="d-block">{{ __('client.contacts.receives') }}</label>
                @foreach(['general_emails' => 'kind_general', 'invoice_emails' => 'kind_invoice', 'product_emails' => 'kind_product', 'domain_emails' => 'kind_domain', 'support_emails' => 'kind_support'] as $field => $label)
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="contact-{{ $contact->id }}-{{ $field }}" name="{{ $field }}" value="1" {{ $contact->{$field} ? 'checked' : '' }}>
                        <label class="form-check-label" for="contact-{{ $contact->id }}-{{ $field }}">{{ __('client.contacts.'.$label) }}</label>
                    </div>
                @endforeach
            </div>
            <div class="text-right mt-3">
                <button type="button" onclick="closeModal('edit-contact-{{ $contact->id }}')" class="btn btn-default btn-sm">{{ __('common.actions.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm ml-1">{{ __('common.actions.save_changes') }}</button>
            </div>
        </form>
    </x-modal>
@endforeach

@endsection

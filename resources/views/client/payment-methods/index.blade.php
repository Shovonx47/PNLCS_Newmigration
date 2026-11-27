@extends('client.layouts.twentyone')
@section('title', __('client.payment_methods.title'))
@section('content')

@if($staffSession ?? false)
    <div class="alert alert-warning" role="status">
        <i class="fas fa-exclamation-triangle fa-fw"></i>
        {{ __('client.payment_methods.staff_cannot_consent') }}
    </div>
@endif

@if($autoChargeOffered)
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title h5 mb-0">{{ __('client.payment_methods.auto_charge_title') }}</h3>
        </div>
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <p class="text-muted mb-2 mb-md-0 mr-md-3">
                {{ $autoCharge ? __('client.payment_methods.auto_charge_on_hint') : __('client.payment_methods.auto_charge_off_hint') }}
            </p>
            @unless($staffSession ?? false)
                <form method="POST" action="{{ route('client.payment-methods.auto-charge') }}">
                    @csrf
                    <input type="hidden" name="auto_charge" value="{{ $autoCharge ? '0' : '1' }}">
                    <button type="submit" class="btn {{ $autoCharge ? 'btn-default' : 'btn-primary' }} btn-sm">
                        {{ $autoCharge ? __('client.payment_methods.auto_charge_turn_off') : __('client.payment_methods.auto_charge_turn_on') }}
                    </button>
                </form>
            @endunless
        </div>
    </div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.payment_methods.title') }}</h3>
        <p class="text-muted">{{ __('client.payment_methods.subtitle') }}</p>

        @if($cardStorageOffered && ! ($staffSession ?? false))
            <p>
                <a href="{{ route('client.payment-methods.add-card') }}" class="btn btn-primary" data-role="add-new-credit-card">
                    <i class="fas fa-credit-card mr-1"></i>{{ __('client.payment_methods.add_card_button') }}
                </a>
            </p>
        @endif

        <div class="table-responsive">
            <table class="table table-striped mb-0" id="payMethodList">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ 'Name' }}</th>
                        <th>{{ 'Description' }}</th>
                        <th>{{ 'Status' }}</th>
                        <th colspan="2">{{ 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($methods as $method)
                        <tr>
                            <td>
                                <i class="{{ strtolower($method->payment_type ?? '') === 'bankaccount' ? 'fas fa-university' : 'far fa-credit-card' }}"></i>
                            </td>
                            <td>{{ __('client.payment_methods.type_' . strtolower($method->payment_type ?: 'other')) }}</td>
                            <td>
                                {{ $method->description ?: '-' }}
                                @if($method->last_four || $method->expiry_date)
                                    <br><small class="text-muted">
                                        @if($method->last_four)&bull;&bull;&bull;&bull; {{ $method->last_four }}@endif
                                        @if($method->expiry_date) &nbsp;{{ __('client.payment_methods.expires') }} {{ $method->expiry_date }}@endif
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($method->is_default)
                                    <span class="badge badge-success">{{ __('client.payment_methods.default') }}</span>
                                @endif
                                @if($method->status === \App\Models\PaymentMethod::STATUS_REQUIRES_UPDATE)
                                    <span class="badge badge-danger">{{ __('client.payment_methods.needs_replacing') }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap" colspan="2">
                                @if(!$method->is_default)
                                    <form method="POST" action="{{ route('client.payment-methods.default', $method) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-default">{{ __('client.payment_methods.make_default') }}</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('client.payment-methods.destroy', $method) }}" class="d-inline"
                                      onsubmit="return pnConfirm(event, @js(__('client.payment_methods.remove_confirm')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash mr-1"></i>{{ __('common.actions.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">{{ __('client.payment_methods.none') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.payment_methods.add_bank_account') }}</h3>
        <p class="text-muted">{{ __('client.payment_methods.add_bank_hint') }}</p>
        <form method="POST" action="{{ route('client.payment-methods.store') }}">
            @csrf
            <input type="hidden" name="payment_type" value="BankAccount">
            <div class="form-row">
                <div class="form-group col-md-8">
                    <label for="bank-description">{{ __('client.payment_methods.account_label') }} <span class="text-danger">*</span></label>
                    <input type="text" id="bank-description" name="description" class="form-control" required maxlength="255"
                           placeholder="{{ __('client.payment_methods.account_placeholder') }}">
                </div>
                <div class="form-group col-md-4">
                    <label for="bank-last-four">{{ __('client.payment_methods.last_four') }}</label>
                    <input type="text" id="bank-last-four" name="last_four" class="form-control" maxlength="4" pattern="[0-9]{4}" placeholder="{{ '1234' }}">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{{ __('client.payment_methods.add_button') }}</button>
        </form>
    </div>
</div>

@endsection

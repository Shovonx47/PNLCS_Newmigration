@extends('client.layouts.twentyone')
@section('title', __('client.payment_methods.title'))
@section('content')

<div class="card">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.payment_methods.title') }}</h3>

        <div class="table-responsive">
            <table class="table table-striped mb-0" id="payMethodList">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('client.payment_methods.type') }}</th>
                        <th>{{ __('client.payment_methods.details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($paymentMethods) && count($paymentMethods) > 0)
                        @foreach($paymentMethods as $pm)
                            <tr>
                                <td><i class="far fa-credit-card"></i></td>
                                <td><strong>{{ $pm->type ?? '—' }}</strong></td>
                                <td class="text-muted">{{ __('client.payment_methods.card_ending', ['last4' => $pm->last_four ?? '****']) }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="3" class="text-center py-4">
                                <p class="font-weight-bold mb-1">{{ __('client.payment_methods.no_methods') }}</p>
                                <p class="text-muted mb-0">{{ __('client.payment_methods.will_appear') }}</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

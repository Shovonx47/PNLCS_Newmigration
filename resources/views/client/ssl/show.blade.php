@extends('client.layouts.twentyone')
@section('title', __('client.ssl.show_title', ['domain' => $order->domain ?: __('client.ssl.order_fallback', ['id' => $order->id])]))
@section('content')

<div class="mb-3">
    <a href="{{ route('client.ssl.index') }}" class="btn btn-sm btn-default">&larr; {{ __('client.ssl.back_to_ssl') }}</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title h5 mb-0">{{ __('client.ssl.certificate_details') }}</h3>
                <span class="badge {{ $order->getStatusBadgeClass() }}">{{ $order->status }}</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted">{{ __('client.cart.domain') }}</th>
                            <td class="font-weight-bold">{{ $order->domain ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">{{ __('client.ssl.certificate_type') }}</th>
                            <td>{{ $order->cert_type ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">{{ __('client.ssl.validation_method') }}</th>
                            <td>{{ $order->validation_method ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">{{ __('client.ssl.approver_email') }}</th>
                            <td>{{ $order->approver_email ?: '—' }}</td>
                        </tr>
                        @if($order->crt_expires)
                            <tr>
                                <th class="text-muted">{{ __('client.ssl.issued_date') }}</th>
                                <td>{{ $order->completion_date?->format(date_fmt()) ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th class="text-muted">{{ __('client.domains.expiry_date') }}</th>
                                <td>
                                    {{ $order->crt_expires->format(date_fmt()) }}
                                    @if($order->daysUntilExpiry() !== null)
                                        <small class="{{ $order->daysUntilExpiry() <= 7 ? 'text-danger' : ($order->daysUntilExpiry() <= 30 ? 'text-warning' : 'text-success') }}">
                                            ({{ $order->daysUntilExpiry() }} {{ __('client.ssl.days_remaining') }})
                                        </small>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        @if($order->domains)
                            <tr>
                                <th class="text-muted">{{ __('client.ssl.san_domains') }}</th>
                                <td>{{ $order->domains }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        @if($order->status === 'Awaiting Configuration')
            <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <strong>{{ __('client.ssl.action_required') }}:</strong> {{ __('client.ssl.needs_configuration') }}
                </div>
                <a href="{{ route('client.ssl.configure', $order) }}" class="btn btn-warning btn-sm ml-md-2 mt-2 mt-md-0">{{ __('client.ssl.configure_now') }}</a>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title h5 mb-0">{{ __('client.ssl.actions') }}</h3>
            </div>
            <div class="card-body">
                @if($order->isCompleted())
                    <a href="{{ route('client.ssl.download', $order) }}" class="btn btn-success btn-block mb-2">
                        <i class="fas fa-download mr-1"></i> {{ __('client.ssl.download_certificate') }}
                    </a>
                @endif

                @if(in_array($order->status, ['Awaiting Issuance', 'Configuration Submitted']))
                    <form method="POST" action="{{ route('client.ssl.resendValidation', $order) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-block">{{ __('client.ssl.resend_validation') }}</button>
                    </form>
                @endif

                @if($order->status === 'Awaiting Configuration')
                    <a href="{{ route('client.ssl.configure', $order) }}" class="btn btn-primary btn-block">{{ __('client.ssl.configure_certificate') }}</a>
                @endif
            </div>
        </div>

        @if($order->service?->product)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title h5 mb-0">{{ __('client.cart.product') }}</h3>
                </div>
                <div class="card-body">
                    <p class="mb-1 font-weight-bold">{{ $order->service->product->name }}</p>
                    @if($order->service->product->hasDescription())
                        <div class="mb-0 text-muted small">{!! $order->service->product->descriptionHtml() !!}</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

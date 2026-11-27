@extends("client.layouts.twentyone")

@section("title", __("client.ssl.title"))

@section("content")
<div class="clearfix mb-3">
    <a href="{{ route('client.store') }}" class="btn btn-primary float-right">
        <i class="fas fa-shopping-cart"></i> {{ __('client.ssl.browse_ssl') }}
    </a>
    <p class="mb-0">{{ __('client.ssl.my_certificates') }}</p>
</div>

@if($orders->count())
<div class="table-container clearfix">
    <table class="table table-list">
        <thead>
            <tr>
                <th>{{ 'Domain' }}</th>
                <th>{{ __('client.cart.product') }}</th>
                <th>{{ 'Status' }}</th>
                <th>{{ __('client.ssl.expires') }}</th>
                <th>{{ __('client.ssl.ordered') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>{{ $order->domain ?: __('client.ssl.pending_config') }}</td>
                <td>{{ $order->cert_type ?: $order->module }}</td>
                <td><span class="badge {{ $order->getStatusBadgeClass() }}">{{ $order->status }}</span></td>
                <td>
                    @if($order->crt_expires)
                        {{ $order->crt_expires->format(date_fmt()) }}
                        @if($order->daysUntilExpiry() !== null && $order->daysUntilExpiry() <= 30)
                            <span class="text-warning">({{ $order->daysUntilExpiry() }}d)</span>
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td>{{ $order->created_at->format(date_fmt()) }}</td>
                <td class="text-right">
                    @if($order->status === 'Awaiting Configuration')
                        <a href="{{ route('client.ssl.configure', $order) }}" class="btn btn-default btn-sm">{{ __('client.ssl.configure_now') }}</a>
                    @else
                        <a href="{{ route('client.ssl.show', $order) }}" class="btn btn-default btn-sm">{{ __('client.ssl.view_details') }}</a>
                    @endif
                    @if($order->isCompleted())
                        <a href="{{ route('client.ssl.download', $order) }}" class="btn btn-default btn-sm">{{ __('common.actions.download') }}</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-3">{{ $orders->links() }}</div>
@else
<div class="alert alert-info">{{ __('client.ssl.no_ssl_desc') }}</div>
<div class="text-center">
    <a href="{{ route('client.store') }}" class="btn btn-primary">{{ __('client.ssl.browse_ssl') }}</a>
</div>
@endif
@endsection

@section('no_page_header')@endsection
@extends('client.layouts.twentyone')
@section('title', __('client.services.title'))

@section('content')
<div class="header-lined">
    <h1>{{ __('client.services.page_title') }}</h1>
    <p class="mb-0">{{ __('client.services.page_subtitle') }}</p>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    @if(count($types ?? []) > 1 || count($statuses ?? []) > 1)
    <form method="GET" action="{{ route('client.services.index') }}" class="form-inline">
        @if(count($types) > 1)
        <select name="type" class="form-control form-control-sm mr-2" aria-label="{{ __('admin.products.product_type') }}">
            <option value="">{{ __('client.services.all_types') }}</option>
            @foreach($types as $t)
            <option value="{{ $t }}" @selected($type === $t)>{{ __('admin.products.type_'.$t) }}</option>
            @endforeach
        </select>
        @endif
        @if(count($statuses) > 1)
        <select name="status" class="form-control form-control-sm mr-2" aria-label="{{ __('common.table.status') }}">
            <option value="">{{ __('common.misc.all_statuses') }}</option>
            @foreach($statuses as $s)
            <option value="{{ $s }}" @selected($status === $s)>{{ __('client.status.'.strtolower($s)) }}</option>
            @endforeach
        </select>
        @endif
        <button type="submit" class="btn btn-default btn-sm">{{ __('common.actions.filter') }}</button>
        @if($type || $status)
        <a href="{{ route('client.services.index') }}" class="btn btn-link btn-sm">{{ __('common.actions.reset') }}</a>
        @endif
    </form>
    @else
    <span></span>
    @endif

    <a href="{{ route('client.store') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i>&nbsp;{{ __('client.services.order_new') }}
    </a>
</div>

<div class="table-container clearfix">
    <table class="table table-list">
        <thead>
            <tr>
                <th>{{ __('common.table.product') }}</th>
                <th class="text-center">{{ __('common.table.amount') }}</th>
                <th class="text-center">{{ __('common.table.next_due') }}</th>
                <th class="text-center">{{ __('common.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($services as $s)
            <tr onclick="window.location='{{ route('client.services.show', $s) }}'" style="cursor:pointer">
                <td>
                    <strong>{{ $s->product?->name ?? 'N/A' }}</strong>
                    <br>
                    @if($s->domain)
                        {{ $s->domain }}
                    @else
                        -
                    @endif
                    @if($ip = $s->primaryIp())
                        <br><small class="text-muted">{{ $ip }}</small>
                    @endif
                    @include('client.services.partials.tool-links', ['svc' => $s])
                </td>
                <td class="text-center">
                    {{ display_money_fmt($s->amount) }}
                    <small class="text-muted">{{ $s->billing_cycle ?? '' }}</small>
                </td>
                <td class="text-center">{{ $s->next_due_date?->format(date_fmt()) ?? '-' }}</td>
                <td class="text-center">
                    <span class="label status status-{{ strtolower($s->status) }}">{{ __('client.status.' . strtolower($s->status)) }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-5">
                    <p class="text-muted mb-3">{{ __('client.services.no_services') }}</p>
                    <a href="{{ route('client.store') }}" class="btn btn-primary">{{ __('client.services.order_first') }}</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($services instanceof \Illuminate\Pagination\LengthAwarePaginator && $services->hasPages())
    <div class="mt-3">{{ $services->links() }}</div>
@endif
@endsection

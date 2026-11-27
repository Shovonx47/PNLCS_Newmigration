@section('no_page_header')@endsection
@extends('client.layouts.twentyone')
@section('title', __('client.invoices.title'))

@section('content')
<div class="header-lined">
    <h1>{{ __('client.invoices.page_title') }}</h1>
    <p class="mb-0">{{ __('client.invoices.page_subtitle') }}</p>
</div>

{{-- Two or more open invoices: tick them and pay them in one go. --}}
<form method="POST" action="{{ route('client.invoices.mass-pay') }}" id="mass-pay-form">@csrf</form>

<div class="table-container clearfix">
    <table class="table table-list">
        <thead>
            <tr>
                @if(($payable ?? collect())->count() > 1)<th style="width:28px"><span class="sr-only">{{ __('client.invoices.mass_pay_select') }}</span></th>@endif
                <th>{{ __('common.table.invoice_num') }}</th>
                <th>{{ __('common.table.date') }}</th>
                <th>{{ __('common.table.due_date') }}</th>
                <th>{{ __('common.table.total') }}</th>
                <th>{{ __('common.table.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $inv)
            <tr onclick="window.location='{{ route('client.invoices.show', $inv) }}'" style="cursor:pointer">
                @if(($payable ?? collect())->count() > 1)
                <td onclick="event.stopPropagation()">
                    @if($payable->contains('id', $inv->id))
                    <input type="checkbox" name="invoice_ids[]" value="{{ $inv->id }}" form="mass-pay-form" checked aria-label="{{ __('client.invoices.mass_pay_select') }} #{{ $inv->invoice_num ?? $inv->id }}">
                    @endif
                </td>
                @endif
                <td><strong>#{{ $inv->invoice_num ?? $inv->id }}</strong></td>
                <td>{{ $inv->date?->format(date_fmt()) ?? '-' }}</td>
                <td>{{ $inv->due_date?->format(date_fmt()) ?? '-' }}</td>
                <td>{{ dual_money_fmt($inv->total, $inv) }}</td>
                <td><span class="label status status-{{ strtolower($inv->status) }}">{{ invoice_status_label($inv->status) }}</span></td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5">
                    <p class="text-muted mb-0">{{ __('admin.invoices.no_invoices') }}</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if(($payable ?? collect())->count() > 1)
<div class="d-flex justify-content-end align-items-center mb-3">
    <span class="text-muted mr-3">{{ __('client.invoices.mass_pay_hint') }}</span>
    <button type="submit" form="mass-pay-form" class="btn btn-primary">{{ __('client.invoices.mass_pay') }}</button>
</div>
@endif

@if($invoices instanceof \Illuminate\Pagination\LengthAwarePaginator && $invoices->hasPages())
    <div class="mt-3">{{ $invoices->links() }}</div>
@endif
@endsection

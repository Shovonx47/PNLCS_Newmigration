@section('no_page_header')@endsection
@extends('client.layouts.twentyone')
@section('title', __('admin.tickets.title'))

@section('content')
<div class="header-lined">
    <h1>{{ __('client.tickets.page_title') }}</h1>
    <p class="mb-0">{{ __('client.tickets.page_subtitle') }}</p>
</div>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('client.tickets.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i>&nbsp;{{ __('client.tickets.open_new') }}
    </a>
</div>

<div class="table-container clearfix">
    <table class="table table-list">
        <thead>
            <tr>
                <th>{{ __('client.tickets.ticket_prefix') }}</th>
                <th>{{ __('common.table.subject') }}</th>
                <th>{{ __('common.table.department') }}</th>
                <th>{{ __('common.table.status') }}</th>
                <th>{{ __('common.table.last_reply') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $t)
            <tr onclick="window.location='{{ route('client.tickets.show', $t) }}'" style="cursor:pointer">
                <td><strong>#{{ $t->tid }}</strong></td>
                <td>{{ Str::limit($t->title, 55) }}</td>
                <td>{{ $t->department->name ?? '-' }}</td>
                <td><span class="label status status-{{ strtolower(str_replace(' ', '-', $t->status)) }}">{{ __('client.status.' . strtolower(str_replace(' ', '-', $t->status))) }}</span></td>
                <td>{{ $t->last_reply?->diffForHumans() ?? $t->created_at?->diffForHumans() }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-5">
                    <p class="text-muted mb-3">{{ __('client.tickets.no_tickets') }}</p>
                    <a href="{{ route('client.tickets.create') }}" class="btn btn-primary">{{ __('client.tickets.open_first') }}</a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($tickets instanceof \Illuminate\Pagination\LengthAwarePaginator && $tickets->hasPages())
    <div class="mt-3">{{ $tickets->links() }}</div>
@endif
@endsection

@extends('client.layouts.twentyone')
@section('title', __('client.quotes.title'))
@section('content')

<p class="text-muted mb-3">{{ __('client.quotes.subtitle') }}</p>

<div class="table-container clearfix">
    <table id="tableQuotesList" class="table table-list">
        <thead>
            <tr>
                <th>{{ 'Quote Number' }}</th>
                <th>{{ 'Quote Subject' }}</th>
                <th>{{ 'Date Created' }}</th>
                <th>{{ 'Valid Until' }}</th>
                <th>{{ 'Stage' }}</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quotes as $quote)
                <tr onclick="clickableSafeRedirect(event, '{{ route('client.quotes.show', $quote) }}', true)">
                    <td>{{ $quote->id }}</td>
                    <td>{{ $quote->subject }}</td>
                    <td>{{ $quote->date ? \Carbon\Carbon::parse($quote->date)->format(date_fmt()) : '-' }}</td>
                    <td>{{ $quote->valid_until ? \Carbon\Carbon::parse($quote->valid_until)->format(date_fmt()) : '-' }}</td>
                    <td><span class="label status status-{{ strtolower($quote->status) }}">{{ __('client.status.' . strtolower($quote->status)) }}</span></td>
                    <td class="text-center">
                        <a href="{{ route('client.quotes.show', $quote) }}" class="btn btn-default btn-sm"><i class="fas fa-download"></i> {{ 'Download' }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('client.quotes.none') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">{{ $quotes->links() }}</div>

@endsection

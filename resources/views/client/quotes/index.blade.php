@extends("client.layouts.twentyone")
@section("title", __("client.quotes.title"))
@section("content")

<div class="pn-page-header">
    <div>
        <h1 class="h1 mb-0">{{ __('client.quotes.title') }}</h1>
        <p class="text-muted">{{ __('client.quotes.subtitle') }}</p>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-list">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('client.quotes.subject') }}</th>
                    <th>{{ __('common.table.date') }}</th>
                    <th>{{ __('client.quotes.valid_until') }}</th>
                    <th>{{ __('common.table.total') }}</th>
                    <th>{{ __('common.table.status') }}</th>
                    <th>{{ __('common.table.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotes as $quote)
                <tr>
                    <td style="font-weight:600">#{{ $quote->id }}</td>
                    <td><a href="{{ route('client.quotes.show', $quote) }}" style="font-weight:600">{{ $quote->subject }}</a></td>
                    <td class="text-muted text-sm">{{ $quote->date ? \Carbon\Carbon::parse($quote->date)->format(date_fmt()) : '-' }}</td>
                    <td class="text-muted text-sm">{{ $quote->valid_until ? \Carbon\Carbon::parse($quote->valid_until)->format(date_fmt()) : '-' }}</td>
                    <td style="font-weight:700">{{ money_fmt((float) $quote->total) }}</td>
                    <td><span class="badge badge-{{ strtolower($quote->status) }}">{{ __('client.status.' . strtolower($quote->status)) }}</span></td>
                    <td><a href="{{ route('client.quotes.show', $quote) }}" class="btn btn-outline btn-xs">{{ __('common.actions.view') }}</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="pn-empty">
                            <div class="pn-empty-icon">&#128203;</div>
                            <p>{{ __('client.quotes.none') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $quotes->links() }}</div>

@endsection

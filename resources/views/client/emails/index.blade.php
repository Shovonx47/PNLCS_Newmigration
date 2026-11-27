@extends("client.layouts.twentyone")
@section("title", __("client.emails.title"))
@section("content")

<div class="pn-page-header">
    <div>
        <h1 class="h1 mb-0">{{ __('client.emails.title') }}</h1>
        <p class="text-muted">{{ __('client.emails.subtitle') }}</p>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-list">
            <thead>
                <tr>
                    <th>{{ __('client.emails.subject') }}</th>
                    <th style="width:200px">{{ __('common.table.date') }}</th>
                    <th style="width:100px">{{ __('common.table.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($emails as $email)
                <tr>
                    <td><a href="{{ route('client.emails.show', $email) }}" style="font-weight:600">{{ $email->subject }}</a></td>
                    <td class="text-muted text-sm">{{ $email->date?->timezone(display_tz())->format(datetime_fmt()) ?? $email->created_at?->timezone(display_tz())->format(datetime_fmt()) }}</td>
                    <td><a href="{{ route('client.emails.show', $email) }}" class="btn btn-outline btn-xs">{{ __('common.actions.view') }}</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="3">
                        <div class="pn-empty">
                            <div class="pn-empty-icon">&#9993;</div>
                            <p>{{ __('client.emails.none') }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $emails->links() }}</div>

@endsection

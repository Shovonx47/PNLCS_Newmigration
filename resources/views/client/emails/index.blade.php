@extends('client.layouts.twentyone')
@section('title', __('client.emails.title'))
@section('content')

<p class="text-muted mb-3">{{ __('client.emails.subtitle') }}</p>

<div class="table-container clearfix">
    <table id="tableEmailsList" class="table table-list">
        <thead>
            <tr>
                <th>{{ __('common.table.date') }}</th>
                <th>{{ __('client.emails.subject') }}</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            @forelse($emails as $email)
                <tr onclick="popupWindow('{{ route('client.emails.show', $email) }}', 'emailWin', '800', '600', 'scrollbars=1,')">
                    <td class="text-nowrap">
                        {{ $email->date?->timezone(display_tz())->format(datetime_fmt()) ?? $email->created_at?->timezone(display_tz())->format(datetime_fmt()) }}
                    </td>
                    <td>
                        {{ $email->subject }}{{-- attachments stay in the message view --}}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-info btn-sm text-nowrap" onclick="event.stopPropagation();popupWindow('{{ route('client.emails.show', $email) }}', 'emailWin', '800', '600', 'scrollbars=1,')">
                            {{ 'View Message' }}
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">{{ __('client.emails.none') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">{{ $emails->links() }}</div>

@endsection

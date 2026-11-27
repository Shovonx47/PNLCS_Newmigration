@extends('client.layouts.twentyone')
@section('title', __('client.domains.title'))

@section('page_subheading')
    {{ __('client.domains.page_subtitle') }}
@endsection

@section('content')

@foreach($incomingMoves ?? [] as $move)
<div class="alert alert-info" role="alert">
    <div class="float-right">
        <form method="POST" action="{{ route('client.domain-moves.accept', $move) }}" class="d-inline">@csrf<button type="submit" class="btn btn-primary btn-xs">{{ __('client.domain_move.accept') }}</button></form>
        <form method="POST" action="{{ route('client.domain-moves.decline', $move) }}" class="d-inline">@csrf<button type="submit" class="btn btn-default btn-xs">{{ __('client.domain_move.decline') }}</button></form>
    </div>
    <strong>{{ __('client.domain_move.incoming', ['domain' => $move->domain?->domain, 'from' => $move->fromClient?->full_name ?: $move->fromClient?->email]) }}</strong>
    <div class="small">{{ __('client.domain_move.incoming_hint', ['date' => $move->expires_at->format(date_fmt())]) }}</div>
</div>
@endforeach

<div class="tab-content">
    <div class="tab-pane fade show active" id="tabOverview">

        <div class="btn-group btn-group-sm mb-3" role="group">
            <a href="{{ route('client.domain.search') }}" class="btn btn-default">
                <i class="fal fa-globe fa-fw"></i>
                {{ __('client.domains.register_domain') }}
            </a>
            <a href="{{ route('client.domains.transfer') }}" class="btn btn-default">
                <i class="fal fa-sync fa-fw"></i>
                {{ __('client.domains.transfer_domain') }}
            </a>
            <button type="submit" form="renew-many" class="btn btn-default">
                <i class="fal fa-sync fa-fw"></i>
                {{ __('client.domains.renew_selected') }}
            </button>
        </div>

        <div class="table-container clearfix">
            <table class="table table-list">
                <thead>
                    <tr>
                        <th class="width-fixed-20"></th>
                        <th>{{ __('client.domains.domain_name') }}</th>
                        <th>{{ __('client.domains.registration_date_col') }}</th>
                        <th>{{ __('common.table.expiry_date') }}</th>
                        <th>{{ __('common.table.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($domains as $d)
                    @php
                        $expiringSoon = $d->expiry_date && $d->expiry_date->diffInDays(now()) <= 30 && $d->expiry_date->isFuture();
                        $expired = $d->expiry_date && $d->expiry_date->isPast();
                        $renewable = strtolower((string) $d->status) === 'active' && (float) $d->recurring_amount > 0;
                    @endphp
                    <tr>
                        <td>
                            @if($renewable)
                            <input type="checkbox" name="domain_ids[]" value="{{ $d->id }}" form="renew-many" class="stopEventBubble" aria-label="{{ $d->domain }}">
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('client.domains.show', $d) }}">{{ $d->domain }}</a>
                            <br>
                            <small>
                                @if($d->auto_renew)
                                    <i class="fas fa-fw fa-check text-success"></i>
                                @else
                                    <i class="fas fa-fw fa-times text-danger"></i>
                                @endif
                                {{ __('client.domains.auto_renew_col') }}:
                                {{ $d->auto_renew ? __('client.domains.yes') : __('client.domains.no') }}
                            </small>
                        </td>
                        <td>{{ $d->registration_date?->format(date_fmt()) ?? '-' }}</td>
                        <td>
                            {{ $d->expiry_date?->format(date_fmt()) ?? '-' }}
                        </td>
                        <td>
                            <span class="label status status-{{ $expired ? 'terminated' : ($expiringSoon ? 'pending' : strtolower((string) $d->status)) }}">{{ status_label($d->status) }}</span>
                            @if($expired)
                                <span class="label status status-terminated">{{ __('client.domains.expired') }}</span>
                            @elseif($expiringSoon)
                                <span class="label status status-pending">{{ __('client.domains.expiring') }}</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('client.domains.show', $d) }}" class="btn btn-default btn-xs">{{ __('common.actions.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">
                            {{ __('admin.domains.no_domains') }}
                            <br><br>
                            <a href="{{ route('client.domain.search') }}" class="btn btn-primary">{{ __('client.domains.register_a_domain') }}</a>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('client.domains.renew-many') }}" id="renew-many">@csrf</form>

    </div>
</div>

@if($domains instanceof \Illuminate\Pagination\LengthAwarePaginator && $domains->hasPages())
    <div class="mt-3">{{ $domains->links() }}</div>
@endif

@endsection

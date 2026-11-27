@extends('admin.layouts.app')
@section('title', __('admin.addons.service_edit_title'))
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
    <h1 style="margin:0;">
        {{ __('admin.addons.service_edit_title') }}
        <span class="whmcs-status badge-{{ strtolower($addon->status) }}" style="font-size:12px;vertical-align:middle;margin-left:8px;">{{ status_label($addon->status) }}</span>
    </h1>
    <a href="{{ route('admin.services.show', $service) }}" class="btn btn-default btn-sm">&larr; {{ __('admin.services.back') }}</a>
</div>

@if($addon->isEnded())
    <div class="alert alert-danger">{{ __('admin.addons.service_ended') }}</div>
@else
    @if($addon->isProrated())
        <div class="infobox">{{ __('admin.addons.service_prorated_note', [
            'first' => money_fmt($addon->firstPeriodAmount()),
            'days' => $addon->proration_days,
            'cycle_days' => $addon->proration_cycle_days,
            'full' => money_fmt($addon->amount),
            'date' => $addon->next_due_date?->format(date_fmt()) ?? '-',
        ]) }}</div>
    @endif

    @if($addon->isDraft())
        <div class="infobox">{{ __('admin.addons.service_draft_note', ['date' => ($addon->first_invoice_date ?: $addon->next_due_date)?->format(date_fmt()) ?? now()->format(date_fmt())]) }}</div>
    @elseif($addon->isPending())
        <div class="alert alert-warning">{{ $addon->isProrated() ? __('admin.addons.service_pending_prorated_edit_note') : ($addon->first_invoice_date ? __('admin.addons.service_pending_follows_service_edit_note') : __('admin.addons.service_pending_edit_note')) }}</div>
    @endif

    <form method="POST" action="{{ route('admin.services.addons.update', [$service, $addon]) }}">
        @csrf
        @method('PUT')

        <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
            <tr>
                <td width="20%" class="fieldlabel">{{ __('common.table.name') }}</td>
                <td class="fieldarea">
                    <strong>{{ $addon->label() }}</strong>
                    <span class="text-muted">&mdash; {{ $service->domain ?: ('#'.$service->id) }}</span>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel"><label for="addon-amount">{{ __('common.table.amount') }}</label></td>
                <td class="fieldarea">
                    <input type="number" step="0.01" min="0" id="addon-amount" name="amount"
                           value="{{ old('amount', number_format((float) $addon->amount, 2, '.', '')) }}"
                           class="form-control input-150">
                </td>
            </tr>
            @if($addon->first_invoice_date)
            <tr>
                <td class="fieldlabel">{{ __('common.table.billing_cycle') }}</td>
                <td class="fieldarea">
                    <input type="text" class="form-control input-200" value="{{ cycle_label($addon->billing_cycle) }}" disabled>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel">{{ __('client.services.next_due_date') }}</td>
                <td class="fieldarea">
                    <input type="text" class="form-control input-200" value="{{ $addon->next_due_date?->format(date_fmt()) ?? '-' }}" disabled>
                </td>
            </tr>
            @else
            <tr>
                <td class="fieldlabel"><label for="addon-cycle">{{ __('common.table.billing_cycle') }}</label></td>
                <td class="fieldarea">
                    <select id="addon-cycle" name="billing_cycle" class="form-control select-inline">
                        @foreach($cycles as $cycle)
                            <option value="{{ $cycle }}" @selected(old('billing_cycle', $addon->billing_cycle) === $cycle)>{{ cycle_label($cycle) }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
            <tr>
                <td class="fieldlabel"><label for="addon-next-due">{{ $addon->isDraft() ? __('admin.addons.service_issue_date') : __('client.services.next_due_date') }}</label></td>
                <td class="fieldarea">
                    <input type="date" id="addon-next-due" name="next_due_date"
                           value="{{ old('next_due_date', $addon->next_due_date?->toDateString()) }}"
                           class="form-control input-200">
                </td>
            </tr>
            @endif
            <tr>
                <td class="fieldlabel"><label for="addon-notes">{{ __('admin.addons.service_notes') }}</label></td>
                <td class="fieldarea">
                    <textarea id="addon-notes" name="notes" rows="3" class="form-control">{{ old('notes', $addon->notes) }}</textarea>
                </td>
            </tr>
        </table>

        <div class="btn-container">
            <button type="submit" class="btn btn-primary">{{ __('common.actions.save') }}</button>
            @if($addon->isDraft())
            <button type="submit" name="accept" value="1" class="btn btn-success">{{ __('admin.addons.service_save_accept') }}</button>
            @endif
            <a href="{{ route('admin.services.show', $service) }}" class="btn btn-default">{{ __('common.actions.cancel') }}</a>
        </div>
    </form>
@endif
@endsection

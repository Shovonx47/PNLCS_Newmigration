@extends('client.layouts.twentyone')
@section('title', __('client.domains.glue_title').' - '.$domain->domain)
@section('content')

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title h5 mb-0">{{ __('client.domains.glue_title') }} &mdash; {{ $domain->domain }}</h3>
        <a href="{{ route('client.domains.show', $domain) }}" class="btn btn-default btn-sm">{{ __('common.actions.back') }}</a>
    </div>
    <div class="card-body">
        <p class="text-muted">{{ __('client.domains.glue_hint', ['domain' => $domain->domain]) }}</p>
        @if($hosts === null)
            <div class="alert alert-warning mb-0">{{ __('client.domains.glue_unreadable') }}</div>
        @elseif($hosts === [])
            <p class="text-muted mb-0">{{ __('client.domains.glue_none') }}</p>
        @else
            <div class="table-container clearfix">
                <table class="table table-list mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('client.domains.glue_host') }}</th>
                            <th>{{ __('client.domains.glue_ips') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hosts as $h)
                            <tr>
                                <td class="text-monospace">{{ $h['host'] }}</td>
                                <td class="text-monospace small">{{ implode(', ', $h['ips']) }}</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('client.domains.glue.delete', $domain) }}"
                                          onsubmit="return pnConfirm(event, @js(__('client.domains.glue_confirm_delete')))" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="host" value="{{ $h['host'] }}">
                                        <button type="submit" class="btn btn-danger btn-sm">{{ __('common.actions.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title h5 mb-0">{{ __('client.domains.glue_add') }}</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('client.domains.glue.save', $domain) }}">
            @csrf
            <div class="form-group row">
                <label class="col-md-3 col-form-label" for="g-host">{{ __('client.domains.glue_host') }}</label>
                <div class="col-md-7">
                    <div class="input-group">
                        <input id="g-host" name="host" class="form-control" value="{{ old('host') }}" required maxlength="100">
                        <div class="input-group-append">
                            <span class="input-group-text">.{{ $domain->domain }}</span>
                        </div>
                    </div>
                    @error('host')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-3 col-form-label" for="g-ipv4">{{ __('client.domains.glue_ipv4') }}</label>
                <div class="col-md-7">
                    <input id="g-ipv4" name="ipv4" class="form-control" value="{{ old('ipv4') }}" required>
                    @error('ipv4')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-3 col-form-label" for="g-ipv6">
                    {{ __('client.domains.glue_ipv6') }} <span class="text-muted">({{ __('client.form.optional') }})</span>
                </label>
                <div class="col-md-7">
                    <input id="g-ipv6" name="ipv6" class="form-control" value="{{ old('ipv6') }}">
                    @error('ipv6')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="form-group row mb-0">
                <div class="col-md-7 offset-md-3">
                    <p class="text-muted small">{{ __('client.domains.glue_save_hint') }}</p>
                    <button type="submit" class="btn btn-primary">{{ __('common.actions.save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

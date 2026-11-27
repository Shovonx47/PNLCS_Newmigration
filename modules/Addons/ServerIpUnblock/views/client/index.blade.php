@extends('client.layouts.twentyone')
@section('title', 'Unblock IP Address')

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-shield-alt fa-fw"></i>&nbsp;{{ 'Check & Unblock Firewall IP' }}</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">{{ 'Restore connection access if your network IP was temporarily blocked after failed login attempts.' }}</p>
        @if($services->isEmpty())
            <div class="alert alert-info mb-0">{{ 'None of your active services currently require or support self-service IP unblocking.' }}</div>
        @else
            <form method="POST" action="{{ route('client.unblock-ip.submit') }}">
                @csrf
                <div class="form-row align-items-end">
                    <div class="col-md-5 form-group mb-2">
                        <label>{{ 'Select Active Service' }}</label>
                        <select name="service_id" class="form-control" required>
                            @foreach($services as $svc)
                                <option value="{{ $svc->id }}">{{ $svc->product?->name }}{{ $svc->domain ? ' — '.$svc->domain : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label>{{ 'IP Address to Unblock' }}</label>
                        <input type="text" name="ip_address" value="{{ old('ip_address', $visitorIp) }}" required class="form-control">
                    </div>
                    <div class="col-md-3 form-group mb-2">
                        <button type="submit" class="btn btn-primary btn-block">{{ 'Check & Unblock IP' }}</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>

@if($recentLogs->isNotEmpty())
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history fa-fw"></i>&nbsp;{{ 'Recent Unblock Requests' }}</h5>
    </div>
    <div class="table-container clearfix">
        <table class="table table-list mb-0">
            <thead><tr><th>{{ 'Date' }}</th><th>{{ 'Service' }}</th><th>{{ 'IP Address' }}</th><th>{{ 'Status' }}</th></tr></thead>
            <tbody>
                @foreach($recentLogs as $log)
                <tr>
                    <td class="text-muted">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                    <td>{{ $log->service?->product?->name ?? 'Service #'.$log->service_id }}</td>
                    <td><code>{{ $log->ip_address }}</code></td>
                    <td><span class="label {{ $log->success ? 'label-success' : 'label-default' }}">{{ $log->success ? 'Completed' : 'Unavailable' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection

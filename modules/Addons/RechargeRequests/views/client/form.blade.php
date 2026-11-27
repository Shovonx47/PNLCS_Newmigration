@extends('client.layouts.twentyone')
@section('title', 'IP Number Balance Recharge')
@section('content')

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-bolt fa-fw"></i>&nbsp;{{ 'IP Number Calling Balance Recharge' }}</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">
            {{ 'Enter the IP phone number exactly as it appears on your account and the amount you want to add. An invoice is raised immediately; the balance is topped up once it is paid.' }}
        </p>

        <div class="alert alert-danger">
            <strong>{{ 'ভুল নাম্বার সতর্কতা:' }}</strong> {{ 'দয়া করে নিশ্চিত হয়ে আপনার আইপি ফোন নাম্বারটি দিন। একবার রিচার্জ সম্পন্ন হলে,' }}
            <strong>{{ 'ভুল নাম্বারে যাওয়া টাকার জন্য আমরা দায়ী নই' }}</strong> {{ 'এবং সেক্ষেত্রে কোনো প্রকার ফেরত (Refund) বা সমন্বয় (Adjustment) করা সম্ভব নয়।' }}
        </div>

        <div class="alert alert-warning">
            <strong>{{ 'Important:' }}</strong> {{ 'this form is for customers who receive an IP Number service from us. Minimum top-up: ' . money_fmt($minAmount) . ', maximum per request: ' . money_fmt($maxAmount) . '.' }}
        </div>

        <form method="POST" action="{{ route('client.recharge.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 form-group">
                    <label for="mobile_number">{{ 'IP phone number' }}</label>
                    <input id="mobile_number" type="tel" name="mobile_number" value="{{ old('mobile_number') }}" class="form-control" placeholder="{{ 'e.g. 096XXXXXXXX' }}" required>
                </div>
                <div class="col-md-6 form-group">
                    <label for="amount">{{ 'Recharge amount' }}</label>
                    <input id="amount" type="number" step="0.01" min="{{ $minAmount }}" max="{{ $maxAmount }}" name="amount" value="{{ old('amount') }}" class="form-control" placeholder="{{ 'Minimum ' . money_fmt($minAmount) }}" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-bolt"></i> {{ 'Create recharge invoice' }}
            </button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history fa-fw"></i>&nbsp;{{ 'Your recent recharge requests' }}</h5>
    </div>
    @if($requests->isEmpty())
        <div class="card-body text-muted">{{ 'You have not requested a recharge yet.' }}</div>
    @else
        <div class="table-container clearfix">
            <table class="table table-list mb-0">
                <thead>
                    <tr>
                        <th>{{ '#' }}</th>
                        <th>{{ 'IP number' }}</th>
                        <th>{{ 'Amount' }}</th>
                        <th>{{ 'Invoice' }}</th>
                        <th>{{ 'Requested' }}</th>
                        <th>{{ 'Status' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td><code>{{ $row->mobile_number }}</code></td>
                        <td>{{ money_fmt((float) $row->amount) }}</td>
                        <td>
                            @if($row->invoice)
                                <a href="{{ route('client.invoices.show', $row->invoice) }}">{{ $row->invoice->invoice_num }}</a>
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td class="text-muted">{{ $row->requested_at?->format(date_fmt().' H:i') }}</td>
                        <td><span class="label label-{{ strtolower($row->status) === 'completed' || strtolower($row->status) === 'success' ? 'success' : (strtolower($row->status) === 'pending' ? 'warning' : 'default') }}">{{ $row->status }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@extends('admin.layouts.app')
@section('title', 'Mass Invoice PDF Export')

@section('content')
<div class="page-header">
    <h1>Mass Invoice PDF &amp; CSV Export</h1>
</div>

<div class="card">
    <div class="card-header"><strong>Export Invoices to ZIP Archive</strong> <span style="font-size:12px;color:#777;">({{ $totalInvoices }} total invoices in database)</span></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.extensions.invoice-export.download') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
                <div class="form-group">
                    <label class="form-label">Invoice Date From</label>
                    <input type="date" name="date_from" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Invoice Date To</label>
                    <input type="date" name="date_to" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="all">All Statuses</option>
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Client (optional)</label>
                    <select name="client_id" class="form-control">
                        <option value="">All Clients</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ trim($c->first_name.' '.$c->last_name) }} ({{ $c->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Maximum Invoices (up to 300)</label>
                    <input type="number" name="limit" min="1" max="300" value="50" class="form-control">
                </div>
            </div>
            <div style="margin-top:14px;">
                <button type="submit" class="btn btn-primary">Generate &amp; Download ZIP</button>
            </div>
        </form>
    </div>
</div>
@endsection

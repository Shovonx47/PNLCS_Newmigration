@section('no_page_header')@endsection
@extends('client.layouts.twentyone')
@section('title', __('client.dashboard.title'))

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">{{ __('client.dashboard.title') }}</li>
@endsection

@section('content')
<div class="header-lined">
    <h1>{{ __('client.dashboard.welcome_back') }} {{ auth('web')->user()->first_name }}!</h1>
</div>

<div class="tiles clearfix">
    <div class="row">
        <div class="col-sm-3 col-xs-6 tile" onclick="window.location='{{ route('client.services.index') }}'">
            <a href="{{ route('client.services.index') }}">
                <div class="icon"><i class="fas fa-cube"></i></div>
                <div class="stat">{{ $serviceCount }}</div>
                <div class="title">{{ __('client.nav.my_services') }}</div>
                <div class="highlight bg-color-blue"></div>
            </a>
        </div>
        <div class="col-sm-3 col-xs-6 tile" onclick="window.location='{{ route('client.domains.index') }}'">
            <a href="{{ route('client.domains.index') }}">
                <div class="icon"><i class="fas fa-globe"></i></div>
                <div class="stat">{{ $domainCount }}</div>
                <div class="title">{{ __('client.nav.my_domains') }}</div>
                <div class="highlight bg-color-green"></div>
            </a>
        </div>
        <div class="col-sm-3 col-xs-6 tile" onclick="window.location='{{ route('client.tickets.index') }}'">
            <a href="{{ route('client.tickets.index') }}">
                <div class="icon"><i class="fas fa-comments"></i></div>
                <div class="stat">{{ $openTickets }}</div>
                <div class="title">{{ __('client.nav.my_tickets') }}</div>
                <div class="highlight bg-color-red"></div>
            </a>
        </div>
        <div class="col-sm-3 col-xs-6 tile" onclick="window.location='{{ route('client.invoices.index') }}'">
            <a href="{{ route('client.invoices.index') }}">
                <div class="icon"><i class="fas fa-credit-card"></i></div>
                <div class="stat">{{ $unpaidInvoices }}</div>
                <div class="title">My Invoices</div>
                <div class="highlight bg-color-gold"></div>
            </a>
        </div>
    </div>
</div>

@if(kb_enabled())
<form role="form" method="GET" action="{{ route('client.kb.index') }}" class="mb-4">
    <div class="input-group input-group-lg">
        <div class="input-group-prepend">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
        </div>
        <input type="text" name="q" class="form-control" placeholder="Search our knowledgebase..." />
    </div>
</form>
@endif

@php($homePanels = hook_output('ClientAreaHomePagePanels', ['user' => auth()->user()]))

<div class="client-home-panels">
    <div class="row">
        <div class="col-sm-6">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="fas fa-credit-card"></i>&nbsp;{{ __('client.dashboard.recent_invoices_title') }}</h5>
                    <a href="{{ route('client.invoices.index') }}" class="btn btn-default btn-sm">{{ __('client.dashboard.view_all') }}</a>
                </div>
                @if($recentInvoices->isEmpty())
                    <div class="card-body text-center text-muted">{{ __('client.dashboard.no_invoices') }}</div>
                @else
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('client.dashboard.invoice_col') }}</th>
                                <th>{{ __('client.dashboard.due_col') }}</th>
                                <th>{{ __('common.table.total') }}</th>
                                <th>{{ __('common.table.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentInvoices as $invoice)
                            <tr>
                                <td><a href="{{ route('client.invoices.show', $invoice) }}">#{{ $invoice->invoice_num ?? $invoice->id }}</a></td>
                                <td>{{ $invoice->due_date?->format(date_fmt()) ?? '-' }}</td>
                                <td>{{ dual_money_fmt($invoice->total, $invoice) }}</td>
                                <td><span class="label label-{{ in_array(strtolower($invoice->status), ['paid']) ? 'success' : (in_array(strtolower($invoice->status), ['unpaid', 'pending']) ? 'warning' : 'default') }}">{{ invoice_status_label($invoice->status) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="col-sm-6">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="fas fa-comments"></i>&nbsp;{{ __('client.dashboard.recent_tickets_title') }}</h5>
                    <a href="{{ route('client.tickets.index') }}" class="btn btn-default btn-sm">{{ __('client.dashboard.view_all') }}</a>
                </div>
                @if($recentTickets->isEmpty())
                    <div class="card-body text-center text-muted">{{ __('client.dashboard.no_tickets') }}</div>
                @else
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('common.table.subject') }}</th>
                                <th>{{ __('common.table.status') }}</th>
                                <th>{{ __('common.table.last_reply') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTickets as $ticket)
                            <tr>
                                <td><a href="{{ route('client.tickets.show', $ticket) }}">{{ Str::limit($ticket->title, 38) }}</a></td>
                                <td><span class="label label-default">{{ $ticket->status }}</span></td>
                                <td>{{ $ticket->last_reply?->diffForHumans() ?? $ticket->created_at?->diffForHumans() }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if($activeServices->isNotEmpty())
    <div class="row">
        <div class="col-sm-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="fas fa-cube"></i>&nbsp;{{ __('client.dashboard.active_services_title') }}</h5>
                    <a href="{{ route('client.services.index') }}" class="btn btn-default btn-sm">{{ __('client.dashboard.view_all') }}</a>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('common.table.product') }}</th>
                                <th>{{ __('common.table.domain') }}</th>
                                <th>{{ __('common.table.amount') }}</th>
                                <th>{{ __('client.dashboard.next_due') }}</th>
                                <th>{{ __('common.table.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeServices as $service)
                            <tr>
                                <td>
                                    <a href="{{ route('client.services.show', $service) }}">{{ $service->product?->name ?? __('client.dashboard.service_fallback', ['id' => $service->id]) }}</a>
                                    @include('client.services.partials.tool-links', ['svc' => $service])
                                </td>
                                <td>{{ $service->domain ?? '-' }}</td>
                                <td>{{ display_money_fmt($service->amount) }}<span class="text-muted">/{{ cycle_label($service->billing_cycle) }}</span></td>
                                <td>{{ $service->next_due_date?->format(date_fmt()) ?? 'N/A' }}</td>
                                <td><span class="label label-success">{{ __('client.status.active') }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if(trim($homePanels) !== '')
        <div class="row">
            <div class="col-sm-12">{!! $homePanels !!}</div>
        </div>
    @endif
</div>
@endsection

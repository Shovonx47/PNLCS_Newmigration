@extends("client.layouts.twentyone")
@section("title", __("client.cart.title"))
@section("no_page_header")@endsection
@push("styles")
<link href="{{ asset('templates/orderforms/standard_cart/css/all.min.css') }}" rel="stylesheet">
@endpush
@section("content")

<div id="order-standard_cart">
    <div class="row">
        @include('client.cart.partials.sidebar')

        <div class="cart-body">
            <div class="header-lined">
                <h1 class="font-size-36">Review &amp; Checkout</h1>
            </div>

            @if(session("success"))
                <div class="alert alert-success text-center" role="alert">{{ session("success") }}</div>
            @elseif(session("error"))
                <div class="alert alert-danger" role="alert">{{ session("error") }}</div>
            @endif

            @if(empty($totals["items"]))
                <div class="view-cart-empty">{{ __("client.cart.empty") }}</div>
                <div class="text-center mt-4">
                    <a href="{{ route("client.store") }}" class="btn btn-default">{{ __("client.cart.browse_products") }}</a>
                </div>
            @else
                <form method="POST" action="{{ route("client.cart.promo") }}">
                    @csrf
                    <div class="view-cart-items-header">
                        <div class="row">
                            <div class="col-sm-7 col-xs-7 col-7">{{ __("client.cart.cart_items") }}</div>
                            <div class="col-sm-4 col-xs-5 col-5 text-right">{{ __("common.table.price") }}</div>
                        </div>
                    </div>
                    <div class="view-cart-items">
                        @foreach($totals["items"] as $key => $item)
                            <div class="item">
                                <div class="row">
                                    <div class="col-sm-7">
                                        <span class="item-title">
                                            @if(($item["type"] ?? "") === "domain")
                                                {{ __(($item["action"] ?? "register") === "transfer" ? 'client.cart.line_domain_transfer' : 'client.cart.line_domain_register') }}
                                            @else
                                                {{ $item["product_name"] ?? $item["name"] ?? __("client.cart.product") }}
                                            @endif
                                            @if(!empty($item["domain"]))
                                                <span class="item-domain">{{ $item["domain"] }}</span>
                                            @endif
                                        </span>
                                        @if(!empty($item["comes_with"]))
                                            <span class="item-group">{{ __("client.cart.comes_with", ["product" => $item["comes_with"]]) }}</span>
                                        @endif
                                        @if((float) ($item["setup_fee"] ?? 0) > 0)
                                            <small>&raquo; {{ __("client.cart.setup_fee") }}: {{ display_money_fmt($item["setup_fee"]) }}</small>
                                        @endif
                                        @if(($item["type"] ?? "") === "domain" && ($item["privacy_price"] ?? null) !== null)
                                            <small>
                                                &raquo; {{ __("client.cart.privacy_label") }}:
                                                {{ (float) $item["privacy_price"] > 0 ? __("client.cart.privacy_per_year", ["price" => display_money_fmt($item["privacy_price"])]) : __("client.cart.privacy_free") }}
                                            </small>
                                        @endif
                                    </div>
                                    <div class="col-sm-4 item-price">
                                        <span>{{ display_money_fmt($item["price"] ?? 0) }}</span>
                                        <span class="cycle">{{ ($item["type"] ?? "") === "domain" ? trans_choice('client.cart.line_years', (int) ($item["years"] ?? 1), ['count' => (int) ($item["years"] ?? 1)]) : ($item["billing_cycle"] ?? "") }}</span>
                                        @if(($item["type"] ?? "") === "domain" && ($item["privacy_price"] ?? null) !== null)
                                            <br><small>{{ (float) $item["privacy_price"] > 0 ? __("client.cart.privacy_per_year", ["price" => display_money_fmt($item["privacy_price"])]) : __("client.cart.privacy_free") }}</small>
                                        @endif
                                    </div>
                                    <div class="col-sm-1">
                                        @if(!empty($item["comes_with"]))
                                            <span class="text-muted">&nbsp;</span>
                                        @elseif(($item["type"] ?? "") === "domain" && ($item["privacy_price"] ?? null) !== null)
                                            <button type="submit" class="btn btn-link btn-xs btn-remove-from-cart" formaction="{{ route('client.cart.privacy', $key) }}" name="privacy" value="{{ !empty($item['privacy']) ? '0' : '1' }}" title="{{ !empty($item['privacy']) ? __('common.actions.remove') : __('common.actions.add') }}">
                                                <i class="fas fa-shield-alt"></i>
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-link btn-xs btn-remove-from-cart" formaction="{{ route('client.cart.remove', $key) }}" name="_method" value="DELETE" onclick="return confirm('{{ __("common.confirm.are_you_sure") }}')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </form>

                <div class="view-cart-tabs">
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="nav-item active">
                            <a href="#applyPromo" class="nav-link active" aria-controls="applyPromo" role="tab" data-toggle="tab" aria-selected="true">{{ __("client.cart.promo_code") }}</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active promo" id="applyPromo">
                            @if($totals["promo_code"] ?? false)
                                <div class="view-cart-promotion-code">{{ $totals["promo_code"] }}</div>
                                <div class="text-center">
                                    <span class="btn btn-default btn-xs">{{ __("client.cart.promo_applied", ["code" => $totals["promo_code"]]) }}</span>
                                </div>
                            @else
                                <form method="POST" action="{{ route("client.cart.promo") }}">
                                    @csrf
                                    <div class="form-group prepend-icon">
                                        <label for="inputPromotionCode" class="field-icon"><i class="fas fa-ticket-alt"></i></label>
                                        <input type="text" name="code" id="inputPromotionCode" class="field form-control" placeholder="{{ __("client.cart.enter_promo_code") }}" required>
                                    </div>
                                    <button type="submit" class="btn btn-block btn-default">{{ __("common.actions.apply") }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="secondary-cart-sidebar">
                    <div class="order-summary" id="orderSummary">
                        <h2 class="font-size-30">{{ __("client.cart.order_summary") }}</h2>
                        <div class="summary-container">
                            <div class="subtotal clearfix">
                                <span class="pull-left float-left">{{ __("client.cart.subtotal") }}</span>
                                <span class="pull-right float-right">{{ display_money_fmt($totals["subtotal"]) }}</span>
                            </div>
                            @if(($totals["discount"] ?? 0) > 0)
                                <div class="clearfix">
                                    <span class="pull-left float-left">{{ __("client.cart.discount") }}</span>
                                    <span class="pull-right float-right">-{{ display_money_fmt($totals["discount"]) }}</span>
                                </div>
                            @endif
                            @if(($totals["tax"] ?? 0) > 0)
                                <div class="clearfix">
                                    <span class="pull-left float-left">{{ __("client.cart.tax") }} @ {{ $totals["tax_rate"] ?? 0 }}%</span>
                                    <span class="pull-right float-right">{{ display_money_fmt($totals["tax"]) }}</span>
                                </div>
                            @endif
                            <div class="total-due-today total-due-today-padded">
                                <span class="amt">{{ display_money_fmt($totals["total"]) }}</span>
                                <span>{{ __("client.cart.total") }}</span>
                            </div>
                            @if($taxNotice = \App\Support\TaxNotice::forCart($totals))
                                <p class="text-muted text-sm">{{ $taxNotice }}</p>
                            @endif
                            @include('client.cart.partials.currency-note')
                            <div class="text-right">
                                <a href="{{ route("client.cart.checkout") }}" class="btn btn-success btn-lg btn-checkout">
                                    {{ __("client.cart.checkout") }}
                                    <i class="fas fa-arrow-right"></i>
                                </a><br>
                                <a href="{{ route("client.store") }}" class="btn btn-link btn-continue-shopping">{{ __("client.cart.continue_shopping") }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

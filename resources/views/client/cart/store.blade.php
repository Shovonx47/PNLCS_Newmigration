@extends("client.layouts.twentyone")
@section("title", __("client.store.title"))
@section("no_page_header")@endsection
@push("styles")
<link href="{{ asset('templates/orderforms/standard_cart/css/all.min.css') }}" rel="stylesheet">
<style>
    .product ul{list-style:none;margin:0;padding:0}
    .product ul li{font-size:.92rem;color:#6c757d;line-height:1.65}
    .product ul li .feature-value{font-weight:600;color:#343a40;margin-right:4px}
</style>
@endpush
@section("content")

<div id="order-standard_cart">
    <div class="row">
        @include('client.cart.partials.sidebar')

        <div class="cart-body">
            <div class="header-lined">
                <h1 class="font-size-36">{{ __("client.store.title") }}</h1>
                <p>{{ __("client.store.subtitle") }}</p>
            </div>

            @if(session("error"))
                <div class="alert alert-danger" role="alert">{{ session("error") }}</div>
            @endif

            @forelse($groups as $group)
                <div class="header-lined" id="group-{{ $group->id }}">
                    <h1 class="font-size-36">{{ $group->name }}</h1>
                </div>

                @if($group->products->isEmpty())
                    <div class="alert alert-info">{{ __("client.store.no_products") }}</div>
                @else
                    <div class="products" id="products-{{ $group->id }}">
                        <div class="row row-eq-height">
                            @foreach($group->products as $product)
                                @php
                                    $pricedCycles = $product->pricedCycles($currency?->id);
                                    $startingCycle = (string) array_key_first($pricedCycles);
                                    $startingPrice = $pricedCycles[$startingCycle] ?? null;
                                    $idPrefix = 'product'.$product->id;
                                @endphp
                                <div class="col-md-6">
                                    <div class="product clearfix" id="{{ $idPrefix }}">
                                        <header>
                                            <span id="{{ $idPrefix }}-name">{{ $product->name }}</span>
                                            @if($product->is_featured)
                                                <span class="qty">{{ __("client.store.most_popular") }}</span>
                                            @endif
                                        </header>
                                        <div class="product-desc">
                                            @if($product->hasDescription())
                                                <p id="{{ $idPrefix }}-description">{{ Str::limit($product->descriptionText(), 180) }}</p>
                                            @endif
                                            @php $res = $product->resourceSummary(); @endphp
                                            @if($res)
                                                <ul>
                                                    @foreach($res as $r)
                                                        <li id="{{ $idPrefix }}-feature{{ $loop->iteration }}">
                                                            <span class="feature-value">{{ $r['text'] }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                        <footer>
                                            <div class="product-pricing" id="{{ $idPrefix }}-price">
                                                @if($startingPrice === null)
                                                    {{ __("client.store.contact_us") }}
                                                @elseif($product->pay_type === 'free')
                                                    <span class="price">{{ __("common.billing.free") }}</span>
                                                @else
                                                    @if($product->compare_price !== null && (float) $product->compare_price > (float) $startingPrice)
                                                        <small><s>{{ display_money_fmt((float) $product->compare_price) }}</s></small><br>
                                                    @endif
                                                    <span class="price">{{ display_money_fmt($startingPrice) }}</span>
                                                    <br>
                                                    {{ __('common.billing.'.str_replace('semiannually', 'semi_annually', $startingCycle)) }}
                                                    <br>
                                                    @if((int) ($product->trial_days ?? 0) > 0)
                                                        <small>{{ $product->trial_days }}-day free trial</small>
                                                    @endif
                                                @endif
                                            </div>
                                            @if($product->outOfStock())
                                                <span class="btn btn-default btn-sm btn-order-now" style="opacity:.65;cursor:default">{{ __("client.cart.out_of_stock") }}</span>
                                            @else
                                                <a href="{{ route("client.store.configure", $product) }}" class="btn btn-success btn-sm btn-order-now" id="{{ $idPrefix }}-order-button">
                                                    <i class="fas fa-shopping-cart"></i>
                                                    {{ __("client.store.order_now") }}
                                                </a>
                                            @endif
                                        </footer>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @empty
                <div class="alert alert-info">{{ __("client.store.no_products_at_all") }}</div>
            @endforelse
        </div>
    </div>
</div>

@endsection

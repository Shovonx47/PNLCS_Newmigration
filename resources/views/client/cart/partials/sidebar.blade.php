@php
    // WHMCS draws the store's categories in the cart sidebar
    // (sidebar-categories.tpl). The list is read here so every cart page shows
    // the same navigation.
    $sidebarGroups = $sidebarGroups ?? \App\Models\ProductGroup::query()
        ->where('hidden', false)
        ->orderBy('sort_order')
        ->get(['id', 'name']);
    $cartItemCount = 0;
    try {
        $sidebarCart = $cart ?? \App\Models\Cart::query()
            ->when(auth('web')->check(), fn ($q) => $q->where('user_id', auth('web')->id()))
            ->when(! auth('web')->check(), fn ($q) => $q->where('session_id', session()->getId()))
            ->latest('id')
            ->first();
        $cartItemCount = count($sidebarCart->data['items'] ?? []);
    } catch (\Throwable $e) {
        $cartItemCount = 0;
    }
@endphp
<div class="cart-sidebar">
    <div class="panel card card-sidebar mb-3 panel-sidebar">
        <div class="panel-heading card-header">
            <h3 class="panel-title">Categories</h3>
        </div>
        <div class="list-group collapsable-card-body">
            @foreach($sidebarGroups as $sidebarGroup)
                <a href="{{ route('client.store') }}#group-{{ $sidebarGroup->id }}" class="list-group-item list-group-item-action">{{ $sidebarGroup->name }}</a>
            @endforeach
        </div>
    </div>

    <div class="panel card card-sidebar mb-3 panel-sidebar">
        <div class="panel-heading card-header">
            <h3 class="panel-title">Cart</h3>
        </div>
        <div class="list-group collapsable-card-body">
            <a href="{{ route('client.cart.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('client.cart.index') ? 'active' : '' }}">
                View Cart
                <span class="badge">{{ $cartItemCount }}</span>
            </a>
            <a href="{{ route('client.cart.checkout') }}" class="list-group-item list-group-item-action {{ request()->routeIs('client.cart.checkout') ? 'active' : '' }}">Checkout</a>
        </div>
    </div>
</div>

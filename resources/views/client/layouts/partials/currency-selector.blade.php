{{-- The currency prices are shown in (CustomerCurrency). A visitor picks it
     for the session; a customer's account currency is changed by staff. --}}
@if(isset($customerCurrencyChoice) && $customerCurrencyChoice->count() > 1)
<div class="dropdown pn-currency-selector d-inline-block">
    <button class="btn btn-sm btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __('common.form.currency') }}">
        {{ $displayCurrency?->code }}
    </button>
    <div class="dropdown-menu dropdown-menu-right">
        @foreach($customerCurrencyChoice as $choice)
        <a href="{{ request()->fullUrlWithQuery(['currency' => $choice->code]) }}" rel="nofollow"
           class="dropdown-item px-2 py-0{{ $choice->id === $displayCurrency?->id ? ' active' : '' }}">
            {{ $choice->code }}@if($choice->symbol() !== '') ({{ $choice->symbol() }})@endif
        </a>
        @endforeach
    </div>
</div>
@elseif(($customerCurrencyEnabled ?? false) && $displayCurrency && auth()->check())
<div class="pn-currency-selector d-inline-block">
    <span class="btn btn-sm btn-default disabled" title="{{ __('client.topbar.currency_account') }}">{{ $displayCurrency->code }}</span>
</div>
@endif

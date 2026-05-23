@extends('layouts.shop')

@section('content')
    <div class="page-head">
        <h1>{{ setting('shop.name', config('app.name')) }}</h1>
        <p class="lead">@lang('shop.cart.continue_shopping')</p>
    </div>

    @if ($categories->isNotEmpty())
        <div class="cat-list">
            @foreach ($categories as $cat)
                <a href="{{ route('shop.category', $cat->slug) }}" class="cat-pill">{{ $cat->localized('name') }}</a>
            @endforeach
        </div>
    @endif

    <div class="product-grid">
        @foreach ($featured as $product)
            <a class="product-card" href="{{ route('shop.product', $product->slug) }}">
                @if ($product->imageUrl())
                    <img src="{{ $product->imageUrl() }}" alt="{{ $product->localized('name') }}" style="aspect-ratio: 1/1; object-fit: cover;">
                @else
                    <div class="ph">🛍</div>
                @endif
                <div class="body">
                    <div class="name">{{ $product->localized('name') }}</div>
                    <div class="price">{{ App\Support\Money::format($product->price, setting('shop.currency', 'SEK')) }}</div>
                    <div class="vat">{{ __('shop.product.price_incl_vat') }} ({{ rtrim(rtrim(number_format($product->vat_rate, 2, '.', ''), '0'), '.') }} %)</div>
                </div>
            </a>
        @endforeach
    </div>
@endsection

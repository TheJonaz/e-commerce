@php
    $currency = setting('shop.currency', 'SEK');
    $inStock = $product->stock === null || $product->stock > 0;
    $lowStock = $product->stock !== null && $product->stock > 0 && $product->stock <= 5;
@endphp

<div class="product-card">
    <a class="product-card-media" href="{{ route('shop.product', $product->slug) }}">
        @if ($product->imageUrl())
            <img src="{{ $product->imageUrl() }}" alt="{{ $product->localized('name') }}">
        @else
            <span class="product-card-placeholder">🛍</span>
        @endif

        @if (! $inStock)
            <span class="product-card-badge product-card-badge--out">Slut</span>
        @elseif ($lowStock)
            <span class="product-card-badge product-card-badge--low">Få kvar</span>
        @endif

        @if ($inStock)
            <form method="POST" action="{{ route('cart.add', $product->slug) }}" class="product-card-quickadd" onclick="event.stopPropagation();">
                @csrf
                <input type="hidden" name="qty" value="1">
                <button type="submit" title="{{ __('shop.cart.add') }}" aria-label="{{ __('shop.cart.add') }}">+</button>
            </form>
        @endif
    </a>
    <a class="product-card-body" href="{{ route('shop.product', $product->slug) }}">
        @if ($product->categories?->isNotEmpty())
            <div class="product-card-cat">{{ $product->categories->first()->localized('name') }}</div>
        @endif
        <div class="product-card-name">{{ $product->localized('name') }}</div>
        @if ($product->reviewCount() > 0)
            <div style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: var(--muted);">
                @include('shop._stars', ['rating' => $product->averageRating(), 'size' => '0.75rem'])
                <span>({{ $product->reviewCount() }})</span>
            </div>
        @endif
        <div class="product-card-foot">
            <span class="product-card-price">{{ App\Support\Money::format($product->displayPrice(), $currency) }}</span>
            <span class="product-card-vat">{{ $product->vatLabel() }}</span>
        </div>
    </a>
</div>

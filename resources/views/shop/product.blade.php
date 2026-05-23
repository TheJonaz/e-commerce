@extends('layouts.shop')

@section('content')
    <div class="breadcrumbs">
        <a href="{{ url('/') }}">Hem</a>
        @if ($product->categories->isNotEmpty())
            @php $primaryCat = $product->categories->first(); @endphp
            · <a href="{{ route('shop.category', $primaryCat->slug) }}">{{ $primaryCat->localized('name') }}</a>
        @endif
        · {{ $product->localized('name') }}
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; align-items: start; margin-top: 1rem;">
        <div style="aspect-ratio: 1/1; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, #f5f5f4 0%, #e7e5e4 100%); display: flex; align-items: center; justify-content: center; color: #d6d3d1; font-size: 6rem;">
            @if ($product->imageUrl())
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->localized('name') }}" style="width: 100%; height: 100%; object-fit: cover;">
            @else
                🛍
            @endif
        </div>

        <div>
            <h1 style="font-size: 2rem; letter-spacing: -0.01em; margin-bottom: 0.25rem;">{{ $product->localized('name') }}</h1>
            @if ($product->sku)
                <div style="color: var(--muted); font-size: 0.85rem; margin-bottom: 1.25rem;">{{ __('shop.product.sku') }}: {{ $product->sku }}</div>
            @endif

            <div style="font-size: 2rem; font-weight: 700; color: var(--price); margin-bottom: 0.25rem;">
                {{ App\Support\Money::format($product->price, setting('shop.currency', 'SEK')) }}
            </div>
            <div style="color: var(--muted); font-size: 0.85rem; margin-bottom: 1.5rem;">
                {{ App\Support\Money::format($product->priceExclVat(), setting('shop.currency', 'SEK')) }} {{ __('shop.product.price_excl_vat') }}
                · {{ rtrim(rtrim(number_format($product->vat_rate, 2, '.', ''), '0'), '.') }} % moms
            </div>

            @if ($product->localized('short_description'))
                <p style="margin-bottom: 1.5rem;">{{ $product->localized('short_description') }}</p>
            @endif

            <div style="margin-bottom: 1.5rem; font-size: 0.9rem; color: {{ $product->stock === null || $product->stock > 0 ? '#15803d' : '#b91c1c' }};">
                @if ($product->stock === null || $product->stock > 0)
                    ✓ {{ __('shop.product.in_stock') }}
                @else
                    ✗ {{ __('shop.product.out_of_stock') }}
                @endif
            </div>

            <form method="POST" action="{{ route('cart.add', $product->slug) }}" style="display: flex; gap: 0.75rem; align-items: center;">
                @csrf
                <input type="number" name="qty" value="1" min="1" max="99" style="width: 70px; padding: 0.6rem 0.65rem; border: 1px solid var(--border); border-radius: 8px; font: inherit; text-align: center;">
                <button type="submit" class="btn btn-primary">{{ __('shop.cart.add') }}</button>
            </form>

            @if ($product->localized('description'))
                <div style="margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                    <p style="white-space: pre-line;">{{ $product->localized('description') }}</p>
                </div>
            @endif
        </div>
    </div>

    @if ($related->isNotEmpty())
        <h2 style="font-size: 1.1rem; margin: 3rem 0 1rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Liknande produkter</h2>
        <div class="product-grid">
            @foreach ($related as $r)
                <a class="product-card" href="{{ route('shop.product', $r->slug) }}">
                    @if ($r->imageUrl())
                        <img src="{{ $r->imageUrl() }}" alt="{{ $r->localized('name') }}" style="aspect-ratio: 1/1; object-fit: cover;">
                    @else
                        <div class="ph">🛍</div>
                    @endif
                    <div class="body">
                        <div class="name">{{ $r->localized('name') }}</div>
                        <div class="price">{{ App\Support\Money::format($r->price, setting('shop.currency', 'SEK')) }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection

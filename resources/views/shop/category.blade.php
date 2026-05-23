@extends('layouts.shop')

@section('content')
    <div class="breadcrumbs">
        <a href="{{ url('/') }}">Hem</a> · {{ $category->localized('name') }}
    </div>
    <div class="page-head">
        <h1>{{ $category->localized('name') }}</h1>
        @if ($category->localized('description'))
            <p class="lead">{{ $category->localized('description') }}</p>
        @endif
    </div>

    @if ($products->isEmpty())
        <p style="color: var(--muted);">Inga produkter i denna kategori än.</p>
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                <a class="product-card" href="{{ route('shop.product', $product->slug) }}">
                    @if ($product->imageUrl())
                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->localized('name') }}" style="aspect-ratio: 1/1; object-fit: cover;">
                    @else
                        <div class="ph">🛍</div>
                    @endif
                    <div class="body">
                        <div class="name">{{ $product->localized('name') }}</div>
                        <div class="price">{{ App\Support\Money::format($product->price, setting('shop.currency', 'SEK')) }}</div>
                        <div class="vat">{{ __('shop.product.price_incl_vat') }}</div>
                    </div>
                </a>
            @endforeach
        </div>

        @if ($products->hasPages())
            <div style="margin-top: 2rem;">{{ $products->withQueryString()->links() }}</div>
        @endif
    @endif
@endsection

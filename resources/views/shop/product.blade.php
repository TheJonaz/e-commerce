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
        <div>
            @php $images = $product->images; @endphp
            <div id="product-gallery-main" style="aspect-ratio: 1/1; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, #f5f5f4 0%, #e7e5e4 100%); display: flex; align-items: center; justify-content: center; color: #d6d3d1; font-size: 6rem;">
                @if ($images->isNotEmpty())
                    <img id="product-gallery-img" src="{{ $images->first()->url() }}" alt="{{ $images->first()->localizedAlt() ?: $product->localized('name') }}" style="width: 100%; height: 100%; object-fit: cover;">
                @elseif ($product->imageUrl())
                    <img id="product-gallery-img" src="{{ $product->imageUrl() }}" alt="{{ $product->localized('name') }}" style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    🛍
                @endif
            </div>

            @if ($images->count() > 1)
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 0.5rem; margin-top: 0.75rem;">
                    @foreach ($images as $img)
                        <button type="button"
                            data-src="{{ $img->url() }}"
                            data-alt="{{ $img->localizedAlt() ?: $product->localized('name') }}"
                            class="gallery-thumb {{ $loop->first ? 'active' : '' }}"
                            style="aspect-ratio: 1/1; border-radius: 8px; overflow: hidden; border: 2px solid {{ $loop->first ? 'var(--primary)' : 'transparent' }}; cursor: pointer; padding: 0; background: var(--card); transition: border-color 0.15s;">
                            <img src="{{ $img->url() }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        </button>
                    @endforeach
                </div>
                <script>
                    (function () {
                        const main = document.getElementById('product-gallery-img');
                        const thumbs = document.querySelectorAll('.gallery-thumb');
                        thumbs.forEach(t => t.addEventListener('click', () => {
                            main.src = t.dataset.src;
                            main.alt = t.dataset.alt || '';
                            thumbs.forEach(other => other.style.borderColor = 'transparent');
                            t.style.borderColor = 'var(--primary)';
                        }));
                    })();
                </script>
            @endif
        </div>

        <div>
            <h1 style="font-size: 2rem; letter-spacing: -0.01em; margin-bottom: 0.25rem;">{{ $product->localized('name') }}</h1>
            @if ($product->sku)
                <div style="color: var(--muted); font-size: 0.85rem; margin-bottom: 1.25rem;">{{ __('shop.product.sku') }}: {{ $product->sku }}</div>
            @endif

            @php $pricesInclVat = (bool) setting('shop.prices_include_vat', '1'); @endphp
            <div style="font-size: 2rem; font-weight: 700; color: var(--price); margin-bottom: 0.25rem;">
                {{ App\Support\Money::format($product->displayPrice(), setting('shop.currency', 'SEK')) }}
            </div>
            <div style="color: var(--muted); font-size: 0.85rem; margin-bottom: 1.5rem;">
                {{ $product->vatLabel() }}
                · {{ rtrim(rtrim(number_format($product->vat_rate, 2, '.', ''), '0'), '.') }} % moms
                @if (! $pricesInclVat)
                    · totalt {{ App\Support\Money::format($product->price, setting('shop.currency', 'SEK')) }} inkl. moms
                @endif
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
                @include('shop._product-card', ['product' => $r])
            @endforeach
        </div>
    @endif
@endsection

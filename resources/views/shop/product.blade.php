@php
    $title = $product->localized('name');
    $description = $product->localized('short_description')
        ?: \Illuminate\Support\Str::limit(strip_tags($product->localized('description')), 160)
        ?: $product->localized('name');
    $canonicalUrl = route('shop.product', $product->slug);
    $ogImage = $product->imageUrl() ? \Illuminate\Support\Str::startsWith($product->imageUrl(), 'http') ? $product->imageUrl() : url($product->imageUrl()) : null;
    $ogType = 'product';
@endphp

@extends('layouts.shop')

@push('head')
    {{-- Schema.org Product --}}
    @php
        $stockStatus = ($product->stock === null || $product->stock > 0) ? 'InStock' : 'OutOfStock';
        $jsonLdProduct = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->localized('name'),
            'description' => $description,
            'sku' => (string) ($product->sku ?: $product->id),
            'image' => $ogImage ? [$ogImage] : [],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => setting('shop.currency', 'SEK'),
                'price' => number_format((float) $product->price, 2, '.', ''),
                'availability' => "https://schema.org/{$stockStatus}",
                'url' => $canonicalUrl,
            ],
        ];
        // BreadcrumbList
        $crumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Hem', 'item' => url('/')]];
        if ($product->categories->isNotEmpty()) {
            $cat = $product->categories->first();
            $crumbs[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $cat->localized('name'), 'item' => route('shop.category', $cat->slug)];
        }
        $crumbs[] = ['@type' => 'ListItem', 'position' => count($crumbs) + 1, 'name' => $product->localized('name'), 'item' => $canonicalUrl];
        $jsonLdBreadcrumb = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs];
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLdProduct, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    <script type="application/ld+json">{!! json_encode($jsonLdBreadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

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

            @php
                $variants = $product->variants->where('is_active', true);
                $hasVariants = $variants->isNotEmpty();
                $axes = [];
                foreach ($variants as $v) {
                    foreach (($v->options ?? []) as $key => $val) {
                        $axes[$key][$val] = true;
                    }
                }
                $variantsJson = json_encode($variants->map(fn ($v) => [
                    'id' => $v->id,
                    'options' => $v->options ?? [],
                    'price' => \App\Support\Money::format($v->price, setting('shop.currency', 'SEK')),
                    'stock' => $v->stock,
                    'label' => $v->label(),
                ])->values(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            @endphp

            <div style="margin-bottom: 1.25rem; font-size: 0.9rem; color: {{ $hasVariants ? '#64748b' : (($product->stock === null || $product->stock > 0) ? '#15803d' : '#b91c1c') }};">
                @if ($hasVariants)
                    Välj variant
                @elseif ($product->stock === null || $product->stock > 0)
                    ✓ {{ __('shop.product.in_stock') }}
                @else
                    ✗ {{ __('shop.product.out_of_stock') }}
                @endif
            </div>

            @if ($hasVariants)
                <div id="variant-picker" style="display: grid; gap: 0.85rem; margin-bottom: 1.5rem;"
                    data-variants='{!! $variantsJson !!}'>
                    @foreach ($axes as $axis => $values)
                        <div>
                            <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); margin-bottom: 0.4rem; font-weight: 500;">{{ $axis }}</div>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                                @foreach (array_keys($values) as $value)
                                    <button type="button"
                                        class="variant-option"
                                        data-axis="{{ $axis }}" data-value="{{ $value }}"
                                        style="padding: 0.45rem 0.85rem; border: 1px solid var(--border); border-radius: 8px; background: var(--card); cursor: pointer; font: inherit; font-size: 0.85rem; transition: all 0.15s;">
                                        {{ $value }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('cart.add', $product->slug) }}" style="display: flex; gap: 0.75rem; align-items: center;">
                @csrf
                <input type="hidden" name="variant_id" id="selected-variant-id" value="">
                <input type="number" name="qty" value="1" min="1" max="99" style="width: 70px; padding: 0.6rem 0.65rem; border: 1px solid var(--border); border-radius: 8px; font: inherit; text-align: center;">
                <button type="submit" id="add-to-cart-btn" class="btn btn-primary" {{ $hasVariants ? 'disabled' : '' }}>
                    {{ __('shop.cart.add') }}
                </button>
            </form>

            @if ($hasVariants)
                <script>
                    (function () {
                        const picker = document.getElementById('variant-picker');
                        const variants = JSON.parse(picker.dataset.variants);
                        const hiddenId = document.getElementById('selected-variant-id');
                        const btn = document.getElementById('add-to-cart-btn');
                        const selected = {};

                        picker.querySelectorAll('.variant-option').forEach(b => b.addEventListener('click', () => {
                            const axis = b.dataset.axis;
                            const value = b.dataset.value;
                            selected[axis] = value;

                            // Highlight chosen value, dim others on the same axis.
                            picker.querySelectorAll(`.variant-option[data-axis="${axis}"]`).forEach(other => {
                                const active = other === b;
                                other.style.borderColor = active ? 'var(--primary)' : 'var(--border)';
                                other.style.background = active ? '#eef2ff' : 'var(--card)';
                                other.style.color = active ? 'var(--primary)' : 'inherit';
                            });

                            // Find matching variant.
                            const match = variants.find(v => Object.keys(v.options).every(k => selected[k] === v.options[k]));
                            if (match) {
                                hiddenId.value = match.id;
                                btn.disabled = false;
                                btn.textContent = '{{ __('shop.cart.add') }} – ' + match.price;
                            } else {
                                hiddenId.value = '';
                                btn.disabled = true;
                            }
                        }));
                    })();
                </script>
            @endif

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

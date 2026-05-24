@php
    $title = $product->seoTitle();
    $description = $product->seoDescription();
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
        if ($product->brand) {
            $jsonLdProduct['brand'] = ['@type' => 'Brand', 'name' => $product->brand];
        }
        if ($product->gtin) {
            $jsonLdProduct['gtin'] = $product->gtin;
        }
        if ($product->reviewCount() > 0) {
            $jsonLdProduct['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format($product->averageRating(), 1, '.', ''),
                'reviewCount' => $product->reviewCount(),
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }
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

    {{-- Reviews --}}
    @if ((bool) setting('reviews.enabled', '1'))
        @php
            $reviews = $product->reviews;
            $avg = $product->averageRating();
            $count = $reviews->count();
        @endphp
        <section style="margin: 3rem 0 0; padding-top: 2rem; border-top: 1px solid var(--border);">
            <div style="display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.25rem;">Recensioner</h2>
                @if ($count > 0)
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        @include('shop._stars', ['rating' => $avg, 'size' => '1.1rem'])
                        <span style="font-weight: 600; font-variant-numeric: tabular-nums;">{{ number_format($avg, 1, ',', ' ') }}</span>
                        <span style="color: var(--muted); font-size: 0.9rem;">({{ $count }} {{ $count === 1 ? 'recension' : 'recensioner' }})</span>
                    </div>
                @endif
            </div>

            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2.5rem; align-items: start;">
                <div>
                    @if ($reviews->isEmpty())
                        <p style="color: var(--muted);">Inga recensioner än. Var den första!</p>
                    @else
                        <div style="display: grid; gap: 1.25rem;">
                            @foreach ($reviews->take(10) as $r)
                                <article style="background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 1rem 1.25rem;">
                                    <header style="display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin-bottom: 0.4rem;">
                                        <div style="display: inline-flex; align-items: center; gap: 0.55rem;">
                                            @include('shop._stars', ['rating' => $r->rating, 'size' => '0.95rem'])
                                            <strong style="font-size: 0.95rem;">{{ $r->name }}</strong>
                                            @if ($r->is_verified_purchase)
                                                <span style="font-size: 0.7rem; padding: 0.1rem 0.5rem; border-radius: 999px; background: #dcfce7; color: #15803d; font-weight: 600;">Verifierat köp</span>
                                            @endif
                                        </div>
                                        <time style="color: var(--muted); font-size: 0.8rem;" datetime="{{ $r->created_at->toIso8601String() }}">{{ $r->created_at->format('Y-m-d') }}</time>
                                    </header>
                                    @if ($r->title)
                                        <div style="font-weight: 600; margin-bottom: 0.25rem;">{{ $r->title }}</div>
                                    @endif
                                    @if ($r->body)
                                        <p style="margin: 0; color: var(--text);">{{ $r->body }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>

                <aside>
                    <div style="background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem;">
                        <h3 style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 1rem;">Skriv en recension</h3>

                        @if (session('status'))
                            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 0.55rem 0.85rem; border-radius: 8px; margin-bottom: 0.85rem; font-size: 0.875rem;">{{ session('status') }}</div>
                        @endif

                        @if ($errors->any())
                            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #7f1d1d; padding: 0.55rem 0.85rem; border-radius: 8px; margin-bottom: 0.85rem; font-size: 0.85rem;">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('shop.review.store', $product->slug) }}" style="display: grid; gap: 0.75rem;">
                            @csrf
                            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position: absolute; left: -9999px; top: -9999px;" aria-hidden="true">

                            <div>
                                <label style="font-size: 0.8rem; color: var(--muted); display: block; margin-bottom: 0.25rem;">Betyg</label>
                                <div class="star-input" style="display: inline-flex; gap: 0.15rem; font-size: 1.6rem; color: #e2e8f0; cursor: pointer;">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <label style="cursor: pointer; line-height: 1;">
                                            <input type="radio" name="rating" value="{{ $i }}" {{ old('rating') == $i ? 'checked' : '' }} required style="position: absolute; opacity: 0; pointer-events: none;">
                                            <span data-star="{{ $i }}">★</span>
                                        </label>
                                    @endfor
                                </div>
                            </div>

                            <label style="font-size: 0.85rem;">Namn
                                <input type="text" name="name" value="{{ old('name', auth('customer')->user()?->name ?? '') }}" required maxlength="120"
                                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                            </label>
                            <label style="font-size: 0.85rem;">E-post
                                <input type="email" name="email" value="{{ old('email', auth('customer')->user()?->email ?? '') }}" required
                                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                            </label>
                            <label style="font-size: 0.85rem;">Rubrik
                                <input type="text" name="title" value="{{ old('title') }}" maxlength="160"
                                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                            </label>
                            <label style="font-size: 0.85rem;">Din recension
                                <textarea name="body" rows="4" maxlength="4000"
                                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: 6px; font: inherit; resize: vertical;">{{ old('body') }}</textarea>
                            </label>
                            <button type="submit" class="btn btn-primary">Skicka recension</button>
                        </form>
                        <script>
                            (function () {
                                document.querySelectorAll('.star-input').forEach(group => {
                                    const stars = group.querySelectorAll('[data-star]');
                                    const inputs = group.querySelectorAll('input[type="radio"]');
                                    function paint(active) {
                                        stars.forEach((s, i) => s.style.color = (i + 1) <= active ? '#f59e0b' : '#e2e8f0');
                                    }
                                    stars.forEach((s, i) => {
                                        const label = s.closest('label');
                                        label.addEventListener('mouseenter', () => paint(i + 1));
                                        label.addEventListener('mouseleave', () => {
                                            const checked = Array.from(inputs).findIndex(r => r.checked);
                                            paint(checked >= 0 ? checked + 1 : 0);
                                        });
                                        label.addEventListener('click', () => paint(i + 1));
                                    });
                                    const initial = Array.from(inputs).findIndex(r => r.checked);
                                    if (initial >= 0) paint(initial + 1);
                                });
                            })();
                        </script>
                    </div>
                </aside>
            </div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <h2 style="font-size: 1.1rem; margin: 3rem 0 1rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em;">Liknande produkter</h2>
        <div class="product-grid">
            @foreach ($related as $r)
                @include('shop._product-card', ['product' => $r])
            @endforeach
        </div>
    @endif
@endsection

@extends('layouts.shop')

@section('content')
    <div class="page-head">
        <h1>{{ __('shop.cart.title') }}</h1>
    </div>

    @if (! $cart || $cart->items->isEmpty())
        <p style="color: var(--muted); margin-bottom: 1.5rem;">{{ __('shop.cart.empty') }}</p>
        <a class="btn btn-primary" href="{{ url('/') }}">{{ __('shop.cart.continue_shopping') }}</a>
    @else
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 10px; overflow: hidden;">
            @foreach ($cart->items as $item)
                <div style="display: grid; grid-template-columns: 60px 1fr auto auto auto; gap: 1rem; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border);">
                    <div style="aspect-ratio: 1/1; border-radius: 6px; overflow: hidden; background: linear-gradient(135deg, #f5f5f4 0%, #e7e5e4 100%); display: flex; align-items: center; justify-content: center; color: #d6d3d1; font-size: 1.25rem;">
                        @if ($item->product?->imageUrl())
                            <img src="{{ $item->product->imageUrl() }}" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            🛍
                        @endif
                    </div>
                    <div>
                        <a href="{{ route('shop.product', $item->product->slug) }}" style="font-weight: 600;">{{ $item->product->localized('name') }}</a>
                        @if ($item->variant)
                            <span style="color: var(--muted); font-size: 0.85rem;"> · {{ $item->variant->label() }}</span>
                        @endif
                        <div style="color: var(--muted); font-size: 0.85rem;">{{ App\Support\Money::format($item->price_snapshot, $cart->currency) }} / st</div>
                    </div>
                    <form method="POST" action="{{ route('cart.update', $item) }}" style="display: flex; gap: 0.4rem; align-items: center;">
                        @csrf @method('PATCH')
                        <input type="number" name="qty" value="{{ $item->qty }}" min="1" max="99" style="width: 60px; padding: 0.35rem; border: 1px solid var(--border); border-radius: 6px; font: inherit; text-align: center;">
                        <button type="submit" class="btn-link">{{ __('shop.cart.update') }}</button>
                    </form>
                    <div style="font-weight: 600; min-width: 100px; text-align: right;">{{ App\Support\Money::format($item->lineTotal(), $cart->currency) }}</div>
                    <form method="POST" action="{{ route('cart.remove', $item) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-link" style="color: #b91c1c;">{{ __('shop.cart.remove') }}</button>
                    </form>
                </div>
            @endforeach

            {{-- Discount code --}}
            <div style="padding: 1rem 1.25rem; border-top: 1px solid var(--border);">
                @if ($totals['discount_code'] ?? null)
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 0.55rem 0.85rem; font-size: 0.9rem;">
                        <span><strong>{{ $totals['discount_code'] }}</strong> aktiverad — du sparar {{ App\Support\Money::format($totals['discount'], $cart->currency) }}</span>
                        <form method="POST" action="{{ route('cart.discount.remove') }}" style="display: inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-link" style="color: #b91c1c;">Ta bort</button>
                        </form>
                    </div>
                @else
                    @if ($errors->has('discount'))
                        <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 0.55rem 0.85rem; border-radius: 8px; margin-bottom: 0.5rem; color: #7f1d1d; font-size: 0.85rem;">
                            {{ $errors->first('discount') }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('cart.discount.apply') }}" style="display: flex; gap: 0.5rem; align-items: center;">
                        @csrf
                        <input type="text" name="code" placeholder="Rabattkod" value="{{ old('code') }}"
                            style="flex: 1; max-width: 220px; padding: 0.5rem 0.7rem; border: 1px solid var(--border); border-radius: 8px; font: inherit; font-size: 0.875rem; text-transform: uppercase;">
                        <button type="submit" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Använd kod</button>
                    </form>
                @endif
            </div>

            <div style="padding: 1rem 1.25rem 0.5rem; display: grid; grid-template-columns: auto auto; gap: 0.4rem 1rem; justify-content: end; text-align: right;">
                <div style="color: var(--muted);">{{ __('shop.totals.subtotal') }}</div>
                <div>{{ App\Support\Money::format($totals['subtotal'], $cart->currency) }}</div>
                <div style="color: var(--muted);">{{ __('shop.totals.vat') }}</div>
                <div>{{ App\Support\Money::format($totals['vat'], $cart->currency) }}</div>
                @if (($totals['discount'] ?? 0) > 0)
                    <div style="color: #15803d;">Rabatt ({{ $totals['discount_code'] }})</div>
                    <div style="color: #15803d;">−{{ App\Support\Money::format($totals['discount'], $cart->currency) }}</div>
                @endif
                <div style="font-weight: 700; font-size: 1.1rem;">{{ __('shop.totals.total') }}</div>
                <div style="font-weight: 700; font-size: 1.1rem; color: var(--price);">{{ App\Support\Money::format($totals['grand'], $cart->currency) }}</div>
            </div>

            <div style="padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                <a class="btn btn-secondary" href="{{ url('/') }}">{{ __('shop.cart.continue_shopping') }}</a>
                <a class="btn btn-primary" href="{{ route('checkout.show') }}">{{ __('shop.cart.checkout') }} →</a>
            </div>
        </div>
    @endif
@endsection

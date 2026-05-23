@extends('layouts.shop')

@section('content')
    <div class="page-head">
        <h1>{{ __('shop.checkout.title') }}</h1>
    </div>

    @if ($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; color: #7f1d1d;">
            <ul style="margin-left: 1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
        @csrf

        <div style="display: grid; gap: 1rem;">
            <div style="background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem;">
                <h2 style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 1rem;">{{ __('shop.checkout.contact') }}</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <label style="font-size: 0.85rem;">Namn
                        <input type="text" name="name" value="{{ old('name') }}" required style="display: block; width: 100%; padding: 0.55rem 0.7rem; margin-top: 0.25rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                    </label>
                    <label style="font-size: 0.85rem;">E-post
                        <input type="email" name="email" value="{{ old('email') }}" required style="display: block; width: 100%; padding: 0.55rem 0.7rem; margin-top: 0.25rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                    </label>
                    <label style="font-size: 0.85rem; grid-column: 1 / -1;">Telefon
                        <input type="tel" name="phone" value="{{ old('phone') }}" style="display: block; width: 100%; padding: 0.55rem 0.7rem; margin-top: 0.25rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                    </label>
                </div>
            </div>

            <div style="background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem;">
                <h2 style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 1rem;">{{ __('shop.checkout.shipping') }}</h2>
                <div style="display: grid; gap: 0.75rem;">
                    <label style="font-size: 0.85rem;">Gata
                        <input type="text" name="street" value="{{ old('street') }}" required style="display: block; width: 100%; padding: 0.55rem 0.7rem; margin-top: 0.25rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                    </label>
                    <div style="display: grid; grid-template-columns: 120px 1fr 120px; gap: 0.75rem;">
                        <label style="font-size: 0.85rem;">Postnummer
                            <input type="text" name="zip" value="{{ old('zip') }}" required style="display: block; width: 100%; padding: 0.55rem 0.7rem; margin-top: 0.25rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                        </label>
                        <label style="font-size: 0.85rem;">Ort
                            <input type="text" name="city" value="{{ old('city') }}" required style="display: block; width: 100%; padding: 0.55rem 0.7rem; margin-top: 0.25rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                        </label>
                        <label style="font-size: 0.85rem;">Land
                            <select name="country" style="display: block; width: 100%; padding: 0.55rem 0.7rem; margin-top: 0.25rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                                @foreach (['SE' => 'Sverige', 'NO' => 'Norge', 'DK' => 'Danmark', 'FI' => 'Finland'] as $code => $name)
                                    <option value="{{ $code }}" {{ old('country', 'SE') === $code ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label style="font-size: 0.85rem;">Meddelande (valfritt)
                        <textarea name="notes" rows="2" style="display: block; width: 100%; padding: 0.55rem 0.7rem; margin-top: 0.25rem; border: 1px solid var(--border); border-radius: 6px; font: inherit; resize: vertical;">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </div>
        </div>

        <aside style="background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem; position: sticky; top: 5rem;">
            <h2 style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 0.75rem;">Sammanfattning</h2>
            @foreach ($cart->items as $item)
                <div style="display: flex; justify-content: space-between; gap: 0.5rem; padding: 0.35rem 0; font-size: 0.875rem;">
                    <span>{{ $item->qty }} × {{ $item->product->localized('name') }}</span>
                    <span>{{ App\Support\Money::format($item->lineTotal(), $cart->currency) }}</span>
                </div>
            @endforeach
            <div style="border-top: 1px solid var(--border); margin-top: 0.5rem; padding-top: 0.75rem; display: grid; grid-template-columns: auto auto; gap: 0.4rem 1rem; text-align: right; font-size: 0.875rem;">
                <div style="color: var(--muted);">{{ __('shop.totals.subtotal') }}</div>
                <div>{{ App\Support\Money::format($totals['subtotal'], $cart->currency) }}</div>
                <div style="color: var(--muted);">{{ __('shop.totals.vat') }}</div>
                <div>{{ App\Support\Money::format($totals['vat'], $cart->currency) }}</div>
                <div style="font-weight: 700; font-size: 1rem;">{{ __('shop.totals.total') }}</div>
                <div style="font-weight: 700; font-size: 1rem; color: var(--price);">{{ App\Support\Money::format($totals['grand'], $cart->currency) }}</div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.25rem;">{{ __('shop.checkout.place_order') }}</button>
            <p style="font-size: 0.75rem; color: var(--muted); margin-top: 0.75rem; text-align: center;">
                Betalning sker via faktura (demo). Riktiga betalsätt kommer i Fas 4.
            </p>
        </aside>
    </form>
@endsection

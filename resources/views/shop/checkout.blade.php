@extends('layouts.shop')

@section('content')
    <div class="page-head">
        <h1>{{ __('shop.checkout.title') }}</h1>
    </div>

    @guest('customer')
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 0.65rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem;">
            Har du redan ett konto? <a href="{{ route('customer.login') }}" style="color: var(--primary); font-weight: 500; text-decoration: underline;">Logga in</a> för snabbare checkout.
        </div>
    @endguest

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
            @php
                $cardStyle = 'background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 1.25rem;';
                $h2Style = 'font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 1rem;';
                $inputStyle = 'display: block; width: 100%; padding: 0.55rem 0.7rem; margin-top: 0.25rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;';
                $labelStyle = 'font-size: 0.85rem;';
            @endphp

            <div style="{{ $cardStyle }}">
                <h2 style="{{ $h2Style }}">{{ __('shop.checkout.contact') }}</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <label style="{{ $labelStyle }}">Namn
                        <input type="text" name="name" value="{{ old('name', $prefill['name']) }}" required style="{{ $inputStyle }}">
                    </label>
                    <label style="{{ $labelStyle }}">E-post
                        <input type="email" name="email" value="{{ old('email', $prefill['email']) }}" required style="{{ $inputStyle }}">
                    </label>
                    <label style="{{ $labelStyle }} grid-column: 1 / -1;">Telefon
                        <input type="tel" name="phone" value="{{ old('phone', $prefill['phone']) }}" style="{{ $inputStyle }}">
                    </label>
                </div>
            </div>

            <div style="{{ $cardStyle }}">
                <h2 style="{{ $h2Style }}">{{ __('shop.checkout.shipping') }}</h2>
                <div style="display: grid; gap: 0.75rem;">
                    <label style="{{ $labelStyle }}">Gata
                        <input type="text" name="street" value="{{ old('street', $prefill['street']) }}" required style="{{ $inputStyle }}">
                    </label>
                    <div style="display: grid; grid-template-columns: 120px 1fr 120px; gap: 0.75rem;">
                        <label style="{{ $labelStyle }}">Postnummer
                            <input type="text" name="zip" value="{{ old('zip', $prefill['zip']) }}" required style="{{ $inputStyle }}">
                        </label>
                        <label style="{{ $labelStyle }}">Ort
                            <input type="text" name="city" value="{{ old('city', $prefill['city']) }}" required style="{{ $inputStyle }}">
                        </label>
                        <label style="{{ $labelStyle }}">Land
                            <select name="country" style="{{ $inputStyle }}">
                                @foreach (['SE' => 'Sverige', 'NO' => 'Norge', 'DK' => 'Danmark', 'FI' => 'Finland'] as $code => $name)
                                    <option value="{{ $code }}" {{ old('country', $prefill['country']) === $code ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <label style="{{ $labelStyle }}">Meddelande (valfritt)
                        <textarea name="notes" rows="2" style="{{ $inputStyle }} resize: vertical;">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </div>

            <div style="{{ $cardStyle }}">
                <h2 style="{{ $h2Style }}">Frakt</h2>
                <div style="display: grid; gap: 0.5rem;">
                    @foreach ($shipping_options as $opt)
                        @php
                            $cost = $opt->cost($cart);
                            $isSelected = old('shipping_method', $selected_shipping) === $opt->code();
                        @endphp
                        <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; border: 1px solid {{ $isSelected ? 'var(--primary)' : 'var(--border)' }}; border-radius: 8px; cursor: pointer; background: {{ $isSelected ? '#eef2ff' : 'transparent' }};">
                            <input type="radio" name="shipping_method" value="{{ $opt->code() }}" {{ $isSelected ? 'checked' : '' }} style="margin-top: 0.2rem;" onchange="this.form.requestSubmit ? null : null">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; display: flex; justify-content: space-between;">
                                    <span>{{ $opt->label() }}</span>
                                    <span>{{ $cost > 0 ? App\Support\Money::format($cost, $cart->currency) : 'Gratis' }}</span>
                                </div>
                                <div style="color: var(--muted); font-size: 0.85rem;">{{ $opt->description() }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="{{ $cardStyle }}">
                <h2 style="{{ $h2Style }}">{{ __('shop.checkout.payment') }}</h2>
                <div style="display: grid; gap: 0.5rem;">
                    @foreach ($payments as $gateway)
                        @php $isSelected = old('payment_method', $selected_payment) === $gateway->code(); @endphp
                        <label style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; border: 1px solid {{ $isSelected ? 'var(--primary)' : 'var(--border)' }}; border-radius: 8px; cursor: pointer; background: {{ $isSelected ? '#eef2ff' : 'transparent' }};">
                            <input type="radio" name="payment_method" value="{{ $gateway->code() }}" {{ $isSelected ? 'checked' : '' }} style="margin-top: 0.2rem;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600;">{{ $gateway->label() }}</div>
                                <div style="color: var(--muted); font-size: 0.85rem;">{{ $gateway->description() }}</div>
                            </div>
                        </label>
                    @endforeach
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
                <div style="color: var(--muted);">{{ __('shop.totals.shipping') }}</div>
                <div>{{ ($totals['shipping'] ?? 0) > 0 ? App\Support\Money::format($totals['shipping'], $cart->currency) : 'Gratis' }}</div>
                <div style="color: var(--muted);">{{ __('shop.totals.vat') }}</div>
                <div>{{ App\Support\Money::format($totals['vat'], $cart->currency) }}</div>
                <div style="font-weight: 700; font-size: 1rem;">{{ __('shop.totals.total') }}</div>
                <div style="font-weight: 700; font-size: 1rem; color: var(--price);">{{ App\Support\Money::format($totals['grand'], $cart->currency) }}</div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.25rem;">{{ __('shop.checkout.place_order') }}</button>
            <p style="font-size: 0.75rem; color: var(--muted); margin-top: 0.75rem; text-align: center;">
                Fraktpriset uppdateras när du slutför beställningen.
            </p>
        </aside>
    </form>
@endsection

@extends('layouts.shop')

@section('content')
    <div class="breadcrumbs">
        <a href="{{ route('account.show') }}">Mitt konto</a> ·
        <a href="{{ route('account.orders') }}">Ordrar</a> ·
        {{ $order->order_number }}
    </div>

    <div class="page-head">
        <h1>{{ $order->order_number }}</h1>
        <p class="lead">Beställd {{ $order->placed_at?->format('Y-m-d H:i') }} · Status: <strong>{{ __('shop.order.status.' . $order->status) }}</strong></p>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start;">
        {{-- Items --}}
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden;">
            @foreach ($order->items as $item)
                <div style="display: grid; grid-template-columns: 1fr auto auto; gap: 1rem; padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; align-items: center;">
                    <div>
                        <div style="font-weight: 600;">{{ $item->name_snapshot }}</div>
                        @if ($item->sku_snapshot)
                            <div style="color: var(--muted); font-size: 0.8rem;">{{ $item->sku_snapshot }}</div>
                        @endif
                    </div>
                    <div style="color: var(--muted);">{{ $item->qty }} × {{ App\Support\Money::format($item->unit_price_incl_vat, $order->currency) }}</div>
                    <div style="font-weight: 600; font-variant-numeric: tabular-nums;">{{ App\Support\Money::format($item->line_total_incl_vat, $order->currency) }}</div>
                </div>
            @endforeach

            <div style="padding: 1rem 1.25rem; display: grid; grid-template-columns: auto auto; gap: 0.35rem 1rem; justify-content: end; text-align: right; font-size: 0.875rem;">
                <div style="color: var(--muted);">Delsumma</div>
                <div>{{ App\Support\Money::format($order->subtotal_excl_vat, $order->currency) }}</div>
                <div style="color: var(--muted);">Moms</div>
                <div>{{ App\Support\Money::format($order->vat_total, $order->currency) }}</div>
                @if ((float) $order->shipping_total > 0)
                    <div style="color: var(--muted);">Frakt</div>
                    <div>{{ App\Support\Money::format($order->shipping_total, $order->currency) }}</div>
                @endif
                <div style="font-weight: 700; font-size: 1rem;">Totalt</div>
                <div style="font-weight: 700; font-size: 1rem; color: var(--price);">{{ App\Support\Money::format($order->grand_total, $order->currency) }}</div>
            </div>
        </div>

        {{-- Shipping address --}}
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem;">
            <h2 style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.075em; color: var(--muted); margin-bottom: 0.5rem;">Levereras till</h2>
            @if ($order->shipping_address)
                <div style="font-size: 0.9rem; line-height: 1.55;">
                    {{ $order->shipping_address['name'] ?? '' }}<br>
                    {{ $order->shipping_address['street'] ?? '' }}<br>
                    {{ $order->shipping_address['zip'] ?? '' }} {{ $order->shipping_address['city'] ?? '' }}<br>
                    {{ $order->shipping_address['country'] ?? '' }}
                </div>
            @endif

            <h2 style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.075em; color: var(--muted); margin: 1.25rem 0 0.5rem;">Betalning</h2>
            <div style="font-size: 0.9rem;">
                {{ $order->payment_method }} <span style="color: var(--muted);">({{ $order->payment_status }})</span>
            </div>
            <h2 style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.075em; color: var(--muted); margin: 1.25rem 0 0.5rem;">Frakt</h2>
            <div style="font-size: 0.9rem;">
                {{ $order->shipping_method }} <span style="color: var(--muted);">({{ $order->shipping_status }})</span>
            </div>
        </div>
    </div>
@endsection

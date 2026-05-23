@extends('layouts.shop')

@section('content')
    <div style="max-width: 640px; margin: 2rem auto;">
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 2rem; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;">✓</div>
            <h1 style="font-size: 1.75rem; margin-bottom: 0.5rem;">{{ __('shop.checkout.order_received') }}</h1>
            <p style="color: var(--muted); margin-bottom: 2rem;">{{ __('shop.order.number') }}: <strong>{{ $order->order_number }}</strong></p>

            <div style="text-align: left; background: var(--accent); border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
                @foreach ($order->items as $item)
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0; font-size: 0.9rem;">
                        <span>{{ $item->qty }} × {{ $item->name_snapshot }}</span>
                        <span>{{ App\Support\Money::format($item->line_total_incl_vat, $order->currency) }}</span>
                    </div>
                @endforeach
                <div style="display: flex; justify-content: space-between; padding-top: 0.75rem; margin-top: 0.5rem; border-top: 1px solid var(--border); font-weight: 700;">
                    <span>{{ __('shop.totals.total') }}</span>
                    <span style="color: var(--price);">{{ App\Support\Money::format($order->grand_total, $order->currency) }}</span>
                </div>
            </div>

            <p style="color: var(--muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
                En bekräftelse skickas till <strong>{{ $order->email }}</strong>.
            </p>

            <a class="btn btn-primary" href="{{ url('/') }}">{{ __('shop.cart.continue_shopping') }}</a>
        </div>
    </div>
@endsection

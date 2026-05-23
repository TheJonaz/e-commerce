@extends('layouts.shop')

@section('content')
    <div class="page-head">
        <h1>Mitt konto</h1>
        <p class="lead">Hej {{ $customer->name }}!</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
        {{-- Profile card --}}
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem;">
            <h2 style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.075em; color: var(--muted); margin-bottom: 0.75rem;">Profil</h2>
            <div style="font-size: 0.9rem; line-height: 1.7;">
                <div><strong>{{ $customer->name }}</strong></div>
                <div style="color: var(--muted);">{{ $customer->email }}</div>
                @if ($customer->phone)
                    <div style="color: var(--muted);">{{ $customer->phone }}</div>
                @endif
                @if ($customer->is_business)
                    <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--primary);">⚐ Företagskonto</div>
                    @if ($customer->vat_number)
                        <div style="color: var(--muted); font-size: 0.8rem;">VAT: {{ $customer->vat_number }}</div>
                    @endif
                @endif
            </div>

            <form method="POST" action="{{ route('customer.logout') }}" style="margin-top: 1.25rem;">
                @csrf
                <button type="submit" class="btn btn-secondary" style="width: 100%;">Logga ut</button>
            </form>
        </div>

        {{-- Recent orders --}}
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.75rem;">
                <h2 style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.075em; color: var(--muted);">Senaste ordrar</h2>
                <a href="{{ route('account.orders') }}" style="font-size: 0.85rem; color: var(--primary);">Visa alla →</a>
            </div>

            @if ($recentOrders->isEmpty())
                <p style="color: var(--muted); font-size: 0.9rem;">Du har inga beställningar än. <a href="{{ url('/') }}" style="color: var(--primary);">Börja handla</a>.</p>
            @else
                <div style="display: grid; gap: 0.5rem;">
                    @foreach ($recentOrders as $o)
                        <a href="{{ route('account.order', $o->order_number) }}" style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 0.75rem; align-items: center; padding: 0.6rem 0.85rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.875rem;">
                            <span style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 600;">{{ $o->order_number }}</span>
                            <span style="color: var(--muted);">{{ $o->placed_at?->format('Y-m-d') }}</span>
                            <span style="font-weight: 600; font-variant-numeric: tabular-nums;">{{ App\Support\Money::format($o->grand_total, $o->currency) }}</span>
                            <span style="font-size: 0.75rem; padding: 0.15rem 0.5rem; border-radius: 999px; background: {{ $o->status === 'paid' || $o->status === 'delivered' ? '#dcfce7' : ($o->status === 'cancelled' ? '#fee2e2' : '#fef3c7') }}; color: {{ $o->status === 'paid' || $o->status === 'delivered' ? '#15803d' : ($o->status === 'cancelled' ? '#b91c1c' : '#a16207') }};">{{ __('shop.order.status.' . $o->status) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

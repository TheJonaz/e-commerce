@extends('layouts.shop')

@section('content')
    <div class="breadcrumbs">
        <a href="{{ route('account.show') }}">Mitt konto</a> · Ordrar
    </div>
    <div class="page-head">
        <h1>Mina ordrar</h1>
    </div>

    @if ($orders->isEmpty())
        <p style="color: var(--muted);">Du har inga beställningar än.</p>
    @else
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden;">
            @foreach ($orders as $o)
                <a href="{{ route('account.order', $o->order_number) }}" style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 1rem; align-items: center; padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border); font-size: 0.9rem;">
                    <div>
                        <div style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 600;">{{ $o->order_number }}</div>
                        <div style="color: var(--muted); font-size: 0.8rem;">{{ $o->placed_at?->format('Y-m-d H:i') }}</div>
                    </div>
                    <span style="font-weight: 600; font-variant-numeric: tabular-nums;">{{ App\Support\Money::format($o->grand_total, $o->currency) }}</span>
                    <span style="color: var(--muted); font-size: 0.8rem;">{{ $o->shipping_method }}</span>
                    <span style="font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 999px; background: {{ $o->status === 'paid' || $o->status === 'delivered' ? '#dcfce7' : ($o->status === 'cancelled' ? '#fee2e2' : '#fef3c7') }}; color: {{ $o->status === 'paid' || $o->status === 'delivered' ? '#15803d' : ($o->status === 'cancelled' ? '#b91c1c' : '#a16207') }};">{{ __('shop.order.status.' . $o->status) }}</span>
                </a>
            @endforeach
        </div>

        @if ($orders->hasPages())
            <div style="margin-top: 1.5rem;">{{ $orders->withQueryString()->links() }}</div>
        @endif
    @endif
@endsection

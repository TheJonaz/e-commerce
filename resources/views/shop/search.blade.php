@extends('layouts.shop')

@section('content')
    <div class="page-head">
        <h1>
            @if ($q !== '')
                Sökresultat för "{{ $q }}"
            @else
                Sök
            @endif
        </h1>
        @if ($q !== '' && ! $products->isEmpty())
            <p class="lead">{{ $products->total() }} träff{{ $products->total() === 1 ? '' : 'ar' }}</p>
        @endif
    </div>

    <form method="GET" action="{{ route('shop.search') }}" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
        <input
            type="search" name="q" value="{{ $q }}" placeholder="Sök produkter…" autofocus
            style="flex: 1; padding: 0.65rem 0.9rem; border: 1px solid var(--border); border-radius: 8px; font: inherit;">
        <button type="submit" class="btn btn-primary">Sök</button>
    </form>

    @if ($q === '')
        <p style="color: var(--muted);">Skriv vad du letar efter — produktnamn, SKU eller beskrivning.</p>
    @elseif ($products->isEmpty())
        <p style="color: var(--muted);">Inga träffar för "{{ $q }}". Försök med ett annat sökord.</p>
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                @include('shop._product-card', ['product' => $product])
            @endforeach
        </div>

        @if ($products->hasPages())
            <div style="margin-top: 2rem;">{{ $products->withQueryString()->links() }}</div>
        @endif
    @endif
@endsection

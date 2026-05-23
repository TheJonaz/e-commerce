@extends('layouts.shop')

@section('content')
    <div class="breadcrumbs">
        <a href="{{ url('/') }}">Hem</a> · {{ $category->localized('name') }}
    </div>
    <div class="page-head">
        <h1>{{ $category->localized('name') }}</h1>
        @if ($category->localized('description'))
            <p class="lead">{{ $category->localized('description') }}</p>
        @endif
    </div>

    @if ($products->isEmpty())
        <p style="color: var(--muted);">Inga produkter i denna kategori än.</p>
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

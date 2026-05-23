@extends('layouts.shop')

@section('content')
    <div class="page-head">
        <h1>{{ setting('shop.name', config('app.name')) }}</h1>
        <p class="lead">@lang('shop.cart.continue_shopping')</p>
    </div>

    @if ($categories->isNotEmpty())
        <div class="cat-list">
            @foreach ($categories as $cat)
                <a href="{{ route('shop.category', $cat->slug) }}" class="cat-pill">{{ $cat->localized('name') }}</a>
            @endforeach
        </div>
    @endif

    <div class="product-grid">
        @foreach ($featured as $product)
            @include('shop._product-card', ['product' => $product])
        @endforeach
    </div>
@endsection

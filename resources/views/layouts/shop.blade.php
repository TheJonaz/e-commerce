<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? setting('shop.name', config('app.name')) }}</title>
    <style>
        :root {
            --bg: #fafaf9;
            --card: #ffffff;
            --border: #e7e5e4;
            --text: #1a1a1a;
            --muted: #78716c;
            --primary: #1d4ed8;
            --primary-hover: #1e40af;
            --accent: #f5f5f4;
            --price: #15803d;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { background: var(--bg); color: var(--text); }
        body { font: 15px/1.55 -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }

        header.site {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 0;
            position: sticky; top: 0; z-index: 50;
        }
        .container { max-width: 1100px; margin: 0 auto; padding: 0 1.25rem; }
        .topbar { display: flex; align-items: center; gap: 1.5rem; }
        .brand { font-size: 1.15rem; font-weight: 700; letter-spacing: -0.01em; }
        nav.main { display: flex; gap: 1.25rem; flex: 1; font-size: 0.9rem; }
        nav.main a { color: var(--muted); transition: color 0.15s; }
        nav.main a:hover, nav.main a.active { color: var(--text); }
        .cart-link { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.45rem 0.9rem; border: 1px solid var(--border); border-radius: 8px; font-size: 0.875rem; transition: background 0.15s; }
        .cart-link:hover { background: var(--accent); }
        .cart-badge { background: var(--primary); color: white; border-radius: 999px; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: 600; }

        main { padding: 2rem 0 4rem; }
        .page-head { margin-bottom: 1.5rem; }
        .page-head h1 { font-size: 1.75rem; letter-spacing: -0.01em; margin-bottom: 0.25rem; }
        .page-head .lead { color: var(--muted); }
        .breadcrumbs { color: var(--muted); font-size: 0.85rem; margin-bottom: 0.75rem; }
        .breadcrumbs a:hover { color: var(--text); }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.25rem; }
        .product-card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; transition: transform 0.15s, box-shadow 0.15s; }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .product-card .ph { background: linear-gradient(135deg, #f5f5f4 0%, #e7e5e4 100%); aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center; color: #d6d3d1; font-size: 2.5rem; }
        .product-card .body { padding: 0.85rem 1rem 1rem; }
        .product-card .name { font-weight: 600; font-size: 0.95rem; margin-bottom: 0.25rem; }
        .product-card .price { color: var(--price); font-weight: 600; }
        .product-card .vat { color: var(--muted); font-size: 0.75rem; }

        .cat-list { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem; }
        .cat-pill { padding: 0.4rem 0.9rem; background: var(--card); border: 1px solid var(--border); border-radius: 999px; font-size: 0.85rem; transition: background 0.15s; }
        .cat-pill:hover, .cat-pill.active { background: var(--text); color: white; border-color: var(--text); }

        .flash { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 0.65rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; }

        .btn { display: inline-block; padding: 0.65rem 1.25rem; border: 0; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; transition: background 0.15s, color 0.15s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-secondary { background: var(--card); color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--accent); }
        .btn-link { background: transparent; color: var(--muted); padding: 0.35rem 0.5rem; font-weight: 500; font-size: 0.85rem; }
        .btn-link:hover { color: var(--text); }

        footer.site { border-top: 1px solid var(--border); padding: 2rem 0; color: var(--muted); font-size: 0.85rem; }
        footer.site a { color: var(--muted); border-bottom: 1px dotted #cbd5e1; }
        footer.site a:hover { color: var(--primary); }
    </style>
</head>
<body>
    <header class="site">
        <div class="container topbar">
            <a class="brand" href="{{ url('/') }}">{{ setting('shop.name', config('app.name')) }}</a>
            <nav class="main">
                <a href="{{ url('/') }}" class="{{ request()->path() === '/' ? 'active' : '' }}">Hem</a>
                @foreach ($categories ?? [] as $cat)
                    <a href="{{ route('shop.category', $cat->slug) }}" class="{{ request()->routeIs('shop.category') && request()->route('slug') === $cat->slug ? 'active' : '' }}">{{ $cat->localized('name') }}</a>
                @endforeach
            </nav>
            <a class="cart-link" href="{{ route('cart.show') }}">
                {{ __('shop.cart.title') }}
                @php $cartCount = app(\App\Support\CartService::class)->totals()['count']; @endphp
                @if ($cartCount > 0)
                    <span class="cart-badge">{{ $cartCount }}</span>
                @endif
            </a>
        </div>
    </header>

    <main>
        <div class="container">
            @if (session('status'))
                <div class="flash">{{ session('status') }}</div>
            @endif
            @yield('content')
        </div>
    </main>

    <footer class="site">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>{{ setting('shop.name', config('app.name')) }} · {{ setting('shop.currency', 'SEK') }}</div>
                <div>
                    by <a href="https://www.thern.io" target="_blank" rel="noopener noreferrer">Thern AI Solutions</a>
                    · <a href="{{ url('/admin') }}" target="_blank">Admin</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>

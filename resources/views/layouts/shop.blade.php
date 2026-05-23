<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        .cart-badge { background: var(--primary); color: white; border-radius: 999px; padding: 0.1rem 0.5rem; font-size: 0.75rem; font-weight: 600; transition: transform 0.2s ease; }
        .cart-badge.bump { transform: scale(1.25); }

        .toast-stack { position: fixed; top: 4.25rem; right: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; z-index: 100; pointer-events: none; }
        .toast {
            background: #15803d; color: white; padding: 0.65rem 1rem; border-radius: 10px;
            font-size: 0.875rem; box-shadow: 0 8px 24px -8px rgba(21, 128, 61, 0.45);
            display: flex; align-items: center; gap: 0.5rem;
            pointer-events: auto;
            transform: translateX(20px); opacity: 0;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .toast.shown { transform: translateX(0); opacity: 1; }
        .toast::before { content: '✓'; font-weight: 700; }

        main { padding: 2rem 0 4rem; }
        .page-head { margin-bottom: 1.5rem; }
        .page-head h1 { font-size: 1.75rem; letter-spacing: -0.01em; margin-bottom: 0.25rem; }
        .page-head .lead { color: var(--muted); }
        .breadcrumbs { color: var(--muted); font-size: 0.85rem; margin-bottom: 0.75rem; }
        .breadcrumbs a:hover { color: var(--text); }

        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem; }
        .product-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            display: flex; flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative;
        }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.18); border-color: #cbd5e1; }
        .product-card-media {
            position: relative; display: block;
            aspect-ratio: 1/1;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
            overflow: hidden;
        }
        .product-card-media img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease; }
        .product-card:hover .product-card-media img { transform: scale(1.05); }
        .product-card-placeholder {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            color: #cbd5e1; font-size: 2.75rem;
        }
        .product-card-badge {
            position: absolute; top: 0.65rem; left: 0.65rem;
            font-size: 0.7rem; font-weight: 600;
            padding: 0.2rem 0.55rem; border-radius: 999px;
            text-transform: uppercase; letter-spacing: 0.04em;
            backdrop-filter: blur(4px);
        }
        .product-card-badge--out { background: rgba(239, 68, 68, 0.92); color: white; }
        .product-card-badge--low { background: rgba(245, 158, 11, 0.92); color: white; }
        .product-card-quickadd {
            position: absolute; bottom: 0.65rem; right: 0.65rem;
            opacity: 0; transform: translateY(6px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .product-card:hover .product-card-quickadd { opacity: 1; transform: translateY(0); }
        .product-card-quickadd button {
            width: 2.25rem; height: 2.25rem; border: 0; border-radius: 999px;
            background: var(--primary); color: white;
            font: inherit; font-size: 1.35rem; font-weight: 400; line-height: 1;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(29, 78, 216, 0.35);
            transition: background 0.15s, transform 0.1s;
        }
        .product-card-quickadd button:hover { background: var(--primary-hover); }
        .product-card-quickadd button:active { transform: scale(0.92); }
        .product-card-body { display: flex; flex-direction: column; padding: 0.85rem 1rem 1rem; gap: 0.15rem; flex: 1; }
        .product-card-cat { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); font-weight: 500; }
        .product-card-name {
            font-weight: 600; font-size: 0.95rem; line-height: 1.3;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-card-foot { display: flex; align-items: baseline; gap: 0.4rem; margin-top: auto; padding-top: 0.4rem; }
        .product-card-price { color: var(--price); font-weight: 700; font-size: 1.05rem; font-variant-numeric: tabular-nums; }
        .product-card-vat { color: var(--muted); font-size: 0.7rem; }

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
            <div style="display: inline-flex; gap: 0.5rem; align-items: center;">
                @auth('customer')
                    <a href="{{ route('account.show') }}" style="padding: 0.45rem 0.85rem; font-size: 0.85rem; color: var(--muted); transition: color 0.15s;" onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">{{ auth('customer')->user()->name }}</a>
                @else
                    <a href="{{ route('customer.login') }}" style="padding: 0.45rem 0.85rem; font-size: 0.85rem; color: var(--muted); transition: color 0.15s;" onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">Logga in</a>
                @endauth
                <a class="cart-link" href="{{ route('cart.show') }}">
                    {{ __('shop.cart.title') }}
                    @php $cartCount = app(\App\Support\CartService::class)->totals()['count']; @endphp
                    <span class="cart-badge" id="cart-badge" data-count="{{ $cartCount }}" @if ($cartCount === 0) style="display:none" @endif>{{ $cartCount }}</span>
                </a>
            </div>
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

    <div class="toast-stack" id="toast-stack" aria-live="polite"></div>

    <footer class="site">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>{{ setting('shop.name', config('app.name')) }} · {{ setting('shop.currency', 'SEK') }}</div>
                <div>
                    by <a href="https://www.thern.io" target="_blank" rel="noopener noreferrer">Thern AI Solutions</a>
                    @auth
                        @if (auth()->user()->isAdmin())
                            · <a href="{{ url('/admin') }}" target="_blank">Admin</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </footer>

    <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const stack = document.getElementById('toast-stack');
            const badge = document.getElementById('cart-badge');

            function toast(msg) {
                if (! stack) return;
                const el = document.createElement('div');
                el.className = 'toast';
                el.textContent = msg;
                stack.appendChild(el);
                requestAnimationFrame(() => el.classList.add('shown'));
                setTimeout(() => {
                    el.classList.remove('shown');
                    setTimeout(() => el.remove(), 200);
                }, 2200);
            }

            function bumpBadge(count) {
                if (! badge) return;
                badge.textContent = count;
                badge.dataset.count = count;
                badge.style.display = count > 0 ? '' : 'none';
                badge.classList.remove('bump');
                requestAnimationFrame(() => badge.classList.add('bump'));
                setTimeout(() => badge.classList.remove('bump'), 220);
            }

            document.addEventListener('submit', async (e) => {
                const form = e.target.closest('.product-card-quickadd');
                if (! form) return;
                e.preventDefault();

                const url = form.getAttribute('action');
                const fd = new FormData(form);
                const btn = form.querySelector('button');
                if (btn) btn.disabled = true;

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd,
                    });
                    if (! res.ok) throw new Error('http ' + res.status);
                    const body = await res.json();
                    bumpBadge(body.count);
                    toast(body.product + ' tillagd i varukorgen');
                } catch (err) {
                    toast('Kunde inte lägga till — försök igen');
                } finally {
                    if (btn) btn.disabled = false;
                }
            });
        })();
    </script>
</body>
</html>

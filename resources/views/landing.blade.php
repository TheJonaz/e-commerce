<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1a1a1a; background: #fafaf9; line-height: 1.6; }
        .container { max-width: 880px; margin: 0 auto; padding: 4rem 1.5rem; }
        h1 { font-size: 2.5rem; margin-bottom: 0.5rem; letter-spacing: -0.02em; }
        .lead { color: #666; font-size: 1.1rem; margin-bottom: 3rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 3rem; }
        .stat { background: white; border: 1px solid #e7e5e4; border-radius: 8px; padding: 1.5rem; }
        .stat .num { font-size: 2rem; font-weight: 600; }
        .stat .label { color: #666; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .links { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 500; transition: background 0.15s; }
        .btn-primary { background: #1d4ed8; color: white; }
        .btn-primary:hover { background: #1e40af; }
        .btn-secondary { background: white; color: #1a1a1a; border: 1px solid #d6d3d1; }
        .btn-secondary:hover { background: #f5f5f4; }
        h2 { font-size: 1.1rem; margin: 3rem 0 1rem; color: #666; text-transform: uppercase; letter-spacing: 0.05em; }
        .tenants { background: white; border: 1px solid #e7e5e4; border-radius: 8px; padding: 1rem; }
        .tenant { padding: 0.5rem 0; border-bottom: 1px solid #f5f5f4; }
        .tenant:last-child { border: 0; }
        code { background: #f5f5f4; padding: 0.15em 0.4em; border-radius: 3px; font-size: 0.9em; }
        footer { margin-top: 4rem; padding-top: 2rem; border-top: 1px solid #e7e5e4; color: #999; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ config('app.name') }}</h1>
        <p class="lead">Modulär, självhostbar webshop. Den här sidan är platshållaren — riktig butiksvy kommer i Fas 3.</p>

        <div class="grid">
            <div class="stat">
                <div class="num">{{ $stats['tenants'] }}</div>
                <div class="label">Butiker</div>
            </div>
            <div class="stat">
                <div class="num">{{ $stats['products'] }}</div>
                <div class="label">Produkter</div>
            </div>
            <div class="stat">
                <div class="num">{{ $stats['orders'] }}</div>
                <div class="label">Ordrar</div>
            </div>
        </div>

        <div class="links">
            <a class="btn btn-primary" href="{{ url('/admin') }}">Öppna admin</a>
            <a class="btn btn-secondary" href="https://github.com/TheJonaz/e-commerce" target="_blank">GitHub</a>
        </div>

        @if ($tenants->isNotEmpty())
            <h2>Butiker</h2>
            <div class="tenants">
                @foreach ($tenants as $tenant)
                    <div class="tenant">
                        <strong>{{ $tenant->name }}</strong> — <code>{{ $tenant->slug }}</code> — {{ $tenant->currency }} / {{ $tenant->locale }}
                    </div>
                @endforeach
            </div>
        @endif

        <footer>
            Open E-commerce · MIT · byggd på Laravel, körs på din egen server
        </footer>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install — Open E-commerce</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #222; }
        h1 { font-size: 1.8rem; margin-bottom: 0.25rem; }
        h2 { font-size: 1.1rem; margin-top: 2rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem; }
        .lead { color: #666; margin-top: 0; }
        .check { display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px dashed #eee; }
        .ok { color: #15803d; }
        .fail { color: #b91c1c; font-weight: 600; }
        label { display: block; margin-top: 1rem; font-weight: 500; }
        input, select { display: block; width: 100%; padding: 0.5rem; margin-top: 0.25rem; border: 1px solid #ccc; border-radius: 4px; font: inherit; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        button { margin-top: 2rem; padding: 0.7rem 1.5rem; background: #1d4ed8; color: white; border: 0; border-radius: 4px; font: inherit; font-weight: 600; cursor: pointer; }
        button:hover { background: #1e40af; }
        .errors { background: #fee2e2; border: 1px solid #fca5a5; padding: 0.75rem; border-radius: 4px; margin: 1rem 0; }
        .errors ul { margin: 0.25rem 0 0 1.25rem; }
        .check-list { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.9rem; }
        small { color: #666; font-weight: 400; }
    </style>
</head>
<body>
    <h1>Install Open E-commerce</h1>
    <p class="lead">One-time setup. This page is disabled after installation completes.</p>

    @if ($errors->any())
        <div class="errors">
            <strong>Please fix the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h2>Environment</h2>
    <div class="check-list">
        @foreach ($checks as $check)
            <div class="check">
                <span>{{ $check['label'] }}</span>
                <span class="{{ $check['ok'] ? 'ok' : 'fail' }}">{{ $check['ok'] ? '✓' : '✗' }} {{ $check['value'] }}</span>
            </div>
        @endforeach
    </div>

    <form method="POST" action="/install">
        @csrf

        <h2>Database</h2>
        <label>Connection
            <select name="db_connection" id="db_connection">
                <option value="mysql" {{ ($old['db_connection'] ?? 'mysql') === 'mysql' ? 'selected' : '' }}>MySQL / MariaDB</option>
                <option value="sqlite" {{ ($old['db_connection'] ?? '') === 'sqlite' ? 'selected' : '' }}>SQLite (file)</option>
            </select>
        </label>
        <div class="row">
            <label>Host
                <input type="text" name="db_host" value="{{ $old['db_host'] ?? '127.0.0.1' }}">
            </label>
            <label>Port
                <input type="number" name="db_port" value="{{ $old['db_port'] ?? '3306' }}">
            </label>
        </div>
        <label>Database name <small>(for SQLite: absolute path or "database/database.sqlite")</small>
            <input type="text" name="db_database" value="{{ $old['db_database'] ?? '' }}" required>
        </label>
        <div class="row">
            <label>Username
                <input type="text" name="db_username" value="{{ $old['db_username'] ?? '' }}">
            </label>
            <label>Password
                <input type="password" name="db_password" value="">
            </label>
        </div>

        <h2>Admin account</h2>
        <label>Name
            <input type="text" name="admin_name" value="{{ $old['admin_name'] ?? '' }}" required>
        </label>
        <div class="row">
            <label>Email
                <input type="email" name="admin_email" value="{{ $old['admin_email'] ?? '' }}" required>
            </label>
            <label>Password <small>(min 8 chars)</small>
                <input type="password" name="admin_password" required minlength="8">
            </label>
        </div>

        <h2>First shop (tenant)</h2>
        <div class="row">
            <label>Slug <small>(used in subdomain)</small>
                <input type="text" name="tenant_slug" value="{{ $old['tenant_slug'] ?? 'shop' }}" pattern="[a-z0-9-]+" required>
            </label>
            <label>Display name
                <input type="text" name="tenant_name" value="{{ $old['tenant_name'] ?? '' }}" required>
            </label>
        </div>
        <div class="row">
            <label>Currency
                <select name="tenant_currency">
                    @foreach (['SEK', 'EUR', 'USD', 'NOK', 'DKK'] as $cur)
                        <option value="{{ $cur }}" {{ ($old['tenant_currency'] ?? 'SEK') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                    @endforeach
                </select>
            </label>
            <label>Locale
                <select name="tenant_locale">
                    <option value="sv" {{ ($old['tenant_locale'] ?? 'sv') === 'sv' ? 'selected' : '' }}>Svenska</option>
                    <option value="en" {{ ($old['tenant_locale'] ?? '') === 'en' ? 'selected' : '' }}>English</option>
                </select>
            </label>
        </div>

        <label style="margin-top: 1.5rem;">
            <input type="checkbox" name="seed_demo" value="1" {{ old('seed_demo') ? 'checked' : '' }} style="width: auto; display: inline; margin-right: 0.5rem;">
            Seed with demo data (20 products, 5 categories, 3 customers)
        </label>

        <button type="submit">Install</button>
    </form>
</body>
</html>

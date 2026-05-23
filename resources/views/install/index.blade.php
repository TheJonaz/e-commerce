<!DOCTYPE html>
<html lang="sv" id="html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="title">Installera Open E-commerce</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --ok: #15803d;
            --fail: #b91c1c;
            --shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 1px 3px rgba(15, 23, 42, 0.06);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { background: var(--bg); }
        body {
            font: 14px/1.5 -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text);
            max-width: 880px;
            margin: 0 auto;
            padding: 2rem 1.25rem 4rem;
        }
        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.01em; }
        .lead { color: var(--muted); font-size: 0.875rem; margin-top: 0.15rem; }
        .lang { display: inline-flex; background: var(--card); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; box-shadow: var(--shadow); }
        .lang button {
            padding: 0.4rem 0.85rem; background: transparent; border: 0; cursor: pointer; font: inherit; font-weight: 500; color: var(--muted);
        }
        .lang button.active { background: var(--primary); color: white; }
        .errors { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.875rem; }
        .errors strong { color: #991b1b; display: block; margin-bottom: 0.25rem; }
        .errors ul { margin-left: 1.25rem; color: #7f1d1d; }
        .grid { display: grid; gap: 1rem; grid-template-columns: 1fr 1fr; }
        .grid.three { grid-template-columns: repeat(3, 1fr); }
        @media (max-width: 720px) { .grid, .grid.three { grid-template-columns: 1fr; } }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1rem 1.1rem 1.1rem;
            box-shadow: var(--shadow);
        }
        .card.full { grid-column: 1 / -1; }
        .card h2 {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.075em;
            color: var(--muted);
            margin-bottom: 0.75rem;
        }
        .checks { display: grid; grid-template-columns: 1fr 1fr; gap: 0.15rem 1.5rem; font-size: 0.8125rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .check { display: flex; justify-content: space-between; padding: 0.2rem 0; }
        .check .label { color: var(--text); }
        .check .val.ok { color: var(--ok); }
        .check .val.fail { color: var(--fail); font-weight: 600; }
        label { display: block; font-size: 0.8125rem; font-weight: 500; margin-bottom: 0.6rem; color: var(--text); }
        label small { color: var(--muted); font-weight: 400; margin-left: 0.25rem; }
        label:last-child { margin-bottom: 0; }
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], select {
            display: block; width: 100%; margin-top: 0.25rem;
            padding: 0.5rem 0.65rem;
            border: 1px solid var(--border); border-radius: 6px;
            font: inherit; color: var(--text); background: var(--card);
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12); }
        .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
        .row3 { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 0.75rem; }
        .actions { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: 1.25rem; }
        .checkbox { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text); cursor: pointer; }
        .checkbox input { width: 16px; height: 16px; accent-color: var(--primary); }
        button.submit {
            padding: 0.65rem 1.25rem; background: var(--primary); color: white;
            border: 0; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer;
            transition: background 0.15s;
        }
        button.submit:hover { background: var(--primary-hover); }
    </style>
</head>
<body>
    <header class="top">
        <div>
            <h1 data-i18n="title">Installera Open E-commerce</h1>
            <p class="lead" data-i18n="lead">Engångskonfiguration. Sidan låses när installationen är klar.</p>
        </div>
        <div class="lang" role="tablist" aria-label="UI language">
            <button type="button" data-lang="sv" class="active">SV</button>
            <button type="button" data-lang="en">EN</button>
        </div>
    </header>

    @if ($errors->any())
        <div class="errors">
            <strong data-i18n="errors_title">Åtgärda följande:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid" style="margin-bottom: 1rem;">
        <div class="card full">
            <h2 data-i18n="env">Miljö</h2>
            <div class="checks">
                @foreach ($checks as $check)
                    <div class="check">
                        <span class="label">{{ $check['label'] }}</span>
                        <span class="val {{ $check['ok'] ? 'ok' : 'fail' }}">{{ $check['ok'] ? '✓' : '✗' }} {{ $check['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <form method="POST" action="/install">
        @csrf

        <div class="grid">
            <div class="card">
                <h2 data-i18n="database">Databas</h2>
                <label>
                    <span data-i18n="db_connection">Anslutning</span>
                    <select name="db_connection">
                        <option value="mysql" {{ ($old['db_connection'] ?? 'mysql') === 'mysql' ? 'selected' : '' }}>MySQL / MariaDB</option>
                        <option value="sqlite" {{ ($old['db_connection'] ?? '') === 'sqlite' ? 'selected' : '' }}>SQLite</option>
                    </select>
                </label>
                <div class="row2">
                    <label>
                        <span data-i18n="db_host">Host</span>
                        <input type="text" name="db_host" value="{{ $old['db_host'] ?? '127.0.0.1' }}">
                    </label>
                    <label>
                        <span data-i18n="db_port">Port</span>
                        <input type="number" name="db_port" value="{{ $old['db_port'] ?? '3306' }}">
                    </label>
                </div>
                <label>
                    <span data-i18n="db_database">Databasnamn</span>
                    <small data-i18n="db_database_hint">(SQLite: filsökväg)</small>
                    <input type="text" name="db_database" value="{{ $old['db_database'] ?? '' }}" required>
                </label>
                <div class="row2">
                    <label>
                        <span data-i18n="db_username">Användare</span>
                        <input type="text" name="db_username" value="{{ $old['db_username'] ?? '' }}">
                    </label>
                    <label>
                        <span data-i18n="db_password">Lösenord</span>
                        <input type="password" name="db_password" value="">
                    </label>
                </div>
            </div>

            <div class="card">
                <h2 data-i18n="admin">Administratör</h2>
                <label>
                    <span data-i18n="admin_name">Namn</span>
                    <input type="text" name="admin_name" value="{{ $old['admin_name'] ?? '' }}" required>
                </label>
                <label>
                    <span data-i18n="admin_email">E-post</span>
                    <input type="email" name="admin_email" value="{{ $old['admin_email'] ?? '' }}" required>
                </label>
                <label>
                    <span data-i18n="admin_password">Lösenord</span>
                    <small data-i18n="admin_password_hint">(minst 8 tecken)</small>
                    <input type="password" name="admin_password" required minlength="8">
                </label>
            </div>

            <div class="card full">
                <h2 data-i18n="shop">Butik</h2>
                <div class="row3">
                    <label>
                        <span data-i18n="shop_name">Butikens namn</span>
                        <input type="text" name="shop_name" value="{{ $old['shop_name'] ?? '' }}" required>
                    </label>
                    <label>
                        <span data-i18n="shop_currency">Valuta</span>
                        <select name="shop_currency">
                            @foreach (['SEK', 'EUR', 'USD', 'NOK', 'DKK'] as $cur)
                                <option value="{{ $cur }}" {{ ($old['shop_currency'] ?? 'SEK') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span data-i18n="shop_locale">Språk</span>
                        <select name="shop_locale">
                            <option value="sv" {{ ($old['shop_locale'] ?? 'sv') === 'sv' ? 'selected' : '' }}>Svenska</option>
                            <option value="en" {{ ($old['shop_locale'] ?? '') === 'en' ? 'selected' : '' }}>English</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <div class="actions">
            <label class="checkbox">
                <input type="checkbox" name="seed_demo" value="1" {{ old('seed_demo') ? 'checked' : '' }}>
                <span data-i18n="seed_demo">Fyll på med demo-data (20 produkter, 5 kategorier, 3 kunder)</span>
            </label>
            <button type="submit" class="submit" data-i18n="install">Installera</button>
        </div>
    </form>

    <script>
        const i18n = {
            sv: {
                title: 'Installera Open E-commerce',
                lead: 'Engångskonfiguration. Sidan låses när installationen är klar.',
                errors_title: 'Åtgärda följande:',
                env: 'Miljö',
                database: 'Databas',
                db_connection: 'Anslutning',
                db_host: 'Host',
                db_port: 'Port',
                db_database: 'Databasnamn',
                db_database_hint: '(SQLite: filsökväg)',
                db_username: 'Användare',
                db_password: 'Lösenord',
                admin: 'Administratör',
                admin_name: 'Namn',
                admin_email: 'E-post',
                admin_password: 'Lösenord',
                admin_password_hint: '(minst 8 tecken)',
                shop: 'Butik',
                shop_name: 'Butikens namn',
                shop_currency: 'Valuta',
                shop_locale: 'Språk',
                seed_demo: 'Fyll på med demo-data (20 produkter, 5 kategorier, 3 kunder)',
                install: 'Installera',
            },
            en: {
                title: 'Install Open E-commerce',
                lead: 'One-time setup. This page is disabled after installation completes.',
                errors_title: 'Please fix the following:',
                env: 'Environment',
                database: 'Database',
                db_connection: 'Connection',
                db_host: 'Host',
                db_port: 'Port',
                db_database: 'Database name',
                db_database_hint: '(SQLite: file path)',
                db_username: 'Username',
                db_password: 'Password',
                admin: 'Admin account',
                admin_name: 'Name',
                admin_email: 'Email',
                admin_password: 'Password',
                admin_password_hint: '(min 8 characters)',
                shop: 'Shop',
                shop_name: 'Shop name',
                shop_currency: 'Currency',
                shop_locale: 'Language',
                seed_demo: 'Seed with demo data (20 products, 5 categories, 3 customers)',
                install: 'Install',
            },
        };

        const STORAGE_KEY = 'install_ui_lang';
        const buttons = document.querySelectorAll('.lang button');
        const html = document.getElementById('html');

        function applyLang(lang) {
            const dict = i18n[lang] || i18n.sv;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (dict[key] !== undefined) el.textContent = dict[key];
            });
            html.setAttribute('lang', lang);
            buttons.forEach(b => b.classList.toggle('active', b.dataset.lang === lang));
            try { localStorage.setItem(STORAGE_KEY, lang); } catch (e) {}
        }

        buttons.forEach(b => b.addEventListener('click', () => applyLang(b.dataset.lang)));

        let initial = 'sv';
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored && i18n[stored]) initial = stored;
            else if ((navigator.language || 'sv').toLowerCase().startsWith('en')) initial = 'en';
        } catch (e) {}
        applyLang(initial);
    </script>
</body>
</html>

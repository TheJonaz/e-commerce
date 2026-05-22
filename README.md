# Open E-commerce

A modular, self-hostable web shop. **One install = one shop** (like WordPress / WooCommerce). Drop it into any directory served by PHP, open it in a browser, click through the installer.

> Status: early development. Not yet ready for production.

## Highlights

- **Drop-in install** — unzip into a folder, point your browser at it, fill in the form.
- **Modular** — payment gateways, shipping providers, product types and themes live under `modules/` as self-contained packages.
- **Swedish-market ready** — SEK, Swedish VAT (25/12/6 %), Klarna and Swish (planned).
- **Multi-language / multi-currency** — `sv` and `en` shipped, more easy to add.
- **Admin panel** included (Filament).

## Requirements

- PHP 8.2 or newer with extensions: `xml`, `mbstring`, `curl`, `pdo_mysql` (or `pdo_sqlite`), `zip`, `bcmath`, `intl`
- MySQL 8 / MariaDB 10.6+ **or** SQLite
- Apache (`mod_rewrite`) or Nginx
- Write permissions on `storage/` and the project root (so the installer can write `.env` and `install.lock`)

## Install from release zip (recommended)

The release zip already includes `vendor/`, so no Composer needed on the server.

1. Download the latest `open-ecommerce-X.Y.Z.zip` from the [releases page](https://github.com/TheJonaz/e-commerce/releases).
2. Unzip into your web root (e.g. `public_html/shop/` on Inleed-style FTP hosting).
3. Make sure `storage/` is writable by the web server user.
4. Open `https://your-domain.tld/shop/` in a browser.
5. The installer (`/install`) takes over: it runs environment checks, asks for database credentials, admin user and shop name, then locks itself.

## Install from git (for development)

```bash
git clone https://github.com/TheJonaz/e-commerce.git
cd e-commerce
composer install
cp .env.example .env
php artisan key:generate
chmod -R ug+w storage bootstrap/cache
```

Then point your web server at the project root (not at `public/` — there is no `public/`) and visit `/install`.

## Web server config

The project follows a **WordPress-style flat layout**: `index.php` is at the project root, sensitive directories are blocked by `.htaccess` (Apache) or location rules (Nginx).

### Apache

The bundled `.htaccess` handles routing and blocks sensitive paths. Just make sure `mod_rewrite` is enabled and `AllowOverride All` is set for the directory.

### Nginx (subdirectory)

```nginx
location ^~ /shop/.env    { deny all; return 404; }
location ^~ /shop/vendor/ { deny all; return 404; }
location ^~ /shop/app/    { deny all; return 404; }
# ... see deploy/nginx.example.conf for the full list

location ^~ /shop/ {
    alias /var/www/e-commerce/;
    index index.php;
    try_files $uri $uri/ @shop_fallback;
}

location ~ ^/shop/.*\.php$ {
    root /var/www/e-commerce;
    rewrite ^/shop/(.*)$ /$1 break;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param SCRIPT_NAME /shop/index.php;
}

location @shop_fallback {
    rewrite ^/shop(/.*)?$ /shop/index.php last;
}
```

## Project structure

```
index.php             Front controller (root level — WordPress-style)
.htaccess             Apache routing + deny rules
app/                  Domain models, controllers, Filament resources
modules/              Pluggable modules (payments, shipping, product-types, themes)
resources/views/      Blade templates (default theme + installer)
database/migrations/  Schema
storage/              Logs, cache, uploads, install.lock (writable)
vendor/               Composer dependencies (in releases; cloners run `composer install`)
```

## License

MIT — see [LICENSE](LICENSE).

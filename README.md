# Open E-commerce

A modular, self-hostable web shop built with Laravel. Designed to be easy to install on shared PHP hosting (FTP-deployable) and extensible via drop-in modules for payments, shipping, product types, and themes.

> Status: early development. Not yet ready for production.

## Highlights

- **Multi-tenant** — host many independent shops from one installation, one per subdomain.
- **Modular** — add payment gateways, shipping providers, product types, and themes as packages under `modules/`.
- **Swedish-market ready** — SEK, Swedish VAT (25/12/6 %), Klarna and Swish (planned).
- **Multi-language / multi-currency** out of the box.
- **FTP-deployable** — build locally, upload the artifact, run the web installer.

## Requirements

- PHP 8.2 or newer with extensions: `xml`, `mbstring`, `curl`, `mysql`/`mysqli`, `zip`, `bcmath`, `intl`
- MySQL 8 (or MariaDB 10.6+)
- A web server pointing its document root at `public/`

## Quick start (development)

```bash
git clone <repo-url> ecommerce
cd ecommerce
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Installation on shared hosting

A web-based installer at `/install` will guide you through database setup, the first admin user, and your first tenant shop. (Planned for Fas 1.)

## Project structure

```
app/                  Core domain (products, orders, carts, tenants, …)
modules/              Pluggable modules (see modules/README.md)
resources/views/      Default theme (Blade)
database/migrations/  Core schema
public/               Web root
install/              First-run installer
```

## License

MIT — see [LICENSE](LICENSE).

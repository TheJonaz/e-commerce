# Modules

Pluggable modules that extend the core e-commerce platform. Each module lives in its own subdirectory and registers itself via a Service Provider declared in `module.json`.

## Categories

- **payments/** — Payment gateways (Stripe, Klarna, Swish, invoice, …)
- **shipping/** — Shipping providers (PostNord, DHL, local pickup, …)
- **product-types/** — Product behaviors (physical, digital downloads, subscriptions, …)
- **themes/** — Storefront themes (Blade view overrides + assets)

## Module layout

```
modules/<category>/<name>/
├── module.json          # name, version, provider class, requires
├── src/                 # PHP code (PSR-4 autoloaded)
│   └── ServiceProvider.php
├── resources/           # views, lang, assets (optional)
├── database/migrations/ # module-specific migrations (optional)
└── README.md
```

Modules are auto-discovered at boot. Enable/disable per tenant via the admin panel.

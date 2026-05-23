<?php

namespace App\Modules;

use App\Modules\Contracts\ShippingProvider;

class ShippingRegistry
{
    /** @var array<string, ShippingProvider> */
    protected array $providers = [];

    public function register(ShippingProvider $provider): void
    {
        $this->providers[$provider->code()] = $provider;
    }

    /** @return array<string, ShippingProvider> */
    public function all(): array
    {
        return $this->providers;
    }

    public function find(string $code): ?ShippingProvider
    {
        return $this->providers[$code] ?? null;
    }

    public function default(): ?ShippingProvider
    {
        return $this->providers[array_key_first($this->providers)] ?? null;
    }
}

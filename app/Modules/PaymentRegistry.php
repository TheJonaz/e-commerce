<?php

namespace App\Modules;

use App\Modules\Contracts\PaymentGateway;

class PaymentRegistry
{
    /** @var array<string, PaymentGateway> */
    protected array $gateways = [];

    public function register(PaymentGateway $gateway): void
    {
        $this->gateways[$gateway->code()] = $gateway;
    }

    /** @return array<string, PaymentGateway> */
    public function all(): array
    {
        return $this->gateways;
    }

    public function find(string $code): ?PaymentGateway
    {
        return $this->gateways[$code] ?? null;
    }

    public function default(): ?PaymentGateway
    {
        return $this->gateways[array_key_first($this->gateways)] ?? null;
    }
}

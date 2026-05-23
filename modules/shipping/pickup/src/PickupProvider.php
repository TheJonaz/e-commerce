<?php

namespace Modules\Shipping\Pickup;

use App\Models\Cart;
use App\Modules\Contracts\ShippingProvider;

class PickupProvider implements ShippingProvider
{
    public function code(): string
    {
        return 'pickup';
    }

    public function label(): string
    {
        return 'Hämta i butik';
    }

    public function description(): string
    {
        $addr = setting('shipping.pickup.address', '');

        return $addr
            ? "Hämtning på {$addr}. Gratis."
            : 'Hämtning i butik. Gratis.';
    }

    public function cost(Cart $cart): float
    {
        return 0.0;
    }

    public function vatRate(): float
    {
        return 0.0;
    }
}

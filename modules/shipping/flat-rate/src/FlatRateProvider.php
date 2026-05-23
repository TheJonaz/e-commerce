<?php

namespace Modules\Shipping\FlatRate;

use App\Models\Cart;
use App\Modules\Contracts\ShippingProvider;

class FlatRateProvider implements ShippingProvider
{
    public function code(): string
    {
        return 'flat-rate';
    }

    public function label(): string
    {
        return 'Standardfrakt';
    }

    public function description(): string
    {
        $days = setting('shipping.flat_rate.lead_time', '2–5');

        return "Levereras inom {$days} arbetsdagar.";
    }

    public function cost(Cart $cart): float
    {
        $threshold = (float) setting('shipping.flat_rate.free_threshold', 0);
        $price = (float) setting('shipping.flat_rate.price', 49);

        if ($threshold > 0) {
            $subtotalIncl = (float) $cart->items->sum(fn ($i) => $i->qty * (float) $i->price_snapshot);
            if ($subtotalIncl >= $threshold) {
                return 0.0;
            }
        }

        return $price;
    }

    public function vatRate(): float
    {
        return (float) setting('shipping.flat_rate.vat_rate', 25.0);
    }
}

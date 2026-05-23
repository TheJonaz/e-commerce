<?php

namespace App\Modules\Contracts;

use App\Models\Cart;

interface ShippingProvider
{
    public function code(): string;

    public function label(): string;

    public function description(): string;

    /** Cost INCLUDING VAT in the cart currency. May depend on cart contents. */
    public function cost(Cart $cart): float;

    /** VAT rate applied to the shipping cost. */
    public function vatRate(): float;
}

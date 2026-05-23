<?php

namespace Modules\Shipping\Pickup;

use App\Modules\ShippingRegistry;
use Illuminate\Support\ServiceProvider;

class PickupServiceProvider extends ServiceProvider
{
    public function boot(ShippingRegistry $registry): void
    {
        $registry->register(new PickupProvider());
    }
}

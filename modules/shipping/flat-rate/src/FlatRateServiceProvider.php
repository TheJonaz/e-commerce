<?php

namespace Modules\Shipping\FlatRate;

use App\Modules\ShippingRegistry;
use Illuminate\Support\ServiceProvider;

class FlatRateServiceProvider extends ServiceProvider
{
    public function boot(ShippingRegistry $registry): void
    {
        $registry->register(new FlatRateProvider());
    }
}

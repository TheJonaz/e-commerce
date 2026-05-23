<?php

namespace Modules\Shipping\PostNord;

use App\Modules\ShippingRegistry;
use Illuminate\Support\ServiceProvider;

class PostNordServiceProvider extends ServiceProvider
{
    public function boot(ShippingRegistry $registry): void
    {
        $registry->register(new PostNordProvider());
    }
}

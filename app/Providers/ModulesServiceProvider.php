<?php

namespace App\Providers;

use App\Modules\ModuleManager;
use App\Modules\PaymentRegistry;
use App\Modules\ShippingRegistry;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentRegistry::class);
        $this->app->singleton(ShippingRegistry::class);

        $this->app->singleton(ModuleManager::class, function ($app) {
            return new ModuleManager($app, base_path('modules'));
        });

        $this->app->make(ModuleManager::class)->discover();
    }
}

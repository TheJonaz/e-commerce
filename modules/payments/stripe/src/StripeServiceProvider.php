<?php

namespace Modules\Payments\Stripe;

use App\Modules\PaymentRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class StripeServiceProvider extends ServiceProvider
{
    public function boot(PaymentRegistry $registry): void
    {
        // Only expose the gateway when a secret key is configured. Wrapped in
        // rescue() because boot can fire before the settings table exists
        // (fresh install, RefreshDatabase test bootstrap).
        $hasKey = rescue(fn () => (bool) setting('payment.stripe.secret_key'), false, report: false);
        if ($hasKey) {
            $registry->register(new StripeGateway());
        }

        Route::middleware('web')->group(function () {
            Route::get('/checkout/stripe/return/{orderNumber}', [StripeController::class, 'return'])
                ->name('stripe.return');
            Route::get('/checkout/stripe/cancel/{orderNumber}', [StripeController::class, 'cancel'])
                ->name('stripe.cancel');
        });

        Route::post('/webhooks/stripe', [StripeController::class, 'webhook'])
            ->name('stripe.webhook');
    }
}

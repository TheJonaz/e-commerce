<?php

namespace Modules\Payments\Swish;

use App\Modules\PaymentRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SwishServiceProvider extends ServiceProvider
{
    public function boot(PaymentRegistry $registry): void
    {
        // Need at least a payee Swish number to register.
        $configured = rescue(
            fn () => (bool) setting('payment.swish.payee_alias'),
            false,
            report: false,
        );

        if ($configured) {
            $registry->register(new SwishGateway());
        }

        Route::middleware('web')->group(function () {
            Route::get('/checkout/swish/return/{orderNumber}', [SwishController::class, 'return'])
                ->name('swish.return');
        });

        // Server-to-server callback from Swish. No CSRF.
        Route::post('/webhooks/swish/{orderNumber}', [SwishController::class, 'callback'])
            ->name('swish.callback');
    }
}

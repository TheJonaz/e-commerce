<?php

namespace Modules\Payments\Klarna;

use App\Modules\PaymentRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class KlarnaServiceProvider extends ServiceProvider
{
    public function boot(PaymentRegistry $registry): void
    {
        $configured = rescue(
            fn () => (bool) setting('payment.klarna.username') && (bool) setting('payment.klarna.password'),
            false,
            report: false,
        );

        if ($configured) {
            $registry->register(new KlarnaGateway());
        }

        Route::middleware('web')->group(function () {
            Route::get('/checkout/klarna/return/{orderNumber}', [KlarnaController::class, 'return'])
                ->name('klarna.return');
            Route::get('/checkout/klarna/cancel/{orderNumber}', [KlarnaController::class, 'cancel'])
                ->name('klarna.cancel');
        });

        Route::post('/webhooks/klarna/{orderNumber}', [KlarnaController::class, 'push'])
            ->name('klarna.push');
    }
}

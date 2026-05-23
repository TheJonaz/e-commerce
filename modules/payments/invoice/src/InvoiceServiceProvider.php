<?php

namespace Modules\Payments\Invoice;

use App\Modules\PaymentRegistry;
use Illuminate\Support\ServiceProvider;

class InvoiceServiceProvider extends ServiceProvider
{
    public function boot(PaymentRegistry $registry): void
    {
        $registry->register(new InvoiceGateway());
    }
}

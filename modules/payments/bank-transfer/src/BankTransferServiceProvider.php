<?php

namespace Modules\Payments\BankTransfer;

use App\Modules\PaymentRegistry;
use Illuminate\Support\ServiceProvider;

class BankTransferServiceProvider extends ServiceProvider
{
    public function boot(PaymentRegistry $registry): void
    {
        $registry->register(new BankTransferGateway());
    }
}

<?php

namespace App\Modules\Contracts;

use App\Models\Order;

interface PaymentGateway
{
    public function code(): string;

    public function label(): string;

    /** Optional explanatory copy shown next to the radio button in checkout. */
    public function description(): string;

    /**
     * Called after an order is created. May redirect to an external gateway
     * (return a URL string) or finalize immediately (return null).
     */
    public function process(Order $order): ?string;
}

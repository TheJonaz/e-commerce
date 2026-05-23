<?php

namespace Modules\Payments\Invoice;

use App\Models\Order;
use App\Modules\Contracts\PaymentGateway;

class InvoiceGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'invoice';
    }

    public function label(): string
    {
        return 'Faktura';
    }

    public function description(): string
    {
        return 'Faktura skickas till din e-post. Betalningsvillkor 30 dagar.';
    }

    public function process(Order $order): ?string
    {
        $order->payment_status = 'awaiting_invoice';
        $order->save();

        return null;
    }
}

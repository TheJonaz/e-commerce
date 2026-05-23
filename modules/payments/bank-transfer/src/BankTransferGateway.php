<?php

namespace Modules\Payments\BankTransfer;

use App\Models\Order;
use App\Modules\Contracts\PaymentGateway;

class BankTransferGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'bank-transfer';
    }

    public function label(): string
    {
        return 'Direktbetalning till bankkonto';
    }

    public function description(): string
    {
        $bg = setting('payment.bank_transfer.bg', '');

        return $bg
            ? "Sätt in på BG {$bg}. Ange ordernummer som referens."
            : 'Bankuppgifter visas efter beställning. Ange ordernummer som referens.';
    }

    public function process(Order $order): ?string
    {
        $order->payment_status = 'awaiting_transfer';
        $order->save();

        return null;
    }
}

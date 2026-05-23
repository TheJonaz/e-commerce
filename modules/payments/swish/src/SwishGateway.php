<?php

namespace Modules\Payments\Swish;

use App\Models\Order;
use App\Modules\Contracts\PaymentGateway;

class SwishGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'swish';
    }

    public function label(): string
    {
        return 'Swish';
    }

    public function description(): string
    {
        $testMode = (string) setting('payment.swish.test_mode', '1') === '1';
        $suffix = $testMode ? ' (test-läge — använd Swish-simulatorn).' : '';

        return 'Betala direkt med Swish-appen på din mobil.' . $suffix;
    }

    public function process(Order $order): ?string
    {
        $payeeAlias = preg_replace('/[^0-9]/', '', (string) setting('payment.swish.payee_alias', ''));
        $certPath = (string) setting('payment.swish.cert_path', '');

        if (! $payeeAlias) {
            report(new \RuntimeException('Swish: payee_alias not configured'));
            return null;
        }
        if (! is_file($certPath)) {
            report(new \RuntimeException('Swish: cert_path missing or unreadable: ' . $certPath));
            return null;
        }

        $instructionId = SwishClient::instructionId();
        $payload = [
            'payeeAlias' => $payeeAlias,
            'amount' => number_format((float) $order->grand_total, 2, '.', ''),
            'currency' => $order->currency,
            'message' => substr('Order ' . $order->order_number, 0, 50),
            'callbackUrl' => route('swish.callback', $order->order_number),
        ];

        try {
            $resp = SwishClient::http()->put('/paymentrequests/' . $instructionId, $payload);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }

        if (! $resp->successful()) {
            report(new \RuntimeException('Swish create failed: ' . $resp->status() . ' ' . $resp->body()));
            return null;
        }

        $token = (string) $resp->header('PaymentRequestToken');
        if (! $token) {
            // E-commerce flow (no token) needs a phone number we don't collect at this stage.
            // For now we only support the M-commerce / token flow.
            report(new \RuntimeException('Swish response missing PaymentRequestToken header'));
            return null;
        }

        $order->payment_reference = $instructionId;
        $order->payment_status = 'awaiting_payment';
        $order->save();

        return SwishClient::paymentRequestUrl($token) . '&callbackurl=' . urlencode(route('swish.return', $order->order_number));
    }
}

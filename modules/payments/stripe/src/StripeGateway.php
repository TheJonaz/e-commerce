<?php

namespace Modules\Payments\Stripe;

use App\Models\Order;
use App\Modules\Contracts\PaymentGateway;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class StripeGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Kort (Stripe)';
    }

    public function description(): string
    {
        $testMode = setting('payment.stripe.test_mode', '1') === '1';
        $modeNote = $testMode ? ' Testkort: 4242 4242 4242 4242, valfri framtida datum, valfri CVC.' : '';

        return 'Visa, Mastercard, Amex via Stripe.' . $modeNote;
    }

    public function process(Order $order): ?string
    {
        $secret = setting('payment.stripe.secret_key');
        if (! $secret) {
            return null;
        }

        $stripe = new StripeClient($secret);

        $lineItems = [];
        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => (int) round((float) $item->unit_price_incl_vat * 100),
                    'product_data' => [
                        'name' => $item->name_snapshot,
                        'metadata' => $item->sku_snapshot ? ['sku' => $item->sku_snapshot] : [],
                    ],
                ],
                'quantity' => $item->qty,
            ];
        }

        if ((float) $order->shipping_total > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => (int) round((float) $order->shipping_total * 100),
                    'product_data' => ['name' => 'Frakt'],
                ],
                'quantity' => 1,
            ];
        }

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'customer_email' => $order->email,
            'client_reference_id' => $order->order_number,
            'success_url' => route('stripe.return', $order->order_number) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('stripe.cancel', $order->order_number),
            'metadata' => [
                'order_number' => $order->order_number,
                'order_id' => (string) $order->id,
            ],
        ]);

        $order->payment_reference = $session->id;
        $order->payment_status = 'awaiting_payment';
        $order->save();

        return $session->url;
    }
}

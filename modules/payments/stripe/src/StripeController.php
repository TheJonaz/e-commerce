<?php

namespace Modules\Payments\Stripe;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeController extends Controller
{
    /** Customer returns here after a successful Stripe Checkout. */
    public function return(Request $request, string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $sessionId = (string) $request->query('session_id');

        $secret = setting('payment.stripe.secret_key');
        if ($secret && $sessionId) {
            try {
                $stripe = new StripeClient($secret);
                $session = $stripe->checkout->sessions->retrieve($sessionId);
                if ($session && $session->payment_status === 'paid') {
                    $this->markPaid($order, $session->payment_intent);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('checkout.thanks', $order->order_number);
    }

    /** Customer clicked cancel on the Stripe Checkout page. */
    public function cancel(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $order->payment_status = 'cancelled';
        $order->status = Order::STATUS_CANCELLED;
        $order->save();

        return redirect()->route('cart.show')->with('status', 'Betalningen avbröts.');
    }

    /** Server-to-server confirmation from Stripe — the source of truth. */
    public function webhook(Request $request): Response
    {
        $secret = setting('payment.stripe.webhook_secret');
        $payload = $request->getContent();
        $sig = (string) $request->header('Stripe-Signature');

        if (! $secret) {
            return response('webhook secret not configured', 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            report($e);
            return response('invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderNumber = $session->client_reference_id ?? ($session->metadata->order_number ?? null);
            if ($orderNumber && ($order = Order::where('order_number', $orderNumber)->first())) {
                $this->markPaid($order, $session->payment_intent);
            }
        }

        return response('ok', 200);
    }

    protected function markPaid(Order $order, ?string $paymentIntent): void
    {
        if ($order->payment_status === 'paid') {
            return;
        }

        $order->payment_status = 'paid';
        $order->status = Order::STATUS_PAID;
        if ($paymentIntent) {
            $order->payment_reference = $paymentIntent;
        }
        $order->save();
    }
}

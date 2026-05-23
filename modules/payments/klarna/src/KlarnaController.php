<?php

namespace Modules\Payments\Klarna;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class KlarnaController extends Controller
{
    /** Customer returned from Klarna with an authorization_token — finalize the order. */
    public function return(Request $request, string $orderNumber)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();
        $token = (string) $request->query('authorization_token');

        if ($token && $order->payment_status !== 'paid') {
            try {
                $resp = KlarnaClient::http()->post(
                    '/payments/v1/authorizations/' . $token . '/order',
                    KlarnaClient::orderPayload($order)
                );

                if ($resp->successful()) {
                    $order->payment_reference = (string) ($resp->json('order_id') ?: $order->payment_reference);
                    $fraud = (string) $resp->json('fraud_status');
                    if ($fraud === 'ACCEPTED') {
                        $order->payment_status = 'paid';
                        $order->status = Order::STATUS_PAID;
                    } else {
                        $order->payment_status = 'pending_review';
                    }
                    $order->save();
                } else {
                    report(new \RuntimeException('Klarna place order failed: ' . $resp->body()));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('checkout.thanks', $order->order_number);
    }

    public function cancel(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        if ($order->payment_status === 'paid') {
            return redirect()->route('checkout.thanks', $order->order_number);
        }

        $order->status = Order::STATUS_CANCELLED;
        $order->payment_status = 'cancelled';
        $order->save();

        return redirect()->route('cart.show')->with('status', 'Betalningen avbröts.');
    }

    /** Server-to-server status update from Klarna. */
    public function push(Request $request, string $orderNumber): Response
    {
        $order = Order::where('order_number', $orderNumber)->first();
        if (! $order) {
            return response('order not found', 404);
        }

        $klarnaOrderId = (string) $request->query('order_id', $order->payment_reference);
        if (! $klarnaOrderId) {
            return response('missing order id', 400);
        }

        try {
            $resp = KlarnaClient::http()->get('/ordermanagement/v1/orders/' . $klarnaOrderId);
            if (! $resp->successful()) {
                return response('lookup failed', 200);
            }

            $status = (string) $resp->json('fraud_status');
            $captured = (bool) $resp->json('captured_amount');

            if ($status === 'ACCEPTED' || $captured) {
                if ($order->payment_status !== 'paid') {
                    $order->payment_status = 'paid';
                    $order->status = Order::STATUS_PAID;
                    $order->save();
                }
            } elseif ($status === 'REJECTED') {
                $order->payment_status = 'declined';
                $order->status = Order::STATUS_CANCELLED;
                $order->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return response('ok', 200);
    }
}

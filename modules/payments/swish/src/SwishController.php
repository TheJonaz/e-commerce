<?php

namespace Modules\Payments\Swish;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SwishController extends Controller
{
    /** Customer returned from Swish app — payment may already be confirmed via callback. */
    public function return(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        return redirect()->route('checkout.thanks', $order->order_number);
    }

    /** Server-to-server callback from Swish CPC API. POSTs the full payment object as JSON. */
    public function callback(Request $request, string $orderNumber): Response
    {
        $order = Order::where('order_number', $orderNumber)->first();
        if (! $order) {
            return response('order not found', 404);
        }

        $status = (string) $request->input('status');
        $paymentReference = (string) $request->input('paymentReference', '');
        $errorCode = (string) $request->input('errorCode', '');

        switch ($status) {
            case 'PAID':
                if ($order->payment_status !== 'paid') {
                    $order->payment_status = 'paid';
                    $order->status = Order::STATUS_PAID;
                    if ($paymentReference) {
                        $order->payment_reference = $paymentReference;
                    }
                    $order->save();
                }
                break;

            case 'DECLINED':
            case 'CANCELLED':
                $order->payment_status = 'declined';
                $order->status = Order::STATUS_CANCELLED;
                $order->save();
                break;

            case 'ERROR':
                report(new \RuntimeException('Swish ERROR for ' . $orderNumber . ': ' . $errorCode));
                $order->payment_status = 'error';
                $order->save();
                break;
        }

        return response('ok', 200);
    }
}

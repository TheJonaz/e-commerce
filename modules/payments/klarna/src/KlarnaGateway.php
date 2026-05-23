<?php

namespace Modules\Payments\Klarna;

use App\Models\Order;
use App\Modules\Contracts\PaymentGateway;

class KlarnaGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'klarna';
    }

    public function label(): string
    {
        return 'Klarna';
    }

    public function description(): string
    {
        $testMode = (string) setting('payment.klarna.test_mode', '1') === '1';
        $suffix = $testMode ? ' (test-läge — använd Klarnas testkort/personnummer)' : '';

        return 'Faktura, delbetalning eller kort via Klarna.' . $suffix;
    }

    public function process(Order $order): ?string
    {
        $merchantUrls = [
            'success' => route('klarna.return', $order->order_number) . '?authorization_token={authorization_token}',
            'cancel' => route('klarna.cancel', $order->order_number),
            'back' => route('klarna.cancel', $order->order_number),
            'failure' => route('klarna.cancel', $order->order_number),
            'error' => route('klarna.cancel', $order->order_number),
            'status_update' => route('klarna.push', $order->order_number),
        ];

        try {
            // 1. Create a Payments session (holds order + customer)
            $sessionPayload = KlarnaClient::orderPayload($order);
            $sessionResp = KlarnaClient::http()->post('/payments/v1/sessions', $sessionPayload);
            if (! $sessionResp->successful()) {
                report(new \RuntimeException('Klarna session create failed: ' . $sessionResp->body()));
                return null;
            }
            $sessionId = $sessionResp->json('session_id');

            // 2. Wrap that session in a Hosted Payment Page session
            $hppResp = KlarnaClient::http()->post('/hpp/v1/sessions', [
                'payment_session_url' => KlarnaClient::baseUrl() . '/payments/v1/sessions/' . $sessionId,
                'merchant_urls' => $merchantUrls,
                'options' => [
                    'background_images' => [],
                    'logo_url' => null,
                    'page_title' => setting('shop.name', config('app.name')),
                    'purchase_type' => 'buy',
                ],
            ]);
            if (! $hppResp->successful()) {
                report(new \RuntimeException('Klarna HPP create failed: ' . $hppResp->body()));
                return null;
            }

            $order->payment_reference = (string) $hppResp->json('session_id');
            $order->payment_status = 'awaiting_payment';
            $order->save();

            return (string) $hppResp->json('redirect_url');
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}

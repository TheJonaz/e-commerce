<?php

namespace Modules\Payments\Klarna;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** Tiny HTTP wrapper around the Klarna REST API. */
class KlarnaClient
{
    public static function baseUrl(): string
    {
        return (string) setting('payment.klarna.test_mode', '1') === '1'
            ? 'https://api.playground.klarna.com'
            : 'https://api.klarna.com';
    }

    public static function http(): PendingRequest
    {
        return Http::withBasicAuth(
            (string) setting('payment.klarna.username'),
            (string) setting('payment.klarna.password'),
        )
            ->acceptJson()
            ->asJson()
            ->baseUrl(self::baseUrl())
            ->timeout(15);
    }

    /** Build the order_lines / amounts payload from an Order. */
    public static function orderPayload(Order $order, array $merchantUrls = []): array
    {
        $lines = [];
        foreach ($order->items as $item) {
            $unit = (int) round((float) $item->unit_price_incl_vat * 100);
            $totalGross = (int) round((float) $item->line_total_incl_vat * 100);
            $totalTax = (int) round((float) $item->line_vat_amount * 100);

            $lines[] = [
                'type' => 'physical',
                'reference' => (string) ($item->sku_snapshot ?: $item->id),
                'name' => $item->name_snapshot,
                'quantity' => $item->qty,
                'unit_price' => $unit,
                'tax_rate' => (int) round((float) $item->vat_rate * 100),
                'total_amount' => $totalGross,
                'total_tax_amount' => $totalTax,
            ];
        }

        if ((float) $order->shipping_total > 0) {
            $gross = (int) round((float) $order->shipping_total * 100);
            // Assume Swedish 25% shipping VAT unless configured otherwise.
            $rate = (int) round(((float) setting('shipping.flat_rate.vat_rate', '25')) * 100);
            $tax = $rate > 0 ? (int) round($gross - ($gross / (1 + $rate / 10000))) : 0;
            $lines[] = [
                'type' => 'shipping_fee',
                'reference' => 'shipping',
                'name' => 'Frakt',
                'quantity' => 1,
                'unit_price' => $gross,
                'tax_rate' => $rate,
                'total_amount' => $gross,
                'total_tax_amount' => $tax,
            ];
        }

        $payload = [
            'purchase_country' => $order->shipping_address['country'] ?? 'SE',
            'purchase_currency' => $order->currency,
            'locale' => app()->getLocale() === 'en' ? 'en-SE' : 'sv-SE',
            'order_amount' => (int) round((float) $order->grand_total * 100),
            'order_tax_amount' => (int) round((float) $order->vat_total * 100),
            'order_lines' => $lines,
            'merchant_reference1' => $order->order_number,
            'intent' => 'buy',
        ];

        if ($order->shipping_address) {
            $payload['shipping_address'] = self::address($order->shipping_address, $order->email);
        }
        if ($order->billing_address) {
            $payload['billing_address'] = self::address($order->billing_address, $order->email);
        }

        if ($merchantUrls) {
            $payload['merchant_urls'] = $merchantUrls;
        }

        return $payload;
    }

    protected static function address(array $a, string $email): array
    {
        $parts = explode(' ', trim((string) ($a['name'] ?? '')), 2);

        return [
            'given_name' => $parts[0] ?? '',
            'family_name' => $parts[1] ?? '',
            'email' => $email,
            'street_address' => (string) ($a['street'] ?? ''),
            'postal_code' => (string) ($a['zip'] ?? ''),
            'city' => (string) ($a['city'] ?? ''),
            'country' => (string) ($a['country'] ?? 'SE'),
            'phone' => (string) ($a['phone'] ?? ''),
        ];
    }
}

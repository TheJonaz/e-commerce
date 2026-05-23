<?php

namespace Modules\Payments\Swish;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** Wrapper around Swish CPC API. Uses client certificate auth. */
class SwishClient
{
    public static function baseUrl(): string
    {
        return (string) setting('payment.swish.test_mode', '1') === '1'
            ? 'https://mss.cpc.getswish.net/swish-cpcapi/api/v2'
            : 'https://cpc.getswish.net/swish-cpcapi/api/v2';
    }

    public static function http(): PendingRequest
    {
        $certPath = (string) setting('payment.swish.cert_path', '');
        $certPassword = (string) setting('payment.swish.cert_password', '');

        $options = [];
        if ($certPath && is_file($certPath)) {
            $options['cert'] = $certPassword ? [$certPath, $certPassword] : $certPath;
        }

        return Http::withOptions($options)
            ->acceptJson()
            ->asJson()
            ->baseUrl(self::baseUrl())
            ->timeout(15);
    }

    /** Swish requires a 32-char uppercase hex UUID for payment requests. */
    public static function instructionId(): string
    {
        return strtoupper(bin2hex(random_bytes(16)));
    }

    /** Token-based payment URL the customer is redirected to. */
    public static function paymentRequestUrl(string $token): string
    {
        return 'https://app.swish.nu/1/p/sw/?token=' . urlencode($token);
    }
}

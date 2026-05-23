<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GeoIp
{
    /** Resolve an IPv4/IPv6 to a 2-letter ISO country code, or null. */
    public static function country(string $ip, ?string $cloudflareHeader = null): ?string
    {
        if ($cloudflareHeader && preg_match('/^[A-Z]{2}$/', $cloudflareHeader)) {
            return $cloudflareHeader;
        }

        if (self::isPrivate($ip)) {
            return null;
        }

        return Cache::rememberForever('geoip.country.' . $ip, function () use ($ip) {
            try {
                $resp = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,countryCode',
                ]);
                if ($resp->successful() && ($resp->json('status') === 'success')) {
                    return $resp->json('countryCode');
                }
            } catch (\Throwable $e) {
                // swallow
            }

            return null;
        });
    }

    protected static function isPrivate(string $ip): bool
    {
        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /** Display name for an ISO-2 code; falls back to the code itself. */
    public static function name(?string $code, string $locale = 'sv'): string
    {
        if (! $code) {
            return $locale === 'sv' ? 'Okänt' : 'Unknown';
        }

        $sv = [
            'SE' => 'Sverige', 'NO' => 'Norge', 'DK' => 'Danmark', 'FI' => 'Finland',
            'GB' => 'Storbritannien', 'US' => 'USA', 'DE' => 'Tyskland', 'FR' => 'Frankrike',
            'NL' => 'Nederländerna', 'ES' => 'Spanien', 'IT' => 'Italien', 'PL' => 'Polen',
            'BE' => 'Belgien', 'AT' => 'Österrike', 'CH' => 'Schweiz', 'IE' => 'Irland',
            'CA' => 'Kanada', 'AU' => 'Australien', 'JP' => 'Japan', 'BR' => 'Brasilien',
        ];
        $en = [
            'SE' => 'Sweden', 'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland',
            'GB' => 'United Kingdom', 'US' => 'United States', 'DE' => 'Germany', 'FR' => 'France',
            'NL' => 'Netherlands', 'ES' => 'Spain', 'IT' => 'Italy', 'PL' => 'Poland',
            'BE' => 'Belgium', 'AT' => 'Austria', 'CH' => 'Switzerland', 'IE' => 'Ireland',
            'CA' => 'Canada', 'AU' => 'Australia', 'JP' => 'Japan', 'BR' => 'Brazil',
        ];

        $map = $locale === 'sv' ? $sv : $en;

        return $map[$code] ?? $code;
    }

    public static function flag(?string $code): string
    {
        if (! $code || ! preg_match('/^[A-Z]{2}$/', $code)) {
            return '🏳';
        }

        return mb_chr(0x1F1E6 + ord($code[0]) - ord('A')) . mb_chr(0x1F1E6 + ord($code[1]) - ord('A'));
    }
}

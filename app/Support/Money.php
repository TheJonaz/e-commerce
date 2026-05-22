<?php

namespace App\Support;

use NumberFormatter;

class Money
{
    public static function format(float|string $amount, ?string $currency = null, ?string $locale = null): string
    {
        $currency ??= config('app.currency', config('shop.default_currency', 'SEK'));
        $locale ??= app()->getLocale();

        $formatter = new NumberFormatter(self::intlLocale($locale), NumberFormatter::CURRENCY);

        return $formatter->formatCurrency((float) $amount, $currency);
    }

    public static function formatPlain(float|string $amount, int $decimals = 2): string
    {
        return number_format((float) $amount, $decimals, ',', ' ');
    }

    protected static function intlLocale(string $locale): string
    {
        return match ($locale) {
            'sv' => 'sv_SE',
            'en' => 'en_GB',
            default => $locale,
        };
    }
}

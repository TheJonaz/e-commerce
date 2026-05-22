<?php

namespace App\Support;

class Vat
{
    public const RATE_STANDARD = 25.00;
    public const RATE_REDUCED = 12.00;
    public const RATE_LOW = 6.00;
    public const RATE_NONE = 0.00;

    /**
     * Extract VAT from a price that already includes VAT.
     */
    public static function fromGross(float $gross, float $ratePercent): float
    {
        if ($ratePercent <= 0) {
            return 0.0;
        }

        return round($gross - ($gross / (1 + $ratePercent / 100)), 2);
    }

    /**
     * Add VAT on top of a net price.
     */
    public static function fromNet(float $net, float $ratePercent): float
    {
        return round($net * ($ratePercent / 100), 2);
    }

    /**
     * Strip VAT from a price that includes VAT, returning the net amount.
     */
    public static function net(float $gross, float $ratePercent): float
    {
        if ($ratePercent <= 0) {
            return round($gross, 2);
        }

        return round($gross / (1 + $ratePercent / 100), 2);
    }

    /**
     * Sum line totals into subtotal/vat/grand totals.
     * Each line: ['qty' => int, 'unit_price_incl_vat' => float, 'vat_rate' => float]
     *
     * @return array{subtotal_excl_vat: float, vat_total: float, grand_total: float}
     */
    public static function summarize(array $lines): array
    {
        $net = 0.0;
        $vat = 0.0;
        $gross = 0.0;

        foreach ($lines as $line) {
            $lineGross = round($line['qty'] * (float) $line['unit_price_incl_vat'], 2);
            $lineNet = self::net($lineGross, (float) $line['vat_rate']);
            $lineVat = round($lineGross - $lineNet, 2);

            $gross += $lineGross;
            $net += $lineNet;
            $vat += $lineVat;
        }

        return [
            'subtotal_excl_vat' => round($net, 2),
            'vat_total' => round($vat, 2),
            'grand_total' => round($gross, 2),
        ];
    }
}

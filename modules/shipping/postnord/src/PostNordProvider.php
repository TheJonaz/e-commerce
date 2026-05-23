<?php

namespace Modules\Shipping\PostNord;

use App\Models\Cart;
use App\Modules\Contracts\ShippingProvider;

class PostNordProvider implements ShippingProvider
{
    public function code(): string
    {
        return 'postnord';
    }

    public function label(): string
    {
        return 'PostNord MyPack Collect';
    }

    public function description(): string
    {
        $days = setting('shipping.postnord.lead_time', '2–4');

        return "Hämtas i närmaste PostNord-ombud, leveranstid {$days} arbetsdagar.";
    }

    public function cost(Cart $cart): float
    {
        $threshold = (float) setting('shipping.postnord.free_threshold', '0');
        if ($threshold > 0) {
            $subtotal = (float) $cart->items->sum(fn ($i) => $i->qty * (float) $i->price_snapshot);
            if ($subtotal >= $threshold) {
                return 0.0;
            }
        }

        $grams = $this->cartWeight($cart);
        $base = $this->priceFromWeight($grams);

        // International surcharge multiplier.
        $intlMultiplier = (float) setting('shipping.postnord.intl_multiplier', '2.0');
        $country = optional($cart->customer)->addresses?->first()?->country ?? 'SE';
        if ($country !== 'SE' && $intlMultiplier > 0) {
            $base *= $intlMultiplier;
        }

        return round($base, 2);
    }

    public function vatRate(): float
    {
        return (float) setting('shipping.postnord.vat_rate', '25');
    }

    protected function cartWeight(Cart $cart): int
    {
        $grams = 0;
        foreach ($cart->items as $item) {
            $w = (int) ($item->product?->weight_grams ?? 0);
            $grams += $w * $item->qty;
        }

        // If no weights are set, assume each item is 500 g — PostNord still needs some basis.
        if ($grams === 0) {
            $grams = 500 * max(1, (int) $cart->items->sum('qty'));
        }

        return $grams;
    }

    /**
     * Tiered price ladder. Defaults follow PostNord MyPack Collect list prices (SEK incl. VAT).
     * Adjust in Settings under PostNord.
     */
    protected function priceFromWeight(int $grams): float
    {
        $tiers = [
            (int) setting('shipping.postnord.tier1_max_grams', '2000') => (float) setting('shipping.postnord.tier1_price', '79'),
            (int) setting('shipping.postnord.tier2_max_grams', '5000') => (float) setting('shipping.postnord.tier2_price', '109'),
            (int) setting('shipping.postnord.tier3_max_grams', '10000') => (float) setting('shipping.postnord.tier3_price', '149'),
            (int) setting('shipping.postnord.tier4_max_grams', '20000') => (float) setting('shipping.postnord.tier4_price', '229'),
        ];
        $overflowPrice = (float) setting('shipping.postnord.overflow_price', '349');

        ksort($tiers);
        foreach ($tiers as $max => $price) {
            if ($grams <= $max) {
                return $price;
            }
        }

        return $overflowPrice;
    }
}

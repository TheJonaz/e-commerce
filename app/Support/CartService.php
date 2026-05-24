<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DiscountCode;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CartService
{
    public const SESSION_KEY = 'cart_id';

    /** Returns the active cart for this session, creating one on demand. */
    public function current(bool $create = false): ?Cart
    {
        $id = Session::get(self::SESSION_KEY);

        if ($id) {
            $cart = Cart::with('items.product', 'items.variant')->find($id);
            if ($cart) {
                return $cart;
            }
        }

        if (! $create) {
            return null;
        }

        $cart = Cart::create([
            'session_id' => Str::uuid()->toString(),
            'currency' => setting('shop.currency', 'SEK'),
        ]);

        Session::put(self::SESSION_KEY, $cart->id);

        return $cart;
    }

    public function add(Product $product, int $qty = 1, ?ProductVariant $variant = null): CartItem
    {
        $cart = $this->current(create: true);

        $price = $variant ? (float) $variant->price : (float) $product->price;
        $vatRate = $variant ? $variant->vatRate() : (float) $product->vat_rate;

        $item = $cart->items()
            ->where('product_id', $product->id)
            ->where('variant_id', $variant?->id)
            ->first();

        if ($item) {
            $item->qty += $qty;
            $item->save();

            return $item;
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'qty' => $qty,
            'price_snapshot' => $price,
            'vat_rate_snapshot' => $vatRate,
        ]);
    }

    public function updateQty(CartItem $item, int $qty): void
    {
        if ($qty <= 0) {
            $item->delete();
            return;
        }

        $item->qty = $qty;
        $item->save();
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
    }

    public function clear(): void
    {
        if ($cart = $this->current()) {
            $cart->items()->delete();
            $cart->delete();
        }

        Session::forget(self::SESSION_KEY);
    }

    public function totals(): array
    {
        $cart = $this->current();

        if (! $cart || $cart->items->isEmpty()) {
            return ['count' => 0, 'subtotal' => 0.0, 'vat' => 0.0, 'discount' => 0.0, 'discount_code' => null, 'grand' => 0.0];
        }

        $lines = $cart->items->map(fn ($i) => [
            'qty' => $i->qty,
            'unit_price_incl_vat' => (float) $i->price_snapshot,
            'vat_rate' => (float) $i->vat_rate_snapshot,
        ])->all();

        $summary = Vat::summarize($lines);
        $grandBeforeDiscount = $summary['grand_total'];

        $discount = 0.0;
        $appliedCode = null;
        if ($cart->discount_code) {
            $code = DiscountCode::where('code', $cart->discount_code)->first();
            if ($code && $code->checkValidity($grandBeforeDiscount)['valid']) {
                $discount = $code->discountFor($grandBeforeDiscount);
                $appliedCode = $code->code;
            }
        }

        return [
            'count' => (int) $cart->items->sum('qty'),
            'subtotal' => $summary['subtotal_excl_vat'],
            'vat' => $summary['vat_total'],
            'discount' => $discount,
            'discount_code' => $appliedCode,
            'grand' => round($grandBeforeDiscount - $discount, 2),
        ];
    }

    /** Try to apply a discount code to the current cart. Returns [valid:bool, reason?:string]. */
    public function applyDiscount(string $code): array
    {
        $cart = $this->current(create: true);
        $code = strtoupper(trim($code));

        $discount = DiscountCode::where('code', $code)->first();
        if (! $discount) {
            return ['valid' => false, 'reason' => 'Ogiltig kod.'];
        }

        // Validate with current cart amount (pre-discount).
        $cart->load('items');
        $lines = $cart->items->map(fn ($i) => [
            'qty' => $i->qty,
            'unit_price_incl_vat' => (float) $i->price_snapshot,
            'vat_rate' => (float) $i->vat_rate_snapshot,
        ])->all();
        $summary = Vat::summarize($lines);
        $check = $discount->checkValidity($summary['grand_total']);
        if (! $check['valid']) {
            return $check;
        }

        $cart->discount_code = $discount->code;
        $cart->save();

        return ['valid' => true];
    }

    public function removeDiscount(): void
    {
        $cart = $this->current();
        if (! $cart) return;

        $cart->discount_code = null;
        $cart->save();
    }
}

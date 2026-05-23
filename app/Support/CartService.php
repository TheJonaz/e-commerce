<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\CartItem;
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
            return ['count' => 0, 'subtotal' => 0.0, 'vat' => 0.0, 'grand' => 0.0];
        }

        $lines = $cart->items->map(fn ($i) => [
            'qty' => $i->qty,
            'unit_price_incl_vat' => (float) $i->price_snapshot,
            'vat_rate' => (float) $i->vat_rate_snapshot,
        ])->all();

        $summary = Vat::summarize($lines);

        return [
            'count' => (int) $cart->items->sum('qty'),
            'subtotal' => $summary['subtotal_excl_vat'],
            'vat' => $summary['vat_total'],
            'grand' => $summary['grand_total'],
        ];
    }
}

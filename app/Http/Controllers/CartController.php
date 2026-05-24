<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function show()
    {
        $cart = $this->cart->current();

        return view('shop.cart', [
            'cart' => $cart,
            'totals' => $this->cart->totals(),
        ]);
    }

    public function add(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $variant = null;
        if ($variantId = $request->input('variant_id')) {
            $variant = ProductVariant::where('id', $variantId)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();

            if (! $variant) {
                return back()->withErrors(['variant' => 'Vald variant finns inte.']);
            }
        } elseif ($product->hasVariants()) {
            return back()->withErrors(['variant' => 'Välj variant först.']);
        }

        $qty = max(1, (int) $request->input('qty', 1));
        $this->cart->add($product, $qty, $variant);

        if ($request->wantsJson() || $request->ajax()) {
            $totals = $this->cart->totals();

            return response()->json([
                'ok' => true,
                'product' => $product->localized('name'),
                'qty_added' => $qty,
                'count' => $totals['count'],
                'subtotal' => $totals['subtotal'],
                'grand' => $totals['grand'],
            ]);
        }

        return redirect()->back()->with('status', __('shop.cart.add') . ': ' . $product->localized('name'));
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        $this->cart->updateQty($item, (int) $request->input('qty', 1));

        return redirect()->route('cart.show');
    }

    public function remove(CartItem $item): RedirectResponse
    {
        $this->cart->remove($item);

        return redirect()->route('cart.show');
    }

    public function applyDiscount(Request $request): RedirectResponse
    {
        $code = (string) $request->input('code', '');
        if ($code === '') {
            return back()->withErrors(['discount' => 'Ange en kod.']);
        }

        $result = $this->cart->applyDiscount($code);
        if (! $result['valid']) {
            return back()->withErrors(['discount' => $result['reason'] ?? 'Koden kunde inte användas.']);
        }

        return back()->with('status', 'Rabattkod aktiverad.');
    }

    public function removeDiscount(): RedirectResponse
    {
        $this->cart->removeDiscount();

        return back();
    }
}

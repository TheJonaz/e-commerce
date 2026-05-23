<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
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

        $qty = max(1, (int) $request->input('qty', 1));
        $this->cart->add($product, $qty);

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
}

<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function show()
    {
        $cart = $this->cart->current();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        return view('shop.checkout', [
            'cart' => $cart,
            'totals' => $this->cart->totals(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cart = $this->cart->current();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $data = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'street' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:16'],
            'city' => ['required', 'string', 'max:128'],
            'country' => ['required', 'string', 'size:2'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = DB::transaction(function () use ($cart, $data) {
            $customer = Customer::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'phone' => $data['phone'] ?? null]
            );

            $totals = $this->cart->totals();

            $address = [
                'name' => $data['name'],
                'street' => $data['street'],
                'zip' => $data['zip'],
                'city' => $data['city'],
                'country' => $data['country'],
                'phone' => $data['phone'] ?? null,
            ];

            $order = Order::create([
                'customer_id' => $customer->id,
                'order_number' => $this->generateOrderNumber(),
                'email' => $data['email'],
                'currency' => $cart->currency,
                'subtotal_excl_vat' => $totals['subtotal'],
                'vat_total' => $totals['vat'],
                'shipping_total' => 0,
                'discount_total' => 0,
                'grand_total' => $totals['grand'],
                'status' => Order::STATUS_PENDING,
                'payment_status' => 'unpaid',
                'shipping_status' => 'not_shipped',
                'payment_method' => 'invoice',
                'shipping_method' => 'pickup',
                'shipping_address' => $address,
                'billing_address' => $address,
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;
                $unit = (float) $item->price_snapshot;
                $rate = (float) $item->vat_rate_snapshot;
                $lineGross = round($item->qty * $unit, 2);
                $lineNet = $rate > 0 ? round($lineGross / (1 + $rate / 100), 2) : $lineGross;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'name_snapshot' => $product?->localized('name') ?? '(deleted product)',
                    'sku_snapshot' => $product?->sku,
                    'qty' => $item->qty,
                    'unit_price_incl_vat' => $unit,
                    'vat_rate' => $rate,
                    'line_total_incl_vat' => $lineGross,
                    'line_vat_amount' => round($lineGross - $lineNet, 2),
                ]);
            }

            return $order;
        });

        $this->cart->clear();

        return redirect()->route('checkout.thanks', $order->order_number);
    }

    public function thanks(string $orderNumber)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        return view('shop.thanks', ['order' => $order]);
    }

    protected function generateOrderNumber(): string
    {
        do {
            $candidate = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $candidate)->exists());

        return $candidate;
    }
}

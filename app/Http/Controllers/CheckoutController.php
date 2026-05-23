<?php

namespace App\Http\Controllers;

use App\Mail\OrderPlaced;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Modules\PaymentRegistry;
use App\Modules\ShippingRegistry;
use App\Support\CartService;
use App\Support\Vat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cart,
        protected PaymentRegistry $payments,
        protected ShippingRegistry $shipping,
    ) {}

    public function show(Request $request)
    {
        $cart = $this->cart->current();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.show');
        }

        $shippingCode = $request->old('shipping_method') ?? $this->shipping->default()?->code();
        $shipping = $shippingCode ? $this->shipping->find($shippingCode) : null;
        $shippingCost = $shipping ? $shipping->cost($cart) : 0.0;
        $shippingVatRate = $shipping ? $shipping->vatRate() : 0.0;

        $totals = $this->computeTotals($cart, $shippingCost, $shippingVatRate);

        return view('shop.checkout', [
            'cart' => $cart,
            'totals' => $totals,
            'payments' => $this->payments->all(),
            'shipping_options' => $this->shipping->all(),
            'selected_shipping' => $shippingCode,
            'selected_payment' => $request->old('payment_method') ?? $this->payments->default()?->code(),
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
            'shipping_method' => ['required', Rule::in(array_keys($this->shipping->all()))],
            'payment_method' => ['required', Rule::in(array_keys($this->payments->all()))],
        ]);

        $shipping = $this->shipping->find($data['shipping_method']);
        $payment = $this->payments->find($data['payment_method']);

        $shippingCost = $shipping->cost($cart);
        $shippingVatRate = $shipping->vatRate();
        $totals = $this->computeTotals($cart, $shippingCost, $shippingVatRate);

        $order = DB::transaction(function () use ($cart, $data, $shipping, $shippingCost, $shippingVatRate, $totals) {
            $customer = Customer::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'phone' => $data['phone'] ?? null]
            );

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
                'shipping_total' => $shippingCost,
                'discount_total' => 0,
                'grand_total' => $totals['grand'],
                'status' => Order::STATUS_PENDING,
                'payment_status' => 'unpaid',
                'shipping_status' => 'not_shipped',
                'payment_method' => $data['payment_method'],
                'shipping_method' => $shipping->code(),
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

        $redirect = $payment->process($order);

        $this->sendOrderEmails($order);

        $this->cart->clear();

        return $redirect
            ? redirect()->away($redirect)
            : redirect()->route('checkout.thanks', $order->order_number);
    }

    protected function sendOrderEmails(Order $order): void
    {
        try {
            Mail::to($order->email)->send(new OrderPlaced($order, forAdmin: false));
        } catch (\Throwable $e) {
            report($e);
        }

        $adminEmail = setting('shop.admin_email')
            ?: optional(User::where('role', User::ROLE_ADMIN)->orderBy('id')->first())->email;

        if ($adminEmail && $adminEmail !== $order->email) {
            try {
                Mail::to($adminEmail)->send(new OrderPlaced($order, forAdmin: true));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function thanks(string $orderNumber)
    {
        $order = Order::with('items')->where('order_number', $orderNumber)->firstOrFail();

        return view('shop.thanks', [
            'order' => $order,
            'payment' => $this->payments->find($order->payment_method),
            'shipping' => $this->shipping->find($order->shipping_method),
        ]);
    }

    protected function computeTotals($cart, float $shippingGross, float $shippingVatRate): array
    {
        $lines = $cart->items->map(fn ($i) => [
            'qty' => $i->qty,
            'unit_price_incl_vat' => (float) $i->price_snapshot,
            'vat_rate' => (float) $i->vat_rate_snapshot,
        ])->all();

        if ($shippingGross > 0) {
            $lines[] = [
                'qty' => 1,
                'unit_price_incl_vat' => $shippingGross,
                'vat_rate' => $shippingVatRate,
            ];
        }

        $sum = Vat::summarize($lines);

        return [
            'subtotal' => $sum['subtotal_excl_vat'],
            'vat' => $sum['vat_total'],
            'shipping' => $shippingGross,
            'grand' => $sum['grand_total'],
        ];
    }

    protected function generateOrderNumber(): string
    {
        do {
            $candidate = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $candidate)->exists());

        return $candidate;
    }
}

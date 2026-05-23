<?php

namespace Tests\Feature;

use App\Mail\OrderPlaced;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShopFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents(storage_path('install.lock'), now()->toIso8601String());

        $category = Category::create([
            'slug' => 'test',
            'name' => ['sv' => 'Test', 'en' => 'Test'],
            'is_active' => true,
        ]);

        $product = Product::create([
            'sku' => 'TEST-1',
            'slug' => 'test-product',
            'name' => ['sv' => 'Testprodukt', 'en' => 'Test product'],
            'price' => 125.00,
            'vat_rate' => 25.00,
            'stock' => 50,
            'is_active' => true,
        ]);

        $product->categories()->attach($category->id);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('install.lock'));

        parent::tearDown();
    }

    public function test_home_renders(): void
    {
        $this->get('/')->assertOk()->assertSee('Testprodukt');
    }

    public function test_category_page_lists_product(): void
    {
        $this->get('/categories/test')->assertOk()->assertSee('Testprodukt');
    }

    public function test_product_page_renders(): void
    {
        $this->get('/products/test-product')
            ->assertOk()
            ->assertSee('Testprodukt')
            ->assertSee('TEST-1');
    }

    public function test_add_to_cart_and_checkout_creates_order(): void
    {
        $product = Product::first();

        $this->post(route('cart.add', $product->slug), ['qty' => 2])
            ->assertRedirect();

        $this->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Testprodukt')
            ->assertSee('2');

        $response = $this->post(route('checkout.store'), [
            'email' => 'buyer@example.test',
            'name' => 'Test Köpare',
            'phone' => '0701234567',
            'street' => 'Storgatan 1',
            'zip' => '11122',
            'city' => 'Stockholm',
            'country' => 'SE',
            'shipping_method' => 'pickup',
            'payment_method' => 'invoice',
        ]);

        $this->assertSame(1, Order::count());
        $order = Order::first();
        $this->assertSame(250.00, (float) $order->grand_total);
        $this->assertSame(50.00, (float) $order->vat_total);
        $this->assertSame(200.00, (float) $order->subtotal_excl_vat);
        $this->assertSame(0.00, (float) $order->shipping_total);
        $this->assertSame('pickup', $order->shipping_method);
        $this->assertSame('invoice', $order->payment_method);
        $this->assertSame('awaiting_invoice', $order->payment_status);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(2, $order->items()->first()->qty);

        $response->assertRedirect(route('checkout.thanks', $order->order_number));

        $this->get(route('checkout.thanks', $order->order_number))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_empty_checkout_redirects_to_cart(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('cart.show'));
    }

    public function test_cart_add_returns_json_for_ajax_requests(): void
    {
        $product = Product::first();

        $this->postJson(route('cart.add', $product->slug), ['qty' => 3])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'product' => 'Testprodukt',
                'qty_added' => 3,
                'count' => 3,
            ]);
    }

    public function test_flat_rate_shipping_is_added_to_totals(): void
    {
        $product = Product::first();

        $this->post(route('cart.add', $product->slug), ['qty' => 1]);

        $this->post(route('checkout.store'), [
            'email' => 'buyer@example.test',
            'name' => 'Test Köpare',
            'street' => 'Storgatan 1',
            'zip' => '11122',
            'city' => 'Stockholm',
            'country' => 'SE',
            'shipping_method' => 'flat-rate',
            'payment_method' => 'bank-transfer',
        ])->assertRedirect();

        $order = Order::first();
        // 125 (product incl VAT) + 49 (flat-rate incl VAT) = 174 grand total
        $this->assertSame(174.00, (float) $order->grand_total);
        $this->assertSame(49.00, (float) $order->shipping_total);
        $this->assertSame('flat-rate', $order->shipping_method);
        $this->assertSame('bank-transfer', $order->payment_method);
        $this->assertSame('awaiting_transfer', $order->payment_status);
    }

    public function test_order_emails_are_sent(): void
    {
        Mail::fake();

        // Make sure there's an admin to fall back to
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => bcrypt('x'),
            'role' => User::ROLE_ADMIN,
        ]);

        $product = Product::first();
        $this->post(route('cart.add', $product->slug), ['qty' => 1]);

        $this->post(route('checkout.store'), [
            'email' => 'buyer@example.test',
            'name' => 'Test Köpare',
            'street' => 'Storgatan 1',
            'zip' => '11122',
            'city' => 'Stockholm',
            'country' => 'SE',
            'shipping_method' => 'pickup',
            'payment_method' => 'invoice',
        ])->assertRedirect();

        Mail::assertSent(OrderPlaced::class, function (OrderPlaced $mail) {
            return $mail->hasTo('buyer@example.test') && ! $mail->forAdmin;
        });
        Mail::assertSent(OrderPlaced::class, function (OrderPlaced $mail) {
            return $mail->hasTo('admin@example.test') && $mail->forAdmin;
        });
    }

    public function test_order_email_admin_copy_uses_setting_when_present(): void
    {
        Mail::fake();
        Setting::put('shop.admin_email', 'orders@thern.io');

        $product = Product::first();
        $this->post(route('cart.add', $product->slug), ['qty' => 1]);
        $this->post(route('checkout.store'), [
            'email' => 'buyer@example.test',
            'name' => 'X',
            'street' => 'S', 'zip' => '1', 'city' => 'C', 'country' => 'SE',
            'shipping_method' => 'pickup', 'payment_method' => 'invoice',
        ])->assertRedirect();

        Mail::assertSent(OrderPlaced::class, fn ($m) => $m->hasTo('orders@thern.io') && $m->forAdmin);
    }

    public function test_module_registries_have_default_modules(): void
    {
        $payments = app(\App\Modules\PaymentRegistry::class)->all();
        $shipping = app(\App\Modules\ShippingRegistry::class)->all();

        $this->assertArrayHasKey('invoice', $payments);
        $this->assertArrayHasKey('bank-transfer', $payments);
        $this->assertArrayHasKey('pickup', $shipping);
        $this->assertArrayHasKey('flat-rate', $shipping);
    }

    public function test_stripe_gateway_skipped_when_no_secret_key(): void
    {
        // No secret key in the fresh DB — gateway should not register.
        $payments = app(\App\Modules\PaymentRegistry::class)->all();
        $this->assertArrayNotHasKey('stripe', $payments);
    }

    public function test_stripe_cancel_route_aborts_order(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-1',
            'email' => 'x@example.test',
            'currency' => 'SEK',
            'subtotal_excl_vat' => 80, 'vat_total' => 20, 'grand_total' => 100,
            'status' => Order::STATUS_PENDING, 'payment_status' => 'awaiting_payment',
            'payment_method' => 'stripe', 'shipping_method' => 'pickup',
        ]);

        $this->get(route('stripe.cancel', $order->order_number))
            ->assertRedirect(route('cart.show'));

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame('cancelled', $order->payment_status);
    }

    public function test_stripe_webhook_rejects_when_secret_not_configured(): void
    {
        $this->post(route('stripe.webhook'), [], ['Stripe-Signature' => 'sig'])
            ->assertStatus(400)
            ->assertSee('webhook secret not configured');
    }

    public function test_klarna_gateway_skipped_without_credentials(): void
    {
        $payments = app(\App\Modules\PaymentRegistry::class)->all();
        $this->assertArrayNotHasKey('klarna', $payments);
    }

    public function test_klarna_cancel_route_aborts_order(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-KL-1',
            'email' => 'x@example.test',
            'currency' => 'SEK',
            'subtotal_excl_vat' => 80, 'vat_total' => 20, 'grand_total' => 100,
            'status' => Order::STATUS_PENDING, 'payment_status' => 'awaiting_payment',
            'payment_method' => 'klarna', 'shipping_method' => 'pickup',
        ]);

        $this->get(route('klarna.cancel', $order->order_number))
            ->assertRedirect(route('cart.show'));

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame('cancelled', $order->payment_status);
    }

    public function test_swish_gateway_skipped_without_payee_alias(): void
    {
        $payments = app(\App\Modules\PaymentRegistry::class)->all();
        $this->assertArrayNotHasKey('swish', $payments);
    }

    public function test_swish_callback_marks_paid_on_PAID_status(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SW-1',
            'email' => 'x@example.test',
            'currency' => 'SEK',
            'subtotal_excl_vat' => 80, 'vat_total' => 20, 'grand_total' => 100,
            'status' => Order::STATUS_PENDING, 'payment_status' => 'awaiting_payment',
            'payment_method' => 'swish', 'shipping_method' => 'pickup',
        ]);

        $this->postJson(route('swish.callback', $order->order_number), [
            'status' => 'PAID',
            'paymentReference' => 'SWREF123',
        ])->assertOk();

        $order->refresh();
        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('SWREF123', $order->payment_reference);
    }

    public function test_swish_callback_marks_cancelled_on_DECLINED(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-SW-2',
            'email' => 'x@example.test',
            'currency' => 'SEK',
            'subtotal_excl_vat' => 80, 'vat_total' => 20, 'grand_total' => 100,
            'status' => Order::STATUS_PENDING, 'payment_status' => 'awaiting_payment',
            'payment_method' => 'swish', 'shipping_method' => 'pickup',
        ]);

        $this->postJson(route('swish.callback', $order->order_number), [
            'status' => 'DECLINED',
        ])->assertOk();

        $order->refresh();
        $this->assertSame(Order::STATUS_CANCELLED, $order->status);
        $this->assertSame('declined', $order->payment_status);
    }
}

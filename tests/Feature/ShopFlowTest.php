<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertRedirect(route('cart.show'));

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

    public function test_module_registries_have_default_modules(): void
    {
        $payments = app(\App\Modules\PaymentRegistry::class)->all();
        $shipping = app(\App\Modules\ShippingRegistry::class)->all();

        $this->assertArrayHasKey('invoice', $payments);
        $this->assertArrayHasKey('bank-transfer', $payments);
        $this->assertArrayHasKey('pickup', $shipping);
        $this->assertArrayHasKey('flat-rate', $shipping);
    }
}

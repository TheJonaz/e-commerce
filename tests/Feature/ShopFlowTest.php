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
        ]);

        $this->assertSame(1, Order::count());
        $order = Order::first();
        $this->assertSame(250.00, (float) $order->grand_total);
        $this->assertSame(50.00, (float) $order->vat_total);
        $this->assertSame(200.00, (float) $order->subtotal_excl_vat);
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
}

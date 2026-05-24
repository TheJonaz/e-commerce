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

    public function test_customer_can_register_and_is_logged_in(): void
    {
        $this->post(route('customer.register'), [
            'name' => 'Jane Test',
            'email' => 'jane@example.test',
            'phone' => '0701234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('account.show'));

        $this->assertTrue(auth('customer')->check());
        $this->assertSame('jane@example.test', auth('customer')->user()->email);
    }

    public function test_customer_registration_claims_existing_guest_record(): void
    {
        \App\Models\Customer::create([
            'email' => 'guest@example.test',
            'name' => 'Guest Name',
            'phone' => '0700000000',
        ]);

        $this->post(route('customer.register'), [
            'name' => 'New Name',
            'email' => 'guest@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('account.show'));

        $this->assertSame(1, \App\Models\Customer::count(), 'should reuse existing guest customer');
        $this->assertNotNull(\App\Models\Customer::first()->password);
    }

    public function test_customer_registration_blocks_email_with_existing_password(): void
    {
        \App\Models\Customer::create([
            'email' => 'existing@example.test',
            'name' => 'Existing',
            'password' => \Illuminate\Support\Facades\Hash::make('original'),
        ]);

        $this->post(route('customer.register'), [
            'name' => 'Someone Else',
            'email' => 'existing@example.test',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors('email');
    }

    public function test_customer_login(): void
    {
        \App\Models\Customer::create([
            'email' => 'login@example.test',
            'name' => 'L',
            'password' => \Illuminate\Support\Facades\Hash::make('pw-correct'),
        ]);

        $this->post(route('customer.login'), [
            'email' => 'login@example.test',
            'password' => 'pw-wrong',
        ])->assertSessionHasErrors('email');

        $this->post(route('customer.login'), [
            'email' => 'login@example.test',
            'password' => 'pw-correct',
        ])->assertRedirect(route('account.show'));

        $this->assertTrue(auth('customer')->check());
    }

    public function test_account_pages_require_customer_auth(): void
    {
        $this->get(route('account.show'))->assertRedirect();
        $this->get(route('account.orders'))->assertRedirect();
    }

    public function test_postnord_is_registered_and_uses_weight_tiers(): void
    {
        $shipping = app(\App\Modules\ShippingRegistry::class);
        $this->assertArrayHasKey('postnord', $shipping->all());
        $postnord = $shipping->find('postnord');

        $product = Product::first();
        $product->weight_grams = 800;
        $product->save();

        // 1 × 800 g → tier 1 (≤ 2000 g → 79 kr)
        $this->post(route('cart.add', $product->slug), ['qty' => 1]);
        $cart = app(\App\Support\CartService::class)->current();
        $this->assertSame(79.0, $postnord->cost($cart));

        // 3 × 800 = 2400 g → tier 2 (≤ 5000 g → 109 kr)
        $cart->items()->first()->update(['qty' => 3]);
        $cart->load('items.product');
        $this->assertSame(109.0, $postnord->cost($cart));
    }

    public function test_postnord_free_over_threshold(): void
    {
        \App\Models\Setting::put('shipping.postnord.free_threshold', '200');

        $product = Product::first();
        $this->post(route('cart.add', $product->slug), ['qty' => 5]);

        $cart = app(\App\Support\CartService::class)->current();
        $postnord = app(\App\Modules\ShippingRegistry::class)->find('postnord');

        $this->assertSame(0.0, $postnord->cost($cart));
    }

    public function test_search_finds_products_by_name(): void
    {
        Product::create([
            'sku' => 'NEEDLE-1', 'slug' => 'unique-needle',
            'name' => ['sv' => 'Unik nålprodukt', 'en' => 'Unique needle'],
            'price' => 99, 'vat_rate' => 25, 'is_active' => true,
        ]);

        $this->get(route('shop.search', ['q' => 'nålprodukt']))
            ->assertOk()
            ->assertSee('Unik nålprodukt')
            ->assertSee('Sökresultat för');
    }

    public function test_search_finds_products_by_sku(): void
    {
        Product::create([
            'sku' => 'XYZ-12345', 'slug' => 'sku-only',
            'name' => ['sv' => 'SKU only', 'en' => 'SKU only'],
            'price' => 50, 'vat_rate' => 25, 'is_active' => true,
        ]);

        $this->get(route('shop.search', ['q' => 'XYZ-12345']))
            ->assertOk()
            ->assertSee('SKU only');
    }

    public function test_search_empty_state_when_no_query(): void
    {
        $this->get(route('shop.search'))
            ->assertOk()
            ->assertSee('Skriv vad du letar efter');
    }

    public function test_suggest_returns_json_with_top_results(): void
    {
        Product::create([
            'sku' => 'SUG-1', 'slug' => 'suggest-me',
            'name' => ['sv' => 'Suggesto produkt', 'en' => 'Suggesto product'],
            'price' => 50, 'vat_rate' => 25, 'is_active' => true,
        ]);

        $this->getJson(route('shop.suggest', ['q' => 'Suggesto']))
            ->assertOk()
            ->assertJsonStructure(['results' => [['slug', 'name', 'price', 'url']]])
            ->assertJsonFragment(['slug' => 'suggest-me']);
    }

    public function test_suggest_requires_minimum_query_length(): void
    {
        $this->getJson(route('shop.suggest', ['q' => 'a']))
            ->assertOk()
            ->assertJson(['results' => []]);
    }

    public function test_product_gallery_renders_main_image_and_thumbs(): void
    {
        $product = Product::first();
        $product->images()->createMany([
            ['path' => 'products/a.jpg', 'alt' => ['sv' => 'Bild A'], 'position' => 0],
            ['path' => 'products/b.jpg', 'alt' => ['sv' => 'Bild B'], 'position' => 1],
            ['path' => 'products/c.jpg', 'alt' => ['sv' => 'Bild C'], 'position' => 2],
        ]);

        $this->get(route('shop.product', $product->slug))
            ->assertOk()
            ->assertSee('product-gallery-img', escape: false)
            ->assertSee('products/a.jpg', escape: false)
            ->assertSee('gallery-thumb', escape: false)
            ->assertSee('products/c.jpg', escape: false);
    }

    public function test_product_image_url_falls_back_to_legacy_image_path(): void
    {
        $product = Product::create([
            'sku' => 'IMG-LEGACY', 'slug' => 'legacy-img',
            'name' => ['sv' => 'Legacy'], 'price' => 1, 'vat_rate' => 25, 'is_active' => true,
            'image_path' => 'products/old.jpg',
        ]);

        $this->assertStringContainsString('products/old.jpg', (string) $product->imageUrl());
    }

    public function test_product_with_variants_blocks_add_without_selection(): void
    {
        $product = Product::create([
            'sku' => 'VP-1', 'slug' => 'variant-prod',
            'name' => ['sv' => 'Variant prod'], 'price' => 100, 'vat_rate' => 25, 'is_active' => true,
        ]);
        $product->variants()->create([
            'sku' => 'VP-1-S', 'options' => ['size' => 'S'], 'price' => 90, 'is_active' => true,
        ]);

        $this->post(route('cart.add', $product->slug), ['qty' => 1])
            ->assertSessionHasErrors('variant');

        $this->assertSame(0, \App\Models\CartItem::count());
    }

    public function test_cart_uses_variant_price_when_variant_picked(): void
    {
        $product = Product::create([
            'sku' => 'VP-2', 'slug' => 'variant-prod-2',
            'name' => ['sv' => 'Variant 2'], 'price' => 100, 'vat_rate' => 25, 'is_active' => true,
        ]);
        $s = $product->variants()->create(['sku' => 'VP-2-S', 'options' => ['size' => 'S'], 'price' => 90, 'is_active' => true]);
        $l = $product->variants()->create(['sku' => 'VP-2-L', 'options' => ['size' => 'L'], 'price' => 110, 'is_active' => true]);

        $this->post(route('cart.add', $product->slug), ['qty' => 1, 'variant_id' => $l->id])
            ->assertRedirect();

        $item = \App\Models\CartItem::first();
        $this->assertSame($l->id, $item->variant_id);
        $this->assertSame(110.0, (float) $item->price_snapshot);
    }

    public function test_same_product_different_variants_are_separate_cart_items(): void
    {
        $product = Product::create([
            'sku' => 'VP-3', 'slug' => 'variant-prod-3',
            'name' => ['sv' => 'Variant 3'], 'price' => 100, 'vat_rate' => 25, 'is_active' => true,
        ]);
        $s = $product->variants()->create(['sku' => 'VP-3-S', 'options' => ['size' => 'S'], 'price' => 90, 'is_active' => true]);
        $l = $product->variants()->create(['sku' => 'VP-3-L', 'options' => ['size' => 'L'], 'price' => 110, 'is_active' => true]);

        $this->post(route('cart.add', $product->slug), ['qty' => 1, 'variant_id' => $s->id]);
        $this->post(route('cart.add', $product->slug), ['qty' => 1, 'variant_id' => $l->id]);

        $this->assertSame(2, \App\Models\CartItem::count());
    }

    public function test_discount_percent_applies_to_cart_totals(): void
    {
        \App\Models\DiscountCode::create([
            'code' => 'SAVE20', 'type' => 'percent', 'value' => 20, 'is_active' => true,
        ]);
        $product = Product::first();
        $this->post(route('cart.add', $product->slug), ['qty' => 4]); // 4 × 125 = 500

        $this->post(route('cart.discount.apply'), ['code' => 'save20'])
            ->assertRedirect();

        $totals = app(\App\Support\CartService::class)->totals();
        $this->assertSame('SAVE20', $totals['discount_code']);
        $this->assertSame(100.0, $totals['discount']);
        $this->assertSame(400.0, $totals['grand']);
    }

    public function test_discount_fixed_capped_at_order_amount(): void
    {
        \App\Models\DiscountCode::create([
            'code' => 'GIANT', 'type' => 'fixed', 'value' => 9999, 'is_active' => true,
        ]);
        $product = Product::first();
        $this->post(route('cart.add', $product->slug), ['qty' => 1]);
        $this->post(route('cart.discount.apply'), ['code' => 'GIANT']);

        $totals = app(\App\Support\CartService::class)->totals();
        $this->assertSame(125.0, $totals['discount']);
        $this->assertSame(0.0, $totals['grand']);
    }

    public function test_discount_rejected_when_inactive(): void
    {
        \App\Models\DiscountCode::create([
            'code' => 'OFF', 'type' => 'percent', 'value' => 10, 'is_active' => false,
        ]);
        $product = Product::first();
        $this->post(route('cart.add', $product->slug), ['qty' => 1]);
        $this->post(route('cart.discount.apply'), ['code' => 'OFF'])
            ->assertSessionHasErrors('discount');
    }

    public function test_discount_rejected_when_expired(): void
    {
        \App\Models\DiscountCode::create([
            'code' => 'EXPIRED', 'type' => 'percent', 'value' => 10,
            'valid_until' => now()->subDay(), 'is_active' => true,
        ]);
        $product = Product::first();
        $this->post(route('cart.add', $product->slug), ['qty' => 1]);
        $this->post(route('cart.discount.apply'), ['code' => 'EXPIRED'])
            ->assertSessionHasErrors('discount');
    }

    public function test_discount_rejected_below_min_order(): void
    {
        \App\Models\DiscountCode::create([
            'code' => 'BIGORDER', 'type' => 'percent', 'value' => 10,
            'min_order_amount' => 500, 'is_active' => true,
        ]);
        $product = Product::first();
        $this->post(route('cart.add', $product->slug), ['qty' => 1]);
        $this->post(route('cart.discount.apply'), ['code' => 'BIGORDER'])
            ->assertSessionHasErrors('discount');
    }

    public function test_product_page_has_seo_tags_and_json_ld(): void
    {
        $product = Product::first();

        $response = $this->get(route('shop.product', $product->slug));
        $response->assertOk();
        $response->assertSee('<link rel="canonical"', escape: false);
        $response->assertSee('<meta property="og:type" content="product">', escape: false);
        $response->assertSee('<meta property="og:title"', escape: false);
        $response->assertSee('"@type":"Product"', escape: false);
        $response->assertSee('"@type":"BreadcrumbList"', escape: false);
        $response->assertSee('https://schema.org/InStock', escape: false);
    }

    public function test_category_page_has_canonical_and_title(): void
    {
        $this->get(route('shop.category', 'test'))
            ->assertOk()
            ->assertSee('<link rel="canonical"', escape: false)
            ->assertSee('<meta property="og:title"', escape: false);
    }

    public function test_sitemap_lists_categories_and_products(): void
    {
        $product = Product::first();

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', escape: false)
            ->assertSee(route('shop.product', $product->slug))
            ->assertSee(route('shop.category', 'test'));
    }

    public function test_product_uses_custom_meta_when_set(): void
    {
        $product = Product::first();
        $product->meta_title = 'Min skräddade SEO-titel';
        $product->meta_description = 'En specifik beskrivning som ska visas i Google.';
        $product->brand = 'Acme';
        $product->gtin = '7350001234567';
        $product->save();

        $this->get(route('shop.product', $product->slug))
            ->assertOk()
            ->assertSee('Min skräddade SEO-titel', escape: false)
            ->assertSee('En specifik beskrivning som ska visas i Google.', escape: false)
            ->assertSee('"brand":{"@type":"Brand","name":"Acme"}', escape: false)
            ->assertSee('"gtin":"7350001234567"', escape: false);
    }

    public function test_layout_emits_google_verification_when_set(): void
    {
        \App\Models\Setting::put('seo.google_verification', 'verify123token');

        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="google-site-verification" content="verify123token">', escape: false);
    }

    public function test_cookie_banner_rendered_when_enabled(): void
    {
        \App\Models\Setting::put('cookie.banner_enabled', '1');

        $this->get('/')
            ->assertOk()
            ->assertSee('id="cookie-banner"', escape: false)
            ->assertSee('id="cookie-accept"', escape: false)
            ->assertSee('id="cookie-reject"', escape: false);
    }

    public function test_cookie_banner_hidden_when_disabled(): void
    {
        \App\Models\Setting::put('cookie.banner_enabled', '0');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('id="cookie-banner"', escape: false);
    }

    public function test_ga_script_is_gated_behind_consent(): void
    {
        \App\Models\Setting::put('seo.ga_id', 'G-TESTABC');

        $response = $this->get('/');
        $response->assertOk();
        // GA bootstrap is wrapped in a localStorage check, not loaded synchronously.
        $response->assertSee("localStorage.getItem('cookie_consent') !== 'accepted'", escape: false);
        // No raw <script src="googletagmanager"> in the HTML — it's injected only after consent.
        $response->assertDontSee('<script async src="https://www.googletagmanager.com', escape: false);
    }

    public function test_password_reset_sends_link_for_existing_customer(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $customer = \App\Models\Customer::create([
            'email' => 'reset@example.test', 'name' => 'R',
            'password' => \Illuminate\Support\Facades\Hash::make('original'),
        ]);

        $this->post(route('password.email'), ['email' => 'reset@example.test'])
            ->assertRedirect();

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $customer, \Illuminate\Auth\Notifications\ResetPassword::class
        );
    }

    public function test_password_reset_does_not_leak_unknown_email(): void
    {
        // Should redirect with the same "om kontot finns"-flash whether or not the email exists.
        $this->post(route('password.email'), ['email' => 'nobody@example.test'])
            ->assertRedirect()
            ->assertSessionHas('status');
    }

    public function test_password_reset_changes_password_with_valid_token(): void
    {
        $customer = \App\Models\Customer::create([
            'email' => 'reset2@example.test', 'name' => 'R',
            'password' => \Illuminate\Support\Facades\Hash::make('original'),
        ]);
        $token = \Illuminate\Support\Facades\Password::broker('customers')->createToken($customer);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'reset2@example.test',
            'password' => 'brand-new-pw',
            'password_confirmation' => 'brand-new-pw',
        ])->assertRedirect(route('customer.login'));

        $customer->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('brand-new-pw', $customer->password));
    }

    public function test_admin_can_mark_order_shipped_and_email_customer(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $admin = \App\Models\User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password' => \Illuminate\Support\Facades\Hash::make('secret'),
            'role' => \App\Models\User::ROLE_ADMIN,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-SHIP-1',
            'email' => 'customer@example.test', 'currency' => 'SEK',
            'subtotal_excl_vat' => 100, 'vat_total' => 25, 'grand_total' => 125,
            'status' => Order::STATUS_PAID, 'payment_status' => 'paid',
            'shipping_status' => 'not_shipped',
            'payment_method' => 'invoice', 'shipping_method' => 'postnord',
            'shipping_address' => ['name' => 'Buyer', 'street' => 'S', 'zip' => '1', 'city' => 'C', 'country' => 'SE'],
            'placed_at' => now(),
        ]);

        $page = \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Orders\Pages\EditOrder::class, ['record' => $order->id])
            ->callAction('markShipped', data: [
                'tracking_number' => 'PN999XYZ',
                'tracking_url' => 'https://example.test/track/PN999XYZ',
                'notify_customer' => true,
            ])
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertSame(Order::STATUS_SHIPPED, $order->status);
        $this->assertSame('shipped', $order->shipping_status);
        $this->assertSame('PN999XYZ', $order->tracking_number);
        $this->assertNotNull($order->shipped_at);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\OrderShipped::class,
            fn ($m) => $m->hasTo('customer@example.test')
        );
    }

    public function test_admin_can_cancel_order(): void
    {
        $admin = \App\Models\User::create([
            'name' => 'Admin', 'email' => 'admin2@example.test',
            'password' => \Illuminate\Support\Facades\Hash::make('secret'),
            'role' => \App\Models\User::ROLE_ADMIN,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-CXL-1',
            'email' => 'c@e.t', 'currency' => 'SEK',
            'subtotal_excl_vat' => 100, 'vat_total' => 25, 'grand_total' => 125,
            'status' => Order::STATUS_PAID, 'payment_status' => 'paid',
            'shipping_status' => 'not_shipped',
            'payment_method' => 'invoice', 'shipping_method' => 'postnord',
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Resources\Orders\Pages\EditOrder::class, ['record' => $order->id])
            ->callAction('cancelOrder')
            ->assertHasNoErrors();

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
    }

    public function test_review_submission_creates_published_review(): void
    {
        $product = Product::first();

        $this->post(route('shop.review.store', $product->slug), [
            'name' => 'Anna Andersson',
            'email' => 'anna@example.test',
            'rating' => 5,
            'title' => 'Toppen!',
            'body' => 'Bra produkt, snabb leverans.',
        ])->assertRedirect();

        $this->assertSame(1, \App\Models\ProductReview::count());
        $review = \App\Models\ProductReview::first();
        $this->assertSame(5, $review->rating);
        $this->assertTrue($review->is_published);
    }

    public function test_review_moderation_when_auto_publish_off(): void
    {
        \App\Models\Setting::put('reviews.auto_publish', '0');
        $product = Product::first();

        $this->post(route('shop.review.store', $product->slug), [
            'name' => 'A', 'email' => 'a@e.t', 'rating' => 4,
        ]);

        $this->assertFalse(\App\Models\ProductReview::first()->is_published);
    }

    public function test_review_honeypot_blocks_bot(): void
    {
        $product = Product::first();
        $this->post(route('shop.review.store', $product->slug), [
            'name' => 'Bot', 'email' => 'b@e.t', 'rating' => 5,
            'website' => 'http://spam.example',
        ]);
        $this->assertSame(0, \App\Models\ProductReview::count());
    }

    public function test_review_verified_purchase_set_for_past_buyer(): void
    {
        $product = Product::first();

        // Make this email buy the product first.
        $this->post(route('cart.add', $product->slug), ['qty' => 1]);
        $this->post(route('checkout.store'), [
            'email' => 'buyer@e.t', 'name' => 'Buyer',
            'street' => 'S', 'zip' => '1', 'city' => 'C', 'country' => 'SE',
            'shipping_method' => 'pickup', 'payment_method' => 'invoice',
        ])->assertRedirect();

        $this->post(route('shop.review.store', $product->slug), [
            'name' => 'Buyer', 'email' => 'buyer@e.t', 'rating' => 5,
        ]);

        $this->assertTrue(\App\Models\ProductReview::first()->is_verified_purchase);
    }

    public function test_product_page_includes_aggregate_rating_in_json_ld(): void
    {
        $product = Product::first();
        \App\Models\ProductReview::create([
            'product_id' => $product->id, 'name' => 'A', 'email' => 'a@e.t',
            'rating' => 4, 'is_published' => true,
        ]);
        \App\Models\ProductReview::create([
            'product_id' => $product->id, 'name' => 'B', 'email' => 'b@e.t',
            'rating' => 5, 'is_published' => true,
        ]);

        $this->get(route('shop.product', $product->slug))
            ->assertOk()
            ->assertSee('"@type":"AggregateRating"', escape: false)
            ->assertSee('"ratingValue":"4.5"', escape: false)
            ->assertSee('"reviewCount":2', escape: false);
    }

    public function test_robots_disallows_admin_and_lists_sitemap(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: ' . url('/sitemap.xml'));
    }

    public function test_discount_increments_times_used_on_order(): void
    {
        $code = \App\Models\DiscountCode::create([
            'code' => 'USEME', 'type' => 'percent', 'value' => 10, 'is_active' => true,
        ]);
        $product = Product::first();
        $this->post(route('cart.add', $product->slug), ['qty' => 1]);
        $this->post(route('cart.discount.apply'), ['code' => 'USEME']);

        $this->post(route('checkout.store'), [
            'email' => 'b@e.t', 'name' => 'B',
            'street' => 'S', 'zip' => '1', 'city' => 'C', 'country' => 'SE',
            'shipping_method' => 'pickup', 'payment_method' => 'invoice',
        ])->assertRedirect();

        $code->refresh();
        $this->assertSame(1, $code->times_used);
        $order = \App\Models\Order::first();
        $this->assertSame('USEME', $order->discount_code);
        $this->assertGreaterThan(0, (float) $order->discount_total);
    }

    public function test_variant_options_snapshot_stored_on_order(): void
    {
        $product = Product::create([
            'sku' => 'VP-4', 'slug' => 'variant-prod-4',
            'name' => ['sv' => 'Variant 4'], 'price' => 100, 'vat_rate' => 25, 'is_active' => true,
        ]);
        $m = $product->variants()->create([
            'sku' => 'VP-4-M-RED', 'options' => ['size' => 'M', 'color' => 'Röd'],
            'price' => 100, 'is_active' => true,
        ]);

        $this->post(route('cart.add', $product->slug), ['qty' => 1, 'variant_id' => $m->id]);

        $this->post(route('checkout.store'), [
            'email' => 'b@example.test', 'name' => 'B',
            'street' => 'S', 'zip' => '1', 'city' => 'C', 'country' => 'SE',
            'shipping_method' => 'pickup', 'payment_method' => 'invoice',
        ])->assertRedirect();

        $item = \App\Models\Order::first()->items->first();
        $this->assertSame($m->id, $item->variant_id);
        $this->assertSame(['size' => 'M', 'color' => 'Röd'], $item->variant_options_snapshot);
        $this->assertStringContainsString('M / Röd', $item->name_snapshot);
    }
}

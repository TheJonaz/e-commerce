<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        Setting::many([
            'shop.name' => Setting::get('shop.name', 'Demo Shop'),
            'shop.currency' => Setting::get('shop.currency', 'SEK'),
            'shop.locale' => Setting::get('shop.locale', 'sv'),
        ]);

        if (! User::where('email', 'admin@example.com')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ]);
        }

        $categories = collect([
            ['slug' => 'klader', 'name' => ['sv' => 'Kläder', 'en' => 'Clothing']],
            ['slug' => 'skor', 'name' => ['sv' => 'Skor', 'en' => 'Shoes']],
            ['slug' => 'accessoarer', 'name' => ['sv' => 'Accessoarer', 'en' => 'Accessories']],
            ['slug' => 'bocker', 'name' => ['sv' => 'Böcker', 'en' => 'Books']],
            ['slug' => 'mat', 'name' => ['sv' => 'Mat & Dryck', 'en' => 'Food & Drink']],
        ])->map(fn ($c, $i) => Category::firstOrCreate(
            ['slug' => $c['slug']],
            ['name' => $c['name'], 'position' => $i]
        ));

        $products = [
            ['Bomullströja', 'T-shirt cotton', 299.00, 25, 'klader', 'TSHIRT-001'],
            ['Linnejacka', 'Linen jacket', 1299.00, 25, 'klader', 'JACKET-001'],
            ['Jeans slim', 'Slim jeans', 899.00, 25, 'klader', 'JEANS-001'],
            ['Stickad tröja', 'Knitted sweater', 799.00, 25, 'klader', 'SWEATER-001'],
            ['Sneakers vit', 'White sneakers', 1499.00, 25, 'skor', 'SHOES-001'],
            ['Vandringsstövlar', 'Hiking boots', 2299.00, 25, 'skor', 'SHOES-002'],
            ['Loafers brun', 'Brown loafers', 1899.00, 25, 'skor', 'SHOES-003'],
            ['Läderbälte', 'Leather belt', 449.00, 25, 'accessoarer', 'BELT-001'],
            ['Solglasögon', 'Sunglasses', 699.00, 25, 'accessoarer', 'GLASS-001'],
            ['Plånbok', 'Wallet', 599.00, 25, 'accessoarer', 'WALLET-001'],
            ['Mössa ull', 'Wool hat', 249.00, 25, 'accessoarer', 'HAT-001'],
            ['Roman: Berättelsen', 'Novel: The Story', 219.00, 6, 'bocker', 'BOOK-001'],
            ['Kokbok skandinavisk', 'Scandinavian cookbook', 349.00, 6, 'bocker', 'BOOK-002'],
            ['Fotobok natur', 'Nature photo book', 499.00, 6, 'bocker', 'BOOK-003'],
            ['Kaffe rostat 250g', 'Roasted coffee 250g', 119.00, 12, 'mat', 'COFFEE-001'],
            ['Choklad mörk 70%', 'Dark chocolate 70%', 49.00, 12, 'mat', 'CHOC-001'],
            ['Olivolja 500ml', 'Olive oil 500ml', 159.00, 12, 'mat', 'OIL-001'],
            ['Honung 250g', 'Honey 250g', 89.00, 12, 'mat', 'HONEY-001'],
            ['Lakrits klassisk', 'Classic liquorice', 39.00, 12, 'mat', 'CANDY-001'],
            ['Te grönt 50g', 'Green tea 50g', 79.00, 12, 'mat', 'TEA-001'],
        ];

        foreach ($products as $i => [$sv, $en, $price, $vat, $catSlug, $sku]) {
            $product = Product::firstOrCreate(
                ['sku' => $sku],
                [
                    'slug' => str($sv)->slug()->toString(),
                    'name' => ['sv' => $sv, 'en' => $en],
                    'short_description' => ['sv' => "Kort beskrivning av $sv.", 'en' => "Short description of $en."],
                    'description' => ['sv' => "Lång beskrivning av $sv. Lorem ipsum dolor sit amet.", 'en' => "Long description of $en. Lorem ipsum dolor sit amet."],
                    'price' => $price,
                    'vat_rate' => $vat,
                    'stock' => rand(5, 100),
                    'type' => Product::TYPE_PHYSICAL,
                    'is_active' => true,
                ]
            );

            $category = $categories->firstWhere('slug', $catSlug);
            if ($category && ! $product->categories->contains($category->id)) {
                $product->categories()->attach($category->id, ['position' => $i]);
            }
        }

        $customers = [
            ['anna@example.com', 'Anna Andersson', '0701234567'],
            ['bjorn@example.com', 'Björn Bergström', '0702345678'],
            ['cecilia@example.com', 'Cecilia Carlsson', '0703456789'],
        ];

        foreach ($customers as [$email, $name, $phone]) {
            $customer = Customer::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'phone' => $phone, 'accepts_marketing' => false]
            );

            if ($customer->wasRecentlyCreated) {
                Address::create([
                    'customer_id' => $customer->id,
                    'type' => Address::TYPE_SHIPPING,
                    'name' => $name,
                    'street' => 'Storgatan ' . rand(1, 99),
                    'zip' => sprintf('%05d', rand(10000, 99999)),
                    'city' => 'Stockholm',
                    'country' => 'SE',
                    'phone' => $phone,
                    'is_default' => true,
                ]);
            }
        }

        $visits = $this->seedVisits();
        $orders = $this->seedOrders();

        $this->command?->info("Seeded {$categories->count()} categories, " . count($products) . " products, " . count($customers) . " customers, {$visits} visits, {$orders} orders.");
    }

    protected function seedOrders(): int
    {
        $products = Product::all();
        $customers = Customer::all();
        if ($products->isEmpty() || $customers->isEmpty()) {
            return 0;
        }

        // Make some products best-sellers via weighted picking
        $weighted = [];
        foreach ($products as $i => $p) {
            $weight = match (true) {
                $i < 3 => 8,
                $i < 8 => 4,
                $i < 15 => 2,
                default => 1,
            };
            $weighted = array_merge($weighted, array_fill(0, $weight, $p->id));
        }

        $created = 0;
        $now = Carbon::now();

        for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
            $day = $now->copy()->subDays($daysAgo);
            // 3-12 orders/day, fewer on weekends
            $ordersToday = in_array($day->dayOfWeek, [0, 6], true) ? rand(2, 6) : rand(4, 12);

            for ($i = 0; $i < $ordersToday; $i++) {
                $placedAt = $day->copy()->setTime(rand(8, 22), rand(0, 59), rand(0, 59));
                $customer = $customers->random();
                $lineCount = rand(1, 3);
                $pickedIds = collect(array_rand(array_flip($weighted), min($lineCount, count(array_unique($weighted)))));
                $pickedIds = is_object($pickedIds) ? $pickedIds : collect([$pickedIds]);

                $lines = [];
                foreach ((array) $pickedIds->all() as $pid) {
                    $product = $products->firstWhere('id', $pid);
                    if (! $product) continue;
                    $qty = rand(1, 3);
                    $unit = (float) $product->price;
                    $rate = (float) $product->vat_rate;
                    $gross = round($qty * $unit, 2);
                    $net = $rate > 0 ? round($gross / (1 + $rate / 100), 2) : $gross;
                    $lines[] = [
                        'product' => $product, 'qty' => $qty, 'unit' => $unit, 'rate' => $rate,
                        'gross' => $gross, 'net' => $net, 'vat' => round($gross - $net, 2),
                    ];
                }
                if (! $lines) continue;

                $shippingCost = 49.00;
                $shippingNet = round($shippingCost / 1.25, 2);
                $subtotal = round(array_sum(array_column($lines, 'net')) + $shippingNet, 2);
                $vatTotal = round(array_sum(array_column($lines, 'vat')) + ($shippingCost - $shippingNet), 2);
                $grand = round(array_sum(array_column($lines, 'gross')) + $shippingCost, 2);

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'order_number' => 'ORD-' . $placedAt->format('Ymd') . '-' . strtoupper(Str::random(5)),
                    'email' => $customer->email,
                    'currency' => 'SEK',
                    'subtotal_excl_vat' => $subtotal,
                    'vat_total' => $vatTotal,
                    'shipping_total' => $shippingCost,
                    'discount_total' => 0,
                    'grand_total' => $grand,
                    'status' => array_rand(array_flip([Order::STATUS_PAID, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_PENDING])),
                    'payment_status' => 'paid',
                    'shipping_status' => 'shipped',
                    'payment_method' => 'invoice',
                    'shipping_method' => 'flat-rate',
                    'shipping_address' => ['name' => $customer->name, 'street' => 'Storgatan 1', 'zip' => '11122', 'city' => 'Stockholm', 'country' => 'SE'],
                    'billing_address' => ['name' => $customer->name, 'street' => 'Storgatan 1', 'zip' => '11122', 'city' => 'Stockholm', 'country' => 'SE'],
                    'placed_at' => $placedAt,
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ]);

                foreach ($lines as $l) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $l['product']->id,
                        'name_snapshot' => $l['product']->localized('name'),
                        'sku_snapshot' => $l['product']->sku,
                        'qty' => $l['qty'],
                        'unit_price_incl_vat' => $l['unit'],
                        'vat_rate' => $l['rate'],
                        'line_total_incl_vat' => $l['gross'],
                        'line_vat_amount' => $l['vat'],
                    ]);
                }

                $created++;
            }
        }

        return $created;
    }

    protected function seedVisits(): int
    {
        // Weighted country distribution for fake traffic
        $weights = [
            'SE' => 60, 'NO' => 8, 'DK' => 6, 'FI' => 4, 'DE' => 6, 'GB' => 5,
            'US' => 4, 'NL' => 2, 'FR' => 2, 'PL' => 1, 'IT' => 1, 'ES' => 1,
        ];
        $pool = [];
        foreach ($weights as $code => $w) {
            $pool = array_merge($pool, array_fill(0, $w, $code));
        }
        // Build a weighted URL pool — most traffic on a handful of "popular" products
        $productSlugs = Product::pluck('slug')->all();
        $popular = array_slice($productSlugs, 0, 6);
        $urls = ['/', '/', '/categories/klader', '/categories/skor', '/categories/mat', '/cart'];
        foreach ($popular as $i => $s) {
            $weight = match (true) { $i < 2 => 6, $i < 4 => 3, default => 2 };
            $urls = array_merge($urls, array_fill(0, $weight, '/products/' . $s));
        }
        foreach (array_slice($productSlugs, 6) as $s) {
            $urls[] = '/products/' . $s;
        }

        $rows = [];
        $now = Carbon::now();

        for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
            $day = $now->copy()->subDays($daysAgo)->startOfDay();
            // Weekly seasonality: weekends quieter (factor 0.65), weekdays higher.
            $weekday = $day->dayOfWeek;
            $base = in_array($weekday, [0, 6], true) ? rand(40, 110) : rand(90, 240);

            // Slight upward trend
            $trend = (int) round($base * (1 + (29 - $daysAgo) * 0.01));

            for ($i = 0; $i < $trend; $i++) {
                $rows[] = [
                    'session_id' => Str::random(32),
                    'ip' => sprintf('%d.%d.%d.%d', rand(1, 250), rand(0, 255), rand(0, 255), rand(1, 254)),
                    'country' => $pool[array_rand($pool)],
                    'url' => $urls[array_rand($urls)],
                    'referer' => rand(0, 1) ? 'https://www.google.com/' : null,
                    'user_agent_hash' => substr(sha1('demo-ua-' . rand(1, 50)), 0, 64),
                    'visited_at' => $day->copy()->addSeconds(rand(0, 86399)),
                    'created_at' => $day->copy()->addSeconds(rand(0, 86399)),
                    'updated_at' => $day->copy()->addSeconds(rand(0, 86399)),
                ];
            }

            // Flush every ~5k rows
            if (count($rows) >= 5000) {
                Visit::insert($rows);
                $rows = [];
            }
        }
        if ($rows) Visit::insert($rows);

        return Visit::count();
    }
}

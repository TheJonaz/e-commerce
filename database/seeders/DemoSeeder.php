<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
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

        $this->command?->info("Seeded {$categories->count()} categories, " . count($products) . " products, " . count($customers) . " customers, {$visits} visits.");
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
        $urls = ['/', '/categories/klader', '/categories/skor', '/categories/mat', '/products/jeans-slim', '/products/sneakers-vit', '/cart'];

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

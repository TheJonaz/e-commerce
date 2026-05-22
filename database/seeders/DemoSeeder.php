<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create([
            'slug' => 'demo',
            'name' => 'Demo Shop',
            'currency' => 'SEK',
            'locale' => 'sv',
            'is_active' => true,
        ]);

        app()->instance('currentTenant', $tenant);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $categories = collect([
            ['slug' => 'klader', 'name' => ['sv' => 'Kläder', 'en' => 'Clothing']],
            ['slug' => 'skor', 'name' => ['sv' => 'Skor', 'en' => 'Shoes']],
            ['slug' => 'accessoarer', 'name' => ['sv' => 'Accessoarer', 'en' => 'Accessories']],
            ['slug' => 'bocker', 'name' => ['sv' => 'Böcker', 'en' => 'Books']],
            ['slug' => 'mat', 'name' => ['sv' => 'Mat & Dryck', 'en' => 'Food & Drink']],
        ])->map(fn ($c, $i) => Category::create([...$c, 'position' => $i]));

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
            $product = Product::create([
                'sku' => $sku,
                'slug' => str($sv)->slug()->toString(),
                'name' => ['sv' => $sv, 'en' => $en],
                'short_description' => ['sv' => "Kort beskrivning av $sv.", 'en' => "Short description of $en."],
                'description' => ['sv' => "Lång beskrivning av $sv. Lorem ipsum dolor sit amet.", 'en' => "Long description of $en. Lorem ipsum dolor sit amet."],
                'price' => $price,
                'vat_rate' => $vat,
                'stock' => rand(5, 100),
                'type' => Product::TYPE_PHYSICAL,
                'is_active' => true,
            ]);

            $category = $categories->firstWhere('slug', $catSlug);
            if ($category) {
                $product->categories()->attach($category->id, ['position' => $i]);
            }
        }

        $customers = [
            ['anna@example.com', 'Anna Andersson', '0701234567'],
            ['bjorn@example.com', 'Björn Bergström', '0702345678'],
            ['cecilia@example.com', 'Cecilia Carlsson', '0703456789'],
        ];

        foreach ($customers as [$email, $name, $phone]) {
            $customer = Customer::create([
                'email' => $email,
                'name' => $name,
                'phone' => $phone,
                'accepts_marketing' => false,
            ]);

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

        $this->command?->info("Demo tenant '{$tenant->slug}' seeded with {$categories->count()} categories, " . count($products) . " products, " . count($customers) . " customers.");
        $this->command?->info("Admin login: admin@example.com / password");
    }
}

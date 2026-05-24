<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $products = collect();
        if ($q !== '') {
            $products = $this->queryProducts($q)->paginate(24)->withQueryString();
        }

        return view('shop.search', [
            'q' => $q,
            'products' => $products,
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
        ]);
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $results = $this->queryProducts($q)
            ->limit(6)
            ->get()
            ->map(fn (Product $p) => [
                'slug' => $p->slug,
                'name' => $p->localized('name'),
                'price' => \App\Support\Money::format($p->displayPrice(), setting('shop.currency', 'SEK')),
                'image' => $p->imageUrl(),
                'url' => route('shop.product', $p->slug),
            ]);

        return response()->json(['results' => $results]);
    }

    /** Shared query builder used by both search and suggest. */
    protected function queryProducts(string $q)
    {
        $clean = fn (string $s) => '%' . str_replace(['%', '_'], ['\%', '\_'], $s) . '%';
        // SQLite stores JSON with \u-escaped non-ASCII; MySQL stores raw UTF-8.
        // We LIKE against both forms so search works across both drivers.
        $raw = $clean($q);
        $jsonEncoded = trim(json_encode($q, JSON_UNESCAPED_SLASHES), '"');
        $escaped = $jsonEncoded !== $q ? $clean($jsonEncoded) : null;

        return Product::where('is_active', true)
            ->where(function ($w) use ($raw, $escaped) {
                $w->where('name', 'LIKE', $raw)
                    ->orWhere('sku', 'LIKE', $raw)
                    ->orWhere('short_description', 'LIKE', $raw)
                    ->orWhere('description', 'LIKE', $raw);
                if ($escaped) {
                    $w->orWhere('name', 'LIKE', $escaped)
                        ->orWhere('short_description', 'LIKE', $escaped)
                        ->orWhere('description', 'LIKE', $escaped);
                }
            })
            ->orderByDesc('updated_at');
    }

    public function home()
    {
        return view('shop.home', [
            'featured' => Product::where('is_active', true)->latest()->take(8)->get(),
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
        ]);
    }

    public function category(string $slug)
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('shop.category', [
            'category' => $category,
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
            'products' => $category->products()->where('is_active', true)->paginate(24),
        ]);
    }

    public function product(string $slug)
    {
        $product = Product::with(['categories', 'images', 'variants', 'reviews'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('shop.product', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->orderBy('position')->get(),
            'related' => Product::where('is_active', true)
                ->whereKeyNot($product->id)
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $product->categories->pluck('id')))
                ->take(4)
                ->get(),
        ]);
    }
}

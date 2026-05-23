<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class ShopController extends Controller
{
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
        $product = Product::with('categories')
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

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        // Honeypot: bots fill hidden fields humans don't see.
        if ($request->filled('website')) {
            return back();
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:4000'],
        ]);

        $customer = auth('customer')->user();
        $verifiedPurchase = false;

        if ($customer) {
            $verifiedPurchase = Order::where('customer_id', $customer->id)
                ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                ->exists();
        } else {
            // Match guest orders by email + same product
            $verifiedPurchase = Order::where('email', $data['email'])
                ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
                ->exists();
        }

        $autoPublish = (string) setting('reviews.auto_publish', '1') === '1';

        ProductReview::create([
            'product_id' => $product->id,
            'customer_id' => $customer?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'is_published' => $autoPublish,
            'is_verified_purchase' => $verifiedPurchase,
        ]);

        $msg = $autoPublish
            ? 'Tack för din recension!'
            : 'Tack! Din recension granskas innan den publiceras.';

        return back()->with('status', $msg);
    }
}

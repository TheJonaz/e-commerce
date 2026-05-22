<?php

use App\Http\Controllers\InstallController;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'show'])->name('install');
Route::post('/install', [InstallController::class, 'store']);

Route::get('/', function () {
    if (! file_exists(storage_path('install.lock'))) {
        return redirect('/install');
    }

    return view('landing', [
        'shopName' => setting('shop.name', config('app.name')),
        'currency' => setting('shop.currency', 'SEK'),
        'stats' => [
            'products' => Product::count(),
            'categories' => Category::count(),
            'orders' => Order::count(),
        ],
    ]);
});

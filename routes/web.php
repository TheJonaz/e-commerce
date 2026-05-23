<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'show'])->name('install');
Route::post('/install', [InstallController::class, 'store']);
Route::post('/install/test-db', [InstallController::class, 'testDatabase'])->name('install.test-db');

Route::middleware('web')->group(function () {
    Route::get('/', function () {
        if (! file_exists(storage_path('install.lock'))) {
            return redirect('/install');
        }

        return app(ShopController::class)->home();
    });

    Route::get('/categories/{slug}', [ShopController::class, 'category'])->name('shop.category');
    Route::get('/products/{slug}', [ShopController::class, 'product'])->name('shop.product');

    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart/add/{product:slug}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/thanks/{orderNumber}', [CheckoutController::class, 'thanks'])->name('checkout.thanks');
});

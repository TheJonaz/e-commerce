<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\ShopController;
use App\Support\Installation;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'show'])->name('install');
Route::post('/install', [InstallController::class, 'store']);
Route::post('/install/test-db', [InstallController::class, 'testDatabase'])->name('install.test-db');

Route::middleware('web')->group(function () {
    Route::get('/', function () {
        if (! Installation::isInstalled()) {
            return redirect('/install');
        }

        return app(ShopController::class)->home();
    });

    Route::get('/categories/{slug}', [ShopController::class, 'category'])->name('shop.category');
    Route::get('/products/{slug}', [ShopController::class, 'product'])->name('shop.product');
    Route::get('/search', [ShopController::class, 'search'])->name('shop.search');
    Route::get('/search/suggest', [ShopController::class, 'suggest'])->name('shop.suggest');

    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart/add/{product:slug}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/discount', [CartController::class, 'applyDiscount'])->name('cart.discount.apply');
    Route::delete('/cart/discount', [CartController::class, 'removeDiscount'])->name('cart.discount.remove');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/thanks/{orderNumber}', [CheckoutController::class, 'thanks'])->name('checkout.thanks');

    // Customer auth
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login']);
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
    Route::post('/register', [CustomerAuthController::class, 'register']);
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');

    // Customer account (requires customer guard)
    Route::middleware('auth:customer')->group(function () {
        Route::get('/account', [AccountController::class, 'show'])->name('account.show');
        Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
        Route::get('/account/orders/{orderNumber}', [AccountController::class, 'order'])->name('account.order');
    });
});

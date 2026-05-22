<?php

use App\Http\Controllers\InstallController;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'show'])->name('install');
Route::post('/install', [InstallController::class, 'store']);

Route::get('/', function () {
    if (! file_exists(storage_path('install.lock'))) {
        return redirect('/install');
    }

    return view('landing', [
        'stats' => [
            'tenants' => Tenant::count(),
            'products' => Product::withoutGlobalScope('tenant')->count(),
            'orders' => Order::withoutGlobalScope('tenant')->count(),
        ],
        'tenants' => Tenant::orderBy('name')->get(),
    ]);
});

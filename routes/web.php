<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'show'])->name('install');
Route::post('/install', [InstallController::class, 'store']);

Route::get('/', function () {
    if (! file_exists(storage_path('install.lock'))) {
        return redirect('/install');
    }

    return view('welcome');
});

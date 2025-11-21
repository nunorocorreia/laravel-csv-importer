<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
Route::get('/imports/{import}', [ImportController::class, 'show'])->name('imports.show');
Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
Route::get('/imports/{import}/status', [ImportController::class, 'status'])->name('imports.status');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/imports/{import}/products', [ProductController::class, 'byImport'])->name('imports.products');



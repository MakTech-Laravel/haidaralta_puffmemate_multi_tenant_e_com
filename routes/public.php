<?php

use App\Http\Controllers\FirebaseMessagingServiceWorkerController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/firebase-messaging-sw.js', FirebaseMessagingServiceWorkerController::class)
    ->name('firebase.messaging-sw');

Route::inertia('/', 'storefront/home')->name('home');
Route::inertia('/shop', 'storefront/shop')->name('shop');
Route::get('/shop/{category}', [CategoryController::class, 'show'])->name('shop.category');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::inertia('/about', 'storefront/about')->name('about');
Route::inertia('/contact', 'storefront/contact')->name('contact');

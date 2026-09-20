<?php

use App\Http\Controllers\FirebaseMessagingServiceWorkerController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/firebase-messaging-sw.js', FirebaseMessagingServiceWorkerController::class)
    ->name('firebase.messaging-sw');

Route::get('/', HomeController::class)->name('home');

Route::redirect('/driver/login', '/login');
Route::redirect('/platform/login', '/login');
Route::redirect('/branch/login', '/login');
Route::redirect('/customer/login', '/login');
Route::get('/shop', ShopController::class)->name('shop');
Route::get('/shop/{category}', [CategoryController::class, 'show'])->name('shop.category');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::inertia('/about', 'storefront/about')->name('about');
Route::inertia('/faqs', 'storefront/faq')->name('faqs');
Route::inertia('/contact', 'storefront/contact')->name('contact');
Route::inertia('/wholesale', 'storefront/wholesale')->name('wholesale');
Route::inertia('/privacy', 'storefront/privacy')->name('privacy');

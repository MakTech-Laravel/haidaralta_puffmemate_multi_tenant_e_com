<?php

use App\Http\Controllers\FirebaseMessagingServiceWorkerController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CmsPageController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\WebAppManifestController;
use Illuminate\Support\Facades\Route;

Route::get('/firebase-messaging-sw.js', FirebaseMessagingServiceWorkerController::class)
    ->name('firebase.messaging-sw');

Route::get('/manifest.webmanifest', WebAppManifestController::class)
    ->name('pwa.manifest');

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

Route::get('/about', fn () => app(CmsPageController::class)('about'))->name('about');
Route::get('/faqs', fn () => app(CmsPageController::class)('faqs'))->name('faqs');
Route::get('/contact', fn () => app(CmsPageController::class)('contact'))->name('contact');
Route::get('/wholesale', fn () => app(CmsPageController::class)('wholesale'))->name('wholesale');
Route::get('/privacy', fn () => app(CmsPageController::class)('privacy'))->name('privacy');
Route::get('/terms', fn () => app(CmsPageController::class)('terms'))->name('terms');

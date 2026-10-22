<?php

use App\Http\Controllers\FirebaseMessagingServiceWorkerController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CmsPageController;
use App\Http\Controllers\Storefront\ContactInquiryController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PackController;
use App\Http\Controllers\Storefront\PacksSavesController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ShopController;
use App\Http\Controllers\Storefront\WholesaleInquiryController;
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
Route::get('/packs-saves', PacksSavesController::class)->name('packs-saves');
Route::get('/packs-saves/{branch}/{pack}', [PackController::class, 'show'])->name('packs-saves.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::patch('/cart/{cart_item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{cart_item}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::middleware('ensure.storefront.customer')->group(function () {
    Route::post('/packs-saves/{branch}/{pack}/buy', [PackController::class, 'buy'])
        ->name('packs-saves.buy');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout/coupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.coupon');
    Route::delete('/checkout/coupon', [CheckoutController::class, 'removeCoupon'])->name('checkout.coupon.destroy');
    Route::post('/checkout/shipping', [CheckoutController::class, 'quoteShipping'])->name('checkout.shipping');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/confirmation/{order:order_number}', [CheckoutController::class, 'confirmation'])
        ->name('checkout.confirmation');
});

Route::get('/about', fn () => app(CmsPageController::class)('about'))->name('about');
Route::get('/faqs', fn () => app(CmsPageController::class)('faqs'))->name('faqs');
Route::get('/contact', fn () => app(CmsPageController::class)('contact'))->name('contact');
Route::post('/contact', [ContactInquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('contact.store');
Route::get('/wholesale', fn () => app(CmsPageController::class)('wholesale'))->name('wholesale');
Route::post('/wholesale', [WholesaleInquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('wholesale.store');
Route::get('/wholesale/products', [WholesaleInquiryController::class, 'products'])
    ->middleware('throttle:60,1')
    ->name('wholesale.products');
Route::get('/privacy', fn () => app(CmsPageController::class)('privacy'))->name('privacy');
Route::get('/terms', fn () => app(CmsPageController::class)('terms'))->name('terms');

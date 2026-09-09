<?php

use App\Http\Controllers\FirebaseMessagingServiceWorkerController;
use Illuminate\Support\Facades\Route;

Route::get('/firebase-messaging-sw.js', FirebaseMessagingServiceWorkerController::class)
    ->name('firebase.messaging-sw');

Route::inertia('/', 'storefront/home')->name('home');
Route::inertia('/shop', 'storefront/shop')->name('shop');
Route::inertia('/about', 'storefront/about')->name('about');
Route::inertia('/contact', 'storefront/contact')->name('contact');

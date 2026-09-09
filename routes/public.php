<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'storefront/home')->name('home');
Route::inertia('/shop', 'storefront/shop')->name('shop');
Route::inertia('/about', 'storefront/about')->name('about');
Route::inertia('/contact', 'storefront/contact')->name('contact');

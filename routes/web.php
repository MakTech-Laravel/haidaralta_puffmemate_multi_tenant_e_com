<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['ensure.guard:platform'])->prefix('platform')->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('platform.dashboard');
});

Route::middleware(['ensure.guard:branch', 'resolve.branch'])->prefix('branch/{branch}')->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('branch.dashboard');
});

Route::middleware(['ensure.guard:driver'])->prefix('driver')->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('driver.dashboard');
});

Route::middleware(['ensure.guard:customer', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/fortify.php';
require __DIR__.'/settings.php';

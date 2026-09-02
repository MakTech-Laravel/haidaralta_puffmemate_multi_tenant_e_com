<?php

use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['ensure.guard:platform'])->prefix('platform')->group(function () {
    Route::get('dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');
});

Route::middleware(['ensure.guard:branch', 'resolve.branch'])->prefix('branch/{branch}')->group(function () {
    Route::get('dashboard', [BranchDashboardController::class, 'index'])->name('branch.dashboard');
});

Route::middleware(['ensure.guard:driver'])->prefix('driver')->group(function () {
    Route::get('dashboard', [DriverDashboardController::class, 'index'])->name('driver.dashboard');
});

Route::middleware(['ensure.guard:customer', 'verified'])->group(function () {
    Route::get('dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/fortify.php';
require __DIR__.'/settings.php';

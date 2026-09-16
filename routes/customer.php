<?php

use App\Http\Controllers\Customer\ActivityLogController as CustomerActivityLogController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ProductToReviewController as CustomerProductToReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:customer', 'phone.verified'])->group(function () {
    Route::get('dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('orders', [CustomerOrderController::class, 'index'])->name('customer.orders.index');
    Route::get('orders/{order}', [CustomerOrderController::class, 'show'])->name('customer.orders.show');
    Route::get('product-to-review', [CustomerProductToReviewController::class, 'index'])
        ->name('customer.product-to-review.index');
    Route::get('activity', [CustomerActivityLogController::class, 'index'])->name('customer.activity.index');
    Route::get('activity/{activity}', [CustomerActivityLogController::class, 'show'])
        ->whereNumber('activity')
        ->name('customer.activity.show');
});

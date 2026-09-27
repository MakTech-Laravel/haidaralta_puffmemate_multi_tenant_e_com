<?php

use App\Http\Controllers\Customer\AccountController as CustomerAccountController;
use App\Http\Controllers\Customer\ActivityLogController as CustomerActivityLogController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\NotificationController as CustomerNotificationController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ProductToReviewController as CustomerProductToReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:customer', 'phone.verified'])->group(function () {
    Route::get('dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('account', [CustomerAccountController::class, 'index'])->name('customer.account.index');
    Route::patch('account/profile', [CustomerAccountController::class, 'updateProfile'])
        ->middleware('precognitive')
        ->name('customer.account.profile.update');
    Route::put('account/password', [CustomerAccountController::class, 'updatePassword'])
        ->name('customer.account.password.update');
    Route::patch('account/billing-address', [CustomerAccountController::class, 'updateBillingAddress'])
        ->name('customer.account.billing.update');
    Route::get('orders', [CustomerOrderController::class, 'index'])->name('customer.orders.index');
    Route::get('orders/{order}', [CustomerOrderController::class, 'show'])->name('customer.orders.show');
    Route::get('notifications', [CustomerNotificationController::class, 'index'])
        ->name('customer.notifications.index');
    Route::get('notifications/{notification}', [CustomerNotificationController::class, 'show'])
        ->whereNumber('notification')
        ->name('customer.notifications.show');
    Route::post('notifications/{notification}/read', [CustomerNotificationController::class, 'read'])
        ->whereNumber('notification')
        ->name('customer.notifications.read');
    Route::delete('notifications/{notification}', [CustomerNotificationController::class, 'destroy'])
        ->whereNumber('notification')
        ->name('customer.notifications.destroy');
    Route::get('product-to-review', [CustomerProductToReviewController::class, 'index'])
        ->name('customer.product-to-review.index');
    Route::get('product-to-review/{product}', [CustomerProductToReviewController::class, 'show'])
        ->name('customer.product-to-review.show');
    Route::get('activity', [CustomerActivityLogController::class, 'index'])->name('customer.activity.index');
    Route::get('activity/{activity}', [CustomerActivityLogController::class, 'show'])
        ->whereNumber('activity')
        ->name('customer.activity.show');
});

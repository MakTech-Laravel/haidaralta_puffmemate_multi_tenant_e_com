<?php

use App\Http\Controllers\Customer\AccountController as CustomerAccountController;
use App\Http\Controllers\Customer\ActivityLogController as CustomerActivityLogController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\NotificationController as CustomerNotificationController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ProductToReviewController as CustomerProductToReviewController;
use App\Http\Controllers\Customer\WholesaleRequestController as CustomerWholesaleRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:customer', 'phone.verified'])->group(function () {
    Route::get('dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('account', [CustomerAccountController::class, 'index'])->name('customer.account.index');
    Route::patch('account/profile', [CustomerAccountController::class, 'updateProfile'])
        ->middleware('precognitive')
        ->name('customer.account.profile.update');
    Route::put('account/password', [CustomerAccountController::class, 'updatePassword'])
        ->name('customer.account.password.update');
    Route::put('account/delivery-address', [CustomerAccountController::class, 'updateDeliveryAddress'])
        ->name('customer.account.delivery-address.update');
    Route::delete('account/delivery-address', [CustomerAccountController::class, 'destroyDeliveryAddress'])
        ->name('customer.account.delivery-address.destroy');
    Route::get('orders', [CustomerOrderController::class, 'index'])->name('customer.orders.index');
    Route::get('orders/{order:order_number}', [CustomerOrderController::class, 'show'])->name('customer.orders.show');
    Route::post('orders/{order:order_number}/cancel', [CustomerOrderController::class, 'cancel'])->name('customer.orders.cancel');
    Route::post('orders/{order:order_number}/placement-code', [CustomerOrderController::class, 'resendPlacementCode'])->name('customer.orders.placement-code');
    Route::post('orders/{order:order_number}/returns', [CustomerOrderController::class, 'storeReturn'])->name('customer.orders.returns.store');
    Route::get('orders/{order:order_number}/chat', [CustomerOrderController::class, 'chat'])->name('customer.orders.chat');
    Route::post('orders/{order:order_number}/messages', [CustomerOrderController::class, 'storeMessage'])
        ->middleware('throttle:conversation-messages')
        ->name('customer.orders.messages.store');
    Route::post('orders/{order:order_number}/chat/delivered', [CustomerOrderController::class, 'delivered'])
        ->name('customer.orders.chat.delivered');
    Route::post('orders/{order:order_number}/chat/read', [CustomerOrderController::class, 'read'])
        ->name('customer.orders.chat.read');
    Route::get('wholesale-requests', [CustomerWholesaleRequestController::class, 'index'])
        ->name('customer.wholesale-requests.index');
    Route::get('wholesale-requests/{wholesaleInquiry}', [CustomerWholesaleRequestController::class, 'show'])
        ->whereNumber('wholesaleInquiry')
        ->name('customer.wholesale-requests.show');
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
    Route::get('product-to-review/{item}', [CustomerProductToReviewController::class, 'show'])
        ->whereNumber('item')
        ->name('customer.product-to-review.show');
    Route::post('product-to-review/{item}', [CustomerProductToReviewController::class, 'store'])
        ->whereNumber('item')
        ->name('customer.product-to-review.store');
    Route::get('activity', [CustomerActivityLogController::class, 'index'])->name('customer.activity.index');
    Route::get('activity/{activity}', [CustomerActivityLogController::class, 'show'])
        ->whereNumber('activity')
        ->name('customer.activity.show');
});

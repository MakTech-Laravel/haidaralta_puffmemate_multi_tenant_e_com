<?php

use App\Http\Controllers\Driver\ActivityLogController as DriverActivityLogController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\EarningsController as DriverEarningsController;
use App\Http\Controllers\Driver\NotificationController as DriverNotificationController;
use App\Http\Controllers\Driver\OrderController as DriverOrderController;
use App\Http\Controllers\Driver\PlaceOrderController as DriverPlaceOrderController;
use App\Http\Controllers\Driver\ProfileController as DriverProfileController;
use App\Http\Controllers\Driver\RouteController as DriverRouteController;
use App\Http\Controllers\Driver\SecurityController as DriverSecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:driver'])->prefix('driver')->group(function () {
    Route::get('dashboard', [DriverDashboardController::class, 'index'])->name('driver.dashboard');
    Route::get('route', [DriverRouteController::class, 'index'])->name('driver.route.index');
    Route::get('orders', [DriverOrderController::class, 'index'])->name('driver.orders.index');
    Route::get('history', [DriverOrderController::class, 'history'])->name('driver.history.index');
    Route::get('earnings', [DriverEarningsController::class, 'index'])->name('driver.earnings.index');
    Route::get('place-order', [DriverPlaceOrderController::class, 'index'])->name('driver.place-order.index');
    Route::post('place-order/customers', [DriverPlaceOrderController::class, 'lookup'])->name('driver.place-order.customers.lookup');
    Route::post('place-order', [DriverPlaceOrderController::class, 'store'])->name('driver.place-order.store');
    Route::get('orders/{order:order_number}/chat', [DriverOrderController::class, 'chat'])->name('driver.orders.chat');
    Route::post('orders/{order:order_number}/messages', [DriverOrderController::class, 'storeMessage'])->name('driver.orders.messages.store');
    Route::post('orders/{order:order_number}/pick-up', [DriverOrderController::class, 'pickUp'])->name('driver.orders.pick-up');
    Route::post('orders/{order:order_number}/start', [DriverOrderController::class, 'start'])->name('driver.orders.start');
    Route::post('orders/{order:order_number}/arrive', [DriverOrderController::class, 'arrive'])->name('driver.orders.arrive');
    Route::post('orders/{order:order_number}/deliver', [DriverOrderController::class, 'deliver'])->name('driver.orders.deliver');
    Route::post('orders/{order:order_number}/fail', [DriverOrderController::class, 'fail'])->name('driver.orders.fail');
    Route::post('orders/{order:order_number}/return', [DriverOrderController::class, 'beginReturn'])->name('driver.orders.return');
    Route::post('orders/{order:order_number}/returns', [DriverOrderController::class, 'storeReturn'])->name('driver.orders.returns.store');
    Route::get('orders/{order:order_number}', [DriverOrderController::class, 'show'])->name('driver.orders.show');
    Route::get('notifications', [DriverNotificationController::class, 'index'])->name('driver.notifications.index');
    Route::get('notifications/{userNotification}', [DriverNotificationController::class, 'show'])->name('driver.notifications.show');
    Route::post('notifications/{userNotification}/read', [DriverNotificationController::class, 'read'])->name('driver.notifications.read');
    Route::delete('notifications/{userNotification}', [DriverNotificationController::class, 'destroy'])->name('driver.notifications.destroy');
    Route::get('profile', [DriverProfileController::class, 'index'])->name('driver.profile.index');
    Route::get('security', [DriverSecurityController::class, 'index'])->name('driver.security.index');
    Route::put('security/password', [DriverSecurityController::class, 'updatePassword'])
        ->name('driver.security.password.update');
    Route::get('profile/edit', [DriverProfileController::class, 'edit'])->name('driver.profile.edit');
    Route::patch('profile', [DriverProfileController::class, 'update'])
        ->middleware('precognitive')
        ->name('driver.profile.update');
    Route::redirect('account', '/driver/profile');
    Route::get('activity', [DriverActivityLogController::class, 'index'])->name('driver.activity.index');
    Route::get('activity/{activity}', [DriverActivityLogController::class, 'show'])
        ->whereNumber('activity')
        ->name('driver.activity.show');
});

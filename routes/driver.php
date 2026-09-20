<?php

use App\Http\Controllers\Driver\AccountController as DriverAccountController;
use App\Http\Controllers\Driver\ActivityLogController as DriverActivityLogController;
use App\Http\Controllers\Driver\ChatController as DriverChatController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\NotificationController as DriverNotificationController;
use App\Http\Controllers\Driver\OrderController as DriverOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:driver'])->prefix('driver')->group(function () {
    Route::get('dashboard', [DriverDashboardController::class, 'index'])->name('driver.dashboard');
    Route::get('chats', [DriverChatController::class, 'index'])->name('driver.chats.index');
    Route::get('orders', [DriverOrderController::class, 'index'])->name('driver.orders.index');
    Route::get('orders/{order}', [DriverOrderController::class, 'show'])->name('driver.orders.show');
    Route::post('orders/{order}/deliver', [DriverOrderController::class, 'deliver'])->name('driver.orders.deliver');
    Route::get('notifications', [DriverNotificationController::class, 'index'])->name('driver.notifications.index');
    Route::get('account', [DriverAccountController::class, 'index'])->name('driver.account.index');
    Route::get('activity', [DriverActivityLogController::class, 'index'])->name('driver.activity.index');
    Route::get('activity/{activity}', [DriverActivityLogController::class, 'show'])
        ->whereNumber('activity')
        ->name('driver.activity.show');
});

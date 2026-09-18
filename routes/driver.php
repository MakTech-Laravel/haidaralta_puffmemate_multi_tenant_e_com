<?php

use App\Http\Controllers\Driver\ActivityLogController as DriverActivityLogController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Driver\OrderController as DriverOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:driver'])->prefix('driver')->group(function () {
    Route::get('dashboard', [DriverDashboardController::class, 'index'])->name('driver.dashboard');
    Route::get('orders', [DriverOrderController::class, 'index'])->name('driver.orders.index');
    Route::get('orders/{order}', [DriverOrderController::class, 'show'])->name('driver.orders.show');
    Route::post('orders/{order}/deliver', [DriverOrderController::class, 'deliver'])->name('driver.orders.deliver');
    Route::get('activity', [DriverActivityLogController::class, 'index'])->name('driver.activity.index');
    Route::get('activity/{activity}', [DriverActivityLogController::class, 'show'])
        ->whereNumber('activity')
        ->name('driver.activity.show');
});

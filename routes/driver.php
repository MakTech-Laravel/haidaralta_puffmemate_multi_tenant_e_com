<?php

use App\Http\Controllers\Driver\ActivityLogController as DriverActivityLogController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:driver'])->prefix('driver')->group(function () {
    Route::get('dashboard', [DriverDashboardController::class, 'index'])->name('driver.dashboard');
    Route::get('activity', [DriverActivityLogController::class, 'index'])->name('driver.activity.index');
    Route::get('activity/{activity}', [DriverActivityLogController::class, 'show'])
        ->whereNumber('activity')
        ->name('driver.activity.show');
});

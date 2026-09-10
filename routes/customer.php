<?php

use App\Http\Controllers\Customer\ActivityLogController as CustomerActivityLogController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:customer', 'verified'])->group(function () {
    Route::get('dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('activity', [CustomerActivityLogController::class, 'index'])->name('customer.activity.index');
    Route::get('activity/{activity}', [CustomerActivityLogController::class, 'show'])
        ->whereNumber('activity')
        ->name('customer.activity.show');
});

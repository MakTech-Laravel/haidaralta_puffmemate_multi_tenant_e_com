<?php

use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Branch\StaffController as BranchStaffController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Platform\BranchStaffController as PlatformBranchStaffController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\PermissionController as PlatformPermissionController;
use App\Http\Controllers\Platform\RoleController as PlatformRoleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['ensure.guard:platform'])->prefix('platform')->group(function () {
    Route::get('dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');

    Route::middleware(['permission:manage-roles'])->group(function () {
        Route::resource('roles', PlatformRoleController::class)->names('platform.roles');
        Route::get('permissions', [PlatformPermissionController::class, 'index'])->name('platform.permissions.index');
    });

    Route::middleware(['permission:manage-users'])->group(function () {
        Route::get('branch-staff', [PlatformBranchStaffController::class, 'index'])->name('platform.branch-staff.index');
        Route::get('branch-staff/{user}', [PlatformBranchStaffController::class, 'show'])->name('platform.branch-staff.show');
    });
});

Route::middleware(['ensure.guard:branch', 'resolve.branch'])->prefix('branch/{branch}')->group(function () {
    Route::get('dashboard', [BranchDashboardController::class, 'index'])->name('branch.dashboard');

    Route::middleware(['permission:manage-branch-staff'])->group(function () {
        Route::resource('staff', BranchStaffController::class)
            ->except(['show'])
            ->parameters(['staff' => 'user'])
            ->names('branch.staff');
    });
});

Route::middleware(['ensure.guard:driver'])->prefix('driver')->group(function () {
    Route::get('dashboard', [DriverDashboardController::class, 'index'])->name('driver.dashboard');
});

Route::middleware(['ensure.guard:customer', 'verified'])->group(function () {
    Route::get('dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/fortify.php';
require __DIR__.'/settings.php';

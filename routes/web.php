<?php

use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Branch\PermissionController as BranchPermissionController;
use App\Http\Controllers\Branch\RoleController as BranchRoleController;
use App\Http\Controllers\Branch\StaffController as BranchStaffController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\Platform\BranchController as PlatformBranchController;
use App\Http\Controllers\Platform\BranchStaffController as PlatformBranchStaffController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\PermissionController as PlatformPermissionController;
use App\Http\Controllers\Platform\RoleController as PlatformRoleController;
use App\Http\Controllers\Platform\StaffController as PlatformStaffController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['ensure.guard:platform'])->prefix('platform')->group(function () {
    Route::get('dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');

    Route::middleware(['permission:manage-roles'])->group(function () {
        Route::get('roles/export', [PlatformRoleController::class, 'export'])->name('platform.roles.export');
        Route::resource('roles', PlatformRoleController::class)->names('platform.roles');
        Route::get('permissions/export', [PlatformPermissionController::class, 'export'])->name('platform.permissions.export');
        Route::get('permissions', [PlatformPermissionController::class, 'index'])->name('platform.permissions.index');
    });

    Route::middleware(['permission:manage-users'])->group(function () {
        Route::resource('staff', PlatformStaffController::class)
            ->parameters(['staff' => 'user'])
            ->names('platform.staff');
        Route::get('branch-staff', [PlatformBranchStaffController::class, 'index'])->name('platform.branch-staff.index');
        Route::get('branch-staff/{user}', [PlatformBranchStaffController::class, 'show'])->name('platform.branch-staff.show');
    });

    Route::middleware(['permission:create-branches'])->group(function () {
        Route::get('branches/create', [PlatformBranchController::class, 'create'])->name('platform.branches.create');
        Route::post('branches', [PlatformBranchController::class, 'store'])->name('platform.branches.store');
    });

    Route::middleware(['permission:view-branches'])->group(function () {
        Route::get('branches', [PlatformBranchController::class, 'index'])->name('platform.branches.index');
        Route::get('branches/{branch}', [PlatformBranchController::class, 'show'])->name('platform.branches.show');
    });

    Route::middleware(['permission:edit-branches'])->group(function () {
        Route::get('branches/{branch}/edit', [PlatformBranchController::class, 'edit'])->name('platform.branches.edit');
        Route::put('branches/{branch}', [PlatformBranchController::class, 'update'])->name('platform.branches.update');
    });

    Route::middleware(['permission:delete-branches'])->group(function () {
        Route::delete('branches/{branch}', [PlatformBranchController::class, 'destroy'])->name('platform.branches.destroy');
    });
});
Route::middleware(['ensure.guard:branch', 'resolve.branch'])->prefix('branch/{branch}')->group(function () {
    Route::get('dashboard', [BranchDashboardController::class, 'index'])->name('branch.dashboard');

    Route::middleware(['permission:manage-branch-roles'])->group(function () {
        Route::get('roles/export', [BranchRoleController::class, 'export'])->name('branch.roles.export');
        Route::resource('roles', BranchRoleController::class)->names('branch.roles');
        Route::get('permissions/export', [BranchPermissionController::class, 'export'])->name('branch.permissions.export');
        Route::get('permissions', [BranchPermissionController::class, 'index'])->name('branch.permissions.index');
    });

    Route::middleware(['permission:manage-branch-staff'])->group(function () {
        Route::resource('staff', BranchStaffController::class)
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

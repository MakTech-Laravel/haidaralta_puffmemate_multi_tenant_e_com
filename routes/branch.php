<?php

use App\Http\Controllers\Branch\ActivityLogController as BranchActivityLogController;
use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Branch\PermissionController as BranchPermissionController;
use App\Http\Controllers\Branch\RoleController as BranchRoleController;
use App\Http\Controllers\Branch\StaffController as BranchStaffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:branch', 'resolve.branch'])->prefix('branch/{branch}')->group(function () {
    Route::get('dashboard', [BranchDashboardController::class, 'index'])->name('branch.dashboard');

    Route::middleware(['permission:view-branch-activity-logs'])->group(function () {
        Route::get('activity-logs', [BranchActivityLogController::class, 'index'])->name('branch.activity-logs.index');
        Route::get('activity-logs/{activity}', [BranchActivityLogController::class, 'show'])
            ->whereNumber('activity')
            ->name('branch.activity-logs.show');
    });

    Route::middleware(['permission:manage-branch-roles'])->group(function () {
        Route::get('roles/export', [BranchRoleController::class, 'export'])->name('branch.roles.export');
        Route::resource('roles', BranchRoleController::class)
            ->names('branch.roles')
            ->middlewareFor(['store', 'update'], 'precognitive');
        Route::get('permissions/export', [BranchPermissionController::class, 'export'])->name('branch.permissions.export');
        Route::get('permissions', [BranchPermissionController::class, 'index'])->name('branch.permissions.index');
    });

    Route::middleware(['permission:manage-branch-staff'])->group(function () {
        Route::resource('staff', BranchStaffController::class)
            ->parameters(['staff' => 'user'])
            ->names('branch.staff')
            ->middlewareFor(['store', 'update'], 'precognitive');
    });
});

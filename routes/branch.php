<?php

use App\Http\Controllers\Branch\ActivityLogController as BranchActivityLogController;
use App\Http\Controllers\Branch\CategoryController as BranchCategoryController;
use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Branch\PermissionController as BranchPermissionController;
use App\Http\Controllers\Branch\ProductController as BranchProductController;
use App\Http\Controllers\Branch\RoleController as BranchRoleController;
use App\Http\Controllers\Branch\StaffController as BranchStaffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:branch', 'resolve.branch'])->prefix('branch/{branch}')->group(function () {
    Route::get('dashboard', [BranchDashboardController::class, 'index'])->name('branch.dashboard');

    Route::middleware(['permission:manage-branch-products'])->group(function () {
        Route::get('categories/suggestions', [BranchCategoryController::class, 'suggestions'])
            ->name('branch.categories.suggestions');
        Route::get('categories', [BranchCategoryController::class, 'index'])->name('branch.categories.index');
        Route::get('categories/create', [BranchCategoryController::class, 'create'])->name('branch.categories.create');
        Route::post('categories', [BranchCategoryController::class, 'store'])
            ->middleware('precognitive')
            ->name('branch.categories.store');
        Route::get('categories/{category}', [BranchCategoryController::class, 'show'])->name('branch.categories.show');
        Route::get('categories/{category}/edit', [BranchCategoryController::class, 'edit'])->name('branch.categories.edit');
        Route::put('categories/{category}', [BranchCategoryController::class, 'update'])
            ->middleware('precognitive')
            ->name('branch.categories.update');

        Route::get('products/suggestions', [BranchProductController::class, 'suggestions'])
            ->name('branch.products.suggestions');
        Route::get('products', [BranchProductController::class, 'index'])->name('branch.products.index');
        Route::get('products/create', [BranchProductController::class, 'create'])->name('branch.products.create');
        Route::post('products', [BranchProductController::class, 'store'])
            ->middleware('precognitive')
            ->name('branch.products.store');
        Route::post('products/reuse', [BranchProductController::class, 'reuse'])
            ->name('branch.products.reuse');
        Route::get('products/{product}', [BranchProductController::class, 'show'])->name('branch.products.show');
        Route::get('products/{product}/edit', [BranchProductController::class, 'edit'])->name('branch.products.edit');
        Route::put('products/{product}', [BranchProductController::class, 'update'])
            ->middleware('precognitive')
            ->name('branch.products.update');
        Route::put('products/{product}/enable', [BranchProductController::class, 'toggle'])
            ->name('branch.products.enable');
        Route::post('products/{product}/inventory', [BranchProductController::class, 'adjustInventory'])
            ->name('branch.products.inventory');
        Route::put('products/{product}/prices', [BranchProductController::class, 'upsertPrice'])
            ->name('branch.products.prices');
    });

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

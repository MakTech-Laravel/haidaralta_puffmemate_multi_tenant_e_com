<?php

use App\Http\Controllers\Branch\ActivityLogController as BranchActivityLogController;
use App\Http\Controllers\Branch\CategoryController as BranchCategoryController;
use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Branch\DriverController as BranchDriverController;
use App\Http\Controllers\Branch\NotificationController as BranchNotificationController;
use App\Http\Controllers\Branch\PermissionController as BranchPermissionController;
use App\Http\Controllers\Branch\ProductController as BranchProductController;
use App\Http\Controllers\Branch\RoleController as BranchRoleController;
use App\Http\Controllers\Branch\StaffController as BranchStaffController;
use App\Http\Controllers\Branch\StockController as BranchStockController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:branch', 'resolve.branch'])->prefix('branch/{branch}')->group(function () {
    Route::get('dashboard', [BranchDashboardController::class, 'index'])->name('branch.dashboard');
    Route::get('notifications', [BranchNotificationController::class, 'index'])->name('branch.notifications.index');

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
        Route::put('categories/{category}/status', [BranchCategoryController::class, 'toggleStatus'])
            ->name('branch.categories.status');
        Route::put('categories/{category}/featured', [BranchCategoryController::class, 'toggleFeatured'])
            ->name('branch.categories.featured');
        Route::put('categories/{category}/nav', [BranchCategoryController::class, 'toggleNav'])
            ->name('branch.categories.nav');
        Route::put('categories/{category}/nav-sort', [BranchCategoryController::class, 'updateNavSort'])
            ->name('branch.categories.nav-sort');

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
        Route::post('products/{product}/inventory', [BranchProductController::class, 'adjustInventory'])
            ->name('branch.products.inventory');
        Route::put('products/{product}/prices', [BranchProductController::class, 'upsertPrice'])
            ->name('branch.products.prices');
        Route::get('stock/manage', [BranchStockController::class, 'manage'])->name('branch.stock.manage');
        Route::post('stock/manage', [BranchStockController::class, 'adjust'])->name('branch.stock.adjust');
        Route::get('stock/import', [BranchStockController::class, 'import'])->name('branch.stock.import');
        Route::post('stock/import', [BranchStockController::class, 'storeImport'])->name('branch.stock.import.store');
        Route::get('stock/price', [BranchStockController::class, 'price'])->name('branch.stock.price');
        Route::put('stock/price', [BranchStockController::class, 'updatePrice'])->name('branch.stock.price.update');
        Route::get('stock/discount', [BranchStockController::class, 'discount'])->name('branch.stock.discount');
        Route::put('stock/discount', [BranchStockController::class, 'updateDiscount'])->name('branch.stock.discount.update');
        Route::get('stock/transactions', [BranchStockController::class, 'transactions'])->name('branch.stock.transactions');
        Route::get('stock/product-options', [BranchStockController::class, 'productOptions'])
            ->name('branch.stock.product-options');
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

    Route::middleware(['permission:manage-branch-drivers'])->group(function () {
        Route::get('drivers', [BranchDriverController::class, 'index'])->name('branch.drivers.index');
        Route::get('drivers/create', [BranchDriverController::class, 'create'])->name('branch.drivers.create');
        Route::post('drivers', [BranchDriverController::class, 'store'])
            ->middleware('precognitive')
            ->name('branch.drivers.store');
        Route::post('drivers/assign', [BranchDriverController::class, 'assign'])
            ->name('branch.drivers.assign');
        Route::get('drivers/{user}', [BranchDriverController::class, 'show'])->name('branch.drivers.show');
        Route::get('drivers/{user}/edit', [BranchDriverController::class, 'edit'])->name('branch.drivers.edit');
        Route::put('drivers/{user}', [BranchDriverController::class, 'update'])
            ->middleware('precognitive')
            ->name('branch.drivers.update');
        Route::post('drivers/{user}/rosters', [BranchDriverController::class, 'storeRoster'])
            ->name('branch.drivers.rosters.store');
        Route::put('drivers/{user}/rosters/{roster}', [BranchDriverController::class, 'updateRoster'])
            ->name('branch.drivers.rosters.update');
        Route::delete('drivers/{user}/rosters/{roster}', [BranchDriverController::class, 'destroyRoster'])
            ->name('branch.drivers.rosters.destroy');
    });
});

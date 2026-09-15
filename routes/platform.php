<?php

use App\Http\Controllers\Platform\ActivityLogController as PlatformActivityLogController;
use App\Http\Controllers\Platform\BranchController as PlatformBranchController;
use App\Http\Controllers\Platform\BranchDriverController as PlatformBranchDriverController;
use App\Http\Controllers\Platform\BranchInventoryController as PlatformBranchInventoryController;
use App\Http\Controllers\Platform\BranchScopedStaffController as PlatformBranchScopedStaffController;
use App\Http\Controllers\Platform\BranchStaffController as PlatformBranchStaffController;
use App\Http\Controllers\Platform\CategoryController as PlatformCategoryController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\DatabaseBackupController as PlatformDatabaseBackupController;
use App\Http\Controllers\Platform\DriverController as PlatformDriverController;
use App\Http\Controllers\Platform\FcmController as PlatformFcmController;
use App\Http\Controllers\Platform\ObservabilityController as PlatformObservabilityController;
use App\Http\Controllers\Platform\PermissionController as PlatformPermissionController;
use App\Http\Controllers\Platform\ProductController as PlatformProductController;
use App\Http\Controllers\Platform\ProductVariantController as PlatformProductVariantController;
use App\Http\Controllers\Platform\RealtimeController as PlatformRealtimeController;
use App\Http\Controllers\Platform\RoleController as PlatformRoleController;
use App\Http\Controllers\Platform\StaffController as PlatformStaffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:platform'])->prefix('platform')->group(function () {
    Route::get('dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');

    Route::middleware(['permission:view-horizon|view-pulse'])->group(function () {
        Route::get('observability', [PlatformObservabilityController::class, 'index'])
            ->name('platform.observability.index');
    });

    Route::get('realtime', [PlatformRealtimeController::class, 'index'])->name('platform.realtime.index');
    Route::get('realtime/ping', [PlatformRealtimeController::class, 'ping'])->name('platform.realtime.ping');
    Route::get('realtime/health', [PlatformRealtimeController::class, 'health'])->name('platform.realtime.health');
    Route::post('realtime/test-notification', [PlatformRealtimeController::class, 'testNotification'])->name('platform.realtime.test-notification');

    Route::get('fcm', [PlatformFcmController::class, 'index'])->name('platform.fcm.index');
    Route::post('fcm/test', [PlatformFcmController::class, 'test'])->middleware('throttle:10,1')->name('platform.fcm.test');

    Route::middleware(['permission:manage-roles'])->group(function () {
        Route::get('roles/export', [PlatformRoleController::class, 'export'])->name('platform.roles.export');
        Route::resource('roles', PlatformRoleController::class)
            ->names('platform.roles')
            ->middlewareFor(['store', 'update'], 'precognitive');
        Route::get('permissions/export', [PlatformPermissionController::class, 'export'])->name('platform.permissions.export');
        Route::get('permissions', [PlatformPermissionController::class, 'index'])->name('platform.permissions.index');
    });

    Route::middleware(['permission:manage-users'])->group(function () {
        Route::resource('staff', PlatformStaffController::class)
            ->parameters(['staff' => 'user'])
            ->names('platform.staff')
            ->middlewareFor(['store', 'update'], 'precognitive');
    });

    Route::middleware(['permission:manage-branch-users'])->group(function () {
        Route::resource('branch-staff', PlatformBranchStaffController::class)
            ->parameters(['branch-staff' => 'user'])
            ->names('platform.branch-staff')
            ->middlewareFor(['store', 'update'], 'precognitive');

        Route::resource('branches.staff', PlatformBranchScopedStaffController::class)
            ->parameters(['staff' => 'user'])
            ->names('platform.branches.staff')
            ->middlewareFor(['store', 'update'], 'precognitive');
    });

    Route::middleware(['permission:manage-drivers'])->group(function () {
        Route::resource('drivers', PlatformDriverController::class)
            ->parameters(['drivers' => 'user'])
            ->names('platform.drivers')
            ->middlewareFor(['store', 'update'], 'precognitive');

        Route::put('drivers/{user}/branches', [PlatformDriverController::class, 'syncBranches'])
            ->name('platform.drivers.branches.sync');
        Route::post('drivers/{user}/rosters', [PlatformDriverController::class, 'storeRoster'])
            ->name('platform.drivers.rosters.store');
        Route::put('drivers/{user}/rosters/{roster}', [PlatformDriverController::class, 'updateRoster'])
            ->name('platform.drivers.rosters.update');
        Route::delete('drivers/{user}/rosters/{roster}', [PlatformDriverController::class, 'destroyRoster'])
            ->name('platform.drivers.rosters.destroy');

        Route::get('branches/{branch}/drivers', [PlatformBranchDriverController::class, 'index'])
            ->name('platform.branches.drivers.index');
        Route::get('branches/{branch}/drivers/create', [PlatformBranchDriverController::class, 'create'])
            ->name('platform.branches.drivers.create');
        Route::post('branches/{branch}/drivers', [PlatformBranchDriverController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.branches.drivers.store');
        Route::post('branches/{branch}/drivers/assign', [PlatformBranchDriverController::class, 'assign'])
            ->name('platform.branches.drivers.assign');
        Route::get('branches/{branch}/drivers/{user}', [PlatformBranchDriverController::class, 'show'])
            ->name('platform.branches.drivers.show');
        Route::get('branches/{branch}/drivers/{user}/edit', [PlatformBranchDriverController::class, 'edit'])
            ->name('platform.branches.drivers.edit');
        Route::put('branches/{branch}/drivers/{user}', [PlatformBranchDriverController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.branches.drivers.update');
    });

    Route::middleware(['permission:create-branches'])->group(function () {
        Route::get('branches/create', [PlatformBranchController::class, 'create'])->name('platform.branches.create');
        Route::post('branches', [PlatformBranchController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.branches.store');
        Route::get('branches/{branch}/super-admin/create', [PlatformBranchController::class, 'createSuperAdmin'])
            ->name('platform.branches.super-admin.create');
        Route::post('branches/{branch}/super-admin', [PlatformBranchController::class, 'storeSuperAdmin'])
            ->middleware('precognitive')
            ->name('platform.branches.super-admin.store');
    });

    Route::middleware(['permission:view-branches'])->group(function () {
        Route::get('branches', [PlatformBranchController::class, 'index'])->name('platform.branches.index');
        Route::get('branches/{branch}', [PlatformBranchController::class, 'show'])->name('platform.branches.show');
    });

    Route::middleware(['permission:edit-branches'])->group(function () {
        Route::get('branches/{branch}/edit', [PlatformBranchController::class, 'edit'])->name('platform.branches.edit');
        Route::put('branches/{branch}', [PlatformBranchController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.branches.update');
    });

    Route::middleware(['permission:delete-branches'])->group(function () {
        Route::delete('branches/{branch}', [PlatformBranchController::class, 'destroy'])->name('platform.branches.destroy');
    });

    Route::middleware(['permission:manage-branch-inventories'])->group(function () {
        Route::get('products/{product}/branches', [PlatformBranchInventoryController::class, 'forProduct'])
            ->name('platform.products.branches');
        Route::get('branches/{branch}/products', [PlatformBranchInventoryController::class, 'index'])
            ->name('platform.branches.products.index');
        Route::get('branches/{branch}/products/{product}', [PlatformBranchInventoryController::class, 'show'])
            ->name('platform.branches.products.show');
        Route::put('branches/{branch}/products/{product}/enable', [PlatformBranchInventoryController::class, 'toggle'])
            ->name('platform.branches.products.enable');
        Route::post('branches/{branch}/products/{product}/inventory', [PlatformBranchInventoryController::class, 'adjustInventory'])
            ->name('platform.branches.products.inventory');
        Route::put('branches/{branch}/products/{product}/prices', [PlatformBranchInventoryController::class, 'upsertPrice'])
            ->name('platform.branches.products.prices');
    });

    Route::middleware(['permission:manage-catalog'])->group(function () {
        Route::get('categories/suggestions', [PlatformCategoryController::class, 'suggestions'])
            ->name('platform.categories.suggestions');
        Route::resource('categories', PlatformCategoryController::class)
            ->names('platform.categories')
            ->middlewareFor(['store', 'update'], 'precognitive');
        Route::post('categories/{category}/restore', [PlatformCategoryController::class, 'restore'])
            ->name('platform.categories.restore')
            ->withTrashed();

        Route::get('products/suggestions', [PlatformProductController::class, 'suggestions'])
            ->name('platform.products.suggestions');
        Route::resource('products', PlatformProductController::class)
            ->names('platform.products')
            ->middlewareFor(['store', 'update'], 'precognitive');
        Route::post('products/{product}/restore', [PlatformProductController::class, 'restore'])
            ->name('platform.products.restore')
            ->withTrashed();

        Route::post('products/{product}/variants', [PlatformProductVariantController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.products.variants.store');
        Route::put('products/{product}/variants/{variant}', [PlatformProductVariantController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.products.variants.update');
        Route::delete('products/{product}/variants/{variant}', [PlatformProductVariantController::class, 'destroy'])
            ->name('platform.products.variants.destroy');
    });

    Route::middleware(['permission:view-activity-logs'])->group(function () {
        Route::get('activity-logs', [PlatformActivityLogController::class, 'index'])->name('platform.activity-logs.index');
        Route::get('activity-logs/{activity}', [PlatformActivityLogController::class, 'show'])->name('platform.activity-logs.show');
    });

    Route::middleware(['permission:manage-database-backups'])->group(function () {
        Route::get('database-backups', [PlatformDatabaseBackupController::class, 'index'])->name('platform.database-backups.index');
        Route::post('database-backups/run', [PlatformDatabaseBackupController::class, 'run'])
            ->middleware('throttle:5,1')
            ->name('platform.database-backups.run');
        Route::post('database-backups/clean', [PlatformDatabaseBackupController::class, 'clean'])
            ->middleware('throttle:5,1')
            ->name('platform.database-backups.clean');
        Route::get('database-backups/download', [PlatformDatabaseBackupController::class, 'download'])
            ->name('platform.database-backups.download');
        Route::post('database-backups/delete', [PlatformDatabaseBackupController::class, 'destroy'])
            ->middleware('throttle:20,1')
            ->name('platform.database-backups.destroy');
        Route::post('database-backups/restore', [PlatformDatabaseBackupController::class, 'restore'])
            ->middleware('throttle:5,1')
            ->name('platform.database-backups.restore');
    });
});

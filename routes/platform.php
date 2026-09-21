<?php

use App\Http\Controllers\Platform\ActivityLogController as PlatformActivityLogController;
use App\Http\Controllers\Platform\AdminController as PlatformAdminController;
use App\Http\Controllers\Platform\BranchController as PlatformBranchController;
use App\Http\Controllers\Platform\BranchDriverController as PlatformBranchDriverController;
use App\Http\Controllers\Platform\BranchInventoryController as PlatformBranchInventoryController;
use App\Http\Controllers\Platform\BranchScopedStaffController as PlatformBranchScopedStaffController;
use App\Http\Controllers\Platform\BranchShiftAssignmentController as PlatformBranchShiftAssignmentController;
use App\Http\Controllers\Platform\BranchShiftRosterPeriodController as PlatformBranchShiftRosterPeriodController;
use App\Http\Controllers\Platform\BranchShiftTemplateController as PlatformBranchShiftTemplateController;
use App\Http\Controllers\Platform\BranchStaffController as PlatformBranchStaffController;
use App\Http\Controllers\Platform\CategoryController as PlatformCategoryController;
use App\Http\Controllers\Platform\ConversationController as PlatformConversationController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\DatabaseBackupController as PlatformDatabaseBackupController;
use App\Http\Controllers\Platform\DriverController as PlatformDriverController;
use App\Http\Controllers\Platform\FcmController as PlatformFcmController;
use App\Http\Controllers\Platform\NotificationController as PlatformNotificationController;
use App\Http\Controllers\Platform\ObservabilityController as PlatformObservabilityController;
use App\Http\Controllers\Platform\PermissionController as PlatformPermissionController;
use App\Http\Controllers\Platform\ProductController as PlatformProductController;
use App\Http\Controllers\Platform\ProductVariantController as PlatformProductVariantController;
use App\Http\Controllers\Platform\RealtimeController as PlatformRealtimeController;
use App\Http\Controllers\Platform\Reports\PriceHistoryController as PlatformPriceHistoryController;
use App\Http\Controllers\Platform\Reports\StockTransactionController as PlatformStockTransactionController;
use App\Http\Controllers\Platform\RoleController as PlatformRoleController;
use App\Http\Controllers\Platform\ShiftAssignmentController as PlatformShiftAssignmentController;
use App\Http\Controllers\Platform\ShiftRosterPeriodController as PlatformShiftRosterPeriodController;
use App\Http\Controllers\Platform\ShiftTemplateController as PlatformShiftTemplateController;
use App\Http\Controllers\Platform\StockController as PlatformStockController;
use App\Http\Controllers\Platform\TransferController as PlatformTransferController;
use App\Support\ProductManageRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:platform'])->prefix('platform')->group(function () {
    Route::get('dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');
    Route::get('notifications', [PlatformNotificationController::class, 'index'])->name('platform.notifications.index');

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
        Route::resource('admins', PlatformAdminController::class)
            ->parameters(['admins' => 'user'])
            ->names('platform.admins')
            ->middlewareFor(['store', 'update'], 'precognitive');
    });

    Route::permanentRedirect('staff', '/platform/admins');
    Route::permanentRedirect('staff/create', '/platform/admins/create');
    Route::permanentRedirect('staff/{user}', '/platform/admins/{user}');
    Route::permanentRedirect('staff/{user}/edit', '/platform/admins/{user}/edit');

    Route::middleware(['permission:manage-branch-users'])->group(function () {
        Route::resource('branch-staff', PlatformBranchStaffController::class)
            ->parameters(['branch-staff' => 'user'])
            ->names('platform.branch-staff')
            ->middlewareFor(['store', 'update'], 'precognitive');

        Route::post('branch-staff/{user}/transfer', [PlatformTransferController::class, 'storeStaff'])
            ->name('platform.staff.transfer');

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

        Route::get('drivers-branch-options', [PlatformDriverController::class, 'branchOptions'])
            ->name('platform.drivers.branch-options');
        Route::put('drivers/{user}/branches', [PlatformDriverController::class, 'syncBranches'])
            ->name('platform.drivers.branches.sync');
        Route::post('drivers/{user}/transfer', [PlatformTransferController::class, 'storeDriver'])
            ->name('platform.drivers.transfer');
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

    Route::middleware(['permission:view-transfers'])->group(function () {
        Route::get('transfers', [PlatformTransferController::class, 'index'])->name('platform.transfers.index');
    });

    Route::middleware(['permission:manage-shifts'])->group(function () {
        Route::redirect('shifts/assign', '/platform/shift-assignments');
        Route::redirect('shift-periods/create', '/platform/shifts/periods/create');

        Route::get('shifts', [PlatformShiftTemplateController::class, 'index'])->name('platform.shift-templates.index');
        Route::get('shifts/create', [PlatformShiftTemplateController::class, 'create'])->name('platform.shift-templates.create');
        Route::get('shifts/periods/create', [PlatformShiftRosterPeriodController::class, 'create'])->name('platform.shift-roster-periods.create');
        Route::get('shifts/periods/{shift_roster_period}/edit', [PlatformShiftRosterPeriodController::class, 'edit'])->name('platform.shift-roster-periods.edit');
        Route::post('shift-templates', [PlatformShiftTemplateController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.shift-templates.store');
        Route::get('shifts/{shift_template}/edit', [PlatformShiftTemplateController::class, 'edit'])->name('platform.shift-templates.edit');
        Route::put('shift-templates/{shift_template}', [PlatformShiftTemplateController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.shift-templates.update');
        Route::post('shift-roster-periods', [PlatformShiftRosterPeriodController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.shift-roster-periods.store');
        Route::put('shift-roster-periods/{shift_roster_period}', [PlatformShiftRosterPeriodController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.shift-roster-periods.update');

        Route::get('shift-assignments', [PlatformShiftAssignmentController::class, 'create'])->name('platform.shift-assignments.create');
        Route::post('shift-assignments', [PlatformShiftAssignmentController::class, 'store'])->name('platform.shift-assignments.store');
        Route::put('shift-assignments/{user_shift_assignment}', [PlatformShiftAssignmentController::class, 'update'])->name('platform.shift-assignments.update');

        Route::get('branch-shifts', [PlatformBranchShiftTemplateController::class, 'index'])->name('platform.branch-shift-templates.index');
        Route::get('branch-shifts/create', [PlatformBranchShiftTemplateController::class, 'create'])->name('platform.branch-shift-templates.create');
        Route::get('branch-shifts/periods/create', [PlatformBranchShiftRosterPeriodController::class, 'create'])->name('platform.branch-shift-roster-periods.create');
        Route::get('branch-shifts/periods/{shift_roster_period}/edit', [PlatformBranchShiftRosterPeriodController::class, 'edit'])->name('platform.branch-shift-roster-periods.edit');
        Route::post('branch-shift-templates', [PlatformBranchShiftTemplateController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.branch-shift-templates.store');
        Route::get('branch-shifts/{shift_template}/edit', [PlatformBranchShiftTemplateController::class, 'edit'])->name('platform.branch-shift-templates.edit');
        Route::put('branch-shift-templates/{shift_template}', [PlatformBranchShiftTemplateController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.branch-shift-templates.update');
        Route::post('branch-shift-roster-periods', [PlatformBranchShiftRosterPeriodController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.branch-shift-roster-periods.store');
        Route::put('branch-shift-roster-periods/{shift_roster_period}', [PlatformBranchShiftRosterPeriodController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.branch-shift-roster-periods.update');

        Route::get('branch-shift-assignments', [PlatformBranchShiftAssignmentController::class, 'create'])->name('platform.branch-shift-assignments.create');
        Route::post('branch-shift-assignments', [PlatformBranchShiftAssignmentController::class, 'store'])->name('platform.branch-shift-assignments.store');
        Route::put('branch-shift-assignments/{user_shift_assignment}', [PlatformBranchShiftAssignmentController::class, 'update'])->name('platform.branch-shift-assignments.update');
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
        Route::post('branches/{branch}/products/{product}/inventory', [PlatformBranchInventoryController::class, 'adjustInventory'])
            ->name('platform.branches.products.inventory');
        Route::put('branches/{branch}/products/{product}/prices', [PlatformBranchInventoryController::class, 'upsertPrice'])
            ->name('platform.branches.products.prices');
        Route::get('discounts', fn (Request $request) => ProductManageRedirect::platform($request))
            ->name('platform.discounts');
        Route::put('discounts', fn (Request $request) => ProductManageRedirect::platform($request))
            ->name('platform.discounts.update');
        Route::get('discounts/preview', fn (Request $request) => ProductManageRedirect::platform($request))
            ->name('platform.discounts.preview');
        Route::get('stock/discount', fn (Request $request) => ProductManageRedirect::platform($request, 301));
        Route::get('stock/discount-preview', fn (Request $request) => ProductManageRedirect::platform($request, 301));
        Route::get('stock/manage', [PlatformStockController::class, 'manage'])->name('platform.stock.manage');
        Route::get('stock/import', [PlatformStockController::class, 'import'])->name('platform.stock.import');
        Route::post('stock/import', [PlatformStockController::class, 'storeImport'])->name('platform.stock.import.store');
        Route::get('stock/price', fn (Request $request) => ProductManageRedirect::platform($request))
            ->name('platform.stock.price');
        Route::get('reports/stock-transactions', [PlatformStockTransactionController::class, 'index'])
            ->name('platform.reports.stock-transactions');
        Route::get('reports/stock-transactions/export', [PlatformStockTransactionController::class, 'export'])
            ->name('platform.reports.stock-transactions.export');
        Route::get('reports/price-history', [PlatformPriceHistoryController::class, 'index'])
            ->name('platform.reports.price-history');
        Route::get('reports/price-history/export', [PlatformPriceHistoryController::class, 'export'])
            ->name('platform.reports.price-history.export');
        Route::get('stock/branch-options', [PlatformStockController::class, 'branchOptions'])
            ->name('platform.stock.branch-options');
        Route::get('stock/product-options', [PlatformStockController::class, 'productOptions'])
            ->name('platform.stock.product-options');
    });

    Route::middleware(['permission:manage-catalog|manage-branch-inventories'])->group(function () {
        Route::get('categories/options', [PlatformCategoryController::class, 'options'])
            ->name('platform.categories.options');
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
        Route::put('categories/{category}/status', [PlatformCategoryController::class, 'toggleStatus'])
            ->name('platform.categories.status');
        Route::put('categories/{category}/featured', [PlatformCategoryController::class, 'toggleFeatured'])
            ->name('platform.categories.featured');
        Route::put('categories/{category}/nav', [PlatformCategoryController::class, 'toggleNav'])
            ->name('platform.categories.nav');
        Route::put('categories/{category}/nav-sort', [PlatformCategoryController::class, 'updateNavSort'])
            ->name('platform.categories.nav-sort');

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

    Route::middleware(['permission:manage-conversations'])->group(function () {
        Route::get('conversations', [PlatformConversationController::class, 'index'])
            ->name('platform.conversations.index');
        Route::get('conversations/order-options', [PlatformConversationController::class, 'orderOptions'])
            ->name('platform.conversations.order-options');
        Route::post('conversations', [PlatformConversationController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.conversations.store');
        Route::get('conversations/{conversation}', [PlatformConversationController::class, 'show'])
            ->whereNumber('conversation')
            ->name('platform.conversations.show');
        Route::get('conversations/{conversation}/messages', [PlatformConversationController::class, 'messages'])
            ->whereNumber('conversation')
            ->name('platform.conversations.messages');
        Route::post('conversations/{conversation}/messages', [PlatformConversationController::class, 'storeMessage'])
            ->whereNumber('conversation')
            ->middleware('throttle:conversation-messages')
            ->name('platform.conversations.messages.store');
        Route::get('conversation-branch-options', [PlatformConversationController::class, 'branchOptions'])
            ->name('platform.conversations.branch-options');
        Route::post('conversations/{conversation}/delivered', [PlatformConversationController::class, 'delivered'])
            ->whereNumber('conversation')
            ->name('platform.conversations.delivered');
        Route::post('conversations/{conversation}/read', [PlatformConversationController::class, 'read'])
            ->whereNumber('conversation')
            ->name('platform.conversations.read');
        Route::post('conversations/{conversation}/close', [PlatformConversationController::class, 'close'])
            ->whereNumber('conversation')
            ->name('platform.conversations.close');
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

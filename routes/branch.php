<?php

use App\Http\Controllers\Branch\ActivityLogController as BranchActivityLogController;
use App\Http\Controllers\Branch\AreaController as BranchAreaController;
use App\Http\Controllers\Branch\CatalogPricingController as BranchCatalogPricingController;
use App\Http\Controllers\Branch\CategoryController as BranchCategoryController;
use App\Http\Controllers\Branch\ConversationController as BranchConversationController;
use App\Http\Controllers\Branch\CustomerController as BranchCustomerController;
use App\Http\Controllers\Branch\DashboardController as BranchDashboardController;
use App\Http\Controllers\Branch\DriverController as BranchDriverController;
use App\Http\Controllers\Branch\DriverProductAssignController as BranchDriverProductAssignController;
use App\Http\Controllers\Branch\GeneralSettingsController as BranchGeneralSettingsController;
use App\Http\Controllers\Branch\NotificationController as BranchNotificationController;
use App\Http\Controllers\Branch\OrderAssignController as BranchOrderAssignController;
use App\Http\Controllers\Branch\OrderController as BranchOrderController;
use App\Http\Controllers\Branch\PacksSavesController as BranchPacksSavesController;
use App\Http\Controllers\Branch\PermissionController as BranchPermissionController;
use App\Http\Controllers\Branch\ProductController as BranchProductController;
use App\Http\Controllers\Branch\ProductReviewController as BranchProductReviewController;
use App\Http\Controllers\Branch\Reports\OrderAssignHistoryController as BranchOrderAssignHistoryController;
use App\Http\Controllers\Branch\Reports\OrderReportController as BranchOrderReportController;
use App\Http\Controllers\Branch\Reports\PriceHistoryController as BranchPriceHistoryController;
use App\Http\Controllers\Branch\Reports\ProductAssignTransactionController as BranchProductAssignTransactionController;
use App\Http\Controllers\Branch\Reports\StockTransactionController as BranchStockTransactionController;
use App\Http\Controllers\Branch\RoleController as BranchRoleController;
use App\Http\Controllers\Branch\ShiftAssignmentController as BranchShiftAssignmentController;
use App\Http\Controllers\Branch\ShiftTemplateController as BranchShiftTemplateController;
use App\Http\Controllers\Branch\StaffController as BranchStaffController;
use App\Http\Controllers\Branch\StockController as BranchStockController;
use App\Http\Controllers\Branch\StockTransferController as BranchStockTransferController;
use App\Models\Branch;
use App\Models\OrderFulfillment;
use App\Support\ProductManageRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:branch', 'resolve.branch'])->prefix('branch/{branch}')->group(function () {
    Route::get('dashboard', [BranchDashboardController::class, 'index'])->name('branch.dashboard');
    Route::get('notifications', [BranchNotificationController::class, 'index'])->name('branch.notifications.index');
    Route::get('notifications/{userNotification}', [BranchNotificationController::class, 'show'])->name('branch.notifications.show');
    Route::post('notifications/{userNotification}/read', [BranchNotificationController::class, 'read'])->name('branch.notifications.read');
    Route::delete('notifications/{userNotification}', [BranchNotificationController::class, 'destroy'])->name('branch.notifications.destroy');

    Route::middleware(['permission:manage-branch-products'])->group(function () {
        Route::get('categories/options', [BranchCategoryController::class, 'options'])
            ->name('branch.categories.options');
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
        Route::get('packs-saves', [BranchPacksSavesController::class, 'index'])->name('branch.packs-saves.index');
        Route::get('packs-saves/create', [BranchPacksSavesController::class, 'create'])->name('branch.packs-saves.create');
        Route::post('packs-saves', [BranchPacksSavesController::class, 'store'])->name('branch.packs-saves.store');
        Route::get('packs-saves/{pack}', [BranchPacksSavesController::class, 'show'])->name('branch.packs-saves.show');
        Route::get('packs-saves/{pack}/edit', [BranchPacksSavesController::class, 'edit'])->name('branch.packs-saves.edit');
        Route::put('packs-saves/{pack}', [BranchPacksSavesController::class, 'update'])->name('branch.packs-saves.update');
        Route::delete('packs-saves/{pack}', [BranchPacksSavesController::class, 'destroy'])->name('branch.packs-saves.destroy');
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
        Route::get('catalog/pricing/manage', [BranchCatalogPricingController::class, 'manage'])
            ->name('branch.catalog.pricing.manage');
        Route::put('products/{product}/catalog-prices', [BranchCatalogPricingController::class, 'upsert'])
            ->name('branch.products.catalog-prices');
        Route::get('discounts', fn (Request $request, Branch $branch) => ProductManageRedirect::branch($request, $branch))
            ->name('branch.discounts');
        Route::put('discounts', fn (Request $request, Branch $branch) => ProductManageRedirect::branch($request, $branch))
            ->name('branch.discounts.update');
        Route::get('discounts/preview', fn (Request $request, Branch $branch) => ProductManageRedirect::branch($request, $branch))
            ->name('branch.discounts.preview');
        Route::get('stock/discount', fn (Request $request, Branch $branch) => ProductManageRedirect::branch($request, $branch, 301));
        Route::get('stock/discount-preview', fn (Request $request, Branch $branch) => ProductManageRedirect::branch($request, $branch, 301));
        Route::get('stock/manage', [BranchStockController::class, 'manage'])->name('branch.stock.manage');
        Route::get('stock/import', [BranchStockController::class, 'import'])->name('branch.stock.import');
        Route::post('stock/import', [BranchStockController::class, 'storeImport'])->name('branch.stock.import.store');
        Route::get('stock/price', fn (Request $request, Branch $branch) => ProductManageRedirect::branch($request, $branch))
            ->name('branch.stock.price');
        Route::get('reports/order-reports', [BranchOrderReportController::class, 'index'])
            ->name('branch.reports.order-reports');
        Route::get('reports/order-operations', function (string $branch) {
            return redirect()->route('branch.reports.order-reports', ['branch' => $branch], 301);
        });
        Route::get('reports/stock-transactions', [BranchStockTransactionController::class, 'index'])
            ->name('branch.reports.stock-transactions');
        Route::get('reports/stock-transactions/export', [BranchStockTransactionController::class, 'export'])
            ->name('branch.reports.stock-transactions.export');
        Route::get('reports/price-history', [BranchPriceHistoryController::class, 'index'])
            ->name('branch.reports.price-history');
        Route::get('reports/price-history/export', [BranchPriceHistoryController::class, 'export'])
            ->name('branch.reports.price-history.export');
        Route::get('stock/product-options', [BranchStockController::class, 'productOptions'])
            ->name('branch.stock.product-options');
    });

    Route::middleware(['permission:manage-branch-orders'])->group(function () {
        Route::get('customers', [BranchCustomerController::class, 'index'])->name('branch.customers.index');
        Route::get('customers/{user}', [BranchCustomerController::class, 'show'])
            ->whereNumber('user')
            ->name('branch.customers.show');

        Route::get('reviews', [BranchProductReviewController::class, 'index'])->name('branch.reviews.index');
        Route::get('reviews/customer-options', [BranchProductReviewController::class, 'customerOptions'])
            ->name('branch.reviews.customer-options');
        Route::get('reviews/product-options', [BranchProductReviewController::class, 'productOptions'])
            ->name('branch.reviews.product-options');
        Route::get('reviews/{productReview}', [BranchProductReviewController::class, 'show'])
            ->whereNumber('productReview')
            ->name('branch.reviews.show');
        Route::post('reviews/{productReview}/hide', [BranchProductReviewController::class, 'hide'])
            ->whereNumber('productReview')
            ->name('branch.reviews.hide');
        Route::post('reviews/{productReview}/unhide', [BranchProductReviewController::class, 'unhide'])
            ->whereNumber('productReview')
            ->name('branch.reviews.unhide');

        Route::get('orders', [BranchOrderController::class, 'index'])->name('branch.orders.index');
        Route::get('orders/{fulfillment}', [BranchOrderController::class, 'show'])->name('branch.orders.show');
        Route::put('orders/{fulfillment}/status', [BranchOrderController::class, 'updateStatus'])
            ->name('branch.orders.status');
        Route::post('orders/{fulfillment}/paid', [BranchOrderController::class, 'markPaid'])
            ->name('branch.orders.paid');
        Route::post('orders/{fulfillment}/assign-driver', [BranchOrderAssignController::class, 'store'])
            ->name('branch.orders.assign-driver');
        Route::get('orders/{fulfillment}/assign-candidates', [BranchOrderAssignController::class, 'candidates'])
            ->name('branch.orders.assign-candidates');
        Route::get('orders/{fulfillment}/driver-assignment-history', function (string $branch, OrderFulfillment $fulfillment) {
            return redirect()->route('branch.reports.order-assign-history', [
                'branch' => $branch,
                'filter' => ['order_id' => $fulfillment->fulfillment_number],
            ]);
        })->name('branch.orders.driver-assignment-history');
        Route::get('reports/order-assign-history', [BranchOrderAssignHistoryController::class, 'index'])
            ->name('branch.reports.order-assign-history');
        Route::get('reports/order-assign-history/export', [BranchOrderAssignHistoryController::class, 'export'])
            ->name('branch.reports.order-assign-history.export');
        Route::get('reports/order-assign-history/driver-options', [BranchOrderAssignHistoryController::class, 'driverOptions'])
            ->name('branch.reports.order-assign-history.driver-options');
        Route::post('orders/{fulfillment}/placement-code', [BranchOrderController::class, 'resendPlacementCode'])
            ->name('branch.orders.placement-code');
        Route::post('orders/{fulfillment}/cancel', [BranchOrderController::class, 'cancel'])
            ->name('branch.orders.cancel');
        Route::post('orders/{fulfillment}/retry-delivery', [BranchOrderController::class, 'retryDelivery'])
            ->name('branch.orders.retry-delivery');
        Route::post('orders/{fulfillment}/reschedule-delivery', [BranchOrderController::class, 'rescheduleDelivery'])
            ->name('branch.orders.reschedule-delivery');
        Route::post('orders/{fulfillment}/return-delivery', [BranchOrderController::class, 'returnDelivery'])
            ->name('branch.orders.return-delivery');
        Route::post('orders/{fulfillment}/deliver', [BranchOrderController::class, 'deliver'])
            ->name('branch.orders.deliver');
        Route::post('orders/{fulfillment}/deliver-without-code', [BranchOrderController::class, 'deliverWithoutCode'])
            ->middleware('permission:complete-branch-orders-without-code')
            ->name('branch.orders.deliver-without-code');
        Route::post('orders/{fulfillment}/fail', [BranchOrderController::class, 'fail'])
            ->name('branch.orders.fail');
        Route::post('orders/{fulfillment}/returns/{orderReturn}/approve', [BranchOrderController::class, 'approveReturn'])
            ->name('branch.orders.returns.approve');
        Route::post('orders/{fulfillment}/returns/{orderReturn}/reject', [BranchOrderController::class, 'rejectReturn'])
            ->name('branch.orders.returns.reject');
        Route::post('orders/{fulfillment}/confirm-return', [BranchOrderController::class, 'confirmReturn'])
            ->name('branch.orders.confirm-return');
        Route::get('stock-transfers', [BranchStockTransferController::class, 'index'])
            ->name('branch.stock-transfers.index');
        Route::post('stock-transfers/{stockTransfer}/approve', [BranchStockTransferController::class, 'approve'])
            ->name('branch.stock-transfers.approve');
        Route::post('stock-transfers/{stockTransfer}/pick', [BranchStockTransferController::class, 'pick'])
            ->name('branch.stock-transfers.pick');
        Route::post('stock-transfers/{stockTransfer}/dispatch', [BranchStockTransferController::class, 'dispatch'])
            ->name('branch.stock-transfers.dispatch');
        Route::post('stock-transfers/{stockTransfer}/receive', [BranchStockTransferController::class, 'receive'])
            ->name('branch.stock-transfers.receive');
    });

    Route::middleware(['permission:manage-branch-conversations'])->group(function () {
        Route::get('conversations', [BranchConversationController::class, 'index'])
            ->name('branch.conversations.index');
        Route::get('conversations/order-options', [BranchConversationController::class, 'orderOptions'])
            ->name('branch.conversations.order-options');
        Route::post('conversations', [BranchConversationController::class, 'store'])
            ->middleware('precognitive')
            ->name('branch.conversations.store');
        Route::get('conversations/{conversation}', [BranchConversationController::class, 'show'])
            ->whereNumber('conversation')
            ->name('branch.conversations.show');
        Route::get('conversations/{conversation}/messages', [BranchConversationController::class, 'messages'])
            ->whereNumber('conversation')
            ->name('branch.conversations.messages');
        Route::post('conversations/{conversation}/messages', [BranchConversationController::class, 'storeMessage'])
            ->whereNumber('conversation')
            ->middleware('throttle:conversation-messages')
            ->name('branch.conversations.messages.store');
        Route::post('conversations/{conversation}/delivered', [BranchConversationController::class, 'delivered'])
            ->whereNumber('conversation')
            ->name('branch.conversations.delivered');
        Route::post('conversations/{conversation}/read', [BranchConversationController::class, 'read'])
            ->whereNumber('conversation')
            ->name('branch.conversations.read');
        Route::post('conversations/{conversation}/close', [BranchConversationController::class, 'close'])
            ->whereNumber('conversation')
            ->name('branch.conversations.close');
    });

    Route::middleware(['permission:view-branch-activity-logs'])->group(function () {
        Route::get('activity-logs', [BranchActivityLogController::class, 'index'])->name('branch.activity-logs.index');
        Route::get('activity-logs/{activity}', [BranchActivityLogController::class, 'show'])
            ->whereNumber('activity')
            ->name('branch.activity-logs.show');
    });

    Route::middleware(['permission:manage-branch-settings'])->group(function () {
        Route::get('settings/general', [BranchGeneralSettingsController::class, 'edit'])
            ->name('branch.settings.general.edit');
        Route::put('settings/general', [BranchGeneralSettingsController::class, 'update'])
            ->middleware('precognitive')
            ->name('branch.settings.general.update');

        Route::get('areas', [BranchAreaController::class, 'index'])->name('branch.areas.index');
        Route::get('areas/create', [BranchAreaController::class, 'create'])->name('branch.areas.create');
        Route::post('areas', [BranchAreaController::class, 'store'])
            ->middleware('precognitive')
            ->name('branch.areas.store');
        Route::get('areas/{area}', [BranchAreaController::class, 'show'])->name('branch.areas.show');
        Route::get('areas/{area}/edit', [BranchAreaController::class, 'edit'])->name('branch.areas.edit');
        Route::put('areas/{area}', [BranchAreaController::class, 'update'])
            ->middleware('precognitive')
            ->name('branch.areas.update');
        Route::delete('areas/{area}', [BranchAreaController::class, 'destroy'])->name('branch.areas.destroy');
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

        // Legacy nested URLs (before drivers/{user} so they never bind as a user id).
        Route::get('drivers/assign-products', function (Branch $branch) {
            return redirect()->route('branch.assign-products.create', $branch);
        });
        Route::get('drivers/assign-products/driver-options', function (Branch $branch) {
            return redirect()->route('branch.assign-products.driver-options', $branch);
        });
        Route::post('drivers/assign-products', function (Branch $branch) {
            return redirect()->route('branch.assign-products.store', $branch, 308);
        });

        Route::get('drivers/{user}', [BranchDriverController::class, 'show'])
            ->whereNumber('user')
            ->name('branch.drivers.show');
        Route::get('drivers/{user}/edit', [BranchDriverController::class, 'edit'])
            ->whereNumber('user')
            ->name('branch.drivers.edit');
        Route::put('drivers/{user}', [BranchDriverController::class, 'update'])
            ->middleware('precognitive')
            ->whereNumber('user')
            ->name('branch.drivers.update');

        // Sibling of /drivers (not nested) so Drivers nav is not prefix-active on this page.
        Route::get('assign-products', [BranchDriverProductAssignController::class, 'create'])
            ->name('branch.assign-products.create');
        Route::get('assign-products/driver-options', [BranchDriverProductAssignController::class, 'driverOptions'])
            ->name('branch.assign-products.driver-options');
        Route::post('assign-products', [BranchDriverProductAssignController::class, 'store'])
            ->name('branch.assign-products.store');

        Route::get('reports/product-assign-transactions', [BranchProductAssignTransactionController::class, 'index'])
            ->name('branch.reports.product-assign-transactions');
        Route::get('reports/product-assign-transactions/product-options', [BranchProductAssignTransactionController::class, 'productOptions'])
            ->name('branch.reports.product-assign-transactions.product-options');
        Route::get('reports/product-assign-transactions/export', [BranchProductAssignTransactionController::class, 'export'])
            ->name('branch.reports.product-assign-transactions.export');
    });

    Route::middleware(['permission:manage-branch-shifts'])->group(function () {
        Route::get('shifts/assign', function (Branch $branch) {
            return redirect()->route('branch.shift-assignments.create', $branch);
        });

        Route::get('shifts', [BranchShiftTemplateController::class, 'index'])->name('branch.shift-templates.index');
        Route::get('shifts/assigned-people', [BranchShiftAssignmentController::class, 'index'])->name('branch.shift-assignments.index');
        Route::get('shifts/create', [BranchShiftTemplateController::class, 'create'])->name('branch.shift-templates.create');
        Route::post('shift-templates', [BranchShiftTemplateController::class, 'store'])
            ->middleware('precognitive')
            ->name('branch.shift-templates.store');
        Route::get('shifts/{shift_template}/edit', [BranchShiftTemplateController::class, 'edit'])->name('branch.shift-templates.edit');
        Route::put('shift-templates/{shift_template}', [BranchShiftTemplateController::class, 'update'])
            ->middleware('precognitive')
            ->name('branch.shift-templates.update');

        Route::get('shift-assignments', [BranchShiftAssignmentController::class, 'create'])->name('branch.shift-assignments.create');
        Route::get('shift-assignment-people-options', [BranchShiftAssignmentController::class, 'peopleOptions'])
            ->name('branch.shift-assignments.people-options');
        Route::post('shift-assignments', [BranchShiftAssignmentController::class, 'store'])->name('branch.shift-assignments.store');
        Route::get('shift-assignments/{user_shift_assignment}/edit', [BranchShiftAssignmentController::class, 'edit'])->name('branch.shift-assignments.edit');
    });
});

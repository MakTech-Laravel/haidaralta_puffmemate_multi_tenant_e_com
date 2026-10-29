<?php

use App\Http\Controllers\Platform\ActivityLogController as PlatformActivityLogController;
use App\Http\Controllers\Platform\AdminController as PlatformAdminController;
use App\Http\Controllers\Platform\AnnouncementController as PlatformAnnouncementController;
use App\Http\Controllers\Platform\BranchAreaController as PlatformBranchAreaController;
use App\Http\Controllers\Platform\BranchAreasController as PlatformBranchAreasController;
use App\Http\Controllers\Platform\BranchChatController as PlatformBranchChatController;
use App\Http\Controllers\Platform\BranchController as PlatformBranchController;
use App\Http\Controllers\Platform\BranchDriverController as PlatformBranchDriverController;
use App\Http\Controllers\Platform\BranchInventoryController as PlatformBranchInventoryController;
use App\Http\Controllers\Platform\BranchScopedStaffController as PlatformBranchScopedStaffController;
use App\Http\Controllers\Platform\BranchShiftAssignmentController as PlatformBranchShiftAssignmentController;
use App\Http\Controllers\Platform\BranchShiftTemplateController as PlatformBranchShiftTemplateController;
use App\Http\Controllers\Platform\BranchStaffController as PlatformBranchStaffController;
use App\Http\Controllers\Platform\CatalogPricingController as PlatformCatalogPricingController;
use App\Http\Controllers\Platform\CategoryController as PlatformCategoryController;
use App\Http\Controllers\Platform\CmsPageController as PlatformCmsPageController;
use App\Http\Controllers\Platform\ContactInquiryController as PlatformContactInquiryController;
use App\Http\Controllers\Platform\ContactSettingsController as PlatformContactSettingsController;
use App\Http\Controllers\Platform\ConversationController as PlatformConversationController;
use App\Http\Controllers\Platform\CouponController as PlatformCouponController;
use App\Http\Controllers\Platform\CustomerController as PlatformCustomerController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\DatabaseBackupController as PlatformDatabaseBackupController;
use App\Http\Controllers\Platform\DeliverySettingsController as PlatformDeliverySettingsController;
use App\Http\Controllers\Platform\DriverController as PlatformDriverController;
use App\Http\Controllers\Platform\DriverProductAssignController as PlatformDriverProductAssignController;
use App\Http\Controllers\Platform\FcmController as PlatformFcmController;
use App\Http\Controllers\Platform\GeneralSettingsController as PlatformGeneralSettingsController;
use App\Http\Controllers\Platform\HomeBannerController as PlatformHomeBannerController;
use App\Http\Controllers\Platform\NotificationController as PlatformNotificationController;
use App\Http\Controllers\Platform\ObservabilityController as PlatformObservabilityController;
use App\Http\Controllers\Platform\OrderAssignController as PlatformOrderAssignController;
use App\Http\Controllers\Platform\OrderController as PlatformOrderController;
use App\Http\Controllers\Platform\PacksSavesController as PlatformPacksSavesController;
use App\Http\Controllers\Platform\PaymentGatewaySettingsController as PlatformPaymentGatewaySettingsController;
use App\Http\Controllers\Platform\PermissionController as PlatformPermissionController;
use App\Http\Controllers\Platform\PriceRangeController as PlatformPriceRangeController;
use App\Http\Controllers\Platform\ProductController as PlatformProductController;
use App\Http\Controllers\Platform\ProductReviewController as PlatformProductReviewController;
use App\Http\Controllers\Platform\ProductVariantController as PlatformProductVariantController;
use App\Http\Controllers\Platform\RealtimeController as PlatformRealtimeController;
use App\Http\Controllers\Platform\Reports\OrderAssignHistoryController as PlatformOrderAssignHistoryController;
use App\Http\Controllers\Platform\Reports\OrderReportController as PlatformOrderReportController;
use App\Http\Controllers\Platform\Reports\PriceHistoryController as PlatformPriceHistoryController;
use App\Http\Controllers\Platform\Reports\ProductAssignTransactionController as PlatformProductAssignTransactionController;
use App\Http\Controllers\Platform\Reports\StockTransactionController as PlatformStockTransactionController;
use App\Http\Controllers\Platform\RoleController as PlatformRoleController;
use App\Http\Controllers\Platform\ShiftAssignmentController as PlatformShiftAssignmentController;
use App\Http\Controllers\Platform\ShiftTemplateController as PlatformShiftTemplateController;
use App\Http\Controllers\Platform\SmtpSettingsController as PlatformSmtpSettingsController;
use App\Http\Controllers\Platform\StockController as PlatformStockController;
use App\Http\Controllers\Platform\StockTransferController as PlatformStockTransferController;
use App\Http\Controllers\Platform\TransferController as PlatformTransferController;
use App\Http\Controllers\Platform\WholesaleInquiryController as PlatformWholesaleInquiryController;
use App\Http\Controllers\Platform\WholesaleOrderController as PlatformWholesaleOrderController;
use App\Support\ProductManageRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['ensure.guard:platform'])->prefix('platform')->group(function () {
    Route::middleware(['permission:view-dashboard'])->group(function () {
        Route::get('dashboard', [PlatformDashboardController::class, 'index'])->name('platform.dashboard');
        Route::get('notifications', [PlatformNotificationController::class, 'index'])->name('platform.notifications.index');
        Route::get('notifications/{userNotification}', [PlatformNotificationController::class, 'show'])->name('platform.notifications.show');
        Route::post('notifications/{userNotification}/read', [PlatformNotificationController::class, 'read'])->name('platform.notifications.read');
        Route::delete('notifications/{userNotification}', [PlatformNotificationController::class, 'destroy'])->name('platform.notifications.destroy');

        Route::get('realtime', [PlatformRealtimeController::class, 'index'])->name('platform.realtime.index');
        Route::get('realtime/ping', [PlatformRealtimeController::class, 'ping'])->name('platform.realtime.ping');
        Route::get('realtime/health', [PlatformRealtimeController::class, 'health'])->name('platform.realtime.health');
        Route::post('realtime/test-notification', [PlatformRealtimeController::class, 'testNotification'])->name('platform.realtime.test-notification');

        Route::get('fcm', [PlatformFcmController::class, 'index'])->name('platform.fcm.index');
        Route::post('fcm/test', [PlatformFcmController::class, 'test'])->middleware('throttle:10,1')->name('platform.fcm.test');
    });

    Route::middleware(['permission:view-horizon|view-pulse'])->group(function () {
        Route::get('observability', [PlatformObservabilityController::class, 'index'])
            ->name('platform.observability.index');
    });

    Route::middleware(['permission:manage-roles'])->group(function () {
        Route::get('roles/export', [PlatformRoleController::class, 'export'])->name('platform.roles.export');
        Route::resource('roles', PlatformRoleController::class)
            ->names('platform.roles')
            ->middlewareFor(['store', 'update'], 'precognitive');
        Route::get('permissions/export', [PlatformPermissionController::class, 'export'])->name('platform.permissions.export');
        Route::get('permissions', [PlatformPermissionController::class, 'index'])->name('platform.permissions.index');
    });

    Route::middleware(['permission:manage-wholesale'])->group(function () {
        Route::get('wholesale-inquiries', [PlatformWholesaleInquiryController::class, 'index'])
            ->name('platform.wholesale-inquiries.index');
        Route::get('wholesale-inquiries/branch-options', [PlatformWholesaleInquiryController::class, 'branchOptions'])
            ->name('platform.wholesale-inquiries.branch-options');
        Route::get('wholesale-inquiries/place-order', [PlatformWholesaleInquiryController::class, 'createPlaceOrder'])
            ->name('platform.wholesale-inquiries.place-order.create');
        Route::get('wholesale-inquiries/{wholesaleInquiry}/place-order', [PlatformWholesaleInquiryController::class, 'placeOrder'])
            ->name('platform.wholesale-inquiries.place-order');
        Route::get('wholesale-inquiries/{wholesaleInquiry}', [PlatformWholesaleInquiryController::class, 'show'])
            ->name('platform.wholesale-inquiries.show');
        Route::post('wholesale-inquiries/{wholesaleInquiry}/reject', [PlatformWholesaleInquiryController::class, 'reject'])
            ->name('platform.wholesale-inquiries.reject');
        Route::patch('wholesale-inquiries/{wholesaleInquiry}/note', [PlatformWholesaleInquiryController::class, 'updateNote'])
            ->name('platform.wholesale-inquiries.note');
        Route::get('wholesale-orders/delivery-quote', [PlatformWholesaleOrderController::class, 'deliveryQuote'])
            ->name('platform.wholesale-orders.delivery-quote');
        Route::post('wholesale-orders', [PlatformWholesaleOrderController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('platform.wholesale-orders.store');
    });

    Route::middleware(['permission:manage-users'])->group(function () {
        Route::resource('admins', PlatformAdminController::class)
            ->parameters(['admins' => 'user'])
            ->names('platform.admins')
            ->middlewareFor(['store', 'update'], 'precognitive');

        Route::get('contact-inquiries', [PlatformContactInquiryController::class, 'index'])
            ->name('platform.contact-inquiries.index');
        Route::get('contact-inquiries/{contactInquiry}', [PlatformContactInquiryController::class, 'show'])
            ->name('platform.contact-inquiries.show');

        Route::get('customers', [PlatformCustomerController::class, 'index'])
            ->name('platform.customers.index');
        Route::get('customers/create', [PlatformCustomerController::class, 'create'])
            ->name('platform.customers.create');
        Route::post('customers', [PlatformCustomerController::class, 'store'])
            ->name('platform.customers.store')
            ->middleware('precognitive');
        Route::get('customers/{user}', [PlatformCustomerController::class, 'show'])
            ->name('platform.customers.show');
        Route::get('customers/{user}/edit', [PlatformCustomerController::class, 'edit'])
            ->name('platform.customers.edit');
        Route::match(['put', 'patch'], 'customers/{user}', [PlatformCustomerController::class, 'update'])
            ->name('platform.customers.update')
            ->middleware('precognitive');
        Route::post('customers/{user}/toggle-status', [PlatformCustomerController::class, 'toggleStatus'])
            ->name('platform.customers.toggle-status');

        Route::get('reviews', [PlatformProductReviewController::class, 'index'])
            ->name('platform.reviews.index');
        Route::get('reviews/customer-options', [PlatformProductReviewController::class, 'customerOptions'])
            ->name('platform.reviews.customer-options');
        Route::get('reviews/product-options', [PlatformProductReviewController::class, 'productOptions'])
            ->name('platform.reviews.product-options');
        Route::get('reviews/branch-options', [PlatformProductReviewController::class, 'branchOptions'])
            ->name('platform.reviews.branch-options');
        Route::get('reviews/{productReview}', [PlatformProductReviewController::class, 'show'])
            ->name('platform.reviews.show')
            ->whereNumber('productReview');
        Route::post('reviews/{productReview}/hide', [PlatformProductReviewController::class, 'hide'])
            ->name('platform.reviews.hide');
        Route::post('reviews/{productReview}/unhide', [PlatformProductReviewController::class, 'unhide'])
            ->name('platform.reviews.unhide');
    });

    Route::permanentRedirect('staff', '/platform/admins');
    Route::permanentRedirect('staff/create', '/platform/admins/create');
    Route::permanentRedirect('staff/{user}', '/platform/admins/{user}');
    Route::permanentRedirect('staff/{user}/edit', '/platform/admins/{user}/edit');

    Route::middleware(['permission:manage-branch-users'])->group(function () {
        Route::get('branch-staff-branch-options', [PlatformBranchStaffController::class, 'branchOptions'])
            ->name('platform.branch-staff.branch-options');
        Route::get('branch-staff-role-options', [PlatformBranchStaffController::class, 'roleOptions'])
            ->name('platform.branch-staff.role-options');

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

        Route::get('assign-products', [PlatformDriverProductAssignController::class, 'create'])
            ->name('platform.assign-products.create');
        Route::get('assign-products/driver-options', [PlatformDriverProductAssignController::class, 'driverOptions'])
            ->name('platform.assign-products.driver-options');
        Route::post('assign-products', [PlatformDriverProductAssignController::class, 'store'])
            ->name('platform.assign-products.store');

        Route::get('reports/product-assign-transactions', [PlatformProductAssignTransactionController::class, 'index'])
            ->name('platform.reports.product-assign-transactions');
        Route::get('reports/product-assign-transactions/product-options', [PlatformProductAssignTransactionController::class, 'productOptions'])
            ->name('platform.reports.product-assign-transactions.product-options');
        Route::get('reports/product-assign-transactions/branch-options', [PlatformProductAssignTransactionController::class, 'branchOptions'])
            ->name('platform.reports.product-assign-transactions.branch-options');
        Route::get('reports/product-assign-transactions/driver-options', [PlatformProductAssignTransactionController::class, 'driverOptions'])
            ->name('platform.reports.product-assign-transactions.driver-options');
        Route::get('reports/product-assign-transactions/export', [PlatformProductAssignTransactionController::class, 'export'])
            ->name('platform.reports.product-assign-transactions.export');
    });

    Route::middleware(['permission:view-transfers'])->group(function () {
        Route::redirect('transfers', '/platform/reports/transfer-history', 301);
        Route::get('reports/transfer-history', [PlatformTransferController::class, 'index'])
            ->name('platform.reports.transfer-history');
    });

    Route::middleware(['permission:manage-shifts'])->group(function () {
        Route::redirect('shifts/assign', '/platform/shift-assignments');

        Route::get('shifts', [PlatformShiftTemplateController::class, 'index'])->name('platform.shift-templates.index');
        Route::get('shifts/assigned-people', [PlatformShiftAssignmentController::class, 'index'])->name('platform.shift-assignments.index');
        Route::get('shifts/create', [PlatformShiftTemplateController::class, 'create'])->name('platform.shift-templates.create');
        Route::post('shift-templates', [PlatformShiftTemplateController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.shift-templates.store');
        Route::get('shifts/{shift_template}/edit', [PlatformShiftTemplateController::class, 'edit'])->name('platform.shift-templates.edit');
        Route::put('shift-templates/{shift_template}', [PlatformShiftTemplateController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.shift-templates.update');

        Route::get('shift-assignments', [PlatformShiftAssignmentController::class, 'create'])->name('platform.shift-assignments.create');
        Route::get('shift-assignment-people-options', [PlatformShiftAssignmentController::class, 'peopleOptions'])
            ->name('platform.shift-assignments.people-options');
        Route::post('shift-assignments', [PlatformShiftAssignmentController::class, 'store'])->name('platform.shift-assignments.store');
        Route::get('shift-assignments/{user_shift_assignment}/edit', [PlatformShiftAssignmentController::class, 'edit'])->name('platform.shift-assignments.edit');

        Route::get('branch-shifts', [PlatformBranchShiftTemplateController::class, 'index'])->name('platform.branch-shift-templates.index');
        Route::get('branch-shifts/assigned-people', [PlatformBranchShiftAssignmentController::class, 'index'])->name('platform.branch-shift-assignments.index');
        Route::get('branch-shift-branch-options', [PlatformBranchShiftTemplateController::class, 'branchOptions'])
            ->name('platform.branch-shift-templates.branch-options');
        Route::get('branch-shifts/create', [PlatformBranchShiftTemplateController::class, 'create'])->name('platform.branch-shift-templates.create');
        Route::post('branch-shift-templates', [PlatformBranchShiftTemplateController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.branch-shift-templates.store');
        Route::get('branch-shifts/{shift_template}/edit', [PlatformBranchShiftTemplateController::class, 'edit'])->name('platform.branch-shift-templates.edit');
        Route::put('branch-shift-templates/{shift_template}', [PlatformBranchShiftTemplateController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.branch-shift-templates.update');

        Route::get('branch-shift-assignments', [PlatformBranchShiftAssignmentController::class, 'create'])->name('platform.branch-shift-assignments.create');
        Route::get('branch-shift-assignment-people-options', [PlatformBranchShiftAssignmentController::class, 'peopleOptions'])
            ->name('platform.branch-shift-assignments.people-options');
        Route::post('branch-shift-assignments', [PlatformBranchShiftAssignmentController::class, 'store'])->name('platform.branch-shift-assignments.store');
        Route::get('branch-shift-assignments/{user_shift_assignment}/edit', [PlatformBranchShiftAssignmentController::class, 'edit'])->name('platform.branch-shift-assignments.edit');
    });

    Route::middleware(['permission:create-branches|manage-branches'])->group(function () {
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

    Route::middleware(['permission:view-branches|manage-branches'])->group(function () {
        Route::get('branches', [PlatformBranchController::class, 'index'])->name('platform.branches.index');
        Route::get('branches/{branch}', [PlatformBranchController::class, 'show'])->name('platform.branches.show');
        Route::get('branch-areas', [PlatformBranchAreasController::class, 'index'])
            ->name('platform.branch-areas.index');
        Route::get('branch-areas-branch-options', [PlatformBranchAreasController::class, 'branchOptions'])
            ->name('platform.branch-areas.branch-options');
        Route::get('branches/{branch}/areas', [PlatformBranchAreaController::class, 'index'])
            ->name('platform.branches.areas.index');
    });

    Route::middleware(['permission:edit-branches|manage-branches'])->group(function () {
        Route::get('branches/{branch}/edit', [PlatformBranchController::class, 'edit'])->name('platform.branches.edit');
        Route::put('branches/{branch}', [PlatformBranchController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.branches.update');
        Route::get('branch-areas/create', [PlatformBranchAreasController::class, 'create'])
            ->name('platform.branch-areas.create');
        Route::post('branch-areas', [PlatformBranchAreasController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.branch-areas.store');
        Route::get('branch-areas/{area}/edit', [PlatformBranchAreasController::class, 'edit'])
            ->name('platform.branch-areas.edit');
        Route::put('branch-areas/{area}', [PlatformBranchAreasController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.branch-areas.update');
        Route::delete('branch-areas/{area}', [PlatformBranchAreasController::class, 'destroy'])
            ->name('platform.branch-areas.destroy');
        Route::post('branch-areas/{area}/transfer', [PlatformBranchAreasController::class, 'transfer'])
            ->middleware('precognitive')
            ->name('platform.branch-areas.transfer');
        Route::get('branches/{branch}/areas/create', [PlatformBranchAreaController::class, 'create'])
            ->name('platform.branches.areas.create');
        Route::post('branches/{branch}/areas', [PlatformBranchAreaController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.branches.areas.store');
        Route::get('branches/{branch}/areas/{area}/edit', [PlatformBranchAreaController::class, 'edit'])
            ->name('platform.branches.areas.edit');
        Route::put('branches/{branch}/areas/{area}', [PlatformBranchAreaController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.branches.areas.update');
        Route::delete('branches/{branch}/areas/{area}', [PlatformBranchAreaController::class, 'destroy'])
            ->name('platform.branches.areas.destroy');
    });

    Route::middleware(['permission:view-branches|manage-branches'])->group(function () {
        Route::get('branch-areas/{area}', [PlatformBranchAreasController::class, 'show'])
            ->name('platform.branch-areas.show');
        Route::get('branches/{branch}/areas/{area}', [PlatformBranchAreaController::class, 'show'])
            ->name('platform.branches.areas.show');
    });

    Route::middleware(['permission:delete-branches|manage-branches'])->group(function () {
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

        Route::get('packs-saves/branch-options', [PlatformPacksSavesController::class, 'branchOptions'])
            ->name('platform.packs-saves.branch-options');
        Route::get('packs-saves', [PlatformPacksSavesController::class, 'index'])
            ->name('platform.packs-saves.index');
        Route::get('packs-saves/create', [PlatformPacksSavesController::class, 'create'])
            ->name('platform.packs-saves.create');
        Route::post('packs-saves', [PlatformPacksSavesController::class, 'store'])
            ->name('platform.packs-saves.store');
        Route::get('packs-saves/{pack:id}', [PlatformPacksSavesController::class, 'show'])
            ->whereNumber('pack')
            ->name('platform.packs-saves.show');
        Route::get('packs-saves/{pack:id}/edit', [PlatformPacksSavesController::class, 'edit'])
            ->whereNumber('pack')
            ->name('platform.packs-saves.edit');
        Route::put('packs-saves/{pack:id}', [PlatformPacksSavesController::class, 'update'])
            ->whereNumber('pack')
            ->name('platform.packs-saves.update');
        Route::put('packs-saves/{pack:id}/highlight', [PlatformPacksSavesController::class, 'highlight'])
            ->whereNumber('pack')
            ->name('platform.packs-saves.highlight');
        Route::delete('packs-saves/{pack:id}', [PlatformPacksSavesController::class, 'destroy'])
            ->whereNumber('pack')
            ->name('platform.packs-saves.destroy');

        Route::get('catalog/pricing/manage', [PlatformCatalogPricingController::class, 'manage'])
            ->name('platform.catalog.pricing.manage');
        Route::put('products/{product}/catalog-prices', [PlatformCatalogPricingController::class, 'upsert'])
            ->name('platform.products.prices');

        Route::get('price-ranges', [PlatformPriceRangeController::class, 'index'])
            ->name('platform.price-ranges.index');
        Route::put('price-ranges/bounds', [PlatformPriceRangeController::class, 'updateBounds'])
            ->name('platform.price-ranges.bounds');
        Route::get('price-ranges/create', [PlatformPriceRangeController::class, 'create'])
            ->name('platform.price-ranges.create');
        Route::post('price-ranges', [PlatformPriceRangeController::class, 'store'])
            ->name('platform.price-ranges.store');
        Route::get('price-ranges/{price_range}/edit', [PlatformPriceRangeController::class, 'edit'])
            ->name('platform.price-ranges.edit');
        Route::put('price-ranges/{price_range}', [PlatformPriceRangeController::class, 'update'])
            ->name('platform.price-ranges.update');
        Route::delete('price-ranges/{price_range}', [PlatformPriceRangeController::class, 'destroy'])
            ->name('platform.price-ranges.destroy');
        Route::post('price-ranges/{price_range}/move', [PlatformPriceRangeController::class, 'move'])
            ->name('platform.price-ranges.move');
        Route::patch('price-ranges/{price_range}/status', [PlatformPriceRangeController::class, 'toggleStatus'])
            ->name('platform.price-ranges.status');

        Route::get('coupons', [PlatformCouponController::class, 'index'])
            ->name('platform.coupons.index');
        Route::get('coupons/create', [PlatformCouponController::class, 'create'])
            ->name('platform.coupons.create');
        Route::post('coupons', [PlatformCouponController::class, 'store'])
            ->name('platform.coupons.store');
        Route::get('coupons/{coupon}/edit', [PlatformCouponController::class, 'edit'])
            ->name('platform.coupons.edit');
        Route::put('coupons/{coupon}', [PlatformCouponController::class, 'update'])
            ->name('platform.coupons.update');
        Route::delete('coupons/{coupon}', [PlatformCouponController::class, 'destroy'])
            ->name('platform.coupons.destroy');

        Route::get('delivery-settings', [PlatformDeliverySettingsController::class, 'index'])
            ->name('platform.delivery-settings.index');
        Route::put('delivery-settings/{delivery_method}', [PlatformDeliverySettingsController::class, 'update'])
            ->name('platform.delivery-settings.update');
        Route::put('delivery-settings/shipping-rules/{branch_shipping_rule}', [PlatformDeliverySettingsController::class, 'updateShippingRule'])
            ->name('platform.delivery-settings.shipping-rules.update');
        Route::get('payment-methods', [PlatformPaymentGatewaySettingsController::class, 'edit'])
            ->name('platform.payment-methods.index');

        Route::post('products/{product}/variants', [PlatformProductVariantController::class, 'store'])
            ->middleware('precognitive')
            ->name('platform.products.variants.store');
        Route::put('products/{product}/variants/{variant}', [PlatformProductVariantController::class, 'update'])
            ->middleware('precognitive')
            ->name('platform.products.variants.update');
        Route::delete('products/{product}/variants/{variant}', [PlatformProductVariantController::class, 'destroy'])
            ->name('platform.products.variants.destroy');
    });

    Route::middleware(['permission:manage-orders'])->group(function () {
        Route::get('orders', [PlatformOrderController::class, 'index'])->name('platform.orders.index');
        Route::get('orders/{order}', [PlatformOrderController::class, 'show'])->name('platform.orders.show');
        Route::put('orders/{order}/fulfillments/{fulfillment}/status', [PlatformOrderController::class, 'updateStatus'])
            ->name('platform.orders.fulfillments.status');
        Route::get('orders/{order}/assign-candidates', [PlatformOrderAssignController::class, 'candidates'])
            ->name('platform.orders.assign-candidates');
        Route::post('orders/{order}/assign-driver', [PlatformOrderAssignController::class, 'store'])
            ->name('platform.orders.assign-driver');
        Route::post('orders/{order}/placement-code', [PlatformOrderController::class, 'resendPlacementCode'])
            ->name('platform.orders.placement-code');
        Route::post('orders/{order}/cancel', [PlatformOrderController::class, 'cancel'])
            ->name('platform.orders.cancel');
        Route::post('orders/{order}/items/{item}/remove-late', [PlatformOrderController::class, 'removeLateItem'])
            ->name('platform.orders.items.remove-late');
        Route::post('orders/{order}/items/{item}/collect-late', [PlatformOrderController::class, 'collectLateItem'])
            ->name('platform.orders.items.collect-late');

        Route::post('orders/{order}/retry-delivery', [PlatformOrderController::class, 'retryDelivery'])
            ->name('platform.orders.retry-delivery');
        Route::post('orders/{order}/reschedule-delivery', [PlatformOrderController::class, 'rescheduleDelivery'])
            ->name('platform.orders.reschedule-delivery');
        Route::post('orders/{order}/return-delivery', [PlatformOrderController::class, 'returnDelivery'])
            ->name('platform.orders.return-delivery');
        Route::post('orders/{order}/deliver', [PlatformOrderController::class, 'deliver'])
            ->name('platform.orders.deliver');
        Route::post('orders/{order}/deliver-without-code', [PlatformOrderController::class, 'deliverWithoutCode'])
            ->middleware('permission:complete-orders-without-code')
            ->name('platform.orders.deliver-without-code');
        Route::post('orders/{order}/fail', [PlatformOrderController::class, 'fail'])
            ->name('platform.orders.fail');
        Route::post('orders/{order}/returns/{orderReturn}/approve', [PlatformOrderController::class, 'approveReturn'])
            ->name('platform.orders.returns.approve');
        Route::post('orders/{order}/returns/{orderReturn}/reject', [PlatformOrderController::class, 'rejectReturn'])
            ->name('platform.orders.returns.reject');
        Route::post('orders/{order}/confirm-return', [PlatformOrderController::class, 'confirmReturn'])
            ->name('platform.orders.confirm-return');
        Route::put('orders/{order}/payment-status', [PlatformOrderController::class, 'markPaid'])
            ->name('platform.orders.payment-status');
        Route::get('stock-transfers', [PlatformStockTransferController::class, 'index'])
            ->name('platform.stock-transfers.index');
        Route::post('stock-transfers/{stockTransfer}/approve', [PlatformStockTransferController::class, 'approve'])
            ->name('platform.stock-transfers.approve');
        Route::post('stock-transfers/{stockTransfer}/pick', [PlatformStockTransferController::class, 'pick'])
            ->name('platform.stock-transfers.pick');
        Route::post('stock-transfers/{stockTransfer}/dispatch', [PlatformStockTransferController::class, 'dispatch'])
            ->name('platform.stock-transfers.dispatch');
        Route::post('stock-transfers/{stockTransfer}/receive', [PlatformStockTransferController::class, 'receive'])
            ->name('platform.stock-transfers.receive');
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

    Route::middleware(['permission:manage-platform-branch-chat'])->group(function () {
        Route::get('branch-chats', [PlatformBranchChatController::class, 'index'])
            ->name('platform.branch-chats.index');
        Route::get('branch-chats/branch-options', [PlatformBranchChatController::class, 'branchOptions'])
            ->name('platform.branch-chats.branch-options');
        Route::post('branch-chats', [PlatformBranchChatController::class, 'store'])
            ->name('platform.branch-chats.store');
        Route::get('branch-chats/{conversation}', [PlatformBranchChatController::class, 'show'])
            ->whereNumber('conversation')
            ->name('platform.branch-chats.show');
        Route::get('branch-chats/{conversation}/messages', [PlatformBranchChatController::class, 'messages'])
            ->whereNumber('conversation')
            ->name('platform.branch-chats.messages');
        Route::post('branch-chats/{conversation}/messages', [PlatformBranchChatController::class, 'storeMessage'])
            ->whereNumber('conversation')
            ->middleware('throttle:conversation-messages')
            ->name('platform.branch-chats.messages.store');
        Route::post('branch-chats/{conversation}/delivered', [PlatformBranchChatController::class, 'delivered'])
            ->whereNumber('conversation')
            ->name('platform.branch-chats.delivered');
        Route::post('branch-chats/{conversation}/read', [PlatformBranchChatController::class, 'read'])
            ->whereNumber('conversation')
            ->name('platform.branch-chats.read');
        Route::post('branch-chats/{conversation}/close', [PlatformBranchChatController::class, 'close'])
            ->whereNumber('conversation')
            ->name('platform.branch-chats.close');
    });

    Route::middleware(['permission:view-activity-logs'])->group(function () {
        Route::get('activity-logs', [PlatformActivityLogController::class, 'index'])->name('platform.activity-logs.index');
        Route::get('activity-logs/{activity}', [PlatformActivityLogController::class, 'show'])->name('platform.activity-logs.show');
    });

    Route::middleware(['permission:manage-reports'])->group(function () {
        Route::get('announcements', [PlatformAnnouncementController::class, 'index'])
            ->name('platform.announcements.index');
        Route::get('announcements/recipients', [PlatformAnnouncementController::class, 'recipients'])
            ->name('platform.announcements.recipients');
        Route::post('announcements', [PlatformAnnouncementController::class, 'store'])
            ->name('platform.announcements.store');

        Route::get('reports/order-reports', [PlatformOrderReportController::class, 'index'])
            ->name('platform.reports.order-reports');
        Route::get('reports/order-reports/export', [PlatformOrderReportController::class, 'export'])
            ->name('platform.reports.order-reports.export');
        Route::redirect('reports/order-operations', '/platform/reports/order-reports', 301);
        Route::redirect('reports/order-operations/export', '/platform/reports/order-reports/export', 301);
        Route::get('reports/order-assign-history', [PlatformOrderAssignHistoryController::class, 'index'])
            ->name('platform.reports.order-assign-history');
        Route::get('reports/order-assign-history/export', [PlatformOrderAssignHistoryController::class, 'export'])
            ->name('platform.reports.order-assign-history.export');
        Route::get('reports/order-assign-history/driver-options', [PlatformOrderAssignHistoryController::class, 'driverOptions'])
            ->name('platform.reports.order-assign-history.driver-options');
        Route::get('reports/order-assign-history/branch-options', [PlatformOrderAssignHistoryController::class, 'branchOptions'])
            ->name('platform.reports.order-assign-history.branch-options');
    });

    Route::middleware(['permission:manage-pages'])->group(function () {
        Route::get('pages', [PlatformCmsPageController::class, 'index'])
            ->name('platform.pages.index');
        Route::get('pages/{slug}/edit', [PlatformCmsPageController::class, 'edit'])
            ->name('platform.pages.edit');
        Route::put('pages/{slug}', [PlatformCmsPageController::class, 'update'])
            ->name('platform.pages.update');

        Route::get('home-banners', [PlatformHomeBannerController::class, 'index'])
            ->name('platform.home-banners.index');
        Route::get('home-banners/create', [PlatformHomeBannerController::class, 'create'])
            ->name('platform.home-banners.create');
        Route::post('home-banners', [PlatformHomeBannerController::class, 'store'])
            ->name('platform.home-banners.store');
        Route::get('home-banners/{home_banner}/edit', [PlatformHomeBannerController::class, 'edit'])
            ->name('platform.home-banners.edit');
        Route::put('home-banners/{home_banner}', [PlatformHomeBannerController::class, 'update'])
            ->name('platform.home-banners.update');
        Route::delete('home-banners/{home_banner}', [PlatformHomeBannerController::class, 'destroy'])
            ->name('platform.home-banners.destroy');
        Route::post('home-banners/{home_banner}/move', [PlatformHomeBannerController::class, 'move'])
            ->name('platform.home-banners.move');
        Route::patch('home-banners/{home_banner}/toggle', [PlatformHomeBannerController::class, 'toggle'])
            ->name('platform.home-banners.toggle');
    });

    Route::middleware(['permission:manage-application-settings'])->prefix('settings')->group(function () {
        Route::get('general', [PlatformGeneralSettingsController::class, 'edit'])
            ->name('platform.settings.general.edit');
        Route::put('general', [PlatformGeneralSettingsController::class, 'update'])
            ->name('platform.settings.general.update');

        Route::get('contacts', [PlatformContactSettingsController::class, 'edit'])
            ->name('platform.settings.contacts.edit');
        Route::put('contacts', [PlatformContactSettingsController::class, 'update'])
            ->name('platform.settings.contacts.update');

        Route::get('smtp', [PlatformSmtpSettingsController::class, 'edit'])
            ->name('platform.settings.smtp.edit');
        Route::put('smtp', [PlatformSmtpSettingsController::class, 'update'])
            ->name('platform.settings.smtp.update');
        Route::post('smtp/test', [PlatformSmtpSettingsController::class, 'test'])
            ->middleware('throttle:6,1')
            ->name('platform.settings.smtp.test');

        Route::get('payment-gateway', [PlatformPaymentGatewaySettingsController::class, 'edit'])
            ->name('platform.settings.payment-gateway.edit');
        Route::put('payment-gateway', [PlatformPaymentGatewaySettingsController::class, 'update'])
            ->name('platform.settings.payment-gateway.update');
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

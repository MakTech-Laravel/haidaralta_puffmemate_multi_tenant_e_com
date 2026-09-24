# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| resources/js/actions/** | .ai/rules/actions.md |
| resources/js/pages/driver/activity/**,resources/js/components/driver/driver-activity.ts | .ai/rules/activity-js-components-driver.md |
| app/Services/ActivityLog/** | .ai/rules/activity-log.md |
| resources/js/components/admin/staff-form.tsx,app/Http/Requests/{Platform,Branch}/*BranchStaff*Request.php | .ai/rules/admin-http-requests-platform-branch.md |
| resources/js/components/admin/shift-*.tsx,app/Support/WorkforcePayload.php | .ai/rules/admin-support.md |
| resources/js/components/admin/branch-form.tsx, resources/js/components/admin/driver-form.tsx, resources/js/components/admin/branch-location-map-dialog.tsx, resources/js/components/admin/driver-branch-assign-sheet.tsx, resources/js/components/admin/shift-*.tsx, resources/js/components/admin/{admin,staff}-form.tsx, resources/js/components/admin/shift-board.tsx | .ai/rules/admin.md |
| app/Http/Middleware/EnsureAnyPortalGuard.php,routes/settings.php | .ai/rules/app-http-middleware.md |
| resources/js/pages/auth/**,app/Actions/Fortify/CreateNewUser.php,app/Http/Requests/Auth/StoreRegisteredUserRequest.php,resources/js/layouts/auth/** | .ai/rules/auth.md |
| app/Services/Backup/**, config/backup.php, config/scout.php, config/catalog.php, config/shifts.php | .ai/rules/backup.md |
| resources/js/components/admin/staff-*.tsx,resources/js/pages/**/staff/**,resources/js/pages/platform/branch-staff/** | .ai/rules/branch-staff.md |
| app/Models/{Category,Product,ProductVariant,Inventory,InventoryTransaction,BranchProductPrice}.php, app/Services/Catalog/**, app/Services/Inventory/**, app/Models/ProductImage.php, app/Models/{Category,Product}.php, app/Models/Driver*.php, app/Models/User.php, app/Models/Branch.php | .ai/rules/branch.md |
| resources/js/pages/platform/branches/index.tsx, resources/js/pages/platform/branches/show.tsx | .ai/rules/branches.md |
| app/{Models,Services/Inventory,Services/Catalog,DataTables,Support}/** | .ai/rules/catalog-data-tables-support.md |
| resources/js/pages/**/catalog/products/index.tsx, resources/js/pages/**/catalog/products/{index,show}.tsx | .ai/rules/catalog-products.md |
| resources/js/components/catalog/** | .ai/rules/catalog.md |
| resources/js/pages/**/catalog/categories/index.tsx | .ai/rules/categories.md |
| resources/js/components/color-picker/** | .ai/rules/color-picker.md |
| resources/js/pages/**/permissions/**, resources/js/components/admin/permission-groups-table.tsx | .ai/rules/components-admin.md |
| resources/js/components/branch/**, resources/js/components/branch/nav.ts, resources/js/components/branch/product-inventory-panel.tsx, resources/js/components/branch/branch-product-summary.tsx | .ai/rules/components-branch.md |
| {app/Support/ConversationInbox.php,app/Services/Conversation/**,app/Events/ConversationInboxUpdated.php,resources/js/components/conversation/**} | .ai/rules/components-conversation.md |
| resources/js/pages/driver/orders/**,resources/js/components/driver/driver-order-card.tsx,resources/js/components/driver/driver-deliver-otp-form.tsx | .ai/rules/components-driver-js-components-driver.md |
| resources/js/components/driver/nav.ts, resources/js/components/driver/{nav.ts,driver-sidebar.tsx,driver-mobile-nav.tsx,driver-header.tsx}, resources/js/components/driver/** | .ai/rules/components-driver.md |
| resources/js/app.tsx, resources/js/components/pwa/** | .ai/rules/components-pwa.md |
| resources/js/components/stock/**, resources/js/components/stock/manage-stock-page.tsx | .ai/rules/components-stock.md |
| resources/js/components/file-upload.tsx, resources/js/components/product-gallery.tsx, resources/js/components/permission-selector.tsx | .ai/rules/components.md |
| config/*.php, config/scout.php | .ai/rules/config.md |
| app/Models/Branch.php,app/Http/Controllers/Branch/** | .ai/rules/controllers-branch.md |
| app/Http/Controllers/Driver/** | .ai/rules/controllers-driver.md |
| app/Services/Workforce/**,app/Http/Controllers/{Platform,Branch}/** | .ai/rules/controllers-platform-branch.md |
| app/Http/Controllers/Platform/AdminController.php, app/Http/Controllers/Platform/BranchStaffController.php, app/Http/Controllers/Platform/*Shift*.php | .ai/rules/controllers-platform.md |
| resources/js/pages/settings/profile.tsx,app/Http/Controllers/Settings/ProfileController.php | .ai/rules/controllers-settings.md |
| app/Http/Controllers/FirebaseMessagingServiceWorkerController.php | .ai/rules/controllers.md |
| app/Services/Conversation/**,resources/js/components/conversation/** | .ai/rules/conversation.md |
| {app/Support/ConversationInbox.php,app/Http/Controllers/*/ConversationController.php,resources/js/components/conversation/**,resources/js/pages/*/conversations/**} | .ai/rules/conversations.md |
| resources/css/app.css | .ai/rules/css.md |
| resources/js/layouts/customer/** | .ai/rules/customer.md |
| app/DataTables/**, resources/js/components/data-table/** | .ai/rules/data-table.md |
| resources/js/components/admin/shift-*.tsx,app/DataTables/ShiftAssignmentDataTable.php,app/Http/Controllers/{Platform,Branch}/*ShiftAssignment*.php | .ai/rules/data-tables-http-controllers-platform-branch.md |
| app/DataTables/**, resources/js/components/data-table/**, resources/js/pages/**/index.tsx, app/DataTables/DatabaseBackupZipDataTable.php, app/DataTables/BranchProductListingDataTable.php, app/DataTables/BranchCategoryDataTable.php, app/DataTables/ProductBranchInventoryDataTable.php, app/DataTables/BranchStaffDataTable.php, app/DataTables/BranchRoleDataTable.php, app/DataTables/AbstractDataTable.php | .ai/rules/data-tables.md |
| resources/js/components/{ui/date-picker,branch/product-inventory-panel}.tsx | .ai/rules/date-pickerbranch.md |
| resources/js/components/branch/product-inventory-panel.tsx, resources/js/pages/{platform,branch}/**/products/show.tsx, resources/js/pages/{platform,branch}/stock/manage.tsx, resources/js/pages/{platform,branch}/catalog/pricing/manage.tsx, resources/js/components/catalog/manage-catalog-pricing-page.tsx, app/Support/ProductManageRedirect.php, routes/{platform,branch}.php, app/Support/BranchProductPriceDisplay.php, routes/platform.php, routes/*.php | .ai/rules/discounts.md |
| docker/**, docker/nginx.conf, docker/**, docker/nginx.conf | .ai/rules/docker.md |
| Dockerfile | .ai/rules/dockerfile.md |
| resources/js/pages/driver/account/**,resources/js/pages/driver/activity/**,resources/js/components/driver/driver-account-sheet.tsx | .ai/rules/driver-activity-js-components-driver.md |
| resources/js/pages/driver/dashboard.tsx,resources/js/components/driver/driver-dashboard-hub.tsx | .ai/rules/driver-js-components-driver.md |
| resources/js/pages/driver/route/**,resources/js/components/driver/driver-route-map-panel.tsx | .ai/rules/driver-route-js-components-driver.md |
| app/Services/Driver/**, app/Models/Driver*.php, app/Http/Controllers/{Platform,Branch}/*Driver*.php, resources/js/pages/{platform,branch}/drivers/**, resources/js/pages/platform/branches/drivers/**, app/Http/Controllers/{Platform,Branch}/** | .ai/rules/driver.md |
| resources/js/pages/branch/drivers/index.tsx,app/DataTables/BranchDriverDataTable.php | .ai/rules/drivers-data-tables.md |
| app/Http/Controllers/Branch/*Driver*.php,resources/js/pages/branch/drivers/** | .ai/rules/drivers.md |
| app/Http/Controllers/Driver/EarningsController.php,resources/js/pages/driver/earnings/**,app/Support/Storefront/DemoCatalog.php | .ai/rules/earnings-support-storefront.md |
| app/Enums/ShiftAudience.php | .ai/rules/enums.md |
| app/Events/** | .ai/rules/events.md |
| resources/js/components/export/** | .ai/rules/export.md |
| app/Exports/** | .ai/rules/exports.md |
| app/Services/Fcm/** | .ai/rules/fcm.md |
| package.json, vite.config.ts, package.json, vite.config.ts | .ai/rules/general.md |
| resources/js/hooks/use-database-backup-realtime.ts | .ai/rules/hooks.md |
| app/Services/Driver/**,app/Models/Driver*.php,app/Http/Controllers/{Platform,Branch}/*Driver*.php | .ai/rules/http-controllers-platform-branch.md |
| resources/js/layouts/branch/**,resources/js/components/portal/portal-brand.tsx,app/Http/Middleware/HandleInertiaRequests.php | .ai/rules/http-middleware.md |
| resources/js/components/admin/role-*.tsx, resources/js/pages/{platform,branch}/roles/**, app/Http/Controllers/{Platform,Branch}/RoleController.php, app/Http/Requests/{Platform,Branch}/*RoleRequest.php | .ai/rules/http-requests-platform-branch.md |
| app/Services/Driver/**,app/Http/Requests/{Platform,Branch}/*Driver*.php,resources/js/pages/{platform,branch}/drivers/**,resources/js/components/admin/driver-branch-assign-sheet.tsx | .ai/rules/js-components-admin.md |
| resources/js/components/branch/**, resources/js/components/branch/product-inventory-panel.tsx | .ai/rules/js-components-branch.md |
| app/Http/Controllers/Driver/OrderController.php,resources/js/pages/driver/orders/**,resources/js/components/driver/driver-order-card.tsx | .ai/rules/js-components-driver.md |
| app/Services/Conversation/**,app/Events/Conversation*,resources/js/components/conversation/**,resources/js/hooks/use-chat-scroll.ts | .ai/rules/js-hooks.md |
| resources/js/lib/pwa.ts, resources/js/lib/fcm.ts | .ai/rules/js-lib.md |
| resources/js/components/driver/nav.ts,app/Http/Controllers/Driver/OrderController.php,resources/js/pages/driver/history.tsx | .ai/rules/js-pages-driver.md |
| resources/js/layouts/driver/** | .ai/rules/layouts-driver.md |
| resources/js/layouts/settings/** | .ai/rules/layouts-settings.md |
| resources/js/layouts/{platform,branch}/** | .ai/rules/layoutsplatformbranch.md |
| resources/js/lib/fcm.ts, resources/js/lib/color-picker.ts, resources/js/lib/stock-query.ts, resources/js/lib/branch-people-query.ts | .ai/rules/lib.md |
| routes/public.php,bootstrap/app.php,app/Http/Middleware/EnsurePortalGuard.php | .ai/rules/middleware.md |
| database/migrations/** | .ai/rules/migrations.md |
| app/Models/**.php | .ai/rules/models.md |
| resources/js/components/{navigation,platform,branch,customer,driver}/** | .ai/rules/navigationplatformbranchcustomerdriver.md |
| resources/js/pages/driver/orders/**,resources/js/components/driver/driver-order-card.tsx | .ai/rules/orders-js-components-driver.md |
| resources/js/components/driver/driver-order-chat-panel.tsx,resources/js/pages/driver/orders/chat.tsx | .ai/rules/orders.md |
| resources/js/pages/driver/** | .ai/rules/pages-driver.md |
| resources/js/pages/platform/branch-staff/** | .ai/rules/pages-platform-branch-staff.md |
| resources/js/layouts/settings/**,resources/js/pages/settings/**,routes/settings.php | .ai/rules/pages-settings.md |
| resources/js/components/admin/shift-*.{ts,tsx},resources/js/pages/{platform,branch}/shifts/assign.tsx | .ai/rules/pagesplatformbranch-shifts.md |
| resources/js/pages/**/permissions/** | .ai/rules/permissions.md |
| resources/js/components/admin/staff-*.tsx,resources/js/pages/platform/branch-staff/**, resources/js/components/admin/transfer-employee-sheet.tsx,resources/js/pages/platform/branch-staff/** | .ai/rules/platform-branch-staff.md |
| app/Http/Controllers/{Platform,Branch}/**, app/Http/Controllers/{Platform,Branch}/*Driver*.php | .ai/rules/platform-branch.md |
| resources/js/lib/stock-query.ts, resources/js/pages/**/products/index.tsx, resources/js/pages/platform/catalog/products/branches.tsx | .ai/rules/platform-catalog-products.md |
| resources/js/components/admin/shift-*-empty-state.tsx,resources/js/pages/platform/shifts/assign.tsx | .ai/rules/platform-shifts.md |
| resources/js/components/platform/product-form.tsx, resources/js/components/platform/category-form.tsx, resources/js/components/platform/{category,product}-form.tsx, resources/js/components/platform/category-profile.tsx, resources/js/components/platform/nav.ts | .ai/rules/platform.md |
| resources/js/components/{platform,admin}/*-form.tsx | .ai/rules/platformadmin.md |
| resources/js/pages/{platform,branch}/catalog/products/show.tsx | .ai/rules/platformbranch-catalog-products.md |
| app/Http/Controllers/{Platform,Branch}/Reports/**, resources/js/pages/{platform,branch}/reports/** | .ai/rules/platformbranch-reports.md |
| app/Http/Controllers/{Platform,Branch}/*ShiftAssignmentController.php,app/Services/Workforce/**,app/Support/WorkforcePayload.php,resources/js/components/admin/shift-*.{ts,tsx},resources/js/pages/{platform,branch}/shifts/assign.tsx | .ai/rules/platformbranch-shifts.md |
| resources/js/components/{platform,branch}/nav.ts | .ai/rules/platformbranch.md |
| resources/js/components/portal/** | .ai/rules/portal.md |
| resources/js/pages/platform/catalog/products/show.tsx, resources/js/pages/platform/catalog/products/**, resources/js/pages/branch/catalog/products/show.tsx | .ai/rules/products.md |
| resources/js/pages/driver/profile/** | .ai/rules/profile.md |
| config/pwa.php, pwa-assets.config.ts, resources/views/app.blade.php, resources/js/lib/pwa.ts, resources/js/components/pwa/**, docker/nginx.conf, routes/public.php, app/Http/Controllers/WebAppManifestController.php, app/Http/Controllers/FirebaseMessagingServiceWorkerController.php | .ai/rules/pwa.md |
| app/Http/Controllers/{Platform,Branch}/Reports/**, resources/js/pages/{platform,branch}/reports/** | .ai/rules/reports.md |
| app/Http/Requests/Driver/** | .ai/rules/requests-driver.md |
| app/Http/Requests/{Platform,Branch}/*Product*Request.php | .ai/rules/requests-platform-branch.md |
| app/Http/Requests/Platform/*Category*Request.php | .ai/rules/requests-platform.md |
| resources/js/components/admin/role-*.tsx, resources/js/pages/{platform,branch}/roles/** | .ai/rules/roles.md |
| app/Http/Controllers/Driver/RouteController.php,resources/js/pages/driver/route/**,resources/js/components/driver/nav.ts | .ai/rules/route-js-components-driver.md |
| app/Http/Controllers/Driver/RouteController.php,resources/js/pages/driver/route/** | .ai/rules/route.md |
| routes/settings.php, routes/fortify.php, routes/*.php | .ai/rules/routes.md |
| resources/js/pages/driver/security/**,resources/js/components/driver/driver-account-sheet.tsx | .ai/rules/security-js-components-driver.md |
| resources/js/components/admin/shift-*.tsx,database/seeders/WorkforceShiftSeeder.php,app/Support/WorkforcePayload.php | .ai/rules/seeders-support.md |
| database/seeders/**, database/seeders/WorkforceShiftSeeder.php | .ai/rules/seeders.md |
| app/Services/OtpService.php | .ai/rules/services.md |
| app/Http/Controllers/Branch/GeneralSettingsController.php,resources/js/pages/branch/settings/**,resources/js/components/branch/nav.ts | .ai/rules/settings-js-components-branch.md |
| resources/js/{app.tsx,layouts/settings/**} | .ai/rules/settings.md |
| app/Http/Controllers/Platform/*Shift*.php,resources/js/pages/platform/shifts/assign.tsx | .ai/rules/shifts.md |
| resources/js/components/stock/**,app/Support/StockPageContext.php,app/Support/ProductManageRedirect.php | .ai/rules/stock-support-support.md |
| resources/js/components/stock/**, resources/js/components/stock/manage-stock-page.tsx | .ai/rules/stock.md |
| resources/js/{layouts,components,pages}/storefront/**, resources/js/lib/storefront*.ts | .ai/rules/storefront.md |
| app/DataTables/ShiftAssignmentDataTable.php,app/Support/WorkforcePayload.php,resources/js/components/admin/shift-assigned-people-page.tsx | .ai/rules/support-js-components-admin.md |
| app/Support/Storefront/DemoCatalog.php | .ai/rules/support-storefront.md |
| app/Models/UserShiftAssignment.php,app/Support/DutyWindow.php,app/Support/DriverAvailability.php | .ai/rules/support-support.md |
| app/Support/OpsDashboardAccess.php, config/cache.php | .ai/rules/support.md |
| resources/js/pages/platform/reports/transfer-history.tsx,app/DataTables/TransferDataTable.php | .ai/rules/transfers-data-tables.md |
| resources/js/components/ui/{time-picker,wheel-picker}.tsx | .ai/rules/ui.md |

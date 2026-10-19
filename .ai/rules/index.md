# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| resources/js/actions/** | .ai/rules/actions.md |
| resources/js/pages/driver/activity/**,resources/js/components/driver/driver-activity.ts | .ai/rules/activity-js-components-driver.md |
| app/Services/ActivityLog/** | .ai/rules/activity-log.md |
| resources/js/components/admin/staff-form.tsx,app/Http/Requests/{Platform,Branch}/*BranchStaff*Request.php | .ai/rules/admin-http-requests-platform-branch.md |
| app/Http/Requests/**/*BranchArea*,app/Http/Requests/Concerns/ValidatesBranchArea.php,resources/js/components/admin/transfer-branch-area-sheet.tsx,database/seeders/BranchAreaSeeder.php | .ai/rules/admin-seeders.md |
| resources/js/components/admin/shift-*.tsx,app/Support/WorkforcePayload.php | .ai/rules/admin-support.md |
| resources/js/components/admin/branch-form.tsx, resources/js/components/admin/driver-form.tsx, resources/js/components/admin/branch-location-map-dialog.tsx, resources/js/components/admin/driver-branch-assign-sheet.tsx, resources/js/components/admin/shift-*.tsx, resources/js/components/admin/{admin,staff}-form.tsx, resources/js/components/admin/shift-board.tsx | .ai/rules/admin.md |
| resources/js/pages/platform/announcements/** | .ai/rules/announcements.md |
| app/Http/Middleware/EnsureAnyPortalGuard.php,routes/settings.php | .ai/rules/app-http-middleware.md |
| app/Services/Application/ApplicationSettingsService.php,resources/js/hooks/use-app-brand.ts | .ai/rules/application-js-hooks.md |
| app/Models/BranchArea.php,app/Services/Branch/BranchAreaService.php,app/Http/Controllers/{Platform,Branch}/*Area*Controller.php,resources/js/pages/{platform,branch}/**/areas/**,resources/js/components/admin/branch-area-form.tsx | .ai/rules/areas-js-components-admin.md |
| resources/js/components/admin/branch-area-detail.tsx,app/Services/Branch/BranchAreaService.php,resources/js/pages/{platform,branch}/**/areas/** | .ai/rules/areas.md |
| resources/js/pages/auth/**,app/Actions/Fortify/CreateNewUser.php,app/Http/Requests/Auth/StoreRegisteredUserRequest.php,resources/js/layouts/auth/** | .ai/rules/auth.md |
| app/Services/Backup/**, config/backup.php, config/scout.php, config/catalog.php, config/shifts.php | .ai/rules/backup.md |
| resources/js/components/{platform,branch}/nav.ts,resources/js/pages/platform/branch-areas/**,app/Http/Controllers/Platform/BranchAreasController.php | .ai/rules/branch-areas-http-controllers-platform.md |
| app/Services/Driver/**,app/Http/Controllers/Branch/DriverProductAssignController.php,resources/js/pages/branch/assign-products.tsx | .ai/rules/branch-drivers.md |
| resources/js/components/admin/staff-*.tsx,resources/js/pages/**/staff/**,resources/js/pages/platform/branch-staff/** | .ai/rules/branch-staff.md |
| app/Models/{Category,Product,ProductVariant,Inventory,InventoryTransaction,BranchProductPrice}.php, app/Services/Catalog/**, app/Services/Inventory/**, app/Models/ProductImage.php, app/Models/{Category,Product}.php, app/Models/Driver*.php, app/Models/User.php, app/Models/Branch.php, app/Models/Category.php, app/Models/Product.php, app/Models/{Notification,UserNotification}.php, app/Services/Catalog/PackService.php, app/Services/Inventory/InventoryReservationService.php | .ai/rules/branch.md |
| resources/js/pages/platform/branches/index.tsx, resources/js/pages/platform/branches/show.tsx | .ai/rules/branches.md |
| resources/js/components/storefront/cart/** | .ai/rules/cart.md |
| app/Support/MediaUrl.php,database/seeders/Concerns/SeedsCatalogMedia.php,resources/js/components/storefront/{cart,checkout}/** | .ai/rules/cartcheckout.md |
| app/{Models,Services/Inventory,Services/Catalog,DataTables,Support}/** | .ai/rules/catalog-data-tables-support.md |
| resources/js/pages/**/catalog/products/index.tsx, resources/js/pages/**/catalog/products/{index,show}.tsx | .ai/rules/catalog-products.md |
| resources/js/components/catalog/** | .ai/rules/catalog.md |
| resources/js/pages/**/catalog/categories/index.tsx | .ai/rules/categories.md |
| resources/js/{pages,components}/storefront/checkout/** | .ai/rules/checkout.md |
| resources/js/components/color-picker/** | .ai/rules/color-picker.md |
| resources/js/pages/**/permissions/**, resources/js/components/admin/permission-groups-table.tsx | .ai/rules/components-admin.md |
| resources/js/components/branch/**, resources/js/components/branch/nav.ts, resources/js/components/branch/product-inventory-panel.tsx, resources/js/components/branch/branch-product-summary.tsx, resources/js/components/branch/branch-pack-form.tsx | .ai/rules/components-branch.md |
| {app/Support/ConversationInbox.php,app/Services/Conversation/**,app/Events/ConversationInboxUpdated.php,resources/js/components/conversation/**} | .ai/rules/components-conversation.md |
| app/Http/Controllers/Customer/OrderController.php,resources/js/components/customer/order-list.tsx, app/Http/Controllers/Customer/OrderController.php,routes/customer.php,resources/js/components/customer/order-list.tsx | .ai/rules/components-customer.md |
| resources/js/pages/driver/orders/**,resources/js/components/driver/driver-order-card.tsx,resources/js/components/driver/driver-deliver-otp-form.tsx | .ai/rules/components-driver-js-components-driver.md |
| resources/js/components/driver/nav.ts, resources/js/components/driver/{nav.ts,driver-sidebar.tsx,driver-mobile-nav.tsx,driver-header.tsx}, resources/js/components/driver/**, resources/js/components/driver/driver-account-sheet.tsx | .ai/rules/components-driver.md |
| resources/js/components/notifications/** | .ai/rules/components-notifications.md |
| app/Http/Controllers/Platform/ProductReviewController.php, app/DataTables/ProductReviewDataTable.php, resources/js/pages/platform/reviews/**, resources/js/components/platform/nav.ts | .ai/rules/components-platform.md |
| resources/js/app.tsx, resources/js/components/pwa/** | .ai/rules/components-pwa.md |
| resources/js/components/stock/**, resources/js/components/stock/manage-stock-page.tsx | .ai/rules/components-stock.md |
| resources/js/components/storefront/checkout/** | .ai/rules/components-storefront-checkout.md |
| resources/js/components/storefront/shop-filters-sidebar.tsx, resources/js/components/storefront/product-card.tsx, resources/js/components/storefront/pack-card.tsx, resources/js/components/storefront/** | .ai/rules/components-storefront.md |
| resources/js/components/file-upload.tsx, resources/js/components/product-gallery.tsx, resources/js/components/permission-selector.tsx | .ai/rules/components.md |
| resources/js/pages/settings/**,app/Http/Controllers/Settings/ProfileController.php,resources/js/components/{platform,branch}/nav.ts | .ai/rules/componentsplatformbranch.md |
| app/Services/Media/**,app/Models/Concerns/InteractsWithAppMedia.php,app/Support/MediaUrl.php | .ai/rules/concerns-support.md |
| config/*.php, config/scout.php | .ai/rules/config.md |
| app/Http/Controllers/**/ContactInquiryController.php,app/Http/Requests/Storefront/StoreContactInquiryRequest.php,resources/js/components/storefront/contact/**,resources/js/pages/platform/contact-inquiries/**,routes/public.php,routes/platform.php | .ai/rules/contact-inquiries.md |
| app/Models/Branch.php,app/Http/Controllers/Branch/** | .ai/rules/controllers-branch.md |
| app/Http/Controllers/Customer/**, app/Http/Controllers/Customer/ProductToReviewController.php | .ai/rules/controllers-customer.md |
| app/Http/Controllers/Driver/**, app/Http/Controllers/Driver/PlaceOrderController.php, app/Http/Controllers/Driver/OrderController.php | .ai/rules/controllers-driver.md |
| app/Services/Workforce/**,app/Http/Controllers/{Platform,Branch}/** | .ai/rules/controllers-platform-branch.md |
| app/Http/Controllers/Platform/AdminController.php, app/Http/Controllers/Platform/BranchStaffController.php, app/Http/Controllers/Platform/*Shift*.php, app/Http/Controllers/Platform/AnnouncementController.php, app/Http/Controllers/Platform/PacksSavesController.php | .ai/rules/controllers-platform.md |
| resources/js/pages/settings/**,app/Http/Controllers/Settings/ProfileController.php,resources/js/components/branch/nav.ts | .ai/rules/controllers-settings-js-components-branch.md |
| resources/js/pages/settings/profile.tsx,app/Http/Controllers/Settings/ProfileController.php | .ai/rules/controllers-settings.md |
| app/Http/Controllers/Storefront/HomeController.php, app/Http/Controllers/Storefront/CategoryController.php, app/Http/Controllers/Storefront/ProductController.php | .ai/rules/controllers-storefront.md |
| app/Http/Controllers/FirebaseMessagingServiceWorkerController.php | .ai/rules/controllers.md |
| app/Services/Conversation/**,resources/js/components/conversation/** | .ai/rules/conversation.md |
| {app/Support/ConversationInbox.php,app/Http/Controllers/*/ConversationController.php,resources/js/components/conversation/**,resources/js/pages/*/conversations/**} | .ai/rules/conversations.md |
| resources/css/app.css | .ai/rules/css.md |
| resources/js/layouts/customer/** | .ai/rules/customer.md |
| app/Http/Controllers/Platform/CustomerController.php,app/Services/Platform/PlatformCustomerService.php,app/DataTables/PlatformCustomerDataTable.php,resources/js/pages/platform/customers/**,resources/js/components/platform/nav.ts | .ai/rules/customers-js-components-platform.md |
| app/DataTables/**, resources/js/components/data-table/** | .ai/rules/data-table.md |
| resources/js/components/admin/shift-*.tsx,app/DataTables/ShiftAssignmentDataTable.php,app/Http/Controllers/{Platform,Branch}/*ShiftAssignment*.php | .ai/rules/data-tables-http-controllers-platform-branch.md |
| app/DataTables/**, resources/js/components/data-table/**, resources/js/pages/**/index.tsx, app/DataTables/DatabaseBackupZipDataTable.php, app/DataTables/BranchProductListingDataTable.php, app/DataTables/BranchCategoryDataTable.php, app/DataTables/ProductBranchInventoryDataTable.php, app/DataTables/BranchStaffDataTable.php, app/DataTables/BranchRoleDataTable.php, app/DataTables/AbstractDataTable.php, app/DataTables/OrderDataTable.php | .ai/rules/data-tables.md |
| resources/js/components/{ui/date-picker,branch/product-inventory-panel}.tsx | .ai/rules/date-pickerbranch.md |
| resources/js/components/branch/product-inventory-panel.tsx, resources/js/pages/{platform,branch}/**/products/show.tsx, resources/js/pages/{platform,branch}/stock/manage.tsx, resources/js/pages/{platform,branch}/catalog/pricing/manage.tsx, resources/js/components/catalog/manage-catalog-pricing-page.tsx, app/Support/ProductManageRedirect.php, routes/{platform,branch}.php, app/Support/BranchProductPriceDisplay.php, routes/platform.php, routes/*.php, app/Support/Phone.php | .ai/rules/discounts.md |
| docker/**, docker/nginx.conf, docker/**, docker/nginx.conf | .ai/rules/docker.md |
| Dockerfile | .ai/rules/dockerfile.md |
| resources/js/pages/driver/account/**,resources/js/pages/driver/activity/**,resources/js/components/driver/driver-account-sheet.tsx | .ai/rules/driver-activity-js-components-driver.md |
| resources/js/pages/driver/dashboard.tsx,resources/js/components/driver/driver-dashboard-hub.tsx | .ai/rules/driver-js-components-driver.md |
| app/Http/Controllers/Driver/PlaceOrderController.php,app/Support/Driver/DriverPlaceOrderCatalog.php,resources/js/components/driver/place-order/** | .ai/rules/driver-place-order.md |
| resources/js/pages/driver/route/**,resources/js/components/driver/driver-route-map-panel.tsx | .ai/rules/driver-route-js-components-driver.md |
| app/Services/Driver/**, app/Models/Driver*.php, app/Http/Controllers/{Platform,Branch}/*Driver*.php, resources/js/pages/{platform,branch}/drivers/**, resources/js/pages/platform/branches/drivers/**, app/Http/Controllers/{Platform,Branch}/** | .ai/rules/driver.md |
| resources/js/pages/branch/drivers/index.tsx,app/DataTables/BranchDriverDataTable.php | .ai/rules/drivers-data-tables.md |
| app/Http/Controllers/Branch/*Driver*.php,resources/js/pages/branch/drivers/** | .ai/rules/drivers.md |
| app/Http/Controllers/Driver/EarningsController.php,resources/js/pages/driver/earnings/**,app/Services/Driver/DriverEarningsService.php | .ai/rules/earnings-support-storefront.md |
| app/Enums/ShiftAudience.php | .ai/rules/enums.md |
| app/Events/** | .ai/rules/events.md |
| resources/js/components/export/** | .ai/rules/export.md |
| app/Exports/** | .ai/rules/exports.md |
| app/Services/Fcm/** | .ai/rules/fcm.md |
| package.json, vite.config.ts, package.json, vite.config.ts | .ai/rules/general.md |
| resources/js/hooks/use-database-backup-realtime.ts | .ai/rules/hooks.md |
| app/Http/Controllers/Branch/PacksSavesController.php | .ai/rules/http-controllers-branch.md |
| app/Services/Driver/**,app/Models/Driver*.php,app/Http/Controllers/{Platform,Branch}/*Driver*.php | .ai/rules/http-controllers-platform-branch.md |
| resources/js/pages/platform/wholesale-inquiries/show.tsx,app/Http/Controllers/Platform/WholesaleInquiryController.php | .ai/rules/http-controllers-platform.md |
| resources/js/layouts/settings/**,resources/js/pages/settings/**,app/Http/Controllers/Settings/ProfileController.php | .ai/rules/http-controllers-settings.md |
| config/catalog.php,app/Http/Controllers/Storefront/HomeController.php | .ai/rules/http-controllers-storefront.md |
| app/Services/Storefront/ContactInquiryService.php,app/Http/Controllers/**/ContactInquiryController.php, app/Services/Storefront/WholesaleInquiryService.php,app/Http/Controllers/**/WholesaleInquiryController.php | .ai/rules/http-controllers.md |
| resources/js/layouts/branch/**,resources/js/components/portal/portal-brand.tsx,app/Http/Middleware/HandleInertiaRequests.php | .ai/rules/http-middleware.md |
| resources/js/components/admin/role-*.tsx, resources/js/pages/{platform,branch}/roles/**, app/Http/Controllers/{Platform,Branch}/RoleController.php, app/Http/Requests/{Platform,Branch}/*RoleRequest.php | .ai/rules/http-requests-platform-branch.md |
| app/Services/Catalog/**,app/Services/Inventory/**,app/Support/PackAvailability.php | .ai/rules/inventory-support.md |
| app/Jobs/SendAnnouncementEmailJob.php | .ai/rules/jobs.md |
| app/Services/Driver/**,app/Http/Requests/{Platform,Branch}/*Driver*.php,resources/js/pages/{platform,branch}/drivers/**,resources/js/components/admin/driver-branch-assign-sheet.tsx | .ai/rules/js-components-admin.md |
| resources/js/components/branch/**, resources/js/components/branch/product-inventory-panel.tsx | .ai/rules/js-components-branch.md |
| resources/js/components/customer/product-to-review-*.tsx, resources/js/components/customer/write-product-review-form.tsx, resources/js/components/customer/order-list.tsx | .ai/rules/js-components-customer.md |
| app/Http/Controllers/Driver/OrderController.php,resources/js/pages/driver/orders/**,resources/js/components/driver/driver-order-card.tsx | .ai/rules/js-components-driver.md |
| app/Http/Controllers/Platform/CustomerController.php, app/DataTables/PlatformCustomerDataTable.php, resources/js/pages/platform/customers/**, resources/js/components/platform/nav.ts | .ai/rules/js-components-platform.md |
| resources/js/pages/storefront/checkout*.tsx,resources/js/components/storefront/checkout/** | .ai/rules/js-components-storefront-checkout.md |
| resources/js/components/manage-two-factor.tsx,resources/js/components/manage-passkeys.tsx | .ai/rules/js-components.md |
| app/Http/Controllers/{Platform,Branch}/Reports/**, resources/js/pages/{platform,branch}/reports/**, resources/js/components/{platform,branch}/nav.ts | .ai/rules/js-componentsplatformbranch.md |
| app/Services/Conversation/**,app/Events/Conversation*,resources/js/components/conversation/**,resources/js/hooks/use-chat-scroll.ts | .ai/rules/js-hooks.md |
| resources/js/lib/pwa.ts, resources/js/lib/fcm.ts | .ai/rules/js-lib.md |
| resources/js/components/driver/nav.ts,app/Http/Controllers/Driver/OrderController.php,resources/js/pages/driver/history.tsx | .ai/rules/js-pages-driver.md |
| resources/js/pages/settings/profile.tsx | .ai/rules/js-pages-settings.md |
| app/Http/Controllers/Storefront/CheckoutController.php,resources/js/pages/storefront/checkout.tsx, app/Http/Controllers/Storefront/CheckoutController.php,routes/public.php,resources/js/pages/storefront/checkout-confirmation.tsx | .ai/rules/js-pages-storefront.md |
| resources/js/{app.tsx,components/navigation-overlay.tsx} | .ai/rules/js.md |
| resources/js/layouts/driver/** | .ai/rules/layouts-driver.md |
| resources/js/layouts/settings/** | .ai/rules/layouts-settings.md |
| resources/js/layouts/{platform,branch}/** | .ai/rules/layoutsplatformbranch.md |
| resources/js/lib/fcm.ts, resources/js/lib/color-picker.ts, resources/js/lib/stock-query.ts, resources/js/lib/branch-people-query.ts | .ai/rules/lib.md |
| routes/public.php,app/Http/Middleware/EnsureStorefrontCustomer.php,app/Http/Controllers/Storefront/CheckoutController.php | .ai/rules/middleware-http-controllers-storefront.md |
| routes/public.php,bootstrap/app.php,app/Http/Middleware/EnsurePortalGuard.php | .ai/rules/middleware.md |
| database/migrations/**, database/migrations/2026_09_13_042225_create_products_table.php | .ai/rules/migrations.md |
| app/Models/**.php | .ai/rules/models.md |
| resources/js/components/{navigation,platform,branch,customer,driver}/** | .ai/rules/navigationplatformbranchcustomerdriver.md |
| app/Services/Notifications/** | .ai/rules/notifications.md |
| app/Services/Order/OrderAllocationEngine.php, app/Actions/Order/PlaceOrderAction.php, app/Actions/Order/PlaceDriverOrderAction.php | .ai/rules/order.md |
| app/Http/Controllers/Customer/OrderController.php,resources/js/pages/customer/orders/show.tsx,resources/js/components/customer/order-track-*.tsx | .ai/rules/orders-js-components-customer.md |
| resources/js/pages/driver/orders/**,resources/js/components/driver/driver-order-card.tsx | .ai/rules/orders-js-components-driver.md |
| resources/js/components/driver/driver-order-chat-panel.tsx,resources/js/pages/driver/orders/chat.tsx,resources/js/pages/customer/orders/chat.tsx,resources/js/components/conversation/order-chat-body.tsx | .ai/rules/orders.md |
| resources/js/pages/branch/catalog/packs-saves/** | .ai/rules/packs-saves.md |
| app/Services/Driver/**,app/Http/Controllers/Branch/DriverProductAssignController.php,resources/js/pages/branch/drivers/assign-products.tsx | .ai/rules/pages-branch-drivers.md |
| resources/js/pages/driver/** | .ai/rules/pages-driver.md |
| resources/js/pages/platform/branch-staff/** | .ai/rules/pages-platform-branch-staff.md |
| app/Http/Controllers/Branch/CustomerController.php,app/DataTables/BranchCustomerDataTable.php,resources/js/pages/branch/customers/**,resources/js/components/branch/nav.ts | .ai/rules/pages-branch-customers.md |
| app/Http/Controllers/Branch/OrderController.php,resources/js/pages/branch/orders/show.tsx | .ai/rules/pages-branch-orders.md |
| app/Http/Controllers/Platform/OrderController.php,resources/js/pages/platform/orders/show.tsx | .ai/rules/pages-platform-orders.md |
| app/Http/Controllers/Platform/WholesaleInquiryController.php,resources/js/pages/platform/wholesale-inquiries/**,routes/platform.php | .ai/rules/pages-platform-wholesale-inquiries.md |
| resources/js/layouts/settings/**,resources/js/pages/settings/**,routes/settings.php | .ai/rules/pages-settings.md |
| resources/js/pages/storefront/category.tsx, resources/js/pages/storefront/checkout.tsx, resources/js/pages/storefront/{shop,category,product}.tsx | .ai/rules/pages-storefront.md |
| app/Http/Requests/Storefront/PlaceOrderRequest.php,app/Services/Storefront/CheckoutService.php,resources/js/{pages,components}/storefront/checkout/** | .ai/rules/pagescomponents-storefront-checkout.md |
| resources/js/{pages,components}/storefront/packs-saves*/**, resources/js/{pages,components}/storefront/pack*/** | .ai/rules/pagescomponents-storefront.md |
| resources/js/components/admin/shift-*.{ts,tsx},resources/js/pages/{platform,branch}/shifts/assign.tsx | .ai/rules/pagesplatformbranch-shifts.md |
| resources/js/pages/**/permissions/** | .ai/rules/permissions.md |
| resources/js/components/driver/place-order/** | .ai/rules/place-order.md |
| resources/js/pages/platform/branch-areas/**,app/Http/Controllers/Platform/BranchAreasController.php,routes/platform.php | .ai/rules/platform-branch-areas-http-controllers-platform.md |
| resources/js/components/admin/staff-*.tsx,resources/js/pages/platform/branch-staff/**, resources/js/components/admin/transfer-employee-sheet.tsx,resources/js/pages/platform/branch-staff/** | .ai/rules/platform-branch-staff.md |
| app/Http/Controllers/{Platform,Branch}/**, app/Http/Controllers/{Platform,Branch}/*Driver*.php | .ai/rules/platform-branch.md |
| resources/js/lib/stock-query.ts, resources/js/pages/**/products/index.tsx, resources/js/pages/platform/catalog/products/branches.tsx | .ai/rules/platform-catalog-products.md |
| app/Models/BranchArea*.php,app/Services/Branch/BranchAreaService.php,app/Http/Controllers/Platform/BranchAreasController.php,resources/js/components/admin/transfer-branch-area-sheet.tsx | .ai/rules/platform-js-components-admin.md |
| app/DataTables/OrderDataTable.php,resources/js/pages/platform/orders/index.tsx | .ai/rules/platform-orders.md |
| app/Http/Controllers/Platform/DriverProductAssignController.php,app/Http/Controllers/Platform/Reports/ProductAssignTransactionController.php,resources/js/pages/platform/drivers/assign-products.tsx,resources/js/pages/platform/reports/product-assign-transactions.tsx | .ai/rules/platform-reports.md |
| resources/js/components/admin/shift-*-empty-state.tsx,resources/js/pages/platform/shifts/assign.tsx | .ai/rules/platform-shifts.md |
| app/Http/Controllers/**/WholesaleInquiryController.php,app/Http/Requests/Storefront/StoreWholesaleInquiryRequest.php,resources/js/components/storefront/wholesale/**,resources/js/pages/platform/wholesale-inquiries/** | .ai/rules/platform-wholesale-inquiries.md |
| resources/js/pages/platform/wholesale-inquiries/place-order.tsx,resources/js/components/platform/wholesale/** | .ai/rules/platform-wholesale.md |
| resources/js/components/platform/product-form.tsx, resources/js/components/platform/category-form.tsx, resources/js/components/platform/{category,product}-form.tsx, resources/js/components/platform/category-profile.tsx, resources/js/components/platform/nav.ts, resources/js/pages/platform/coupons/** | .ai/rules/platform.md |
| resources/js/components/{platform,admin}/*-form.tsx | .ai/rules/platformadmin.md |
| resources/js/pages/{platform,branch}/catalog/products/show.tsx | .ai/rules/platformbranch-catalog-products.md |
| resources/js/lib/order-badges.ts,resources/js/pages/{platform,branch}/orders/index.tsx | .ai/rules/platformbranch-orders.md |
| app/Http/Controllers/{Platform,Branch}/Reports/**, resources/js/pages/{platform,branch}/reports/** | .ai/rules/platformbranch-reports.md |
| app/Http/Controllers/{Platform,Branch}/*ShiftAssignmentController.php,app/Services/Workforce/**,app/Support/WorkforcePayload.php,resources/js/components/admin/shift-*.{ts,tsx},resources/js/pages/{platform,branch}/shifts/assign.tsx | .ai/rules/platformbranch-shifts.md |
| resources/js/components/{platform,branch}/nav.ts | .ai/rules/platformbranch.md |
| resources/js/components/portal/** | .ai/rules/portal.md |
| resources/js/pages/storefront/product.tsx,resources/js/components/storefront/product/product-summary.tsx,app/Http/Controllers/Storefront/ProductController.php | .ai/rules/product-http-controllers-storefront.md |
| resources/js/pages/platform/catalog/products/show.tsx, resources/js/pages/platform/catalog/products/**, resources/js/pages/branch/catalog/products/show.tsx | .ai/rules/products.md |
| resources/js/pages/driver/profile/** | .ai/rules/profile.md |
| config/pwa.php, pwa-assets.config.ts, resources/views/app.blade.php, resources/js/lib/pwa.ts, resources/js/components/pwa/**, docker/nginx.conf, routes/public.php, app/Http/Controllers/WebAppManifestController.php, app/Http/Controllers/FirebaseMessagingServiceWorkerController.php | .ai/rules/pwa.md |
| app/Services/Driver/**,app/Http/Controllers/Branch/Reports/ProductAssignTransactionController.php,resources/js/pages/branch/reports/product-assign-transactions.tsx,resources/js/components/driver/product-assign-transactions-page.tsx | .ai/rules/reports-js-components-driver.md |
| app/Http/Controllers/{Platform,Branch}/Reports/**, resources/js/pages/{platform,branch}/reports/** | .ai/rules/reports.md |
| app/Http/Requests/Driver/** | .ai/rules/requests-driver.md |
| app/Http/Requests/{Platform,Branch}/*Product*Request.php | .ai/rules/requests-platform-branch.md |
| app/Http/Requests/Platform/*Category*Request.php | .ai/rules/requests-platform.md |
| resources/js/components/storefront/wholesale/**,resources/js/lib/nominatim.ts,app/Http/Requests/Storefront/StoreWholesaleInquiryRequest.php | .ai/rules/requests-storefront.md |
| app/Http/Controllers/Platform/ProductReviewController.php, resources/js/pages/platform/reviews/** | .ai/rules/reviews.md |
| resources/js/components/admin/role-*.tsx, resources/js/pages/{platform,branch}/roles/** | .ai/rules/roles.md |
| app/Http/Controllers/Driver/RouteController.php,resources/js/pages/driver/route/**,resources/js/components/driver/nav.ts | .ai/rules/route-js-components-driver.md |
| app/Http/Controllers/Driver/RouteController.php,resources/js/pages/driver/route/** | .ai/rules/route.md |
| routes/settings.php, routes/fortify.php, routes/*.php | .ai/rules/routes.md |
| resources/js/pages/driver/security/**,resources/js/components/driver/driver-account-sheet.tsx | .ai/rules/security-js-components-driver.md |
| resources/js/components/admin/shift-*.tsx,database/seeders/WorkforceShiftSeeder.php,app/Support/WorkforcePayload.php | .ai/rules/seeders-support.md |
| database/seeders/**, database/seeders/WorkforceShiftSeeder.php, database/seeders/CatalogSeeder.php, database/seeders/CategorySeeder.php, database/seeders/ProductSeeder.php, database/seeders/PriceRangeSeeder.php | .ai/rules/seeders.md |
| app/Services/Announcements/** | .ai/rules/services-announcements.md |
| app/Services/Customer/** | .ai/rules/services-customer.md |
| app/Http/Controllers/Storefront/ProductController.php,app/Http/Controllers/Storefront/CartController.php,app/Services/Storefront/ProductViewService.php,app/Models/ProductViewLog.php | .ai/rules/services-storefront-models.md |
| app/Services/OtpService.php | .ai/rules/services.md |
| app/Http/Controllers/Branch/GeneralSettingsController.php,resources/js/pages/branch/settings/**,resources/js/components/branch/nav.ts | .ai/rules/settings-js-components-branch.md |
| resources/js/{app.tsx,layouts/settings/**} | .ai/rules/settings.md |
| app/Http/Controllers/Platform/*Shift*.php,resources/js/pages/platform/shifts/assign.tsx | .ai/rules/shifts.md |
| resources/js/components/stock/**,app/Support/StockPageContext.php,app/Support/ProductManageRedirect.php | .ai/rules/stock-support-support.md |
| resources/js/components/stock/**, resources/js/components/stock/manage-stock-page.tsx | .ai/rules/stock.md |
| app/Services/Storefront/CheckoutService.php,resources/js/components/storefront/checkout/** | .ai/rules/storefront-checkout.md |
| resources/js/{pages,components}/storefront/**,app/Http/Controllers/Storefront/{Cart,Checkout}Controller.php | .ai/rules/storefront-http-controllers-storefront.md |
| app/Support/Storefront/StorefrontCatalog.php,resources/js/pages/storefront/product.tsx | .ai/rules/storefront-js-pages-storefront.md |
| app/Support/Storefront/StorefrontShopQuery.php,resources/js/pages/storefront/shop.tsx,app/Models/PriceRange.php,config/scout.php | .ai/rules/storefront-models.md |
| app/Services/Catalog/PriceRangeService.php,app/Models/ShopPriceFilterSetting.php,resources/js/pages/platform/catalog/price-ranges/**,resources/js/components/storefront/shop-filters-sidebar.tsx,config/catalog.php,database/seeders/PriceRangeSeeder.php | .ai/rules/storefront-seeders.md |
| resources/js/{layouts,components,pages}/storefront/**, resources/js/lib/storefront*.ts, app/Support/Storefront/**, app/Http/Middleware/HandleInertiaRequests.php, app/Support/Storefront/StorefrontCatalog.php,config/catalog.php, app/Support/Storefront/StorefrontShopQuery.php | .ai/rules/storefront.md |
| resources/js/components/storefront/{cart,checkout}/** | .ai/rules/storefrontcartcheckout.md |
| app/DataTables/ShiftAssignmentDataTable.php,app/Support/WorkforcePayload.php,resources/js/components/admin/shift-assigned-people-page.tsx | .ai/rules/support-js-components-admin.md |
| app/Support/Storefront/DemoCatalog.php | .ai/rules/support-storefront.md |
| app/Models/UserShiftAssignment.php,app/Support/DutyWindow.php,app/Support/DriverAvailability.php | .ai/rules/support-support.md |
| app/Support/OpsDashboardAccess.php, config/cache.php | .ai/rules/support.md |
| resources/js/pages/platform/reports/transfer-history.tsx,app/DataTables/TransferDataTable.php | .ai/rules/transfers-data-tables.md |
| app/Http/Controllers/Storefront/**,app/Support/Storefront/StorefrontCatalog.php,resources/js/{pages,components}/storefront/**,resources/js/types/storefront.ts,resources/js/lib/storefront*.ts | .ai/rules/types-js-lib.md |
| resources/js/components/ui/{time-picker,wheel-picker}.tsx, resources/js/components/ui/alert.tsx | .ai/rules/ui.md |
| app/Http/Controllers/Storefront/WholesaleInquiryController.php,app/Http/Controllers/Platform/WholesaleInquiryController.php,resources/js/components/storefront/wholesale/**,resources/js/pages/platform/wholesale-inquiries/**,routes/public.php,routes/platform.php | .ai/rules/wholesale-inquiries.md |
| app/Http/Controllers/Platform/WholesaleInquiryController.php,resources/js/pages/platform/wholesale-inquiries/**,resources/js/components/platform/wholesale/** | .ai/rules/wholesale.md |

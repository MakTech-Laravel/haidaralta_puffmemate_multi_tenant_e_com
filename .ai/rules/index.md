# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| resources/js/actions/** | .ai/rules/actions.md |
| app/Services/ActivityLog/** | .ai/rules/activity-log.md |
| resources/js/components/admin/branch-form.tsx, resources/js/components/admin/driver-form.tsx, resources/js/components/admin/duty-days-picker.tsx, resources/js/components/admin/driver-roster-fields.tsx, resources/js/components/admin/branch-location-map-dialog.tsx | .ai/rules/admin.md |
| app/Services/Backup/**, config/backup.php, config/scout.php, config/catalog.php | .ai/rules/backup.md |
| app/Models/{Category,Product,ProductVariant,Inventory,InventoryTransaction,BranchProductPrice}.php, app/Services/Catalog/**, app/Services/Inventory/**, app/Models/ProductImage.php, app/Models/{Category,Product}.php, app/Models/Driver*.php, app/Models/User.php | .ai/rules/branch.md |
| resources/js/pages/platform/branches/index.tsx, resources/js/pages/platform/branches/show.tsx | .ai/rules/branches.md |
| app/{Models,Services/Inventory,Services/Catalog,DataTables,Support}/** | .ai/rules/catalog-data-tables-support.md |
| resources/js/pages/**/catalog/products/index.tsx, resources/js/pages/**/catalog/products/{index,show}.tsx | .ai/rules/catalog-products.md |
| resources/js/pages/**/catalog/categories/index.tsx | .ai/rules/categories.md |
| resources/js/components/color-picker/** | .ai/rules/color-picker.md |
| resources/js/pages/**/permissions/**, resources/js/components/admin/permission-groups-table.tsx | .ai/rules/components-admin.md |
| resources/js/components/branch/**, resources/js/components/branch/nav.ts, resources/js/components/branch/product-inventory-panel.tsx, resources/js/components/branch/branch-product-summary.tsx | .ai/rules/components-branch.md |
| resources/js/components/driver/nav.ts, resources/js/components/driver/{nav.ts,driver-sidebar.tsx}, resources/js/components/driver/{nav.ts,driver-sidebar.tsx,driver-mobile-nav.tsx,driver-header.tsx} | .ai/rules/components-driver.md |
| resources/js/components/stock/**, resources/js/components/stock/manage-stock-page.tsx | .ai/rules/components-stock.md |
| resources/js/components/file-upload.tsx, resources/js/components/product-gallery.tsx, resources/js/components/permission-selector.tsx | .ai/rules/components.md |
| config/scout.php | .ai/rules/config.md |
| app/Http/Controllers/Platform/AdminController.php, app/Http/Controllers/Platform/BranchStaffController.php | .ai/rules/controllers-platform.md |
| app/Http/Controllers/FirebaseMessagingServiceWorkerController.php | .ai/rules/controllers.md |
| app/Services/Conversation/**,resources/js/components/conversation/** | .ai/rules/conversation.md |
| {app/Support/ConversationInbox.php,app/Http/Controllers/*/ConversationController.php,resources/js/components/conversation/**,resources/js/pages/*/conversations/**} | .ai/rules/conversations.md |
| resources/css/app.css | .ai/rules/css.md |
| resources/js/layouts/customer/** | .ai/rules/customer.md |
| app/DataTables/**, resources/js/components/data-table/** | .ai/rules/data-table.md |
| app/DataTables/**, resources/js/components/data-table/**, resources/js/pages/**/index.tsx, app/DataTables/DatabaseBackupZipDataTable.php, app/DataTables/BranchProductListingDataTable.php, app/DataTables/BranchCategoryDataTable.php, app/DataTables/ProductBranchInventoryDataTable.php, app/DataTables/BranchStaffDataTable.php | .ai/rules/data-tables.md |
| resources/js/components/{ui/date-picker,branch/product-inventory-panel}.tsx | .ai/rules/date-pickerbranch.md |
| resources/js/components/branch/product-inventory-panel.tsx, resources/js/pages/{platform,branch}/**/products/show.tsx, resources/js/pages/{platform,branch}/stock/manage.tsx, app/Support/ProductManageRedirect.php, routes/{platform,branch}.php, app/Support/BranchProductPriceDisplay.php, routes/platform.php | .ai/rules/discounts.md |
| docker/**, docker/nginx.conf | .ai/rules/docker.md |
| Dockerfile | .ai/rules/dockerfile.md |
| app/Services/Driver/**, app/Models/Driver*.php, app/Http/Controllers/{Platform,Branch}/*Driver*.php, resources/js/pages/{platform,branch}/drivers/**, resources/js/pages/platform/branches/drivers/** | .ai/rules/driver.md |
| app/Events/** | .ai/rules/events.md |
| resources/js/components/export/** | .ai/rules/export.md |
| app/Exports/** | .ai/rules/exports.md |
| app/Services/Fcm/** | .ai/rules/fcm.md |
| package.json, vite.config.ts | .ai/rules/general.md |
| resources/js/hooks/use-database-backup-realtime.ts | .ai/rules/hooks.md |
| resources/js/components/admin/role-*.tsx, resources/js/pages/{platform,branch}/roles/**, app/Http/Controllers/{Platform,Branch}/RoleController.php, app/Http/Requests/{Platform,Branch}/*RoleRequest.php | .ai/rules/http-requests-platform-branch.md |
| resources/js/components/branch/**, resources/js/components/branch/product-inventory-panel.tsx | .ai/rules/js-components-branch.md |
| app/Services/Conversation/**,app/Events/Conversation*,resources/js/components/conversation/**,resources/js/hooks/use-chat-scroll.ts | .ai/rules/js-hooks.md |
| resources/js/layouts/driver/** | .ai/rules/layouts-driver.md |
| resources/js/layouts/{platform,branch}/** | .ai/rules/layoutsplatformbranch.md |
| resources/js/lib/fcm.ts, resources/js/lib/color-picker.ts, resources/js/lib/stock-query.ts, resources/js/lib/branch-people-query.ts | .ai/rules/lib.md |
| database/migrations/** | .ai/rules/migrations.md |
| app/Models/**.php | .ai/rules/models.md |
| resources/js/components/{navigation,platform,branch,customer,driver}/** | .ai/rules/navigationplatformbranchcustomerdriver.md |
| resources/js/pages/**/permissions/** | .ai/rules/permissions.md |
| app/Http/Controllers/{Platform,Branch}/**, app/Http/Controllers/{Platform,Branch}/*Driver*.php | .ai/rules/platform-branch.md |
| resources/js/lib/stock-query.ts, resources/js/pages/**/products/index.tsx, resources/js/pages/platform/catalog/products/branches.tsx | .ai/rules/platform-catalog-products.md |
| resources/js/components/platform/product-form.tsx, resources/js/components/platform/category-form.tsx, resources/js/components/platform/{category,product}-form.tsx, resources/js/components/platform/category-profile.tsx | .ai/rules/platform.md |
| resources/js/components/{platform,admin}/*-form.tsx | .ai/rules/platformadmin.md |
| resources/js/pages/{platform,branch}/catalog/products/show.tsx | .ai/rules/platformbranch-catalog-products.md |
| app/Http/Controllers/{Platform,Branch}/Reports/**, resources/js/pages/{platform,branch}/reports/** | .ai/rules/platformbranch-reports.md |
| resources/js/components/{platform,branch}/nav.ts | .ai/rules/platformbranch.md |
| resources/js/components/portal/** | .ai/rules/portal.md |
| resources/js/pages/platform/catalog/products/show.tsx, resources/js/pages/platform/catalog/products/**, resources/js/pages/branch/catalog/products/show.tsx | .ai/rules/products.md |
| app/Http/Controllers/{Platform,Branch}/Reports/**, resources/js/pages/{platform,branch}/reports/** | .ai/rules/reports.md |
| app/Http/Requests/{Platform,Branch}/*Product*Request.php | .ai/rules/requests-platform-branch.md |
| app/Http/Requests/Platform/*Category*Request.php | .ai/rules/requests-platform.md |
| resources/js/components/admin/role-*.tsx, resources/js/pages/{platform,branch}/roles/** | .ai/rules/roles.md |
| routes/settings.php, routes/*.php | .ai/rules/routes.md |
| database/seeders/** | .ai/rules/seeders.md |
| app/Services/OtpService.php | .ai/rules/services.md |
| resources/js/{app.tsx,layouts/settings/**} | .ai/rules/settings.md |
| resources/js/components/stock/**, resources/js/components/stock/manage-stock-page.tsx | .ai/rules/stock.md |
| resources/js/{layouts,components,pages}/storefront/**, resources/js/lib/storefront*.ts | .ai/rules/storefront.md |
| app/Support/OpsDashboardAccess.php, config/cache.php | .ai/rules/support.md |
| resources/js/components/ui/{time-picker,wheel-picker}.tsx | .ai/rules/ui.md |

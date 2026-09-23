---
paths:
    - 'resources/js/components/{platform,branch}/nav.ts'
---

# Platformbranch

## Platform and branch nav are domain dropdowns

One unlabeled NavSection: Dashboard and other singles as navLink; multi-item clusters as navGroup. Do not add a SidebarGroupLabel over a dropdown of the same name. Platform Administrator is platform users (Admins `/platform/admins` / Roles / Permissions / Shifts / Assign to shift). Cross-branch staff lives under Branch management as Staff (`/platform/branch-staff`) — no Add staff shortcut in the sidebar; create from the Staff page. Drivers live under Driver management — not Shifts. Settings labels are Backups and Monitoring (URLs stay /platform/database-backups and /platform/observability). Platform Application settings: General (`/platform/settings/general`), SMTP (`/platform/settings/smtp`), and Payment Gateway (`/platform/settings/payment-gateway`, renamed from Payments) are live navLinks gated by `manage-application-settings`; Delivery and fees stays Soon. Branch Settings holds General/Hours Soon plus live Notifications and Audit logs — never Horizon/Pulse/backups/SMTP/Payment Gateway/FCM/realtime. Soon is navSoon (disabled, no href). No pages or routes from the sidebar for Soon items.

## Product and Branch management sidebar groups

Platform **Branch management** is Branches, Staff, Shifts, Assign to shift, and Manage stock (`GET stock/manage`). Platform **Product management** is Categories, Products, Manage pricing when `!show_branch_pricing` (branch portal also needs `branch_can_manage_catalog_pricing`), and Bulk Import only when `catalog.show_bulk_import` is true (`buildPlatformNav(showBulkImport, showBranchPricing)` / `buildBranchNav(..., showBranchPricing, branchCanManageCatalogPricing)`). When branch pricing is on, daily stock/price/discounts are Branch management → Manage stock; when off, catalog price/discount is Product management → Manage pricing. Stock transactions, Price history, and Transfer history live under **Reports and history**. Do not add a top-level Discounts or Stock management group. Platform sidebar order: Dashboard → Administrator → Customer management → Driver management → Branch management → Product management → Order management → Conversations → Marketing → Reports and history → Application settings. Branch sidebar order: Dashboard → Administrator → Customer management → Driver management → Product management → Order management → Conversations → Reports and history → Settings. Under a management group, list labels stay short and plural (Staff not Branch staff; Drivers not All drivers). When branch_can_view_categories is false, omit Categories from branch Product management — keep Products, Manage pricing (when branch pricing is off), and Manage stock (Bulk Import only when show_bulk_import).

## Notifications live under portal Settings

Notifications is a live navLink under platform Application settings and branch Settings (not Soon, not Administrator). Platform href is /platform/notifications with view-dashboard; branch href is /branch/{slug}/notifications with view-branch-dashboard. Keep Settings parent permissions including view-branch-dashboard so the inbox stays visible without activity-log access.

## Sidebar management group order

Platform sidebar order: Dashboard, Administrator, Customer management, Driver management, Branch management, Product management, Order management, then Conversations, Marketing, Reports and history, Application settings. Branch: Dashboard, Administrator, Customer management, Driver management, Product management, Order management, then Conversations, Reports and history, Settings. Platform Branch management holds Branches, Staff, Shifts, Assign to shift, Manage stock. Platform Product management holds Categories, Products, Manage pricing when !show_branch_pricing, and Bulk Import when show_bulk_import.

## Reports and history includes Transfer history

Reports and history → Stock transactions, Price history (when branch pricing on), then Transfer history (`/platform/reports/transfer-history`, `view-transfers`). Old `/platform/transfers` 301s. Do not put Transfer history under Branch management.

## Shifts live under Administrator and Branch management
Shifts and Assign to shift are platform/branch participant duty windows, not a driver catalog. Platform: live under Administrator and Branch management. Branch portal: live under Administrator. Never put them under Driver management.

## Shift nav URLs are owner-scoped siblings
Administrator Shifts is /platform/shifts and Assign is /platform/shift-assignments (platform-owned only). Branch management Shifts is /platform/branch-shifts and Assign is /platform/branch-shift-assignments (branch-owned only). Branch portal Assign is /branch/{slug}/shift-assignments, not a child of /shifts. Edit assignment is /shift-assignments/{id}/edit (and branch-shift-assignments sibling). Never reuse the same href in two groups; isPathActive prefix-matches children so assign must not live under /shifts. There are no /shifts/periods URLs.

## No /shifts/periods URLs
Shift URLs are /platform/shifts and /platform/shift-assignments (edit /shift-assignments/{id}/edit) and the branch-shifts siblings. There are no /shifts/periods routes. Keep assign off the /shifts prefix.

## Manage pricing nav when branch pricing off
Under Product management, after Products, add Manage pricing when !showBranchPricing (platform always; branch also requires branchCanManageCatalogPricing / catalog.branch_can_manage_catalog_pricing). Platform href /platform/catalog/pricing/manage; branch /branch/{slug}/catalog/pricing/manage. Hide when show_branch_pricing is true, or on branch when branch_can_manage_catalog_pricing is false.

---
paths:
  - 'resources/js/components/{platform,branch}/nav.ts'
---

# Platformbranch

## Platform and branch nav are domain dropdowns
One unlabeled NavSection: Dashboard and other singles as navLink; multi-item clusters as navGroup. Do not add a SidebarGroupLabel over a dropdown of the same name. Platform Administrator is platform users only (Admins / Roles / Permissions). Cross-branch staff lives under Branch management as Staff (`/platform/branch-staff`) plus an Add staff shortcut — not a nested roles/permissions group, and do not deep-link a branch portal. Drivers live under Driver management. Settings labels are Backups and Monitoring (URLs stay /platform/database-backups and /platform/observability). Branch Settings holds General/Hours Soon plus live Notifications and Audit logs — never Horizon/Pulse/backups/SMTP/FCM/realtime. Soon is navSoon (disabled, no href). No pages or routes from the sidebar.

## Product management group and sidebar order
Catalog (Categories / Products) and stock tools (Stock, Bulk Import) live under one **Product management** group — not a separate Stock management top-level. Group `href` is products. Daily stock, selling price, and discounts are Product management → **Stock** (`GET stock/manage`). Stock transactions live under Reports. Do not add a top-level Discounts nav item. Platform sidebar order: Dashboard → Administrator → Customer management → Driver management → Branch management → Product management → Order management → Conversations → Marketing → Reports → Application settings. Branch sidebar order: Dashboard → Administrator → Customer management → Driver management → Product management → Order management → Conversations → Reports → Settings. Under a management group, list labels stay short and plural (Staff not Branch staff; Drivers not All drivers). When branch_can_view_categories is false, omit Categories from Product management — keep Products, Stock, and Bulk Import.

## Notifications live under portal Settings
Notifications is a live navLink under platform Application settings and branch Settings (not Soon, not Administrator). Platform href is /platform/notifications with view-dashboard; branch href is /branch/{slug}/notifications with view-branch-dashboard. Keep Settings parent permissions including view-branch-dashboard so the inbox stays visible without activity-log access.

## Sidebar management group order
Platform sidebar order: Dashboard, Administrator, Customer management, Driver management, Branch management, Product management, Order management, then Conversations, Marketing, Reports, Application settings. Branch: Dashboard, Administrator, Customer management, Driver management, Product management, Order management, then Conversations, Reports, Settings. Product management holds Categories, Products, Stock, Bulk Import.

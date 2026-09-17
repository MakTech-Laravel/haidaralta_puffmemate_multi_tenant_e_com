---
paths:
  - 'resources/js/components/{platform,branch}/nav.ts'
---

# Platformbranch

## Platform and branch nav are domain dropdowns
One unlabeled NavSection: Dashboard and other singles as navLink; multi-item clusters as navGroup. Do not add a SidebarGroupLabel over a dropdown of the same name. Platform Administrator is platform users only (Admins / Roles / Permissions). Cross-branch staff lives under Branch management as Staff (`/platform/branch-staff`) plus an Add staff shortcut — not a nested roles/permissions group, and do not deep-link a branch portal. Drivers live under Driver management. Settings labels are Backups and Monitoring (URLs stay /platform/database-backups and /platform/observability). Branch Settings holds General/Hours Soon plus live Notifications and Audit logs — never Horizon/Pulse/backups/SMTP/FCM/realtime. Soon is navSoon (disabled, no href). No pages or routes from the sidebar.

## Stock management group and ops-first nav
Stock tools are their own Stock management group (Manage, Bulk Import)—not Catalog children. Catalog stays Categories/Products only. Group `href` is `stock/manage`. Daily stock, selling price, and discounts are Stock **Manage** (Catalog **Manage** with a known pair opens that page locked; platform catalog **Manage** with only a product opens `stock/manage?product=&locked=product`). Stock transactions live under Reports (`/platform/reports/stock-transactions` and `/branch/{slug}/reports/stock-transactions`). Do not add a top-level Discounts nav item. Sidebar order is ops-first: Dashboard → Catalog → Stock → Orders → Customers → Branch/Driver → Conversations → Marketing → Reports → Administrator → Settings. Under a management group, list labels stay short and plural (Staff not Branch staff; Drivers not All drivers). Branch Catalog collapses to a Products link when branch_can_view_categories is false.

## Notifications live under portal Settings
Notifications is a live navLink under platform Application settings and branch Settings (not Soon, not Administrator). Platform href is /platform/notifications with view-dashboard; branch href is /branch/{slug}/notifications with view-branch-dashboard. Keep Settings parent permissions including view-branch-dashboard so the inbox stays visible without activity-log access.

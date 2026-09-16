---
paths:
  - 'resources/js/components/{platform,branch}/nav.ts'
---

# Platformbranch

## Platform and branch nav are domain dropdowns
One unlabeled NavSection: Dashboard and other singles as navLink; multi-item clusters as navGroup. Do not add a SidebarGroupLabel over a dropdown of the same name. Platform Administrator is platform users only (Admins / Roles / Permissions). Cross-branch staff lives under Branch management as Branch staff (`/platform/branch-staff`) plus an Add staff shortcut to create — not a nested roles/permissions group, and do not deep-link a branch portal. Drivers live under Driver management. Settings labels are Backups and Monitoring (URLs stay /platform/database-backups and /platform/observability). Branch nav: Administrator immediately after Dashboard; Settings holds General/Hours Soon plus live Notifications and Audit logs — never Horizon/Pulse/backups/SMTP/FCM/realtime. Soon is navSoon (disabled, no href). No pages or routes from the sidebar.

## Stock tools live in the Catalog nav group
Both portals list Products, then Manage stock, Import stock, Stock transactions inside the Catalog group (platform children use `manage-branch-inventories`, branch children use `manage-branch-products`). Update price and Add discount are row actions only, not sidebar links. Per-branch stock and price still remain editable on the product screen; the sidebar entries open unlocked pickers (branch portal locks the branch).

## Notifications live under portal Settings
Notifications is a live navLink under platform Application settings and branch Settings (not Soon, not Administrator). Platform href is /platform/notifications with view-dashboard; branch href is /branch/{slug}/notifications with view-branch-dashboard. Keep Settings parent permissions including view-branch-dashboard so the inbox stays visible without activity-log access.

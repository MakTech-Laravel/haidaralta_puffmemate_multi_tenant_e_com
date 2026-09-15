---
paths:
  - 'resources/js/components/{platform,branch}/nav.ts'
---

# Platformbranch

## Platform and branch nav are domain dropdowns
One unlabeled NavSection: Dashboard and other singles as navLink; multi-item clusters as navGroup. Do not add a SidebarGroupLabel over a dropdown of the same name. Platform Administrator is platform users only (Admins / Roles / Permissions). Cross-branch staff lives under Branch management as Branch staff (`/platform/branch-staff`) plus an Add staff shortcut to create — not a nested roles/permissions group, and do not deep-link a branch portal. Drivers live under Driver management. Settings labels are Backups and Monitoring (URLs stay /platform/database-backups and /platform/observability). Soon is navSoon (disabled, no href). No pages or routes from the sidebar. Keep Horizon/Pulse/backups/SMTP/FCM/realtime off the branch tree. Inventory stays on the product screen.

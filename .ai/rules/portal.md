---
paths:
  - 'resources/js/components/portal/**'
---

# Portal

## Bell opens a notifications popover
The header bell opens a Popover anchored to the icon (align end, side bottom), not a centered Dialog/modal. Keep View all in that popover; the full inbox stays on the notifications Inertia page.

## StatCard is the platform/branch summary tile
Platform and branch summary tiles use StatCard (`@/components/portal/stat-card`), not DemoStatCard. Driver/customer/storefront dashboards stay on their own widgets (DemoDataBadge remains a demo marker). Do not restore demo-stat-card.tsx.

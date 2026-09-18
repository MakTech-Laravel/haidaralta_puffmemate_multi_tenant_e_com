---
paths:
    - 'resources/js/components/{navigation,platform,branch,customer,driver}/**'
---

# Navigationplatformbranchcustomerdriver

## Portal sidebar nav uses shared builders

Define portal sidebar trees with navLink / navGroup / navSection from resources/js/components/navigation. Filter with filterNavNodes + canAny (filterNavSections for labeled blocks). Do not add portal-specific sidebar item components or extend legacy AppSidebar/NavMain for platform/branch/customer. UI stays on shadcn Sidebar primitives in SidebarNav.

## Driver uses Lucide sidebar, not PortalShell SidebarNav

Driver navigation is `driverNavItems` + `DriverSidebar` (Dashboard / Orders / My activity / Log Out) with Lucide icons. Below lg: horizontal icon-only row; lg+: vertical icon + label. Do not put driver back on PortalShell SidebarNav or fixed bottom tabs. Do not use StorefrontHeader menu/cart.

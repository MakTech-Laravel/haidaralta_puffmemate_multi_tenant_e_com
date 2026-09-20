---
paths:
    - 'resources/js/components/{navigation,platform,branch,customer,driver}/**'
---

# Navigationplatformbranchcustomerdriver

## Portal sidebar nav uses shared builders

Define portal sidebar trees with navLink / navGroup / navSection from resources/js/components/navigation. Filter with filterNavNodes + canAny (filterNavSections for labeled blocks). Do not add portal-specific sidebar item components or extend legacy AppSidebar/NavMain for platform/branch/customer. UI stays on shadcn Sidebar primitives in SidebarNav.

## Driver field chrome is not PortalShell
Driver navigation is driverNavItems + DriverHeader + DriverMobileNav (below xl) + DriverSidebar (xl+). Do not put driver on PortalShell SidebarNav or StorefrontHeader menu/cart. Phone and tablet must keep the five-tab bar (Dashboard, Orders, Route, Alerts, Account). Route is the center tab. Chat is order-scoped at driver.orders.chat, not a nav destination.

## Driver field tabs include Activity
Phone and tablet must keep the five-tab bar Dashboard, Orders, Route, Activity, Account. Notifications stay on the header bell. Do not restore an Alerts footer tab.

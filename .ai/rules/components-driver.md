---
paths:
  - resources/js/components/driver/nav.ts
  - 'resources/js/components/driver/{nav.ts,driver-sidebar.tsx}'
---

# Components Driver

## Driver sidebar includes Orders
driverNavItems lists Dashboard, Orders (/driver/orders), My activity, Log Out. Keep Orders match covering /driver/orders and /driver/orders/*.

## Driver nav Lucide icons and mobile bar
driverNavItems each carry a LucideIcon (LayoutDashboard, Package, Activity, LogOut). DriverSidebar: <lg horizontal icon-only with aria-label; lg+ vertical icon+text borders. Not PortalShell or fixed bottom tabs.

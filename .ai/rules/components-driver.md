---
paths:
  - resources/js/components/driver/nav.ts
  - 'resources/js/components/driver/{nav.ts,driver-sidebar.tsx,driver-mobile-nav.tsx,driver-header.tsx}'
  - 'resources/js/components/driver/**'
---

# Components Driver

## Driver nav is five field tabs plus desktop extras
driverNavItems is the single source: tab:true items (Dashboard/Orders/Route/Alerts/Account) render in DriverMobileNav (grid-cols-5); the full list including My activity and Log Out renders in DriverSidebar. Route is the center tab at /driver/route. Keep Orders match covering /driver/orders/* so order chat stays on the Orders tab. Do not add a Chats tab — open chat from the order card or order show. Use Wayfinder URLs. Header bell links to notifications; the avatar opens the account sheet — do not put cart, shop menu, or End shift in the driver header.

## Driver lists stay a flat assigned sequence
DriverAssignedOrder may include areaId/areaLabel as address metadata for maps and search. Do not group Orders, Route, or Dashboard with groupDriverAssignedOrdersByArea, suburb section headers, or a suburb badge that implies one assignment per area. Earnings may group by day; history may group by completed day.

## Driver footer uses Activity not Alerts
Driver tab bar is Dashboard, Orders, Route, Activity, Account. Notifications stay on the header bell only — do not restore an Alerts tab. Chat stays order-scoped. Sidebar lists the five tabs plus Log Out.

## Header avatar opens a right sheet
The driver header avatar opens DriverAccountSheet (right Sheet on every breakpoint), not a dropdown and not a direct /driver/account link. Keep profile, settings (security), account hub, call support/branch from shared driverSupport, safety check-in, and log out. Do not reuse UserMenuContent, put End shift or Appearance in the sheet, or restore an Alerts tab.

## Driver field tab is Earnings not Activity
Driver tab bar is Dashboard, Orders, Route, Earnings, Account. Activity is account-only (Account page + account sheet), not a field tab and not a sidebar item. Notifications stay on the header bell. Do not restore an Activity or Alerts footer tab.

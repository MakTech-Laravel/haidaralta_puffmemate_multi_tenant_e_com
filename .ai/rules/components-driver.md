---
paths:
  - resources/js/components/driver/nav.ts
  - 'resources/js/components/driver/{nav.ts,driver-sidebar.tsx,driver-mobile-nav.tsx,driver-header.tsx}'
  - 'resources/js/components/driver/**'
---

# Components Driver

## Driver nav is five field tabs plus desktop extras
driverNavItems is the single source: tab:true items (Dashboard/Orders/Route/Earnings/Profile) render in DriverMobileNav (grid-cols-5); the full list including Old orders and Log Out renders in DriverSidebar. Route is the center tab at /driver/route. Keep Orders match covering /driver/orders/* so order chat stays on the Orders tab. Do not add a Chats tab — open chat from the order card or order show. Use Wayfinder URLs. Header bell links to notifications; the avatar opens the account sheet — do not put cart, shop menu, or End shift in the driver header.

## Driver lists stay a flat assigned sequence
DriverAssignedOrder may include areaId/areaLabel as address metadata for maps and search. Do not group Orders, Route, or Dashboard with groupDriverAssignedOrdersByArea, suburb section headers, or a suburb badge that implies one assignment per area. Earnings may group by day; history may group by completed day.

## Driver footer uses Profile not Account
Driver tab bar is Dashboard, Orders, Route, Earnings, Profile. Account options (edit details, security, activity, support, safety, log out) live in the header sheet, not on a field Account hub. Do not restore an Account, Activity, or Alerts footer tab.

## Header avatar opens a right sheet
The driver header avatar opens DriverAccountSheet (right Sheet on every breakpoint), not a dropdown and not a direct /driver/account link. Keep profile, edit details (settings/profile), security, my activity, call support/branch from shared driverSupport, safety check-in, and log out. Do not reuse UserMenuContent, put End shift or Appearance in the sheet, or restore an Alerts or Account footer tab.

## Driver field tab is Profile not Account
The fifth field tab is Profile at GET /driver/profile. It is the signed-in driver’s identity, vehicle, branches, and roster. Do not turn it back into a link list. Activity stays sheet-only. Notifications stay on the header bell.

## Driver footer tab is Profile not Account
Footer tabs are Dashboard, Orders, Route, Earnings, Profile. Move edit details, security, activity, support, safety, and log out into DriverAccountSheet. Do not restore an Account footer tab.

## Account sheet edit details goes to field editor
DriverAccountSheet Edit details links to driver.profile.edit, not settings/profile. Security stays on settings/security.

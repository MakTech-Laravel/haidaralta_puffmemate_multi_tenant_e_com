---
paths:
  - resources/js/components/driver/nav.ts
  - 'resources/js/components/driver/{nav.ts,driver-sidebar.tsx,driver-mobile-nav.tsx,driver-header.tsx}'
  - 'resources/js/components/driver/**'
  - resources/js/components/driver/driver-account-sheet.tsx
---

# Components Driver

## Driver nav is five field tabs plus desktop Log Out
driverNavItems is the single source: tab:true items (Dashboard/Orders/Route/Earnings/Place order) render in DriverMobileNav (grid-cols-5); the full list including Log Out renders in DriverSidebar. Do not put Old orders or Profile in the sidebar/tabs. Route is the center tab at /driver/route. Keep Orders match covering /driver/orders/* so order chat stays on the Orders tab. Do not add a Chats tab — open chat from the order card or order show. Use Wayfinder URLs. Header bell links to notifications; the avatar opens the account sheet — do not put cart, shop menu, or End shift in the driver header.

## Driver lists stay a flat assigned sequence
DriverAssignedOrder may include areaId/areaLabel as address metadata for maps and search. Do not group Orders, Route, or Dashboard with groupDriverAssignedOrdersByArea, suburb section headers, or a suburb badge that implies one assignment per area. Earnings may group by day; history may group by completed day.

## Driver footer uses Place order not Profile
Driver tab bar is Dashboard, Orders, Route, Earnings, Place order (tabLabel Order). Account options (profile, edit details, security, activity, support, safety, log out) live in the header sheet, not on a field Account or Profile tab. Do not restore an Account, Profile, Activity, Old orders, or Alerts footer tab.

## Header avatar opens a right sheet
The driver header avatar opens DriverAccountSheet (right Sheet on every breakpoint), not a dropdown and not a direct /driver/account link. Keep profile, edit details, security, my activity, Place order (GET /driver/place-order), call support/branch from shared driverSupport, safety check-in, and log out. Do not reuse UserMenuContent, put End shift or Appearance in the sheet, or restore an Alerts or Account footer tab.

## Profile is sheet-only not a field tab
GET /driver/profile stays reachable from DriverAccountSheet (Profile / Edit details). It is the signed-in driver’s identity, vehicle, branches, and roster. Do not put Profile back as a fifth field tab. Activity stays sheet-only. Notifications stay on the header bell. Old orders at GET /driver/history may remain as a route but must not appear in driverNavItems.

## Account sheet edit details goes to field editor
DriverAccountSheet Edit details links to driver.profile.edit, not settings/profile. Security is GET /driver/security (password + Fortify 2FA), not settings/security.

## Account sheet Security is live field page
DriverAccountSheet Security links to GET /driver/security (password update + Fortify 2FA), not settings/security and not a static display-only page.

## Place order is a field tab and sheet quick action
The fifth field tab is Place order at GET /driver/place-order (ShoppingBag icon). DriverAccountSheet Quick actions may also link Place order. Do not add it as a sixth footer tab.

---
paths:
  - resources/js/components/driver/nav.ts
  - 'resources/js/components/driver/{nav.ts,driver-sidebar.tsx,driver-mobile-nav.tsx,driver-header.tsx}'
---

# Components Driver

## Driver nav is five field tabs plus desktop extras
driverNavItems is the single source: tab:true items (Dashboard/Orders/Route/Alerts/Account) render in DriverMobileNav (grid-cols-5); the full list including My activity and Log Out renders in DriverSidebar. Route is the center tab at /driver/route. Keep Orders match covering /driver/orders/* so order chat stays on the Orders tab. Do not add a Chats tab — open chat from the order card or order show. Use Wayfinder URLs. Header icons link to notifications and account — do not put cart, shop menu, or End shift in the driver header.

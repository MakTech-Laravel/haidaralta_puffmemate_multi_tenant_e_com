---
paths:
  - resources/js/components/driver/nav.ts
  - 'resources/js/components/driver/{nav.ts,driver-sidebar.tsx,driver-mobile-nav.tsx,driver-header.tsx}'
---

# Components Driver

## Driver nav is five field tabs plus desktop extras
driverNavItems is the single source: tab:true items (Home/Chats/Orders/Alerts/Account) render in DriverMobileNav; the full list including My activity and Log Out renders in DriverSidebar. Keep Orders match covering /driver/orders/*. Use Wayfinder URLs. Header icons link to notifications and account — do not put cart, shop menu, or End shift in the driver header.

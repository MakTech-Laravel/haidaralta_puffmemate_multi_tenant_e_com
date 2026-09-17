---
paths:
  - 'resources/js/layouts/driver/**'
---

# Layouts Driver

## Driver mirrors customer account shell
Driver portal uses a customer-like chrome: simple DriverHeader (brand + duty, no shop menu/cart), centered “Driver” title band, breadcrumbs, DriverSidebar + content grid. Do not wrap driver pages in PortalShellLayout or StorefrontLayout/StorefrontHeader/StorefrontFooter. Mobile stacks sidebar above content as a horizontal Lucide icon-only bar (labels hidden); lg+ vertical icon+label list. No fixed bottom tabs.

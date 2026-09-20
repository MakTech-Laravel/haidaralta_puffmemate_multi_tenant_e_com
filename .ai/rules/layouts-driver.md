---
paths:
  - 'resources/js/layouts/driver/**'
---

# Layouts Driver

## Driver uses a field header and mobile tab bar
Driver chrome is field-first. Sticky DriverHeader (logo left, notification + avatar right) on every breakpoint. Below xl: fixed five-tab bar (Home, Chats, Orders, Alerts, Account) with safe-area padding and no text footer. xl+: no bottom bar; DriverSidebar lists the five tabs plus My activity and Log out. Do not restore the giant “Driver” title band, StorefrontHeader/Footer, PortalShell, or a copyright footer.

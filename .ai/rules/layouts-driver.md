---
paths:
  - 'resources/js/layouts/driver/**'
---

# Layouts Driver

## Driver uses a field header and mobile tab bar
Driver chrome is field-first. Sticky DriverHeader (logo left, notification bell + account sheet on the right) on every breakpoint. Below xl: fixed five-tab bar (Dashboard, Orders, Route, Earnings, Place order) with safe-area padding and no text footer. xl+: no bottom bar; DriverSidebar lists the five tabs plus Log out. Route is the center tab (today’s sequenced stops at /driver/route). Order chat lives at /driver/orders/{order}/chat, not a nav tab. Do not restore the giant “Driver” title band, StorefrontHeader/Footer, PortalShell, or a copyright footer.

## Driver tab bar has Place order not Profile
Below xl the five-tab bar is Dashboard, Orders, Route, Earnings, Place order. xl+ sidebar lists those five plus Log Out. Profile, activity, security, and log out open from the header sheet. Do not restore an Account, Profile, Old orders, Activity, or Alerts footer tab.

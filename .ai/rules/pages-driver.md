---
paths:
  - 'resources/js/pages/driver/**'
---

# Pages Driver

## Driver lists are assigned stops not suburb groups
Orders, Route, and Dashboard list assigned stops in desk sequence. Do not add suburb section headers, Current/Later area badges, or navigate-to-area. Location stays on each address. Admins assign by load: mix other places onto a driver with spare capacity, or put multiple drivers on the same streets when the pile is too large. Earnings may group by day; history may group by completed day. Chat stays order-scoped. Geography stays Australia only.

## Driver profile tab is a field identity page
The fifth field tab is Profile at GET /driver/profile. Show the signed-in driver's identity, vehicle, branches, and roster from DriverPayload. Do not restore the Account hub link list on this page. Edit details, security, activity, and log out stay in the header sheet.

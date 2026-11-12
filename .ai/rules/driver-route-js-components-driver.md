---
paths:
  - 'resources/js/pages/driver/route/**,resources/js/components/driver/driver-route-map-panel.tsx'
---

# Driver Route Js Components Driver

## Driver route uses a field map panel
The driver route page uses DriverRouteMapPanel on MapView (numbered stop pins, a You marker from the location tracker store, next/selected overlay, optional road polyline). Do not restore OrderTrackMap or “Map coming soon” copy. Keep highlightId from ?highlight= and label the matching stop Selected.

## Route map stays card-sized until full screen
Default map is the padded card (`h-72` / `sm:h-96`) inside the normal DriverLayout shell. Full screen is opt-in via a Full screen control that covers the viewport; Close (or Escape) exits. Do not make the Route tab always full-bleed.

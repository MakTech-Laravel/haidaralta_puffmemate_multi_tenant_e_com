---
paths:
  - 'resources/js/pages/driver/route/**,resources/js/components/driver/driver-route-map-panel.tsx'
---

# Driver Route Js Components Driver

## Driver route uses a field map panel
The driver route page uses DriverRouteMapPanel on MapView (numbered stop pins, a You marker from the location tracker store, optional road polyline). Keep highlightId from ?highlight= and label the matching stop Selected. In card mode, focus-stop actions live under the map (not a large floating card); fullscreen may use a compact bottom panel. Map-local chrome uses z-10 inside an `isolate overflow-hidden` map frame — do not use Leaflet-scale z-[1000] overlays that bleed over the driver header.

## Route map stays card-sized until full screen
Default map is the padded card (`h-72` / `sm:h-96`) inside the normal DriverLayout shell. Full screen is opt-in via a Full screen control that covers the viewport; Close (or Escape) exits. Do not make the Route tab always full-bleed.

## Route lists remaining stops only
Route shows map + next/remaining for what’s left. Keep “X left · Y done” in the progress strip from completed counts, but do not render a Completed stop list on Route — that review belongs on Orders.

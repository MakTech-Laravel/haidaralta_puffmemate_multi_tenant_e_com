---
paths:
  - 'app/Models/BranchArea.php,app/Services/Branch/BranchAreaService.php,app/Http/Controllers/{Platform,Branch}/*Area*Controller.php,app/Support/Geo/Polygon.php,resources/js/lib/nominatim.ts,resources/js/lib/geo-polygon.ts,resources/js/pages/{platform,branch}/**/areas/**,resources/js/components/admin/branch-area-{form,polygon-map}.tsx,resources/js/components/admin/boundary-place-suggest-input.tsx'
---

# Areas Js Components Admin

## Branch areas are delivery zones CRUD only
branch_areas are named delivery/service zones (name, address+lat/lng label pin, required polygon ring, city, state; optional postal_code, notes, is_active) linked to many branches via `branch_area_branch`. Managed on Platform and Branch portal. BranchAreaForm Location: AddressSuggest + CheckoutMapDialog with `countrycodes="au"`; selecting address/pin fills polygon via `resolveAreaZoneRing` (Nominatim geometry or squareAround + 50 m pad). BranchAreaPolygonMap shows outline + optional Leaflet-draw (no separate zone search). Assign = attach; do not steal membership. Storefront cart checkout resolves the nearest active linked branch whose area polygon contains the pin (`AreaBranchResolver`); `delivery_radius_km` / `BranchCandidateFinder` are not used for that cart path.

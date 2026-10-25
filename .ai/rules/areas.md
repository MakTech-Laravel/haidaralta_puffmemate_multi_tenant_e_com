---
paths:
  - 'resources/js/components/admin/branch-area-detail.tsx,app/Services/Branch/BranchAreaService.php,resources/js/pages/{platform,branch}/**/areas/**'
---

# Areas

## Areas show uses BranchAreaDetail + map links
Platform and Branch area show pages render shared BranchAreaDetail (header, Details/Location cards, View on map via BranchLocationMapDialog). BranchAreaService::detail includes linked branches, required `polygon` ring, plus map via BranchMap::links when coords exist. Platform “Add to branch” attaches without removing other memberships. Create/edit use sectioned BranchAreaForm (Identity / Location / Notes).

## Branch areas require a service zone polygon
`branch_areas.polygon` is a closed JSON ring `list<{lat,lng}>` (min 3 unique vertices; server normalizes/closes). Latitude/longitude remain the label pin. Zone outline is driven by **Address** (AU-only Nominatim `countrycodes=au`): select address or AU map pin → `resolveAreaZoneRing` (boundary or squareAround) + silent 50 m `bufferRing` → polygon. No separate Service zone search. Clear address clears polygon. Leaflet-draw optional fine-tune; map uses `.branch-area-polygon-map` low z-index so UI is not covered. Storefront cart checkout uses `AreaBranchResolver` + `Polygon::contains` to pick the nearest linked active branch; pin outside all areas blocks checkout (no radius fallback).

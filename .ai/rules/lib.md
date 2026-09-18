---
paths:
  - resources/js/lib/fcm.ts
  - resources/js/lib/color-picker.ts
  - resources/js/lib/stock-query.ts
  - resources/js/lib/branch-people-query.ts
---

# Lib

## FCM token-subscribe-failed

token-subscribe-failed / missing OAuth on getToken usually means browser lost Firebase Installations auth: API key HTTP-referrer restrictions (allow localhost:8000), missing FCM Registration + Installations APIs on the key, mixed localhost vs 127.0.0.1 origins, or stale firebase-*-database IndexedDB. Client clears IDB and re-registers SW once on that error.

## color-picker lib parse vs normalize
parseColorPickerValue is for display (any mode). normalizeColorPickerValue applies enabledModes/defaultMode for picker state. Catalog uses normalizeCatalogColorPickerValue (transparent default, no radial). colorPickerStyle/cssValue use parse, not solid-only defaults.

## Catalog create default is transparent
normalizeCatalogColorPickerValue and catalogFormColorPickerValue map empty/invalid to transparent. Create and edit share that default — do not invent a solid fill on new listings. DEFAULT_CATALOG_SOLID_COLOR (#EF4444) is a palette swatch only.

## Catalog transparent inherits parent surface
Catalog create/edit default color_code is transparent. Listing/gallery tiles and ColorPicker previews inherit the page or card color when transparent. COLOR_PICKER_CHECKERBOARD stays only on the hex swatch editor, not on catalog tiles.

## Row stock actions are Manage stock plus reports
stockActionItems is the Stock group: optional Manage stock, then Stock transactions and Price history. Daily stock, selling price, and discounts stay on **Manage stock**. Catalog **Manage stock** with a known pair opens that page locked. Do not add Add stock, Update price, or Add discount row actions. List menus use labeled groups, never a flat ungrouped `items` list on branch product rows.

## Branch people list URLs use filter[branch_id]
filteredBranchStaffUrl / filteredBranchDriversUrl in branch-people-query.ts build Wayfinder global list URLs with filter[branch_id]. Use those from the platform Branches list and show page instead of nested index routes.

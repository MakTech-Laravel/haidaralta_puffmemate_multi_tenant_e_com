---
paths:
  - resources/js/lib/fcm.ts
  - resources/js/lib/color-picker.ts
---

# Lib

## FCM token-subscribe-failed

token-subscribe-failed / missing OAuth on getToken usually means browser lost Firebase Installations auth: API key HTTP-referrer restrictions (allow localhost:8000), missing FCM Registration + Installations APIs on the key, mixed localhost vs 127.0.0.1 origins, or stale firebase-*-database IndexedDB. Client clears IDB and re-registers SW once on that error.

## color-picker lib parse vs normalize
parseColorPickerValue is for display (any mode). normalizeColorPickerValue applies enabledModes/defaultMode for picker state. Catalog uses normalizeCatalogColorPickerValue (transparent default, no radial). colorPickerStyle/cssValue use parse, not solid-only defaults.

## Catalog create default is solid red
normalizeCatalogColorPickerValue still maps empty/invalid to transparent for display. Product create uses defaultCatalogColorPickerValue() / DEFAULT_CATALOG_SOLID_COLOR (#EF4444). Do not change the display helper default to solid or existing null listings will gain a fill.

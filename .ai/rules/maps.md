---
paths:
  - 'app/Services/Maps/**'
  - 'config/maps.php'
  - 'app/Http/Controllers/MapProxyController.php'
  - 'app/Http/Controllers/Platform/MapProviderSettingsController.php'
  - 'resources/js/lib/maps/**'
  - 'resources/js/components/map/**'
  - 'resources/js/pages/platform/settings/map-provider.tsx'
  - 'routes/public.php'
---

# Maps

## Providers are defined in code, chosen in the dashboard
The list of map providers is the registry in `config/maps.php` (OpenStreetMap, Mapbox, Google). The platform dashboard (Settings → Map provider, `manage-application-settings`) can only pick from it, save keys, enable/disable and set limits. It cannot add or delete providers. A new provider is one class extending `AbstractMapProvider` plus one registry entry; its DB row is created by `MapSettingsService::sync()`.

## Keys: database first, then env, never to the browser unsafely
`MapSettingsService::credentials()` reads the encrypted `map_providers` row first and falls back to the env keys in `config/maps.php`. Raw attributes are cached and the cache is rebuilt on every write. Browser keys (Google JS key, Mapbox public token) are public by nature and must be restricted by referrer. Server keys (Google Geocoding/Routes) never leave the server: `frontendConfig()` and `/maps/config` must not contain them. A blank key field keeps the saved key; `clear_*` removes it.

## Capabilities are chosen separately
Display, geocoding and routing each have their own selected provider plus one fallback. A provider only supports a capability when the keys for it exist (`capabilities()`), and the dashboard rejects selecting one without keys. `MapProviderResolver::chain()` skips unconfigured, disabled and over-limit providers, then falls back to the fallback and finally to `maps.default_provider` (OpenStreetMap).

## Every paid call goes through MapManager
Use `MapManager::geocode/reverse/route`; never call a provider class directly from a controller. It caches answers (geocode 30 days, routes 10 minutes, keyed on normalized/rounded input), retries the next provider on failure, and counts the call in `map_provider_usages`. Reaching 80% and 100% of a daily or monthly limit notifies platform admins once per period. Do not add per-position routing or geocoding calls.

## Browser address search uses the proxy
Customer-facing address search and reverse geocoding go through `/maps/geocode` and `/maps/reverse` (throttled `maps` limiter, open to guests). Zone outline geometry for branch areas still calls Nominatim directly from the admin editor.

## Frontend renders through MapView
Screens use `MapView` / `createMap()` and the `MapAdapter` interface (Leaflet for OSM and Mapbox, Google Maps JS loaded on demand). Google tiles cannot be drawn in Leaflet, so the polygon editor takes tiles from `/maps/config` `leaflet`. If the active renderer cannot start the map falls back to OpenStreetMap tiles. Do not import Leaflet or Google directly in screens.

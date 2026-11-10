---
paths:
  - 'app/Services/Driver/DriverLocationService.php'
  - 'app/Services/Driver/DriverTrackingGate.php'
  - 'app/Services/Driver/DriverTrackingBoard.php'
  - 'app/Services/Driver/DriverMapWatchers.php'
  - 'app/Events/DriverLocationUpdated.php'
  - 'app/Http/Middleware/EnsureDriverLocationShared.php'
  - 'app/Http/Controllers/*/DriverTrackingController.php'
  - 'app/Http/Controllers/Driver/LocationController.php'
  - 'resources/js/hooks/use-driver-location*.ts'
  - 'resources/js/components/admin/driver-live-board.tsx'
  - 'resources/js/components/driver/driver-location-tracker.tsx'
  - 'config/drivers.php'
---

# Driver tracking

## Collected only while working
`DriverAvailability::shouldTrack()` is the single rule: not on break, and either inside a scheduled/override shift window or holding an active delivery (`FulfillmentStatus::driverWorkload()`). Off duty the server answers 204 to `POST /driver/location` and stores nothing. Shift time uses the same viewer-timezone logic as the rest of the app (`DateTimeFormat`); there is no overnight shift support.

## The last position lives in the cache
`DriverLocationService` keeps the latest position per driver in the cache (TTL `last_position_ttl_seconds`) and writes `driver_locations` breadcrumbs only during an active delivery (50 m or 5 minutes), pruned after `retention_days` by `driver-locations:prune`. `driver_id` is always `users.id`. Readings that are inaccurate, too old, out of order or an impossible jump are ignored.

## Reverb is used only when someone watches
A position is broadcast (`DriverLocationUpdated`, `ShouldBroadcastNow`, small payload with `ts`) only after the driver moved 10 m or a 60 s heartbeat, and only while a live map is open (`DriverMapWatchers`, `DRIVER_TRACKING_WATCH_ONLY`). Channels: `platform.drivers.locations` (`manage-drivers`) and `branch.{id}.drivers` (`manage-branch-drivers`, own branch only). The Reverb server runs outside this repo; broadcasts have short Guzzle timeouts so a slow server never holds a driver request open.

## Live maps are snapshot plus Reverb
`DriverTrackingBoard::snapshot()` builds the page from batched queries plus the cache (never one query per driver). After that the page only listens on Reverb and re-fetches the snapshot on tab focus, reconnect, or when an unknown driver appears. Open maps heartbeat `…/driver-map/watch` every 30 s while visible.

## The portal gate is enforced on the server
`EnsureDriverLocationShared` (alias `ensure.driver.location`) redirects a driver who should be sharing but has no position fresher than `stale_after_seconds` to `/driver/location-required` (423 JSON for background requests). Only the location endpoint, the prompt page and logout stay open. `DRIVER_TRACKING_GATE=false` disables blocking; collection continues. Tests run with the gate and watcher-only broadcasting off (`phpunit.xml`) and enable them per test.

## Browser limits
This is a web app: a locked screen or a background tab can pause GPS. The tracker uses a wake lock, a heartbeat fix, and shows a warning bar; drivers who go stale are sent back to the prompt. True background tracking needs a native app.

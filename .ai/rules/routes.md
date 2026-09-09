---
paths:
  - routes/settings.php
  - 'routes/*.php'
---

# Routes

## Settings require any portal guard
Settings routes must use `ensure.any.portal` (not plain `auth` / default `web`). Portal users authenticate on platform|branch|driver|customer only; plain `auth` redirects platform admins away from /settings/*. Mirror EnsurePortalGuard: detect via PortalAuth::activeGuard(), Auth::shouldUse, reject inactive users.

## Portal routes live in dedicated files
web.php only requires portal route files plus fortify.php and settings.php. Put public routes in public.php, platform in platfrom.php (filename spelling is intentional), branch/driver/customer in their matching files. Do not move fortify, settings, console, or channels into portal files.

---
paths:
  - routes/settings.php
---

# Routes

## Settings require any portal guard
Settings routes must use `ensure.any.portal` (not plain `auth` / default `web`). Portal users authenticate on platform|branch|driver|customer only; plain `auth` redirects platform admins away from /settings/*. Mirror EnsurePortalGuard: detect via PortalAuth::activeGuard(), Auth::shouldUse, reject inactive users.

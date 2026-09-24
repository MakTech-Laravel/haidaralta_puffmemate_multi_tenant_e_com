---
paths:
    - routes/settings.php
    - routes/fortify.php
    - 'routes/*.php'
---

# Routes

## Settings require any portal guard

Settings routes must use `ensure.any.portal` (not plain `auth` / default `web`). Portal users authenticate on platform|branch|driver|customer only; plain `auth` redirects platform admins away from /settings/*. Mirror EnsurePortalGuard: detect via PortalAuth::activeGuard(), Auth::shouldUse, reject inactive users.

## Fortify account routes use ensure.any.portal

Password confirm, confirmation status, profile/password update, 2FA manage, and authenticated passkey routes must use `ensure.any.portal` — not `auth:web` (`fortify.guard`). Portal users are never on web; `auth:web` sends them to login, and LoginController then dumps an already-authenticated portal user on their dashboard. Keep guest 2FA/passkey login on `guest:web`.

## Portal routes live in dedicated files

web.php only requires portal route files plus fortify.php, settings.php, and fcm.php. Put public routes in public.php, platform in platform.php, branch/driver/customer in their matching files. Shared FCM register/unregister live in fcm.php behind ensure.any.portal. Do not move fortify, settings, console, or channels into portal files.

## Precognition on unique-backed mutations

Store/update routes whose Form Requests enforce uniqueness (`email`, branch `slug`/`code`, scoped role `name`, register, profile email) must use the `precognitive` middleware alias (`HandlePrecognitiveRequests`). Do not attach it to index/show/destroy or non-unique flows (login, password, 2FA, deletes, backups, FCM).

## Precognition on unique mutation routes

Unique-backed store/update routes (admins, staff, branch slug/code, roles, register, profile email) must use the precognitive middleware alias. Frontend uses Inertia built-in Precognition and live-validates uniqueness fields only on blur.

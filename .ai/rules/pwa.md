---
paths:
  - config/pwa.php
  - pwa-assets.config.ts
  - resources/views/app.blade.php
  - resources/js/lib/pwa.ts
  - resources/js/components/pwa/**
  - docker/nginx.conf
  - routes/public.php
  - app/Http/Controllers/WebAppManifestController.php
  - app/Http/Controllers/FirebaseMessagingServiceWorkerController.php
---

# PWA

One origin, one product name (**PuffMeMate** in every environment — never a Staging/Local suffix). Identity is per origin; agency-host installs do not follow the client domain. After go-live, `id` `/` must stay stable.

## Manifest

Laravel serves `GET /manifest.webmanifest` (`WebAppManifestController`). Do not commit `public/manifest.webmanifest` (nginx `try_files` would skip Laravel). Headers: `Content-Type: application/manifest+json`, `Cache-Control: no-cache`. `lang` is `en-AU`. Guest/customer `start_url` is `/`; platform/branch/driver use `PortalAuth::dashboardPath`.

## Icons

Generate with `pnpm generate:pwa-assets` (`@vite-pwa/assets-generator` only). Never `vite-plugin-pwa`, Workbox, or Serwist. Output lives in `public/pwa/` and is **committed**. Regenerate when `mark.svg` or `pwa-assets.config.ts` changes; the Docker app image does **not** run generate (Vite build only). No new `VITE_*` PWA args.

## Service worker

`/firebase-messaging-sw.js` is the only worker (`Service-Worker-Allowed: /`). Register on every boot (notification permission is unrelated). Unregister any other `scriptURL` on the origin. Network-only — no `fetch` cache. `PWA_SW_ENABLED` kill-switch. Do not build WordPress user migration into the PWA.

## Install UI

`PwaInstallProvider` lives in `createInertiaApp`'s `withApp`, which is **outside** Inertia page context. Pass `name` from `page.props` — never call `usePage()` there (`usePage must be used within the Inertia component` blanks the app).

## Nginx

`Permissions-Policy` `geolocation=(self)`. Exact locations for `/manifest.webmanifest` and `/firebase-messaging-sw.js` rewrite to `index.php`. Do not long-cache `service-worker.js`. gzip `application/manifest+json`. PNG/splash files under `public/pwa/` keep the 7-day static cache.

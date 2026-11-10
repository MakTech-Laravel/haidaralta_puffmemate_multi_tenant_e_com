---
paths:
    - Dockerfile
    - Dockerfile.base
---

# Dockerfile

## Coolify git auto-deploy (primary)

Root `Dockerfile` is self-contained for Coolify **Dockerfile** pack + git auto-deploy. Stages: `runtime-base` → `build` → `production`. Do not require a pre-pulled GHCR base or Coolify “Docker Image” pack for normal releases. When only app code changes, BuildKit should cache `runtime-base` (no PHP recompile).

Reusable image names (any Laravel project): `php-runtime:8.4` (`Dockerfile.base`) and `app`. Keep `Dockerfile.base` in sync with the `runtime-base` stage.

## Nginx / entrypoint / supervisord

No Docker-speed changes belong in `docker/nginx.conf`, `docker/entrypoint.sh`, or `docker/supervisord.conf`. They already run Nginx, PHP-FPM, Horizon, `pulse:check`, and the scheduler. Do not add Reverb/Postgres/Redis servers here.

## Keep main production processes together

The Coolify application image runs Nginx, PHP-FPM, Laravel Horizon, `pulse:check`, and the scheduler under Supervisor. Reverb, PostgreSQL, Redis server, and Meilisearch remain separate resources and must never be started or proxied locally by this image.

## FCM_SW_SHOW_NOTIFICATION runtime

FCM_SW_SHOW_NOTIFICATION is runtime (production ENV default false / Coolify override). Do not add it as a Vite build ARG; VITE_FIREBASE_* stay build-time only. Default `VITE_APP_NAME` in the Dockerfile is generic (`App`); pass the real product name via Coolify build args.

## postgresql-client for Spatie backups

`runtime-base` / `Dockerfile.base` must include `postgresql-client-18` from PGDG (not Debian’s default `postgresql-client` 15.x) so `pg_dump`/`psql` match Coolify Postgres 18. Install `procps` so Pulse `pulse:check` can read CPU via `top`. Keep QUEUE_TIMEOUT ≥ 600 and Horizon `stopwaitsecs` above that.

## PWA assets are committed — do not regenerate in Docker

Icons/splashes live under `public/pwa/` (committed). The app image runs `pnpm run build` only — do not run `pnpm generate:pwa-assets` in Docker. Regenerate locally when `mark.svg` or `pwa-assets.config.ts` changes. PWA_SW_ENABLED is runtime (default true).

## Build stage needs PHP + minimal Node for Wayfinder

`@laravel/vite-plugin-wayfinder` runs Artisan during Vite. Copy only `node` + `corepack` from `node:24-bookworm-slim` (not all of `/usr/local`). Use a build-only dummy `APP_KEY` — never the production key. Final production stage must not contain Node, pnpm, or Composer.

## PHP base needs ext-exif + GD WebP

`runtime-base` must include exif and GD with freetype/jpeg/webp (`libwebp7` at runtime). Keep `pdo_mysql` and `pdo_pgsql`. Do not install `nano`.

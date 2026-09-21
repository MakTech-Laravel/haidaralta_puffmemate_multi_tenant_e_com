---
paths:
    - Dockerfile
---

# Dockerfile

## Keep main production processes together

The main Coolify application image runs Nginx, PHP-FPM, Laravel Horizon, `pulse:check`, and the scheduler under Supervisor. Reverb, PostgreSQL, Redis server, and Meilisearch remain separate resources and must never be started or proxied locally by this image.

## FCM_SW_SHOW_NOTIFICATION runtime

FCM_SW_SHOW_NOTIFICATION is runtime (production ENV default false / Coolify override). Do not add it as a Vite build ARG; VITE_FIREBASE_* stay build-time only.

## postgresql-client for Spatie backups

Production image must include `postgresql-client-18` from PGDG (not Debian’s default `postgresql-client` 15.x) so `pg_dump`/`psql` match Coolify Postgres 18. A lower client major aborts with “server version mismatch”. Postgres remains a separate Coolify resource; only client tools belong in the app image alongside libpq5. Keep QUEUE_TIMEOUT ≥ 600 and Horizon `stopwaitsecs` above that so RunBackupJob / RestoreDatabaseFromBackupJob can finish. Install `procps` so Pulse `pulse:check` can read CPU via `top` (memory already works from `/proc/meminfo`); without it Servers card shows 0% CPU.

## App image pg_dump must match Postgres major

Coolify Postgres is 18.x. Install postgresql-client-18 from PGDG in the production Dockerfile — Debian bookworm postgresql-client is 15.x and Spatie backup aborts with server version mismatch. Rebuild/redeploy the app image after changing clients.

## Pulse CPU needs procps in the app image

Install procps in the production image so Pulse pulse:check can sample CPU with top. Without it Servers shows 0% CPU while memory/disk still work via /proc.

## Generate PWA assets in the image build
Run pnpm generate:pwa-assets before pnpm run build. No new VITE_* PWA args. PWA_SW_ENABLED is runtime (default true), same pattern as FCM_SW_SHOW_NOTIFICATION.

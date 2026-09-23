---
paths:
    - config/*.php
    - config/scout.php
---

# Config

## Redis Scout Meilisearch external

Redis, Meilisearch, and Reverb stay external Coolify resources. Prefer discrete REDIS_* vars with REDIS_DB=0 / REDIS_CACHE_DB=1; never set a shared REDIS_URL=.../0 for both connections. Scout uses meilisearch with server-only MEILISEARCH_* (never VITE_*). Queued indexing uses Redis queue scout via QUEUE_NAMES=default,scout. Searchable: User, Branch, Category, Product. Keep Category/Product Meilisearch filterable/sortable lists in config/scout.php index-settings in sync when searchable fields change. Soft-deleted catalog models are excluded from default Scout queries. After seed or index-settings/prefix changes, run `scout:import-all`. Locally set `SCOUT_QUEUE=false` when the Redis `scout` worker is not running — otherwise import only queues jobs and Meilisearch stays empty (index not found). Roles, activity, and backups stay SQL. Tests: SCOUT_DRIVER=collection. Never flush shared Redis on deploy.

## PWA name is never env-suffixed

`config/pwa.php` name/short_name come from `PWA_NAME` / `APP_NAME`. Do not branch on `APP_ENV`. See `.ai/rules/pwa.md`.

## Driver assignment mode is a config flag

Driver assignment mode is `config('drivers.single_active_branch')` from env `DRIVER_SINGLE_ACTIVE_BRANCH` (default true). Read via `config()`, never `env()`, in services/requests. Share as Inertia `drivers.single_active_branch`. Do not add a unique-on-active DB index — the flag must be togglable without a migration.

## Platform administrator shifts nav flag

`shifts.show_platform_administrator_shifts` (env `SHIFTS_SHOW_PLATFORM_ADMINISTRATOR_SHIFTS`, default false) toggles only the platform Administrator dropdown Shifts / Assign to shift links. Branch management shifts stay visible. Read via `config()`, share on Inertia `shifts.show_platform_administrator_shifts`.


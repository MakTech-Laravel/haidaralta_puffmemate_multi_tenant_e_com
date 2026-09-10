# Redis / Scout / Meilisearch

- Redis, Meilisearch, and Reverb stay external Coolify resources — never bundle them into the app image or Supervisor process set.
- Prefer discrete `REDIS_HOST` / `REDIS_USERNAME` / `REDIS_PASSWORD` / `REDIS_PORT` with `REDIS_DB=0` and `REDIS_CACHE_DB=1`. Do not set a shared `REDIS_URL=.../0` for both connections (cache would land on DB 0).
- Local Windows/dev against a remote Coolify Redis: use `SESSION_DRIVER=database`, `CACHE_STORE=database`, and `QUEUE_CONNECTION=database` (or `sync`). Keep Redis credentials for Scout import/workers only when you intentionally run them. Remote Redis RTT (~1s+) plus Telescope (default queue = Redis) makes login/Inertia look like “connection errors” and leaves the user on `/login`. Set `TELESCOPE_ENABLED=false` locally unless the queue is not remote Redis.
- Never flush the shared Redis instance on deploy.
- Scout: `SCOUT_DRIVER=meilisearch`, server-only `MEILISEARCH_*` (never `VITE_*`). Queued sync uses Redis queue `scout` (`QUEUE_NAMES=default,scout`).
- Searchable models: `User`, `Branch` (discovered under `app/Models`). After index-settings or `SCOUT_PREFIX` changes: `php artisan scout:import-all` (syncs Meilisearch settings when supported, then imports every Searchable model). Without sync/import, production staff search fatals (`user_type` not filterable / index not found) while local Meilisearch already has settings.
- Tests use `SCOUT_DRIVER=collection` and `SCOUT_QUEUE=false`.

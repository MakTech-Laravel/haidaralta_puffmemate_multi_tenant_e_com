# Redis / Scout / Meilisearch

- Redis, Meilisearch, and Reverb stay external Coolify resources — never bundle them into the app image or Supervisor process set.
- Prefer discrete `REDIS_HOST` / `REDIS_USERNAME` / `REDIS_PASSWORD` / `REDIS_PORT` with `REDIS_DB=0` and `REDIS_CACHE_DB=1`. Do not set a shared `REDIS_URL=.../0` for both connections (cache would land on DB 0).
- Never flush the shared Redis instance on deploy.
- Scout: `SCOUT_DRIVER=meilisearch`, server-only `MEILISEARCH_*` (never `VITE_*`). Queued sync uses Redis queue `scout` (`QUEUE_NAMES=default,scout`).
- Searchable models: `User`, `Branch`. After index-settings changes: `scout:sync-index-settings` then `scout:import`.
- Tests use `SCOUT_DRIVER=collection` and `SCOUT_QUEUE=false`.

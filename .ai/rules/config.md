---
paths:
    - config/scout.php
---

# Config

## Redis Scout Meilisearch external

Redis, Meilisearch, and Reverb stay external Coolify resources. Prefer discrete REDIS_* vars with REDIS_DB=0 / REDIS_CACHE_DB=1; never set a shared REDIS_URL=.../0 for both connections. Scout uses meilisearch with server-only MEILISEARCH_* (never VITE_*). Queued indexing uses Redis queue scout via QUEUE_NAMES=default,scout. Searchable: User, Branch. Tests: SCOUT_DRIVER=collection. Never flush shared Redis on deploy.

---
paths:
    - Dockerfile
---

# Dockerfile

## Keep main production processes together

The main Coolify application image runs Nginx, PHP-FPM, Laravel queue workers, and the scheduler under Supervisor. Reverb, PostgreSQL, Redis server, and Meilisearch remain separate resources and must never be started or proxied locally by this image.

## FCM_SW_SHOW_NOTIFICATION runtime

FCM_SW_SHOW_NOTIFICATION is runtime (production ENV default false / Coolify override). Do not add it as a Vite build ARG; VITE_FIREBASE_* stay build-time only.

## postgresql-client for Spatie backups
Production image must include postgresql-client (pg_dump/psql) for Spatie laravel-backup and DB restore-from-zip. Postgres remains a separate Coolify resource; only client tools belong in the app image alongside libpq5. Keep QUEUE_TIMEOUT ≥ 600 and queue worker stopwaitsecs above that so RunBackupJob / RestoreDatabaseFromBackupJob can finish.

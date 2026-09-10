# Coolify Production Deployment

## Architecture

Deploy this repository as one Coolify **Application** using the root `Dockerfile` build pack. The resulting replaceable container listens on port `80` and runs Nginx, PHP-FPM, Laravel queue workers, and the Laravel scheduler under Supervisor.

PostgreSQL, Redis, and Meilisearch are separate Coolify resources. Laravel Reverb is a separate Git repository, Dockerfile, Coolify Application, domain, and deployment lifecycle. This image does not contain or start any of those servers and does not expose ports `5432`, `6379`, `7700`, `8080`, or `9000`.

## Coolify Application Settings

| Setting                | Value            |
| ---------------------- | ---------------- |
| Build pack             | Dockerfile       |
| Dockerfile             | `Dockerfile`     |
| Internal port          | `80`             |
| Health path            | `/up`            |
| Expected health status | `200`            |
| Health interval        | `30s`            |
| Health timeout         | `5s`             |
| Health retries         | `3`              |
| Stop Grace Period      | `120s` initially |

`/up` is Laravel's built-in health route. It proves Nginx, PHP-FPM, and Laravel bootstrap without making transient dependency failures trigger restart cascades. Monitor PostgreSQL, Redis, Meilisearch, and Reverb separately for readiness and dependency diagnostics.

The 120-second stop grace exceeds the default queue worker timeout (`60s`) and Supervisor worker wait (`90s`). Increase all three coherently if production jobs legitimately need a longer timeout. Coolify's grace period must remain longer than Supervisor's queue `stopwaitsecs`.

## Runtime Environment

Set secrets in Coolify and never commit or build them into the image. Generate `APP_KEY` once with `php artisan key:generate --show`, store it in Coolify, and preserve it across deploys, restarts, and rollbacks.

Required application values:

```dotenv
APP_NAME=PuffMeMate
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://app.example.com
LOG_CHANNEL=stack
LOG_STACK=stderr
LOG_LEVEL=info
```

Use the internal Coolify PostgreSQL hostname, not `127.0.0.1` or an auto-generated container ID:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=postgres-resource-hostname
DB_PORT=5432
DB_DATABASE=puffmemate
DB_USERNAME=puffmemate
DB_PASSWORD=...
DB_SSLMODE=prefer
```

Platform Spatie backups shell out to `pg_dump` / `psql` **inside the PUFFMEMATE app container**, then connect to the separate Postgres resource via `DB_HOST`. Set `POSTGRES_BINARY_PATH=/usr/bin` so Spatie’s db-dumper finds the binaries. The production Dockerfile installs `postgresql-client` for that; do not put dump/restore tools only on the database container. After changing the Dockerfile, rebuild and redeploy the app image.

Backup zips live on the `backups` disk at `storage/app/private/spatie-backups`. Mount a Coolify persistent volume for at least `storage/app/private` (or the backups path) — otherwise redeploys wipe zip inventory. Optional: `BACKUP_ARCHIVE_PASSWORD`, `BACKUP_MAIL_TO` (leave empty to disable backup mail notifications).

Retention (`backup:clean` daily at 03:00 and platform **Clean old backups**):

```dotenv
BACKUP_KEEP_ALL_DAYS=7
BACKUP_KEEP_DAILY_DAYS=0
BACKUP_KEEP_WEEKLY_WEEKS=0
BACKUP_KEEP_MONTHLY_MONTHS=0
BACKUP_KEEP_YEARLY_YEARS=0
BACKUP_MAX_STORAGE_MB=5000
```

With ladder values at `0`, cleanup hard-deletes zips older than `BACKUP_KEEP_ALL_DAYS` (newest zip is never removed by Spatie). Empty `BACKUP_MAX_STORAGE_MB` means no size cap. After changing these on Coolify, clear config cache / redeploy so workers pick them up.

Use the internal Coolify Redis hostname. Separate logical databases prevent normal cache eviction from targeting queue data, but they are not a security boundary:

```dotenv
REDIS_CLIENT=phpredis
REDIS_HOST=redis-resource-hostname
REDIS_PASSWORD=...
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
CACHE_STORE=redis
CACHE_PREFIX=puffmemate_cache_
SESSION_DRIVER=redis
SESSION_CONNECTION=cache
QUEUE_CONNECTION=redis
```

Queue process settings (include the Scout indexing queue):

```dotenv
QUEUE_NAMES=default,scout
QUEUE_SLEEP=3
QUEUE_TRIES=3
QUEUE_TIMEOUT=60
QUEUE_MAX_TIME=3600
QUEUE_MEMORY=128
QUEUE_PROCESSES=2
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=90
REDIS_QUEUE_BLOCK_FOR=5
```

Prefer discrete Redis host/user/password/port with `REDIS_DB=0` and `REDIS_CACHE_DB=1`. Do **not** set a single `REDIS_URL=.../0` for both the `default` and `cache` connections — both read `REDIS_URL`, so `/0` would force cache onto DB 0 and collide with queues. Leave `REDIS_URL` empty when using discrete vars.

Local Windows development against the public Redis hostname often sees ~1s RTT per Redis command. Do **not** point local `SESSION_DRIVER` / `CACHE_STORE` at that remote Redis — login and every page load will hang or show connection errors. Use `SESSION_DRIVER=database` and `CACHE_STORE=database` locally; keep `QUEUE_CONNECTION=redis` for Scout workers if needed. Production Coolify (same network as Redis) should keep session/cache on Redis as shown above.

Scout + Meilisearch (server-only; never `VITE_*`):

```dotenv
SCOUT_DRIVER=meilisearch
SCOUT_PREFIX=puffmemate_
SCOUT_QUEUE=true
SCOUT_QUEUE_CONNECTION=redis
SCOUT_QUEUE_NAME=scout
MEILISEARCH_HOST=https://meilisearch.puffmemate.maktechlaravel.cloud
MEILISEARCH_KEY=...
```

After first deploy (or index-settings change), run once from an app container:

```sh
php artisan scout:sync-index-settings
php artisan scout:import "App\Models\User"
php artisan scout:import "App\Models\Branch"
```

`REDIS_QUEUE_RETRY_AFTER` must remain safely greater than `QUEUE_TIMEOUT`. Begin with two workers. Tune worker count from measured queue latency, backlog, job duration/RSS, CPU, RAM, and PostgreSQL connection capacity rather than setting it from request traffic alone.

Mail credentials and any service credentials used by the application must also be runtime Coolify variables. PHP-FPM uses `clear_env = no`, so the same process environment is visible to HTTP PHP and Artisan CLI processes.

## Build-Time Public Variables

Vite compiles `VITE_*` values into public browser bundles during the Docker build. Changing them in Coolify after the image has built does not modify the bundle; rebuild the image after changing one. Never place secrets in a `VITE_*` variable.

Required build args (Dockerfile already accepts them):

```dotenv
VITE_APP_NAME=PuffMeMate
VITE_ERROR_BOUNDARY_DEBUG=false
VITE_REVERB_APP_KEY=<same as REVERB_APP_KEY>
VITE_REVERB_HOST=ws.puffmemate.maktechlaravel.cloud
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
VITE_FIREBASE_API_KEY=
VITE_FIREBASE_AUTH_DOMAIN=
VITE_FIREBASE_PROJECT_ID=
VITE_FIREBASE_STORAGE_BUCKET=
VITE_FIREBASE_MESSAGING_SENDER_ID=
VITE_FIREBASE_APP_ID=
VITE_FIREBASE_MEASUREMENT_ID=
VITE_FIREBASE_VAPID_KEY=
```

Firebase Admin credentials and SW toast behavior are **runtime** Coolify variables (not build args). Mount or ship `FIREBASE_CREDENTIALS` JSON at the path you set; defaults avoid duplicate OS toasts:

```dotenv
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
FIREBASE_PROJECT_ID=
FCM_SW_SHOW_NOTIFICATION=false
```

## Reverb integration (live)

| Role   | URL                                        |
| ------ | ------------------------------------------ |
| App    | https://puffmemate.maktechlaravel.cloud    |
| Reverb | https://ws.puffmemate.maktechlaravel.cloud |

**App Coolify runtime** (publisher — do not install `laravel/reverb` here):

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<shared>
REVERB_APP_KEY=<shared>
REVERB_APP_SECRET=<shared>
REVERB_HOST=ws.puffmemate.maktechlaravel.cloud
REVERB_PORT=443
REVERB_SCHEME=https
APP_URL=https://puffmemate.maktechlaravel.cloud
FRONTEND_URL=https://puffmemate.maktechlaravel.cloud
CORS_ALLOWED_ORIGINS=https://puffmemate.maktechlaravel.cloud
```

**Reverb Coolify app** (separate repo/image from `jjvan_reverb_server`, use `.env.puffmemate.example`):

```dotenv
APP_URL=https://ws.puffmemate.maktechlaravel.cloud
REVERB_APP_ID=<shared>
REVERB_APP_KEY=<shared>
REVERB_APP_SECRET=<shared>
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_HOST=ws.puffmemate.maktechlaravel.cloud
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_ALLOWED_ORIGINS=puffmemate.maktechlaravel.cloud,localhost,127.0.0.1,*.maktechlaravel.cloud
```

**Origin format rules:**

- HTTP CORS (`config/cors.php`): full URLs (`https://puffmemate.maktechlaravel.cloud`)
- Reverb `REVERB_ALLOWED_ORIGINS`: bare hostnames only (`puffmemate.maktechlaravel.cloud`)

**Echo transports:** ship `enabledTransports: ['ws', 'wss']` with `forceTLS: true`. Enabling only `wss` breaks pusher-js under TLS.

Do not add a Reverb Supervisor program or local Nginx `/app` and `/apps` proxy in this app image. Traefik on the Reverb Coolify app must proxy WebSocket Upgrade to container port **8080**.

Verify after deploy: `https://puffmemate.maktechlaravel.cloud/platform/realtime` → Connected, public ping, private test. If status is Failed after Reverb was down, use Reconnect or hard-refresh.

## External Services

PostgreSQL is a separate Coolify database. The image includes both `pdo_pgsql` and `pdo_mysql`, keeping it compatible with external PostgreSQL and MySQL/MariaDB services without installing either database server. pgvector, if introduced, must be enabled in PostgreSQL rather than installed in PHP. There are currently no vector columns or pgvector dependencies.

Redis is a separate Coolify Redis resource. The image includes phpredis. It supports queues, cache, sessions, distributed locks, and rate limiting. Never flush the shared Redis instance during deployment.

Meilisearch is a separate Coolify resource (official Meilisearch image). The app uses Laravel Scout (`SCOUT_DRIVER=meilisearch`) with `meilisearch/meilisearch-php`. Configure `MEILISEARCH_HOST` (prefer the private Coolify hostname from the app network when available; public HTTPS is acceptable if the key is server-only) and `MEILISEARCH_KEY`. Never expose the key through Vite. Scout indexing jobs use the Redis `scout` queue (`QUEUE_NAMES=default,scout`). Searchable models today: `User`, `Branch`.

Reverb is a separate Coolify Application at `wss://ws.puffmemate.maktechlaravel.cloud` (see above). Port `8080` is not used by this application container.

Supervisor retains fully commented Horizon and Reverb reference programs. They are disabled and do not alter the running process set. Do not enable Horizon until the package is explicitly installed and configured; if it consumes the same queues, disable the standard `laravel-queue-worker` program to prevent duplicate consumers. Do not enable the Reverb template under the current separate-application architecture.

## Database Migrations

Container startup never runs migrations. Run central migrations once as an explicit release operation from one application container:

```sh
php artisan migrate --force --isolated
```

Laravel 13 supports `--isolated`; all deployment instances must share the configured Redis cache so its migration lock is effective. Check migration SQL and take a recoverable database backup before destructive changes. Use expand/contract releases: add backward-compatible structures, deploy compatible code, backfill, then remove old structures in a later release.

The current tenancy model uses one shared database and nullable `branch_id` columns, with Spatie Permission teams scoped by branch. There are no schema-per-tenant, database-per-tenant, or tenant migration paths, so only the central migration command exists today.

## Scheduler and Replicas

Supervisor runs `php artisan schedule:work --no-interaction`. No scheduled tasks are currently defined, so there are no tasks requiring `onOneServer()` or `withoutOverlapping()` today.

Scaling application replicas from 1 to 2 to 4 multiplies Nginx, PHP-FPM, queue workers, and scheduler processes together. Before adding a global scheduled task, use `onOneServer()` with shared Redis locking. Add `withoutOverlapping()` only when overlapping runs would be unsafe. A task intentionally designed per instance should state that constraint in code and operations documentation.

Each replica can open up to eight concurrent PHP-FPM database connections, plus two queue-worker connections and occasional scheduler/CLI connections. The initial practical ceiling is approximately 11 connections per replica before application-specific persistent or nested connections. Recalculate against PostgreSQL `max_connections` before scaling. PgBouncer is a future option only if measured connection pressure warrants it.

Calculate `pm.max_children` from container RAM: subtract measured Nginx, Supervisor, queue-worker, scheduler, and operating-system baseline memory; reserve a safety margin; divide the remainder by measured average PHP-FPM worker RSS. The initial value is eight and must be load-tested before increasing.

## Tenancy and Storage

Current tenant isolation is request middleware plus `branch_id` query scoping and Spatie's branch team context. There are no application queue jobs, tenant caches, search indexes, tenant broadcasts, or tenant-owned file flows yet. Before adding them:

- Serialize an authoritative tenant identifier into jobs, restore it before handling, and clear tenant state after every job.
- Prefix tenant cache keys and invalidate only that tenant's keys.
- Enforce tenant search filters server-side or use isolated indexes/tokens.
- Authorize private/presence channels against the authenticated tenant.
- Prefix object keys by tenant and never trust a client-supplied tenant ID.

The configured local/public/backups disks use container storage and are ephemeral unless Coolify mounts persistent storage. Spatie backup zips and uploads under `storage/app/private` are lost on redeploy without a volume. Before production uploads or relying on backups, mount a deliberate Coolify volume for `storage/app/private` (or approve and install an object-storage adapter for S3/R2/MinIO). Do not rely on the image layer for permanent files.

Redis sessions support multiple replicas without sticky sessions. Use secure cookies in production (`SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`) and set `SESSION_DOMAIN` only when cross-subdomain login is intentionally required.

## Deploy and Roll Back

1. Provision private PostgreSQL and Redis resources, plus Meilisearch and Reverb only when those application features exist.
2. Configure runtime secrets and public build variables in Coolify.
3. Build the root Dockerfile and review image-build logs.
4. Run `php artisan migrate --force --isolated` as the explicit release command.
5. Deploy the image, confirm all four Supervisor programs are running, and verify `/up` returns `200`.
6. Smoke-test authentication and each portal using the production domain.

For rollback, retain the previous immutable image and deploy it without changing `APP_KEY`. Application rollback does not reverse migrations automatically. Roll back code only when the database remains backward-compatible; otherwise use a reviewed forward-fix or a separately tested migration rollback. Restore user files from the configured persistent store, not from a replaced container.

## Production Checklist

- Restrict PostgreSQL, Redis, Meilisearch, and PHP-FPM to private networking.
- Route public HTTP only through Coolify/Traefik to container port `80`.
- Confirm Coolify proxy addresses fall within the private ranges trusted in `bootstrap/app.php` and `docker/nginx.conf`; narrow those ranges when stable addresses are known.
- Full-document Inertia pages (typed `/login`, or 302 from a protected route) must not 502. `docker/nginx.conf` raises FastCGI buffers so Vite `Link: modulepreload` headers fit. Inertia client visits skip Blade/`@vite` and would still work even with the old buffers. After deploy, hard-refresh `https://puffmemate.maktechlaravel.cloud/login` (not the homepage Log in link) to confirm.
- Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, and a production `APP_URL`.
- Confirm no `.env`, credentials, `node_modules`, Git metadata, or build toolchain exists in the final image.
- Monitor HTTP latency/errors, queue depth/latency/failures, worker RSS, database connections, Redis memory, and scheduler failures.

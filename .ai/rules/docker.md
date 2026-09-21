---
paths:
  - 'docker/**'
  - docker/nginx.conf
  - 'docker/**, docker/nginx.conf'
---

# Docker

## Keep external services outside main container

Supervisor manages Nginx, PHP-FPM, Laravel Horizon, `pulse:check`, and the scheduler. Do not run `queue:work` alongside Horizon on the same Redis queues. On deploy, run `php artisan horizon:terminate` and `php artisan pulse:restart` so workers reload cleanly. Reverb has a separate repository/application; PostgreSQL, Redis, and Meilisearch are external Coolify resources. Startup must not generate APP_KEY, rewrite .env, migrate, seed, or flush shared cache.

## Nginx FastCGI buffers for Vite

Full-document Inertia pages emit Vite modulepreload Link headers via AddLinkHeadersForPreloadedAssets. Default nginx FastCGI buffers (4k/8k) then 502. Keep fastcgi_buffer_size 32k / fastcgi_buffers 16 16k on location = /index.php. Inertia XHR visits skip Blade so they do not hit this.

## Nginx PWA manifest/SW and geolocation
Permissions-Policy geolocation=(self) so driver GPS works in the installed PWA. Exact locations for /manifest.webmanifest and /firebase-messaging-sw.js rewrite to index.php. Do not long-cache service-worker.js. gzip application/manifest+json.

---
paths:
  - 'docker/**'
  - docker/nginx.conf
---

# Docker

## Keep external services outside main container
Supervisor manages exactly Nginx, PHP-FPM, queue workers, and scheduler. Reverb has a separate repository/application; PostgreSQL, Redis, and Meilisearch are external Coolify resources. Startup must not generate APP_KEY, rewrite .env, migrate, seed, or flush shared cache.

## Nginx FastCGI buffers for Vite
Full-document Inertia pages emit Vite modulepreload Link headers via AddLinkHeadersForPreloadedAssets. Default nginx FastCGI buffers (4k/8k) then 502. Keep fastcgi_buffer_size 32k / fastcgi_buffers 16 16k on location = /index.php. Inertia XHR visits skip Blade so they do not hit this.

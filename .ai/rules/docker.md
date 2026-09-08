---
paths:
  - 'docker/**'
---

# Docker

## Keep external services outside main container
Supervisor manages exactly Nginx, PHP-FPM, queue workers, and scheduler. Reverb has a separate repository/application; PostgreSQL, Redis, and Meilisearch are external Coolify resources. Startup must not generate APP_KEY, rewrite .env, migrate, seed, or flush shared cache.

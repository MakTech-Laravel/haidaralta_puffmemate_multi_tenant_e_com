---
paths:
  - Dockerfile
---

# Dockerfile

## Keep main production processes together
The main Coolify application image runs Nginx, PHP-FPM, Laravel queue workers, and the scheduler under Supervisor. Reverb, PostgreSQL, Redis server, and Meilisearch remain separate resources and must never be started or proxied locally by this image.

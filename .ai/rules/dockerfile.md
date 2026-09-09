---
paths:
  - Dockerfile
---

# Dockerfile

## Keep main production processes together
The main Coolify application image runs Nginx, PHP-FPM, Laravel queue workers, and the scheduler under Supervisor. Reverb, PostgreSQL, Redis server, and Meilisearch remain separate resources and must never be started or proxied locally by this image.

## FCM_SW_SHOW_NOTIFICATION runtime
FCM_SW_SHOW_NOTIFICATION is runtime (production ENV default false / Coolify override). Do not add it as a Vite build ARG; VITE_FIREBASE_* stay build-time only.

---
paths:
  - app/Support/OpsDashboardAccess.php
  - config/cache.php
---

# Support

## Horizon and Pulse are platform-only via OpsDashboardAccess
Horizon (/horizon) and Pulse (/pulse) use ensure.guard:platform plus OpsDashboardAccess (isPlatform + platform super-admin OR view-horizon/view-pulse). Never rely on hasRole(super-admin) alone — branch super-admins must be denied. Keep official package UIs; the Inertia hub at /platform/observability only links out with shadcn cards. Horizon is the sole Redis queue consumer — do not run queue:work alongside it.

## Pulse needs cache serializable_classes allow-list
Laravel 13 defaults `cache.serializable_classes` to `false`. Pulse `RemembersQueries` caches `Collection` / `stdClass` / `CarbonImmutable` — without allow-listing those three in `config/cache.php`, `/pulse` fatals on Livewire poll with incomplete object errors. Keep the list minimal; clear cache after changing it.

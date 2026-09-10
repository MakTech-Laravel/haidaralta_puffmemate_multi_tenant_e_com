---
paths:
  - app/Support/OpsDashboardAccess.php
---

# Support

## Horizon and Pulse are platform-only via OpsDashboardAccess
Horizon (/horizon) and Pulse (/pulse) use ensure.guard:platform plus OpsDashboardAccess (isPlatform + platform super-admin OR view-horizon/view-pulse). Never rely on hasRole(super-admin) alone — branch super-admins must be denied. Keep official package UIs; the Inertia hub at /platform/observability only links out with shadcn cards. Horizon is the sole Redis queue consumer — do not run queue:work alongside it.

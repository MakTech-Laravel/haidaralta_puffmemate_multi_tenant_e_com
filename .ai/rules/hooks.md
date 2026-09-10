---
paths:
  - resources/js/hooks/use-database-backup-realtime.ts
---

# Hooks

## Hard-reload after DB restore
On restore completed/failed, hard-reload via window.location. Do not Inertia-reload while restore is pending/running (DROP SCHEMA race). Stop usePoll during active restore.

---
paths:
  - 'app/Events/**'
---

# Events

## Backup history Reverb live status
Broadcast DatabaseBackupHistoryUpdated (ShouldBroadcastNow) on private platform.database-backups for manage-database-backups; jobs emit after status changes; backups page listens — partial reload for non-restore; restore completed/failed hard-reloads the browser.

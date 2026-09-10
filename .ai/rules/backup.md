---
paths:
  - 'app/Services/Backup/**'
  - 'config/backup.php'
---

# Backup

## Spatie Backup is the backup source of truth
Use BackupService (not custom pg_dump SQL UI) for run/list/clean/download. Disk: backups → storage/app/private/spatie-backups. Restore is DB-only from zip via RestoreDatabaseFromBackupJob (psql); never extract app files onto Coolify. Persist storage/app/private with a Coolify volume or backups are lost on redeploy. POSTGRES_BINARY_PATH for pg_dump; BACKUP_MAIL_TO empty means no mail channels.

## Cleanup vs individual delete
Clean old backups = Spatie `backup:clean` using env retention. Operator-facing period: `BACKUP_KEEP_ALL_DAYS` (default 7). Ladder keys (`BACKUP_KEEP_DAILY_DAYS` etc.) default to 0 so zips older than the keep-all window are hard-deleted — no lifetime thinned archives. `BACKUP_MAX_STORAGE_MB` trims oldest after age cleanup (empty = unlimited). Individual zip delete is BackupService::delete + platform UI confirm.

## Env backup retention keys
Retention via BACKUP_KEEP_ALL_DAYS; ladder env keys default 0 for hard age delete; BACKUP_MAX_STORAGE_MB empty = unlimited.

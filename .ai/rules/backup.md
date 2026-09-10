---
paths:
  - 'app/Services/Backup/**'
---

# Backup

## Spatie Backup is the backup source of truth
Use BackupService (not custom pg_dump SQL UI) for run/list/clean/download. Disk: backups → storage/app/private/spatie-backups. Restore is DB-only from zip via RestoreDatabaseFromBackupJob (psql); never extract app files onto Coolify. Persist storage/app/private with a Coolify volume or backups are lost on redeploy. POSTGRES_BINARY_PATH for pg_dump; BACKUP_MAIL_TO empty means no mail channels.

## Cleanup vs individual delete
Clean old backups = Spatie retention only (keep_all_backups_for_days etc); never deletes today's zips. Individual zip delete is BackupService::delete + platform UI confirm.

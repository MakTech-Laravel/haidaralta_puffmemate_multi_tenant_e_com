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

## History message size
Failed Spatie runs can produce multi-KB console/stack text. Always store and broadcast via BackupHistoryMessage::summarize (≤500 chars) so Reverb/Pusher does not hit “Payload too large”.

## Restore SQL selection and wipe safety
Spatie zips include storage/app plus db-dumps/*.sql. Always resolve SQL via BackupZipSqlLocator (prefer db-dumps/, then largest .sql). Full Postgres restores DROP SCHEMA public — refuse the wipe unless the dump CREATE TABLE users, and fail the job if users is still missing afterward.

## Restore UI race
While restore is pending/running, stop Inertia polling and skip Echo partial reloads (Auth hits missing users). On restore completed/failed, hard-reload the browser (`window.location`). EnsurePortalGuard catches QueryException during the wipe and redirects to login instead of Ignition 500.

## Restore must prefer db-dumps SQL
Spatie zips include storage/app; never pick the first .sql. Use BackupZipSqlLocator (db-dumps/ then largest). Before DROP SCHEMA public, require CREATE TABLE users; after restore, fail if users is missing.

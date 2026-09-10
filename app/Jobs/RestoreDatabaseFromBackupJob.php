<?php

namespace App\Jobs;

use App\Enums\DatabaseBackupStatus;
use App\Models\DatabaseBackupHistory;
use App\Services\Backup\BackupZipSqlLocator;
use App\Services\Backup\BroadcastsBackupHistory;
use App\Services\Backup\DatabaseRestoreRunner;
use FilesystemIterator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Throwable;
use ZipArchive;

class RestoreDatabaseFromBackupJob implements ShouldQueue
{
    use BroadcastsBackupHistory, Queueable;

    public int $timeout = 600;

    public function __construct(
        public int $historyId,
        public string $zipPath,
    ) {}

    public function handle(DatabaseRestoreRunner $runner, BackupZipSqlLocator $sqlLocator): void
    {
        $history = DatabaseBackupHistory::query()->findOrFail($this->historyId);
        $tempDir = storage_path('app/backup-temp/restore-'.Str::uuid());

        $this->markHistory($history, DatabaseBackupStatus::Running, 'Restore running');

        try {
            if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
                throw new RuntimeException('Unable to create restore temp directory.');
            }

            $localZipPath = $tempDir.DIRECTORY_SEPARATOR.basename($this->zipPath);
            $zipContents = Storage::disk('backups')->get($this->zipPath);

            if ($zipContents === null) {
                throw new RuntimeException('Backup zip is not available.');
            }

            if (file_put_contents($localZipPath, $zipContents) === false) {
                throw new RuntimeException('Unable to copy backup zip to temp directory.');
            }

            $extractDir = $tempDir.DIRECTORY_SEPARATOR.'extract';

            if (! is_dir($extractDir) && ! mkdir($extractDir, 0755, true) && ! is_dir($extractDir)) {
                throw new RuntimeException('Unable to create restore extract directory.');
            }

            $zip = new ZipArchive;

            if ($zip->open($localZipPath) !== true) {
                throw new RuntimeException('Unable to open backup zip.');
            }

            if (! $zip->extractTo($extractDir)) {
                $zip->close();

                throw new RuntimeException('Unable to extract backup zip.');
            }

            $zip->close();

            $sqlPath = $sqlLocator->find($extractDir);

            $connection = (string) config('database.default');
            $runner->restoreFromSqlFile($connection, $sqlPath);

            $this->markHistory(
                $history,
                DatabaseBackupStatus::Completed,
                'Restore completed from '.basename($this->zipPath),
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->markHistory($history, DatabaseBackupStatus::Failed, $exception->getMessage());

            $runner->ensureExcludedTablesExist((string) config('database.default'));

            throw $exception;
        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($directory);
    }
}

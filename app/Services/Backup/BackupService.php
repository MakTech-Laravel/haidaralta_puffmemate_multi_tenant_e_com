<?php

namespace App\Services\Backup;

use App\Enums\DatabaseBackupStatus;
use App\Enums\DatabaseBackupType;
use App\Events\DatabaseBackupHistoryUpdated;
use App\Jobs\CleanBackupJob;
use App\Jobs\RestoreDatabaseFromBackupJob;
use App\Jobs\RunBackupJob;
use App\Models\DatabaseBackupHistory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupService
{
    public const DISK = 'backups';

    /**
     * Zip inventory from the Spatie backups disk.
     *
     * @return list<array{path: string, filename: string, size_bytes: int, date: string|null}>
     */
    public function list(): array
    {
        $destination = BackupDestination::create(
            self::DISK,
            (string) config('backup.backup.name'),
        );

        return $destination->backups()
            ->map(function (Backup $backup): array {
                return [
                    'path' => $backup->path(),
                    'filename' => basename($backup->path()),
                    'size_bytes' => (int) $backup->sizeInBytes(),
                    'date' => $backup->date()->toIso8601String(),
                ];
            })
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    public function run(?User $user): DatabaseBackupHistory
    {
        $history = DatabaseBackupHistory::query()->create([
            'type' => DatabaseBackupType::Run,
            'status' => DatabaseBackupStatus::Pending,
            'disk' => self::DISK,
            'triggered_by' => $user?->id,
            'connection' => (string) config('database.default'),
            'message' => 'Backup queued',
        ]);

        RunBackupJob::dispatch($history->id);
        DatabaseBackupHistoryUpdated::dispatch($history);

        return $history;
    }

    public function clean(?User $user): DatabaseBackupHistory
    {
        $history = DatabaseBackupHistory::query()->create([
            'type' => DatabaseBackupType::Cleanup,
            'status' => DatabaseBackupStatus::Pending,
            'disk' => self::DISK,
            'triggered_by' => $user?->id,
            'connection' => (string) config('database.default'),
            'message' => 'Cleanup queued',
        ]);

        CleanBackupJob::dispatch($history->id);
        DatabaseBackupHistoryUpdated::dispatch($history);

        return $history;
    }

    public function download(string $path): StreamedResponse
    {
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..') || ! Storage::disk(self::DISK)->exists($path)) {
            throw new RuntimeException('Backup zip is not available.');
        }

        return Storage::disk(self::DISK)->download(
            $path,
            basename($path),
            [
                'Content-Type' => 'application/zip',
            ],
        );
    }

    public function restoreDatabase(string $zipPath, User $user): DatabaseBackupHistory
    {
        $zipPath = ltrim($zipPath, '/');

        if ($zipPath === '' || str_contains($zipPath, '..') || ! Storage::disk(self::DISK)->exists($zipPath)) {
            throw new RuntimeException('Backup zip is not available.');
        }

        $history = DatabaseBackupHistory::query()->create([
            'type' => DatabaseBackupType::Restore,
            'status' => DatabaseBackupStatus::Pending,
            'disk' => self::DISK,
            'path' => $zipPath,
            'filename' => basename($zipPath),
            'size_bytes' => Storage::disk(self::DISK)->size($zipPath),
            'triggered_by' => $user->id,
            'connection' => (string) config('database.default'),
            'message' => 'Restore queued',
        ]);

        RestoreDatabaseFromBackupJob::dispatch($history->id, $zipPath);
        DatabaseBackupHistoryUpdated::dispatch($history);

        return $history;
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateHistory(int $perPage = 20): LengthAwarePaginator
    {
        return DatabaseBackupHistory::query()
            ->with('triggeredBy')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (DatabaseBackupHistory $history): array => [
                'id' => $history->id,
                'type' => $history->type->value,
                'status' => $history->status->value,
                'filename' => $history->filename,
                'path' => $history->path,
                'size_bytes' => $history->size_bytes,
                'connection' => $history->connection,
                'message' => $history->message,
                'triggered_by' => $history->triggeredBy !== null
                    ? [
                        'id' => $history->triggeredBy->id,
                        'name' => $history->triggeredBy->name,
                        'email' => $history->triggeredBy->email,
                    ]
                    : null,
                'created_at' => $history->created_at?->toIso8601String(),
            ]);
    }
}

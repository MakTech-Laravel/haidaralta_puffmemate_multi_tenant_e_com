<?php

namespace App\Jobs;

use App\Enums\DatabaseBackupStatus;
use App\Models\DatabaseBackupHistory;
use App\Services\Backup\BroadcastsBackupHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class CleanBackupJob implements ShouldQueue
{
    use BroadcastsBackupHistory, Queueable;

    public int $timeout = 600;

    public function __construct(public int $historyId) {}

    public function handle(): void
    {
        $history = DatabaseBackupHistory::query()->findOrFail($this->historyId);

        $this->markHistory($history, DatabaseBackupStatus::Running, 'Cleanup running');

        try {
            $exitCode = Artisan::call('backup:clean');

            if ($exitCode !== 0) {
                throw new \RuntimeException(trim(Artisan::output()) ?: 'backup:clean failed');
            }

            $this->markHistory($history, DatabaseBackupStatus::Completed, 'Cleanup completed');
        } catch (Throwable $exception) {
            report($exception);

            $this->markHistory($history, DatabaseBackupStatus::Failed, $exception->getMessage());

            throw $exception;
        }
    }
}

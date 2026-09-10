<?php

namespace App\Jobs;

use App\Enums\DatabaseBackupStatus;
use App\Models\DatabaseBackupHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class CleanBackupJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $historyId) {}

    public function handle(): void
    {
        $history = DatabaseBackupHistory::query()->findOrFail($this->historyId);

        $history->update([
            'status' => DatabaseBackupStatus::Running,
            'message' => 'Cleanup running',
        ]);

        try {
            $exitCode = Artisan::call('backup:clean');

            if ($exitCode !== 0) {
                throw new \RuntimeException(trim(Artisan::output()) ?: 'backup:clean failed');
            }

            $history->update([
                'status' => DatabaseBackupStatus::Completed,
                'message' => 'Cleanup completed',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $history->update([
                'status' => DatabaseBackupStatus::Failed,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}

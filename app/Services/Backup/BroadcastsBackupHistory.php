<?php

namespace App\Services\Backup;

use App\Enums\DatabaseBackupStatus;
use App\Events\DatabaseBackupHistoryUpdated;
use App\Models\DatabaseBackupHistory;

trait BroadcastsBackupHistory
{
    protected function markHistory(
        DatabaseBackupHistory $history,
        DatabaseBackupStatus $status,
        string $message,
    ): void {
        $history->update([
            'status' => $status,
            'message' => $message,
        ]);

        DatabaseBackupHistoryUpdated::dispatch($history->fresh() ?? $history);
    }
}

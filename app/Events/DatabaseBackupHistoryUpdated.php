<?php

namespace App\Events;

use App\Models\DatabaseBackupHistory;
use App\Services\Backup\BackupHistoryMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies platform operators when a backup/cleanup/restore history row changes status.
 */
class DatabaseBackupHistoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public const CHANNEL = 'platform.database-backups';

    public const BROADCAST_AS = 'database-backup.history-updated';

    public function __construct(public DatabaseBackupHistory $history) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(self::CHANNEL),
        ];
    }

    public function broadcastAs(): string
    {
        return self::BROADCAST_AS;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->history->id,
            'type' => $this->history->type->value,
            'status' => $this->history->status->value,
            'filename' => $this->history->filename,
            'message' => BackupHistoryMessage::summarize((string) $this->history->message),
        ];
    }
}

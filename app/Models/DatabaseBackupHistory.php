<?php

namespace App\Models;

use App\Enums\DatabaseBackupStatus;
use App\Enums\DatabaseBackupType;
use App\Models\Concerns\AuditsChanges;
use Database\Factories\DatabaseBackupHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseBackupHistory extends Model
{
    /** @use HasFactory<DatabaseBackupHistoryFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'status',
        'disk',
        'path',
        'filename',
        'size_bytes',
        'triggered_by',
        'source_history_id',
        'connection',
        'message',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * @return BelongsTo<DatabaseBackupHistory, $this>
     */
    public function sourceHistory(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_history_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DatabaseBackupType::class,
            'status' => DatabaseBackupStatus::class,
            'size_bytes' => 'integer',
        ];
    }

    public function hasDownloadableFile(): bool
    {
        return $this->status === DatabaseBackupStatus::Completed
            && filled($this->path)
            && filled($this->filename);
    }
}

<?php

namespace Database\Factories;

use App\Enums\DatabaseBackupStatus;
use App\Enums\DatabaseBackupType;
use App\Models\DatabaseBackupHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DatabaseBackupHistory>
 */
class DatabaseBackupHistoryFactory extends Factory
{
    protected $model = DatabaseBackupHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = 'backup-'.fake()->unique()->numerify('########').'.zip';

        return [
            'type' => DatabaseBackupType::Run,
            'status' => DatabaseBackupStatus::Completed,
            'disk' => 'backups',
            'path' => 'app/'.$filename,
            'filename' => $filename,
            'size_bytes' => fake()->numberBetween(1_000, 500_000),
            'triggered_by' => User::factory(),
            'source_history_id' => null,
            'connection' => 'sqlite',
            'message' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => DatabaseBackupStatus::Failed,
            'message' => 'Backup failed',
        ]);
    }

    public function restore(): static
    {
        return $this->state(fn (): array => [
            'type' => DatabaseBackupType::Restore,
        ]);
    }
}

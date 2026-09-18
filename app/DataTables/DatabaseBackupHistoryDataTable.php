<?php

namespace App\DataTables;

use App\Models\DatabaseBackupHistory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends AbstractDataTable<DatabaseBackupHistory>
 */
class DatabaseBackupHistoryDataTable extends AbstractDataTable
{
    /**
     * @return class-string<DatabaseBackupHistory>
     */
    protected function modelClass(): string
    {
        return DatabaseBackupHistory::class;
    }

    /**
     * @return Builder<DatabaseBackupHistory>
     */
    protected function baseQuery(): Builder
    {
        return DatabaseBackupHistory::query()->with('triggeredBy:id,name,email');
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('type'),
            AllowedFilter::exact('status'),
            $this->searchFilter(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['filename', 'message', 'connection'];
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return [
            'id',
            'created_at',
            'type',
            'status',
            'size_bytes',
        ];
    }

    protected function defaultSort(): string
    {
        return '-id';
    }

    /**
     * @param  DatabaseBackupHistory  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var DatabaseBackupHistory $model */
        return [
            'id' => $model->id,
            'type' => $model->type->value,
            'status' => $model->status->value,
            'filename' => $model->filename,
            'path' => $model->path,
            'size_bytes' => $model->size_bytes,
            'connection' => $model->connection,
            'message' => $model->message,
            'triggered_by' => $model->triggeredBy !== null
                ? [
                    'id' => $model->triggeredBy->id,
                    'name' => $model->triggeredBy->name,
                    'email' => $model->triggeredBy->email,
                ]
                : null,
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}

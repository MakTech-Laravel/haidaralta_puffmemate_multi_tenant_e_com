<?php

namespace App\DataTables;

use App\Models\Branch;
use App\Models\User;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends AbstractDataTable<Activity>
 */
class ActivityLogDataTable extends AbstractDataTable
{
    protected ActivityLogService $activityLogs;

    public function __construct(
        Request $request,
        protected ?int $branchId = null,
        protected ?int $causerId = null,
    ) {
        parent::__construct($request);

        $this->activityLogs = app(ActivityLogService::class);
    }

    /**
     * @return array<string, mixed>
     */
    public static function forPlatform(Request $request): array
    {
        return (new static($request))->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public static function forBranch(Request $request, Branch $branch): array
    {
        return (new static($request, $branch->id))->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public static function forCauser(Request $request, User $user): array
    {
        return (new static($request, null, $user->id))->toArray();
    }

    /**
     * @return class-string<Activity>
     */
    protected function modelClass(): string
    {
        return Activity::class;
    }

    /**
     * @return Builder<Activity>
     */
    protected function baseQuery(): Builder
    {
        $query = Activity::query()->with(['causer', 'subject']);

        if ($this->branchId !== null) {
            $branchId = $this->branchId;

            $query->where(function (Builder $scope) use ($branchId): void {
                $scope->where('properties->branch_id', $branchId)
                    ->orWhere(function (Builder $subject) use ($branchId): void {
                        $subject->where('subject_type', Branch::class)
                            ->where('subject_id', $branchId);
                    })
                    ->orWhereHasMorph('causer', [User::class], function (Builder $causer) use ($branchId): void {
                        $causer->where('branch_id', $branchId);
                    });
            });
        }

        if ($this->causerId !== null) {
            $query->where('causer_type', User::class)
                ->where('causer_id', $this->causerId);
        }

        return $query;
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('event'),
            AllowedFilter::callback('portal', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->where('properties->portal', (string) $value);
            }),
            $this->searchFilter(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['description'];
    }

    protected function searchFilter(): AllowedFilter
    {
        return AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
            $term = trim((string) $value);

            if ($term === '') {
                return;
            }

            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('description', 'like', '%'.$term.'%')
                    ->orWhere('subject_type', 'like', '%'.$term.'%')
                    ->orWhereHas('causer', function (Builder $causer) use ($term): void {
                        $causer->where('name', 'like', '%'.$term.'%')
                            ->orWhere('email', 'like', '%'.$term.'%');
                    });
            });
        });
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return [
            'id',
            'created_at',
            'event',
        ];
    }

    protected function defaultSort(): string
    {
        return '-id';
    }

    /**
     * @param  Activity  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var Activity $model */
        return $this->activityLogs->toListRow($model);
    }
}

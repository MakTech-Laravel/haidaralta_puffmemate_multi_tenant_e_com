<?php

namespace App\Services\ActivityLog;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    /**
     * Paginated activity rows for the platform admin list (no subject filter).
     *
     * @return LengthAwarePaginator<int, array{
     *     id: int,
     *     description: string,
     *     event: string|null,
     *     log_name: string|null,
     *     causer: array{id: int, name: string, email: string|null}|null,
     *     subject: array{type: string, id: int|string|null, label: string|null}|null,
     *     created_at: string|null
     * }>
     */
    public function paginateForPlatform(
        string $search = '',
        string $event = '',
        string $portal = '',
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->paginate(
            $this->baseQuery($search, $event, $portal),
            $perPage,
        );
    }

    /**
     * @deprecated Use paginateForPlatform()
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForIndex(string $search = '', string $event = '', int $perPage = 20): LengthAwarePaginator
    {
        return $this->paginateForPlatform($search, $event, '', $perPage);
    }

    /**
     * Branch-scoped activities: properties.branch_id, Branch subject, or causer.branch_id.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForBranch(
        int $branchId,
        string $search = '',
        string $event = '',
        string $portal = '',
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = $this->baseQuery($search, $event, $portal)
            ->where(function (Builder $scope) use ($branchId): void {
                $scope->where('properties->branch_id', $branchId)
                    ->orWhere(function (Builder $subject) use ($branchId): void {
                        $subject->where('subject_type', Branch::class)
                            ->where('subject_id', $branchId);
                    })
                    ->orWhereHasMorph('causer', [User::class], function (Builder $causer) use ($branchId): void {
                        $causer->where('branch_id', $branchId);
                    });
            });

        return $this->paginate($query, $perPage);
    }

    /**
     * Activities caused by a specific user (driver/customer self view).
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateForCauser(
        int $userId,
        string $search = '',
        string $event = '',
        string $portal = '',
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = $this->baseQuery($search, $event, $portal)
            ->where('causer_type', User::class)
            ->where('causer_id', $userId);

        return $this->paginate($query, $perPage);
    }

    /**
     * Detail payload for a single activity log entry.
     *
     * @return array{
     *     id: int,
     *     description: string,
     *     event: string|null,
     *     log_name: string|null,
     *     causer: array{id: int, name: string, email: string|null}|null,
     *     subject: array{type: string, id: int|string|null, label: string|null}|null,
     *     attribute_changes: array{attributes: array<string, mixed>, old: array<string, mixed>},
     *     properties: array<string, mixed>,
     *     created_at: string|null
     * }
     */
    public function toDetail(Activity $activity): array
    {
        $activity->loadMissing(['causer', 'subject']);

        $changes = $activity->attribute_changes?->toArray() ?? [];

        return [
            ...$this->toListRow($activity),
            'attribute_changes' => [
                'attributes' => is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [],
                'old' => is_array($changes['old'] ?? null) ? $changes['old'] : [],
            ],
            'properties' => $activity->properties?->toArray() ?? [],
        ];
    }

    public function findForBranch(int $branchId, int $activityId): ?Activity
    {
        return $this->baseQuery()
            ->whereKey($activityId)
            ->where(function (Builder $scope) use ($branchId): void {
                $scope->where('properties->branch_id', $branchId)
                    ->orWhere(function (Builder $subject) use ($branchId): void {
                        $subject->where('subject_type', Branch::class)
                            ->where('subject_id', $branchId);
                    })
                    ->orWhereHasMorph('causer', [User::class], function (Builder $causer) use ($branchId): void {
                        $causer->where('branch_id', $branchId);
                    });
            })
            ->first();
    }

    public function findForCauser(int $userId, int $activityId): ?Activity
    {
        return Activity::query()
            ->whereKey($activityId)
            ->where('causer_type', User::class)
            ->where('causer_id', $userId)
            ->first();
    }

    /**
     * @return array{
     *     id: int,
     *     description: string,
     *     event: string|null,
     *     log_name: string|null,
     *     causer: array{id: int, name: string, email: string|null}|null,
     *     subject: array{type: string, id: int|string|null, label: string|null}|null,
     *     created_at: string|null
     * }
     */
    public function toListRow(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'description' => $activity->description,
            'event' => $activity->event,
            'log_name' => $activity->log_name,
            'causer' => $this->causerPayload($activity->causer),
            'subject' => $this->subjectPayload($activity),
            'created_at' => $activity->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return Builder<Activity>
     */
    private function baseQuery(string $search = '', string $event = '', string $portal = ''): Builder
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhereHas('causer', function (Builder $causer) use ($search): void {
                            $causer->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($event !== '', fn (Builder $query) => $query->forEvent($event))
            ->when($portal !== '', fn (Builder $query) => $query->where('properties->portal', $portal));
    }

    /**
     * @param  Builder<Activity>  $query
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function paginate(Builder $query, int $perPage): LengthAwarePaginator
    {
        return $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Activity $activity): array => $this->toListRow($activity));
    }

    /**
     * @return array{id: int, name: string, email: string|null}|null
     */
    private function causerPayload(?Model $causer): ?array
    {
        if ($causer === null) {
            return null;
        }

        return [
            'id' => (int) $causer->getKey(),
            'name' => (string) ($causer->getAttribute('name') ?? class_basename($causer).' #'.$causer->getKey()),
            'email' => $causer->getAttribute('email') !== null
                ? (string) $causer->getAttribute('email')
                : null,
        ];
    }

    /**
     * @return array{type: string, id: int|string|null, label: string|null}|null
     */
    private function subjectPayload(Activity $activity): ?array
    {
        if ($activity->subject_type === null && $activity->subject_id === null) {
            return null;
        }

        $subject = $activity->subject;

        return [
            'type' => class_basename((string) $activity->subject_type),
            'id' => $activity->subject_id,
            'label' => $subject !== null
                ? (string) ($subject->getAttribute('name')
                    ?? $subject->getAttribute('email')
                    ?? class_basename($subject).' #'.$subject->getKey())
                : null,
        ];
    }
}

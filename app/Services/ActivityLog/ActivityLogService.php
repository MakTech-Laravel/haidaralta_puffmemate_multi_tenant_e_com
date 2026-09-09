<?php

namespace App\Services\ActivityLog;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    /**
     * Paginated activity rows for the platform admin list.
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
    public function paginateForIndex(string $search = '', string $event = '', int $perPage = 20): LengthAwarePaginator
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhere('subject_type', 'like', "%{$search}%")
                        ->orWhereHas('causer', function ($causer) use ($search): void {
                            $causer->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($event !== '', fn ($query) => $query->forEvent($event))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Activity $activity): array => $this->toListRow($activity));
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

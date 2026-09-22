<?php

namespace App\Support;

use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftOwnerType;
use App\Enums\TransferSubjectType;
use App\Enums\TransferType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverRoster;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftDay;
use App\Models\UserTransferHistory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class WorkforcePayload
{
    /**
     * @return array<string, mixed>
     */
    public static function template(ShiftTemplate $template): array
    {
        return [
            'id' => $template->id,
            'owner_type' => $template->owner_type->value,
            'owner_type_label' => $template->owner_type->label(),
            'branch_id' => $template->branch_id,
            'branch_name' => $template->branch?->name,
            'name' => $template->name,
            'start_time' => $template->start_time?->format('H:i'),
            'end_time' => $template->end_time?->format('H:i'),
            'is_active' => (bool) $template->is_active,
            'scheduled_count' => (int) ($template->scheduled_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function assignment(UserShiftAssignment $assignment, ?CarbonInterface $at = null): array
    {
        $assignment->loadMissing(['shiftTemplate', 'branch:id,name,slug', 'user:id,name,user_type,avatar']);
        $days = $assignment->normalizedDaysOfWeek();
        $template = $assignment->shiftTemplate;
        $moment = $at ?? now();

        return [
            'id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'user_name' => $assignment->user?->name,
            'user_type' => $assignment->user?->user_type?->value,
            'user_type_label' => $assignment->user?->user_type?->label(),
            'avatar_url' => MediaUrl::resolve($assignment->user?->avatar, fallback: null),
            'branch_id' => $assignment->branch_id,
            'branch_name' => $assignment->branch?->name,
            'shift_template_id' => $assignment->shift_template_id,
            'shift_template_name' => $template?->name,
            'start_date' => $assignment->start_date?->format('Y-m-d'),
            'end_date' => $assignment->end_date?->format('Y-m-d'),
            'start_time' => $template?->start_time?->format('H:i'),
            'end_time' => $template?->end_time?->format('H:i'),
            'days_of_week' => $days,
            'days_label' => DriverRoster::daysLabel($days),
            'status' => $assignment->status?->value,
            'status_label' => $assignment->status?->label(),
            'covers_now' => $assignment->coversMoment($moment),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function assignmentDays(UserShiftAssignment $assignment): array
    {
        return $assignment->days()
            ->with('shiftTemplate')
            ->orderBy('duty_on')
            ->get()
            ->map(fn (UserShiftDay $day): array => self::day($day))
            ->values()
            ->all();
    }

    /**
     * Other scheduled duty days for this person in the same store and date span.
     *
     * @return list<array<string, mixed>>
     */
    public static function otherDutyDays(User $user, int $branchId, string $startDate, string $endDate, ?int $exceptAssignmentId = null): array
    {
        return UserShiftDay::query()
            ->with('shiftTemplate')
            ->where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->whereDate('duty_on', '>=', $startDate)
            ->whereDate('duty_on', '<=', $endDate)
            ->onDuty()
            ->when(
                $exceptAssignmentId !== null,
                fn ($query) => $query->where('user_shift_assignment_id', '!=', $exceptAssignmentId),
            )
            ->whereHas('assignment', fn ($query) => $query->where('status', ShiftAssignmentStatus::Scheduled))
            ->orderBy('duty_on')
            ->get()
            ->map(fn (UserShiftDay $day): array => self::day($day))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function day(UserShiftDay $day): array
    {
        $day->loadMissing('shiftTemplate');

        return [
            'id' => $day->id,
            'assignment_id' => $day->user_shift_assignment_id,
            'user_id' => $day->user_id,
            'branch_id' => $day->branch_id,
            'date' => $day->duty_on->format('Y-m-d'),
            'kind' => $day->kind->value,
            'kind_label' => $day->kind->label(),
            'shift_template_id' => $day->shift_template_id,
            'shift_template_name' => $day->shiftTemplate?->name,
            'start_time' => $day->shiftTemplate?->start_time?->format('H:i'),
            'end_time' => $day->shiftTemplate?->end_time?->format('H:i'),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function currentDuty(User $user, ?CarbonInterface $at = null): ?array
    {
        $moment = $at ?? now();

        $day = UserShiftDay::query()
            ->with(['shiftTemplate', 'assignment.branch:id,name,slug', 'user:id,name,user_type,avatar'])
            ->where('user_id', $user->id)
            ->onDuty()
            ->whereDate('duty_on', $moment->toDateString())
            ->whereHas('assignment', fn ($query) => $query->where('status', ShiftAssignmentStatus::Scheduled))
            ->get()
            ->first(fn (UserShiftDay $row): bool => $row->coversMoment($moment));

        if ($day === null || $day->assignment === null) {
            return null;
        }

        $payload = self::assignment($day->assignment, $moment);
        $payload['shift_template_id'] = $day->shift_template_id;
        $payload['shift_template_name'] = $day->shiftTemplate?->name;
        $payload['start_time'] = $day->shiftTemplate?->start_time?->format('H:i');
        $payload['end_time'] = $day->shiftTemplate?->end_time?->format('H:i');
        $payload['covers_now'] = true;

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function transfer(UserTransferHistory $history): array
    {
        $history->loadMissing(['user:id,name,user_type', 'fromBranch:id,name,slug', 'toBranch:id,name,slug', 'transferredBy:id,name']);

        return [
            'id' => $history->id,
            'user_id' => $history->user_id,
            'user_name' => $history->user?->name,
            'subject_type' => $history->subject_type->value,
            'subject_type_label' => $history->subject_type->label(),
            'transfer_type' => $history->transfer_type->value,
            'transfer_type_label' => $history->transfer_type->label(),
            'from_branch_id' => $history->from_branch_id,
            'from_branch_name' => $history->fromBranch?->name,
            'to_branch_id' => $history->to_branch_id,
            'to_branch_name' => $history->toBranch?->name,
            'from_role' => $history->from_role,
            'to_role' => $history->to_role,
            'effective_date' => $history->effective_date?->format('Y-m-d'),
            'reason' => $history->reason,
            'transferred_by_name' => $history->transferredBy?->name,
            'created_at' => $history->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function templatesOwnedBy(ShiftOwnerType $owner, bool $activeOnly = false, ?int $branchId = null): array
    {
        if ($owner === ShiftOwnerType::Branch && $branchId === null) {
            return [];
        }

        return ShiftTemplate::query()
            ->with('branch:id,name')
            ->withCount([
                'assignments as scheduled_count' => fn ($query) => $query->where('status', ShiftAssignmentStatus::Scheduled),
            ])
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->when(
                $owner === ShiftOwnerType::Platform,
                fn ($query) => $query->platformOwned(),
                fn ($query) => $query->branchOwned(),
            )
            ->when(
                $owner === ShiftOwnerType::Branch && $branchId !== null,
                fn ($query) => $query->where('branch_id', $branchId),
            )
            ->orderBy('name')
            ->get()
            ->map(fn (ShiftTemplate $template): array => self::template($template))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function scheduledPeople(ShiftOwnerType $owner, ?int $branchId = null): array
    {
        if ($owner === ShiftOwnerType::Branch && $branchId === null) {
            return [];
        }

        return UserShiftAssignment::query()
            ->with(['shiftTemplate', 'branch:id,name,slug', 'user:id,name,user_type,avatar'])
            ->where('status', ShiftAssignmentStatus::Scheduled)
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->whereHas('shiftTemplate', function ($templates) use ($owner): void {
                if ($owner === ShiftOwnerType::Platform) {
                    $templates->platformOwned();
                } else {
                    $templates->branchOwned();
                }
            })
            ->orderByDesc('start_date')
            ->orderBy('id')
            ->get()
            ->map(fn (UserShiftAssignment $assignment): array => self::assignment($assignment))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function scheduledPeopleAtBranch(int $branchId): array
    {
        return UserShiftAssignment::query()
            ->with(['shiftTemplate', 'branch:id,name,slug', 'user:id,name,user_type,avatar'])
            ->where('status', ShiftAssignmentStatus::Scheduled)
            ->where('branch_id', $branchId)
            ->orderByDesc('start_date')
            ->orderBy('id')
            ->get()
            ->map(fn (UserShiftAssignment $assignment): array => self::assignment($assignment))
            ->values()
            ->all();
    }

    /**
     * Named shifts with nobody scheduled today.
     *
     * @param  list<array<string, mixed>>  $templates
     * @return list<array{id: int, name: string, start_time: string|null, end_time: string|null}>
     */
    public static function uncoveredToday(array $templates, ?CarbonInterface $at = null): array
    {
        if ($templates === []) {
            return [];
        }

        $moment = $at ?? now();
        $templateIds = collect($templates)->pluck('id')->all();
        $todayIds = UserShiftDay::query()
            ->onDuty()
            ->whereDate('duty_on', $moment->toDateString())
            ->whereIn('shift_template_id', $templateIds)
            ->whereHas('assignment', fn ($query) => $query->where('status', ShiftAssignmentStatus::Scheduled))
            ->pluck('shift_template_id')
            ->unique()
            ->all();

        return collect($templates)
            ->filter(fn (array $template): bool => (bool) ($template['is_active'] ?? true))
            ->filter(fn (array $template): bool => ! in_array($template['id'], $todayIds, true))
            ->map(fn (array $template): array => [
                'id' => $template['id'],
                'name' => $template['name'],
                'start_time' => $template['start_time'] ?? null,
                'end_time' => $template['end_time'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * On-duty day rows for scheduled assignments, used as read-only chips on Assign.
     *
     * @return list<array<string, mixed>>
     */
    public static function scheduledDutyDays(?int $branchId = null, ?int $exceptAssignmentId = null): array
    {
        return UserShiftDay::query()
            ->with('shiftTemplate')
            ->onDuty()
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->when(
                $exceptAssignmentId !== null,
                fn ($query) => $query->where('user_shift_assignment_id', '!=', $exceptAssignmentId),
            )
            ->whereHas('assignment', fn ($query) => $query->where('status', ShiftAssignmentStatus::Scheduled))
            ->orderBy('duty_on')
            ->get()
            ->map(fn (UserShiftDay $day): array => self::day($day))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function templatesForBranch(?int $branchId = null): array
    {
        return ShiftTemplate::query()
            ->with('branch:id,name')
            ->withCount([
                'assignments as scheduled_count' => fn ($query) => $query->where('status', ShiftAssignmentStatus::Scheduled),
            ])
            ->where('is_active', true)
            ->when(
                $branchId === null,
                fn ($query) => $query->orderBy('owner_type')->orderBy('name'),
                fn ($query) => $query->availableToBranch($branchId)->orderBy('name'),
            )
            ->get()
            ->map(fn (ShiftTemplate $template): array => self::template($template))
            ->values()
            ->all();
    }

    /**
     * @param  callable(Builder<UserShiftDay>): void  $constrain
     * @return list<array<string, mixed>>
     */
    private static function onDutyQuery(?CarbonInterface $at, callable $constrain): array
    {
        $moment = $at ?? now();

        $query = UserShiftDay::query()
            ->with(['shiftTemplate', 'assignment.branch:id,name,slug', 'user:id,name,user_type,avatar'])
            ->onDuty()
            ->whereDate('duty_on', $moment->toDateString())
            ->whereHas('assignment', fn ($assignments) => $assignments->where('status', ShiftAssignmentStatus::Scheduled));

        $constrain($query);

        return $query
            ->get()
            ->filter(fn (UserShiftDay $day): bool => $day->coversMoment($moment))
            ->values()
            ->map(function (UserShiftDay $day) use ($moment): array {
                $assignment = $day->assignment;
                $payload = $assignment === null
                    ? [
                        'id' => $day->id,
                        'user_id' => $day->user_id,
                        'user_name' => $day->user?->name,
                        'user_type' => $day->user?->user_type?->value,
                        'user_type_label' => $day->user?->user_type?->label(),
                        'avatar_url' => MediaUrl::resolve($day->user?->avatar, fallback: null),
                        'branch_id' => $day->branch_id,
                        'branch_name' => null,
                    ]
                    : self::assignment($assignment, $moment);

                $payload['id'] = $day->id;
                $payload['assignment_id'] = $day->user_shift_assignment_id;
                $payload['shift_template_id'] = $day->shift_template_id;
                $payload['shift_template_name'] = $day->shiftTemplate?->name;
                $payload['start_time'] = $day->shiftTemplate?->start_time?->format('H:i');
                $payload['end_time'] = $day->shiftTemplate?->end_time?->format('H:i');
                $payload['covers_now'] = true;

                return $payload;
            })
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $templates
     * @param  list<array<string, mixed>>  $onDuty
     * @param  list<array<string, mixed>>  $scheduled
     * @return array{on_duty: int, templates: int, active_templates: int, scheduled: int}
     */
    public static function workspaceStats(array $templates, array $onDuty, array $scheduled = []): array
    {
        return [
            'on_duty' => count($onDuty),
            'templates' => count($templates),
            'active_templates' => count(array_filter(
                $templates,
                fn (array $template): bool => (bool) $template['is_active'],
            )),
            'scheduled' => count($scheduled),
        ];
    }

    /**
     * @return list<array{id: int, name: string, user_type: string, branch_id: int|null}>
     */
    public static function assignablePeople(?int $branchId = null): array
    {
        return User::query()
            ->whereIn('user_type', [UserType::DRIVER, UserType::BRANCH])
            ->when($branchId !== null, function ($query) use ($branchId): void {
                $query->where(function ($scope) use ($branchId): void {
                    $scope->where(function ($staff) use ($branchId): void {
                        $staff->where('user_type', UserType::BRANCH)
                            ->where('branch_id', $branchId);
                    })->orWhere(function ($driver) use ($branchId): void {
                        $driver->where('user_type', UserType::DRIVER)
                            ->whereHas('driverBranchAssignments', function ($assignments) use ($branchId): void {
                                $assignments->where('branch_id', $branchId)->where('status', 'active');
                            });
                    });
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'user_type', 'branch_id', 'avatar'])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'user_type' => $user->user_type->value,
                'user_type_label' => $user->user_type->label(),
                'branch_id' => $user->branch_id,
                'avatar_url' => MediaUrl::resolve($user->avatar, fallback: null),
            ])
            ->values()
            ->all();
    }

    /**
     * People currently inside a scheduled duty window for this owner.
     *
     * @return list<array<string, mixed>>
     */
    public static function onDutyOwnedBy(ShiftOwnerType $owner, ?CarbonInterface $at = null, ?int $branchId = null): array
    {
        if ($owner === ShiftOwnerType::Branch && $branchId === null) {
            return [];
        }

        return self::onDutyQuery($at, function ($query) use ($owner, $branchId): void {
            if ($branchId !== null) {
                $query->where('branch_id', $branchId);
            }

            $query->whereHas('shiftTemplate', function ($templates) use ($owner): void {
                if ($owner === ShiftOwnerType::Platform) {
                    $templates->platformOwned();
                } else {
                    $templates->branchOwned();
                }
            });
        });
    }

    /**
     * People currently on duty at one retail branch.
     *
     * @return list<array<string, mixed>>
     */
    public static function onDutyAtBranch(int $branchId, ?CarbonInterface $at = null): array
    {
        return self::onDutyQuery($at, function ($query) use ($branchId): void {
            $query->where('branch_id', $branchId);
        });
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function ownerTypeOptions(): array
    {
        return collect(ShiftOwnerType::cases())
            ->map(fn (ShiftOwnerType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function subjectTypeOptions(): array
    {
        return collect(TransferSubjectType::cases())
            ->map(fn (TransferSubjectType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function transferTypeOptions(): array
    {
        return collect(TransferType::cases())
            ->map(fn (TransferType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    public static function branchOptions(): array
    {
        return Branch::query()
            ->where('status', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Branch $branch): array => self::mapBranchOption($branch))
            ->all();
    }

    /**
     * Same `{data, meta}` contract as stock and conversation option endpoints.
     *
     * @return array{
     *     data: list<array{id: int, name: string, slug: string}>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int}
     * }
     */
    public static function paginatedBranchOptions(Request $request): array
    {
        $term = trim((string) ($request->input('filter.search') ?? $request->query('search', '')));
        $perPage = min(50, max(10, (int) $request->integer('per_page', 20)));

        $paginator = Branch::query()
            ->where('status', true)
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($builder) use ($term): void {
                    $builder
                        ->where('name', 'like', '%'.$term.'%')
                        ->orWhere('slug', 'like', '%'.$term.'%')
                        ->orWhere('code', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage)
            ->appends($request->query());

        return [
            'data' => collect($paginator->items())
                ->map(fn (Branch $branch): array => self::mapBranchOption($branch))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * @return array{id: int, name: string, slug: string}|null
     */
    public static function branchOption(?int $id): ?array
    {
        if ($id === null || $id < 1) {
            return null;
        }

        $branch = Branch::query()
            ->where('status', true)
            ->whereKey($id)
            ->first(['id', 'name', 'slug']);

        return $branch === null ? null : self::mapBranchOption($branch);
    }

    /**
     * @return array{id: int, name: string, slug: string}
     */
    private static function mapBranchOption(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
        ];
    }
}

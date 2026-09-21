<?php

namespace App\Support;

use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftOwnerType;
use App\Enums\TransferSubjectType;
use App\Enums\TransferType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverRoster;
use App\Models\ShiftRosterPeriod;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
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
    public static function period(ShiftRosterPeriod $period): array
    {
        $today = now()->startOfDay();
        $start = $period->start_date?->copy()->startOfDay();
        $end = $period->end_date?->copy()->startOfDay();
        $containsToday = $start !== null && $end !== null && $today->betweenIncluded($start, $end);
        $daysTotal = $start !== null && $end !== null ? (int) $start->diffInDays($end) + 1 : 0;

        return [
            'id' => $period->id,
            'owner_type' => $period->owner_type->value,
            'owner_type_label' => $period->owner_type->label(),
            'branch_id' => $period->branch_id,
            'branch_name' => $period->branch?->name,
            'name' => $period->name,
            'start_date' => $period->start_date?->format('Y-m-d'),
            'end_date' => $period->end_date?->format('Y-m-d'),
            'is_active' => (bool) $period->is_active,
            'scheduled_count' => (int) ($period->scheduled_count ?? 0),
            'contains_today' => $containsToday,
            'days_total' => $daysTotal,
            'days_elapsed' => $containsToday && $start !== null
                ? (int) $start->diffInDays($today) + 1
                : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function assignment(UserShiftAssignment $assignment, ?CarbonInterface $at = null): array
    {
        $assignment->loadMissing(['shiftTemplate', 'shiftRosterPeriod', 'branch:id,name,slug', 'user:id,name,user_type,avatar']);
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
            'shift_roster_period_id' => $assignment->shift_roster_period_id,
            'shift_roster_period_name' => $assignment->shiftRosterPeriod?->name,
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
     * @return array<string, mixed>|null
     */
    public static function currentDuty(User $user, ?CarbonInterface $at = null): ?array
    {
        $moment = $at ?? now();

        $assignment = $user->relationLoaded('shiftAssignments')
            ? $user->shiftAssignments
                ->first(fn (UserShiftAssignment $row): bool => $row->coversMoment($moment))
            : UserShiftAssignment::query()
                ->with(['shiftTemplate', 'shiftRosterPeriod', 'branch:id,name,slug'])
                ->where('user_id', $user->id)
                ->where('status', ShiftAssignmentStatus::Scheduled)
                ->whereDate('start_date', '<=', $moment->toDateString())
                ->whereDate('end_date', '>=', $moment->toDateString())
                ->get()
                ->first(fn (UserShiftAssignment $row): bool => $row->coversMoment($moment));

        return $assignment === null ? null : self::assignment($assignment, $moment);
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
    public static function periodsOwnedBy(ShiftOwnerType $owner, bool $activeOnly = false, ?int $branchId = null): array
    {
        if ($owner === ShiftOwnerType::Branch && $branchId === null) {
            return [];
        }

        return ShiftRosterPeriod::query()
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
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->get()
            ->map(fn (ShiftRosterPeriod $period): array => self::period($period))
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
     * @return list<array<string, mixed>>
     */
    public static function periodsForBranch(?int $branchId = null): array
    {
        return ShiftRosterPeriod::query()
            ->with('branch:id,name')
            ->withCount([
                'assignments as scheduled_count' => fn ($query) => $query->where('status', ShiftAssignmentStatus::Scheduled),
            ])
            ->where('is_active', true)
            ->when(
                $branchId === null,
                fn ($query) => $query->orderByDesc('start_date')->orderBy('name'),
                fn ($query) => $query->availableToBranch($branchId)->orderByDesc('start_date'),
            )
            ->get()
            ->map(fn (ShiftRosterPeriod $period): array => self::period($period))
            ->values()
            ->all();
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
     * @param  callable(Builder<UserShiftAssignment>): void  $constrain
     * @return list<array<string, mixed>>
     */
    private static function onDutyQuery(?CarbonInterface $at, callable $constrain): array
    {
        $moment = $at ?? now();

        $query = UserShiftAssignment::query()
            ->with(['shiftTemplate', 'shiftRosterPeriod', 'branch:id,name,slug', 'user:id,name,user_type,avatar'])
            ->where('status', ShiftAssignmentStatus::Scheduled)
            ->whereDate('start_date', '<=', $moment->toDateString())
            ->whereDate('end_date', '>=', $moment->toDateString());

        $constrain($query);

        return $query
            ->get()
            ->filter(fn (UserShiftAssignment $assignment): bool => $assignment->coversMoment($moment))
            ->values()
            ->map(fn (UserShiftAssignment $assignment): array => self::assignment($assignment, $moment))
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $templates
     * @param  list<array<string, mixed>>  $periods
     * @param  list<array<string, mixed>>  $onDuty
     * @return array{on_duty: int, templates: int, active_templates: int, periods: int}
     */
    public static function workspaceStats(array $templates, array $periods, array $onDuty): array
    {
        return [
            'on_duty' => count($onDuty),
            'templates' => count($templates),
            'active_templates' => count(array_filter(
                $templates,
                fn (array $template): bool => (bool) $template['is_active'],
            )),
            'periods' => count($periods),
        ];
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

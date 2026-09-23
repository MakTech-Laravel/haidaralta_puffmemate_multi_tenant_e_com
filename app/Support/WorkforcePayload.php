<?php

namespace App\Support;

use App\Enums\RoleEnum;
use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftAudience;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class WorkforcePayload
{
    /**
     * Build the gated Assign Shift workspace for one branch.
     *
     * @return array<string, mixed>
     */
    public static function shiftAssignmentWorkspace(
        ?Branch $branch,
        ?string $peopleKind,
        ?int $userId,
        ?string $month,
        int $assignmentPage,
        ?ShiftOwnerType $expectedOwner,
    ): array {
        $selectedPerson = $branch === null || $peopleKind === null || $userId === null
            ? null
            : self::selectedAssignablePerson($branch->id, $peopleKind, $userId);
        $selectedMonth = $month ?? DateTimeFormat::now()->format('Y-m');

        return [
            'selectedBranch' => $branch?->only(['id', 'name', 'slug']),
            'selectedPeopleKind' => $peopleKind,
            'selectedPerson' => $selectedPerson === null ? null : self::assignablePersonOption($selectedPerson),
            'templates' => $branch === null || $peopleKind === null
                ? []
                : self::assignmentTemplates($branch->id, $peopleKind, $expectedOwner),
            'calendar' => $selectedPerson === null
                ? null
                : self::shiftAssignmentCalendar(
                    $selectedPerson,
                    $branch,
                    $selectedMonth,
                    $expectedOwner,
                ),
            'assignmentGroups' => $selectedPerson === null
                ? self::emptyAssignmentGroups($assignmentPage)
                : self::shiftAssignmentGroups(
                    $selectedPerson,
                    $branch,
                    $assignmentPage,
                    $expectedOwner,
                ),
        ];
    }

    private static function selectedAssignablePerson(int $branchId, string $peopleKind, int $userId): ?User
    {
        $userType = $peopleKind === 'driver' ? UserType::DRIVER : UserType::BRANCH;

        $user = self::assignablePeopleQuery($branchId, $userType)
            ->whereKey($userId)
            ->first(['id', 'name', 'user_type', 'branch_id', 'avatar']);

        return $user;
    }

    /**
     * @return array{id: int, name: string, user_type: string, avatar_url: string|null}
     */
    private static function assignablePersonOption(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'user_type' => $user->user_type->value,
            'avatar_url' => MediaUrl::resolve($user->avatar, fallback: null),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function assignmentTemplates(
        int $branchId,
        string $peopleKind,
        ?ShiftOwnerType $expectedOwner,
    ): array {
        $audience = $peopleKind === 'driver' ? ShiftAudience::Driver : ShiftAudience::Staff;

        return array_values(ShiftTemplate::query()
            ->with('branch:id,name')
            ->where('is_active', true)
            ->whereIn('audience', [ShiftAudience::All, $audience])
            ->when(
                $expectedOwner === ShiftOwnerType::Platform,
                fn (Builder $query) => $query->platformOwned(),
            )
            ->when(
                $expectedOwner === ShiftOwnerType::Branch,
                fn (Builder $query) => $query->branchOwned()->where('branch_id', $branchId),
            )
            ->when(
                $expectedOwner === null,
                fn (Builder $query) => $query->availableToBranch($branchId),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (ShiftTemplate $template): array => self::template($template))
            ->values()
            ->all());
    }

    /**
     * @return array{
     *     month: string,
     *     today: string,
     *     grid_start: string,
     *     grid_end: string,
     *     days: array<string, list<array<string, mixed>>>
     * }
     */
    private static function shiftAssignmentCalendar(
        User $user,
        Branch $branch,
        string $month,
        ?ShiftOwnerType $expectedOwner,
    ): array {
        $monthStart = Carbon::createFromFormat(
            'Y-m-d',
            $month.'-01',
            DateTimeFormat::displayTimezone(),
        )->startOfDay();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $today = DateTimeFormat::now()->startOfDay()->toDateString();

        $shiftDays = UserShiftDay::query()
            ->with('shiftTemplate:id,owner_type,branch_id,name,start_time,end_time,is_active,audience')
            ->where('user_id', $user->id)
            ->where('branch_id', $branch->id)
            ->whereBetween('duty_on', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->onDuty()
            ->whereHas('assignment', fn (Builder $query) => $query->where('status', ShiftAssignmentStatus::Scheduled))
            ->orderBy('duty_on')
            ->orderBy('id')
            ->get();
        $days = [];

        foreach ($shiftDays as $day) {
            $date = $day->duty_on->toDateString();
            $template = $day->shiftTemplate;
            $days[$date] ??= [];
            $days[$date][] = [
                'id' => $day->id,
                'assignment_id' => $day->user_shift_assignment_id,
                'shift_template_id' => $day->shift_template_id,
                'name' => $template?->name,
                'start_time' => $template?->start_time?->format('H:i'),
                'end_time' => $template?->end_time?->format('H:i'),
                'editable' => $date >= $today
                    && $template !== null
                    && self::templateEditableInWorkspace($template, $branch, $expectedOwner),
            ];
        }

        return [
            'month' => $month,
            'today' => $today,
            'grid_start' => $gridStart->toDateString(),
            'grid_end' => $gridEnd->toDateString(),
            'days' => $days,
        ];
    }

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int}
     * }
     */
    private static function shiftAssignmentGroups(
        User $user,
        Branch $branch,
        int $page,
        ?ShiftOwnerType $expectedOwner,
    ): array {
        $today = DateTimeFormat::now()->startOfDay()->toDateString();
        $days = UserShiftDay::query()
            ->with('shiftTemplate:id,owner_type,branch_id,name,start_time,end_time,is_active,audience')
            ->where('user_id', $user->id)
            ->where('branch_id', $branch->id)
            ->onDuty()
            ->whereHas('assignment', fn (Builder $query) => $query->where('status', ShiftAssignmentStatus::Scheduled))
            ->orderBy('duty_on')
            ->orderBy('id')
            ->get();

        $groups = $days
            ->groupBy('shift_template_id')
            ->flatMap(function (Collection $templateDays) use ($branch, $expectedOwner, $today): array {
                /** @var UserShiftDay|null $firstDay */
                $firstDay = $templateDays->first();
                $template = $firstDay?->shiftTemplate;

                if ($template === null) {
                    return [];
                }

                $dates = array_values($templateDays
                    ->map(fn (UserShiftDay $day): string => $day->duty_on->toDateString())
                    ->values()
                    ->all());

                return collect(ShiftDateRuns::partition($dates))
                    ->map(function (array $run) use ($branch, $expectedOwner, $template, $today): array {
                        $isPast = $run['end_date'] < $today;

                        return [
                            'key' => $template->id.':'.$run['start_date'].':'.$run['end_date'],
                            'shift_template_id' => $template->id,
                            'name' => $template->name,
                            'start_time' => $template->start_time->format('H:i'),
                            'end_time' => $template->end_time->format('H:i'),
                            'start_date' => $run['start_date'],
                            'end_date' => $run['end_date'],
                            'date_count' => count($run['dates']),
                            'editable' => ! $isPast
                                && self::templateEditableInWorkspace($template, $branch, $expectedOwner),
                            'is_past' => $isPast,
                        ];
                    })
                    ->all();
            })
            ->values();

        $upcoming = $groups
            ->reject(fn (array $group): bool => $group['is_past'])
            ->sortBy([
                ['start_date', 'asc'],
                ['shift_template_id', 'asc'],
            ]);
        $past = $groups
            ->filter(fn (array $group): bool => $group['is_past'])
            ->sortBy([
                ['end_date', 'desc'],
                ['shift_template_id', 'asc'],
            ]);
        $ordered = $upcoming->concat($past)->values();
        $perPage = 20;
        $lastPage = max(1, (int) ceil($ordered->count() / $perPage));
        $currentPage = min(max(1, $page), $lastPage);

        return [
            'data' => array_values($ordered->forPage($currentPage, $perPage)->values()->all()),
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $ordered->count(),
            ],
        ];
    }

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int}
     * }
     */
    private static function emptyAssignmentGroups(int $page): array
    {
        return [
            'data' => [],
            'meta' => [
                'current_page' => max(1, $page),
                'last_page' => 1,
                'per_page' => 20,
                'total' => 0,
            ],
        ];
    }

    private static function templateEditableInWorkspace(
        ShiftTemplate $template,
        Branch $branch,
        ?ShiftOwnerType $expectedOwner,
    ): bool {
        return match ($expectedOwner) {
            ShiftOwnerType::Platform => $template->isPlatformOwned(),
            ShiftOwnerType::Branch => $template->isBranchOwned() && (int) $template->branch_id === $branch->id,
            null => $template->isPlatformOwned()
                || ($template->isBranchOwned() && (int) $template->branch_id === $branch->id),
        };
    }

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
            'audience' => $template->audience->value,
            'audience_label' => $template->audience->label(),
            'start_time' => $template->start_time->format('H:i'),
            'end_time' => $template->end_time->format('H:i'),
            'is_active' => (bool) $template->is_active,
            'scheduled_count' => (int) ($template->scheduled_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function assignment(UserShiftAssignment $assignment, ?CarbonInterface $at = null): array
    {
        $assignment->loadMissing(['shiftTemplate', 'branch:id,name,slug', 'user:id,name,user_type,avatar,branch_id']);
        $days = $assignment->normalizedDaysOfWeek();
        $template = $assignment->shiftTemplate;
        $moment = DateTimeFormat::at($at);

        return [
            'id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'user_name' => $assignment->user?->name,
            'user_type' => $assignment->user?->user_type?->value,
            'user_type_label' => $assignment->user?->user_type?->label(),
            'role_label' => self::roleLabelFor($assignment->user),
            'avatar_url' => MediaUrl::resolve($assignment->user?->avatar, fallback: null),
            'branch_id' => $assignment->branch_id,
            'branch_name' => $assignment->branch?->name,
            'shift_template_id' => $assignment->shift_template_id,
            'shift_template_name' => $template?->name,
            'start_date' => $assignment->start_date->format('Y-m-d'),
            'end_date' => $assignment->end_date->format('Y-m-d'),
            'start_time' => $template?->start_time?->format('H:i'),
            'end_time' => $template?->end_time?->format('H:i'),
            'days_of_week' => $days,
            'days_label' => DriverRoster::daysLabel($days),
            'status' => $assignment->status->value,
            'status_label' => $assignment->status->label(),
            'covers_now' => $assignment->coversMoment($moment),
        ];
    }

    /**
     * Compact assignment row for the Assigned people table — name, dates, shift.
     *
     * @return array<string, mixed>
     */
    /**
     * Monthly Assigned people row: one person, compressed duty days in that month.
     *
     * @param  list<string>  $dates  Y-m-d duty dates already scoped to the month.
     * @return array<string, mixed>
     */
    public static function assignedPersonMonth(User $user, string $month, array $dates, ?int $branchId = null): array
    {
        $peopleKindLabel = $user->user_type === UserType::DRIVER ? 'Driver' : 'Staff';

        return [
            'id' => $user->id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'phone' => $user->phone,
            'user_type' => $user->user_type?->value,
            'people_kind_label' => $peopleKindLabel,
            'avatar_url' => MediaUrl::resolve($user->avatar, fallback: null),
            'branch_id' => $branchId ?? $user->branch_id,
            'assigned_dates_label' => ShiftDateRuns::compactMonthDayLabel($dates),
            'assigned_day_count' => count($dates),
            'month' => $month,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function assignedPerson(UserShiftAssignment $assignment): array
    {
        $assignment->loadMissing(['shiftTemplate', 'branch:id,name,slug', 'user:id,name,user_type,avatar']);
        $template = $assignment->shiftTemplate;
        $startDate = $assignment->start_date->format('Y-m-d');
        $endDate = $assignment->end_date->format('Y-m-d');

        return [
            'id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'user_name' => $assignment->user?->name,
            'user_type' => $assignment->user?->user_type?->value,
            'avatar_url' => MediaUrl::resolve($assignment->user?->avatar, fallback: null),
            'branch_id' => $assignment->branch_id,
            'branch_name' => $assignment->branch?->name,
            'shift_template_id' => $assignment->shift_template_id,
            'shift_template_name' => $template?->name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $template?->start_time?->format('H:i'),
            'end_time' => $template?->end_time?->format('H:i'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function assignmentDays(UserShiftAssignment $assignment): array
    {
        return array_values($assignment->days()
            ->with('shiftTemplate')
            ->orderBy('duty_on')
            ->get()
            ->map(fn (UserShiftDay $day): array => self::day($day))
            ->values()
            ->all());
    }

    /**
     * Other scheduled duty days for this person in the same store and date span.
     *
     * @return list<array<string, mixed>>
     */
    public static function otherDutyDays(User $user, int $branchId, string $startDate, string $endDate, ?int $exceptAssignmentId = null): array
    {
        return array_values(UserShiftDay::query()
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
            ->all());
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
        $moment = DateTimeFormat::at($at);

        $day = UserShiftDay::query()
            ->with(['shiftTemplate', 'assignment.branch:id,name,slug', 'user:id,name,user_type,avatar,branch_id'])
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
        $history->loadMissing(['user:id,name,user_type,avatar', 'fromBranch:id,name,slug', 'toBranch:id,name,slug', 'transferredBy:id,name']);

        return [
            'id' => $history->id,
            'user_id' => $history->user_id,
            'user_name' => $history->user?->name,
            'avatar_url' => MediaUrl::resolve($history->user?->avatar, fallback: null),
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
            'effective_date' => $history->effective_date->format('Y-m-d'),
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

        return array_values(ShiftTemplate::query()
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
                $owner === ShiftOwnerType::Branch,
                fn ($query) => $query->where('branch_id', $branchId),
            )
            ->orderBy('name')
            ->get()
            ->map(fn (ShiftTemplate $template): array => self::template($template))
            ->values()
            ->all());
    }

    /**
     * On-duty day rows for scheduled assignments, used as read-only chips on Assign.
     *
     * @return list<array<string, mixed>>
     */
    public static function scheduledDutyDays(?int $branchId = null, ?int $exceptAssignmentId = null): array
    {
        return array_values(UserShiftDay::query()
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
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function templatesForBranch(?int $branchId = null): array
    {
        return array_values(ShiftTemplate::query()
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
            ->all());
    }

    /**
     * @param  callable(Builder<UserShiftDay>): void  $constrain
     * @return list<array<string, mixed>>
     */
    private static function onDutyQuery(?CarbonInterface $at, callable $constrain): array
    {
        $moment = DateTimeFormat::at($at);

        $query = UserShiftDay::query()
            ->with(['shiftTemplate', 'assignment.branch:id,name,slug', 'assignment.user:id,name,user_type,avatar,branch_id', 'user:id,name,user_type,avatar,branch_id'])
            ->onDuty()
            ->whereDate('duty_on', $moment->toDateString())
            ->whereHas('assignment', fn ($assignments) => $assignments->where('status', ShiftAssignmentStatus::Scheduled));

        $constrain($query);

        $days = $query
            ->get()
            ->filter(fn (UserShiftDay $day): bool => $day->coversMoment($moment))
            ->values();

        self::attachRoleLabels($days->flatMap(fn (UserShiftDay $day): array => array_values(array_filter([
            $day->user,
            $day->assignment?->user,
        ]))));

        return array_values($days
            ->map(function (UserShiftDay $day) use ($moment): array {
                $assignment = $day->assignment;
                $payload = $assignment === null
                    ? [
                        'id' => $day->id,
                        'user_id' => $day->user_id,
                        'user_name' => $day->user?->name,
                        'user_type' => $day->user?->user_type?->value,
                        'user_type_label' => $day->user?->user_type?->label(),
                        'role_label' => self::roleLabelFor($day->user),
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
            ->all());
    }

    /**
     * @param  list<array<string, mixed>>  $templates
     * @param  list<array<string, mixed>>  $onDuty
     * @return array{on_duty: int, templates: int, active_templates: int}
     */
    public static function workspaceStats(array $templates, array $onDuty): array
    {
        return [
            'on_duty' => count($onDuty),
            'templates' => count($templates),
            'active_templates' => count(array_filter(
                $templates,
                fn (array $template): bool => (bool) $template['is_active'],
            )),
        ];
    }

    /**
     * @return list<array{id: int, name: string, user_type: string, user_type_label: string, branch_id: int|null, avatar_url: string|null}>
     */
    public static function assignablePeople(?int $branchId = null): array
    {
        return array_values(User::query()
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
            ->all());
    }

    /**
     * @return array{
     *     data: list<array{id: int, name: string, user_type: string, avatar_url: string|null}>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int}
     * }
     */
    public static function paginatedAssignablePeople(
        int $branchId,
        string $peopleKind,
        ?string $search = null,
        int $perPage = 20,
    ): array {
        $term = trim((string) $search);
        $perPage = min(50, max(10, $perPage));
        $userType = $peopleKind === 'driver' ? UserType::DRIVER : UserType::BRANCH;

        $paginator = self::assignablePeopleQuery($branchId, $userType)
            ->when($term !== '', function ($query) use ($term): void {
                $query->where('name', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);

        return [
            'data' => array_values(
                collect($paginator->items())
                    ->map(fn (User $user): array => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'user_type' => $user->user_type->value,
                        'avatar_url' => MediaUrl::resolve($user->avatar, fallback: null),
                    ])
                    ->all(),
            ),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * @return Builder<User>
     */
    private static function assignablePeopleQuery(int $branchId, UserType $userType): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->where('user_type', $userType)
            ->when($userType === UserType::BRANCH, function (Builder $query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            })
            ->when($userType === UserType::DRIVER, function (Builder $query) use ($branchId): void {
                $query->whereHas('driverBranchAssignments', function (Builder $assignments) use ($branchId): void {
                    $assignments
                        ->where('branch_id', $branchId)
                        ->where('status', 'active');
                });
            });
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
                $templates->where('owner_type', $owner);
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
        return array_values(collect(ShiftOwnerType::cases())
            ->map(fn (ShiftOwnerType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function subjectTypeOptions(): array
    {
        return array_values(collect(TransferSubjectType::cases())
            ->map(fn (TransferSubjectType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function transferTypeOptions(): array
    {
        return array_values(collect(TransferType::cases())
            ->map(fn (TransferType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all());
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    public static function branchOptions(): array
    {
        return array_values(Branch::query()
            ->where('status', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Branch $branch): array => self::mapBranchOption($branch))
            ->all());
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
            'data' => array_values(collect($paginator->items())
                ->map(fn (Branch $branch): array => self::mapBranchOption($branch))
                ->values()
                ->all()),
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

    /**
     * @param  Collection<int, mixed>  $users
     */
    private static function attachRoleLabels(Collection $users): void
    {
        $staffAndDrivers = $users->filter(fn (mixed $user): bool => $user instanceof User);
        $labels = self::roleLabelsByUserId($staffAndDrivers->unique('id')->values());

        foreach ($staffAndDrivers as $user) {
            $user->setAttribute(
                'workforce_role_label',
                $labels[$user->id] ?? $user->user_type->label(),
            );
        }
    }

    /**
     * Spatie roles() is team-scoped to the current request team (platform id 0
     * or the branch from ResolveBranchContext). Load names from the pivot so
     * branch staff still resolve on the platform board.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, string>
     */
    private static function roleLabelsByUserId(Collection $users): array
    {
        $labels = [];

        foreach ($users as $user) {
            if ($user->user_type === UserType::DRIVER) {
                $labels[$user->id] = UserType::DRIVER->label();
            }
        }

        $staff = $users->reject(fn (User $user): bool => $user->user_type === UserType::DRIVER);

        if ($staff->isEmpty()) {
            return $labels;
        }

        $pivot = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');
        $teamKey = config('permission.column_names.team_foreign_key');
        $morphKey = config('permission.column_names.model_morph_key');

        $rows = DB::table($pivot)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$pivot}.role_id")
            ->where("{$pivot}.model_type", (new User)->getMorphClass())
            ->whereIn("{$pivot}.{$morphKey}", $staff->pluck('id'))
            ->get([
                "{$pivot}.{$morphKey} as user_id",
                "{$pivot}.{$teamKey} as team_id",
                "{$rolesTable}.name as role_name",
            ]);

        foreach ($staff as $user) {
            $teamId = $user->user_type === UserType::PLATFORM
                ? PlatformTeamResolver::PLATFORM_TEAM_ID
                : $user->branch_id;

            $match = $rows->first(function (object $row) use ($user, $teamId): bool {
                if ((int) $row->user_id !== $user->id) {
                    return false;
                }

                if ($teamId === null) {
                    return true;
                }

                return (int) $row->team_id === (int) $teamId;
            }) ?? $rows->first(fn (object $row): bool => (int) $row->user_id === $user->id);

            $labels[$user->id] = is_object($match)
                ? self::formatRoleName((string) $match->role_name)
                : $user->user_type->label();
        }

        return $labels;
    }

    private static function roleLabelFor(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $cached = $user->getAttribute('workforce_role_label');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return self::roleLabelsByUserId(collect([$user]))[$user->id]
            ?? $user->user_type->label();
    }

    private static function formatRoleName(string $name): string
    {
        return RoleEnum::tryFrom($name)?->label()
            ?? str($name)->replace('-', ' ')->title()->toString();
    }
}

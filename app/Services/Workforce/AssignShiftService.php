<?php

namespace App\Services\Workforce;

use App\Enums\DriverAssignmentStatus;
use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftAudience;
use App\Enums\ShiftDayKind;
use App\Enums\ShiftOwnerType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverRoster;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftDay;
use App\Models\UserShiftHistory;
use App\Support\DateTimeFormat;
use App\Support\DutyWindow;
use App\Support\ShiftDateRuns;
use App\Support\ShiftDutyCalendar;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignShiftService
{
    public function __construct(private ShiftAssignmentCanonicalizer $canonicalizer) {}

    /**
     * Apply additions, replacements, and removals as one atomic schedule change.
     *
     * @param  list<string>  $assignDates
     * @param  list<int>  $replaceDayIds
     * @param  list<int>  $removeDayIds
     */
    public function applyChanges(
        User $user,
        Branch $branch,
        ?ShiftTemplate $targetTemplate,
        array $assignDates,
        array $replaceDayIds,
        array $removeDayIds,
        ?User $actor = null,
        ?ShiftOwnerType $expectedOwner = null,
    ): void {
        $assignDates = ShiftDateRuns::normalize($assignDates);
        $replaceDayIds = $this->normalizeDayIds($replaceDayIds, 'Replacement shift days must be distinct.');
        $removeDayIds = $this->normalizeDayIds($removeDayIds, 'Removed shift days must be distinct.');

        if ($assignDates === [] && $replaceDayIds === [] && $removeDayIds === []) {
            throw new InvalidArgumentException('Select at least one shift change to save.');
        }

        if (array_intersect($replaceDayIds, $removeDayIds) !== []) {
            throw new InvalidArgumentException('A scheduled day cannot be changed and removed in the same save.');
        }

        DB::transaction(function () use (
            $user,
            $branch,
            $targetTemplate,
            $assignDates,
            $replaceDayIds,
            $removeDayIds,
            $actor,
            $expectedOwner,
        ): void {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedBranch = Branch::query()->whereKey($branch->id)->lockForUpdate()->firstOrFail();

            abort_unless($lockedBranch->status, 404);
            $this->assertAssignableUser(
                $lockedUser,
                $lockedBranch,
                lockMembership: true,
                requireActive: true,
            );

            $lockedTarget = $this->lockTargetTemplate(
                $targetTemplate,
                $assignDates !== [] || $replaceDayIds !== [],
            );

            if ($lockedTarget !== null) {
                $this->assertTargetTemplate($lockedTarget, $lockedUser, $lockedBranch, $expectedOwner);
            }

            $references = $this->lockReferencedDays(
                $lockedUser,
                $lockedBranch,
                $replaceDayIds,
                $removeDayIds,
                $expectedOwner,
            );
            $replaceDays = $replaceDayIds === []
                ? collect()
                : $references['days']->only($replaceDayIds);
            $removeDays = $removeDayIds === []
                ? collect()
                : $references['days']->only($removeDayIds);

            $this->assertReplacementDatesAreUnambiguous($replaceDays);

            $referencedDates = $replaceDays
                ->concat($removeDays)
                ->map(fn (UserShiftDay $day): string => $day->duty_on->toDateString())
                ->values()
                ->all();
            $affectedDates = ShiftDateRuns::normalize([...$assignDates, ...$referencedDates]);

            $this->assertEditableDateSet($affectedDates);
            $this->assertNoContradictoryDates($assignDates, $replaceDays, $removeDays);

            $effectiveReplaceDays = $lockedTarget === null
                ? collect()
                : $replaceDays->reject(
                    fn (UserShiftDay $day): bool => $day->shift_template_id === $lockedTarget->id,
                );
            $deleteDays = $removeDays->concat($effectiveReplaceDays)->keyBy('id');

            /** @var EloquentCollection<int, UserShiftDay> $existingDays */
            $existingDays = UserShiftDay::query()
                ->with('shiftTemplate')
                ->where('user_id', $lockedUser->id)
                ->whereIn('duty_on', $affectedDates)
                ->onDuty()
                ->whereHas('assignment', function ($query): void {
                    $query->where('status', ShiftAssignmentStatus::Scheduled);
                })
                ->orderBy('duty_on')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $assignDatesToPersist = $lockedTarget === null
                ? []
                : $this->datesNeedingTargetShift(
                    $assignDates,
                    $existingDays,
                    $deleteDays,
                    $lockedBranch,
                    $lockedTarget,
                );
            $replacementDates = $effectiveReplaceDays
                ->map(fn (UserShiftDay $day): string => $day->duty_on->toDateString())
                ->values()
                ->all();
            $targetDates = $lockedTarget === null
                ? []
                : $this->datesNeedingTargetShift(
                    ShiftDateRuns::normalize([...$assignDatesToPersist, ...$replacementDates]),
                    $existingDays,
                    $deleteDays,
                    $lockedBranch,
                    $lockedTarget,
                );

            $this->assertValidFinalSchedule(
                $existingDays,
                $deleteDays,
                $targetDates,
                $lockedBranch,
                $lockedTarget,
            );

            $sourceAssignments = $this->lockSourceAssignments($deleteDays);
            $sourceDays = $this->lockAllAssignmentDays($sourceAssignments);
            $canonicalState = $sourceAssignments->mapWithKeys(
                fn (UserShiftAssignment $assignment): array => [
                    $assignment->id => $this->canonicalizer->isCanonical(
                        $assignment,
                        $sourceDays->get($assignment->id, collect()),
                    ),
                ],
            );

            foreach ($deleteDays as $day) {
                $day->delete();
            }

            $mergeTemplateIds = [];

            foreach ($sourceAssignments as $assignment) {
                $mergeTemplateIds = [
                    ...$mergeTemplateIds,
                    ...$this->canonicalizer->normalizeAffected(
                        $assignment,
                        (bool) $canonicalState->get($assignment->id, false),
                    ),
                ];
            }

            if ($lockedTarget !== null && $targetDates !== []) {
                $this->canonicalizer->createCanonicalGroups(
                    $lockedUser,
                    $lockedBranch,
                    $lockedTarget,
                    $targetDates,
                    $actor,
                );
                $mergeTemplateIds[] = $lockedTarget->id;
            }

            foreach (array_values(array_unique($mergeTemplateIds)) as $templateId) {
                $this->canonicalizer->mergeAdjacent($lockedUser, $lockedBranch, $templateId);
            }

            if ($lockedTarget !== null) {
                foreach (ShiftDateRuns::partition($assignDatesToPersist) as $run) {
                    $this->recordHistory(
                        $lockedUser,
                        $lockedBranch,
                        null,
                        $lockedTarget,
                        $run['start_date'],
                        null,
                        null,
                        $actor,
                    );
                }

                foreach ($effectiveReplaceDays as $day) {
                    $previousTemplate = $references['templates']->get($day->shift_template_id);

                    if ($previousTemplate instanceof ShiftTemplate) {
                        $this->recordHistory(
                            $lockedUser,
                            $lockedBranch,
                            null,
                            $lockedTarget,
                            $day->duty_on,
                            null,
                            null,
                            $actor,
                            $previousTemplate,
                        );
                    }
                }
            }
        }, attempts: 3);
    }

    /**
     * @param  array{user_id: int, branch_id: int, shift_template_id: int, start_date: string, end_date: string, days_of_week?: list<int>, days?: list<array{date?: mixed, kind?: mixed, shift_template_id?: mixed}>, reason?: string|null, note?: string|null, effective_date?: string|null}  $data
     */
    public function assign(array $data, ?User $actor = null, ?ShiftOwnerType $expectedOwner = null): UserShiftAssignment
    {
        $user = User::query()->findOrFail((int) $data['user_id']);
        $branch = Branch::query()->findOrFail((int) $data['branch_id']);
        $template = ShiftTemplate::query()->findOrFail((int) $data['shift_template_id']);
        $days = DriverRoster::normalizeDays($data['days_of_week'] ?? null);
        $exceptions = $data['days'] ?? [];

        $this->assertAssignableUser($user, $branch);
        $this->assertExpectedOwner($template, $expectedOwner, $branch);
        $this->assertTemplateVisible($template, $branch);
        $this->assertDateRange($data['start_date'], $data['end_date']);

        $rows = ShiftDutyCalendar::expand(
            $data['start_date'],
            $data['end_date'],
            $template->id,
            $days,
            $exceptions,
        );

        $this->assertNoDayOverlap($user, $rows, $template);

        return DB::transaction(function () use ($user, $branch, $template, $data, $days, $rows, $actor): UserShiftAssignment {
            $assignment = UserShiftAssignment::query()->create([
                'user_id' => $user->id,
                'branch_id' => $branch->id,
                'shift_template_id' => $template->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'days_of_week' => $days,
                'status' => ShiftAssignmentStatus::Scheduled,
                'assigned_by' => $actor?->id,
            ]);

            $this->writeDays($assignment, $rows);
            $this->recordHistory(
                $user,
                $branch,
                null,
                $template,
                $data['effective_date'] ?? $data['start_date'],
                $data['reason'] ?? null,
                $data['note'] ?? null,
                $actor,
            );

            return $assignment;
        });
    }

    /**
     * @param  array{shift_template_id?: int, start_date?: string, end_date?: string, days_of_week?: list<int>, days?: list<array{date?: mixed, kind?: mixed, shift_template_id?: mixed}>, status?: string, reason?: string|null, note?: string|null, effective_date?: string|null}  $data
     */
    public function change(UserShiftAssignment $assignment, array $data, ?User $actor = null, ?ShiftOwnerType $expectedOwner = null): UserShiftAssignment
    {
        $assignment->loadMissing(['user', 'branch', 'shiftTemplate']);

        $user = $assignment->user;
        $branch = $assignment->branch;
        $template = isset($data['shift_template_id'])
            ? ShiftTemplate::query()->findOrFail((int) $data['shift_template_id'])
            : $assignment->shiftTemplate;

        if ($user === null || $branch === null || $template === null) {
            throw new InvalidArgumentException('Shift assignment is missing required relations.');
        }

        $startDate = $data['start_date'] ?? $assignment->start_date->format('Y-m-d');
        $endDate = $data['end_date'] ?? $assignment->end_date->format('Y-m-d');
        $days = DriverRoster::normalizeDays($data['days_of_week'] ?? $assignment->normalizedDaysOfWeek());
        $status = isset($data['status'])
            ? ShiftAssignmentStatus::from($data['status'])
            : $assignment->status;
        $exceptions = $data['days'] ?? $this->existingExceptions($assignment);

        $this->assertExpectedOwner($template, $expectedOwner, $branch);
        $this->assertTemplateVisible($template, $branch);
        $this->assertDateRange($startDate, $endDate);

        $rows = ShiftDutyCalendar::expand($startDate, $endDate, $template->id, $days, $exceptions);

        if ($status === ShiftAssignmentStatus::Scheduled) {
            $this->assertNoDayOverlap($user, $rows, $template, $assignment->id);
        }

        return DB::transaction(function () use ($assignment, $user, $branch, $template, $startDate, $endDate, $days, $status, $rows, $data, $actor): UserShiftAssignment {
            $previousTemplate = $assignment->shiftTemplate;

            $assignment->update([
                'shift_template_id' => $template->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_of_week' => $days,
                'status' => $status->value,
            ]);

            $this->writeDays($assignment->refresh(), $rows);

            if ($previousTemplate?->id !== $template->id || isset($data['days']) || filled($data['reason'] ?? null)) {
                $this->recordHistory(
                    $user,
                    $branch,
                    $assignment,
                    $template,
                    $data['effective_date'] ?? $startDate,
                    $data['reason'] ?? null,
                    $data['note'] ?? null,
                    $actor,
                    $previousTemplate,
                );
            }

            return $assignment->refresh();
        });
    }

    /**
     * @param  list<array{date?: mixed, kind?: mixed, shift_template_id?: mixed}>  $exceptions
     */
    public function syncDays(UserShiftAssignment $assignment, array $exceptions = []): void
    {
        $templateId = (int) $assignment->shift_template_id;
        $rows = ShiftDutyCalendar::expand(
            $assignment->start_date->format('Y-m-d'),
            $assignment->end_date->format('Y-m-d'),
            $templateId,
            $assignment->normalizedDaysOfWeek(),
            $exceptions,
        );

        $this->writeDays($assignment, $rows);
    }

    private function lockTargetTemplate(?ShiftTemplate $template, bool $required): ?ShiftTemplate
    {
        if ($template === null) {
            if ($required) {
                throw new InvalidArgumentException('Select a shift for assignments and changes.');
            }

            return null;
        }

        $lockedTemplate = ShiftTemplate::query()
            ->whereKey($template->id)
            ->lockForUpdate()
            ->first();

        abort_if($lockedTemplate === null, 404);

        return $lockedTemplate;
    }

    private function assertTargetTemplate(
        ShiftTemplate $template,
        User $user,
        Branch $branch,
        ?ShiftOwnerType $expectedOwner,
    ): void {
        $this->assertEditableTemplateOwner($template, $branch, $expectedOwner);

        if (! $template->is_active) {
            throw new InvalidArgumentException('This shift is inactive.');
        }

        if (! DutyWindow::isSameDayWindow($template->startTimeValue(), $template->endTimeValue())) {
            throw new InvalidArgumentException('A shift must end after it starts on the same day.');
        }

        $audience = $user->user_type === UserType::DRIVER
            ? ShiftAudience::Driver
            : ShiftAudience::Staff;

        if (! $template->audience->visibleFor($audience)) {
            throw new InvalidArgumentException('This shift is not available for the selected person type.');
        }
    }

    private function assertEditableTemplateOwner(
        ShiftTemplate $template,
        Branch $branch,
        ?ShiftOwnerType $expectedOwner,
    ): void {
        $editable = match ($expectedOwner) {
            ShiftOwnerType::Platform => $template->isPlatformOwned(),
            ShiftOwnerType::Branch => $template->isBranchOwned() && (int) $template->branch_id === $branch->id,
            null => $template->isPlatformOwned()
                || ($template->isBranchOwned() && (int) $template->branch_id === $branch->id),
        };

        abort_unless($editable, 404);
    }

    /**
     * @param  list<int>  $replaceDayIds
     * @param  list<int>  $removeDayIds
     * @return array{
     *     days: EloquentCollection<int, UserShiftDay>,
     *     assignments: EloquentCollection<int, UserShiftAssignment>,
     *     templates: EloquentCollection<int, ShiftTemplate>
     * }
     */
    private function lockReferencedDays(
        User $user,
        Branch $branch,
        array $replaceDayIds,
        array $removeDayIds,
        ?ShiftOwnerType $expectedOwner,
    ): array {
        $requestedIds = [...$replaceDayIds, ...$removeDayIds];

        if ($requestedIds === []) {
            return [
                'days' => new EloquentCollection,
                'assignments' => new EloquentCollection,
                'templates' => new EloquentCollection,
            ];
        }

        /** @var EloquentCollection<int, UserShiftDay> $days */
        $days = UserShiftDay::query()
            ->whereKey($requestedIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($days->count() !== count($requestedIds)) {
            throw new InvalidArgumentException('The schedule changed before it could be saved. Reload and try again.');
        }

        /** @var EloquentCollection<int, UserShiftAssignment> $assignments */
        $assignments = UserShiftAssignment::query()
            ->whereKey($days->pluck('user_shift_assignment_id')->unique()->values()->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        /** @var EloquentCollection<int, ShiftTemplate> $templates */
        $templates = ShiftTemplate::query()
            ->whereKey($days->pluck('shift_template_id')->filter()->unique()->values()->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($days as $day) {
            if ($day->user_id !== $user->id || $day->branch_id !== $branch->id) {
                abort(404);
            }

            $assignment = $assignments->get($day->user_shift_assignment_id);
            $template = $day->shift_template_id === null ? null : $templates->get($day->shift_template_id);

            if (
                ! $assignment instanceof UserShiftAssignment
                || $assignment->status !== ShiftAssignmentStatus::Scheduled
                || $assignment->user_id !== $user->id
                || $assignment->branch_id !== $branch->id
                || $day->kind !== ShiftDayKind::Scheduled
                || ! $template instanceof ShiftTemplate
            ) {
                throw new InvalidArgumentException('The schedule changed before it could be saved. Reload and try again.');
            }

            $this->assertEditableTemplateOwner($template, $branch, $expectedOwner);
        }

        return [
            'days' => $days,
            'assignments' => $assignments,
            'templates' => $templates,
        ];
    }

    /**
     * @param  Collection<int, UserShiftDay>  $replaceDays
     */
    private function assertReplacementDatesAreUnambiguous(Collection $replaceDays): void
    {
        $hasAmbiguousDate = $replaceDays
            ->groupBy(fn (UserShiftDay $day): string => $day->duty_on->toDateString())
            ->contains(fn (Collection $days): bool => $days->count() > 1);

        if ($hasAmbiguousDate) {
            throw new InvalidArgumentException('Only one scheduled shift per date can be changed at a time.');
        }
    }

    /**
     * @param  list<string>  $dates
     */
    private function assertEditableDateSet(array $dates): void
    {
        if (count($dates) > ShiftDutyCalendar::MAX_DAYS || ShiftDateRuns::inclusiveSpan($dates) > ShiftDutyCalendar::MAX_DAYS) {
            throw new InvalidArgumentException('Shift changes cannot span more than 366 days.');
        }

        $today = DateTimeFormat::now()->startOfDay()->toDateString();

        if (collect($dates)->contains(fn (string $date): bool => $date < $today)) {
            throw new InvalidArgumentException('Past shifts cannot be changed.');
        }
    }

    /**
     * @param  list<string>  $assignDates
     * @param  Collection<int, UserShiftDay>  $replaceDays
     * @param  Collection<int, UserShiftDay>  $removeDays
     */
    private function assertNoContradictoryDates(
        array $assignDates,
        Collection $replaceDays,
        Collection $removeDays,
    ): void {
        $changedDates = $replaceDays
            ->concat($removeDays)
            ->map(fn (UserShiftDay $day): string => $day->duty_on->toDateString())
            ->all();

        if (array_intersect($assignDates, $changedDates) !== []) {
            throw new InvalidArgumentException('A date cannot be assigned and changed or removed in the same save.');
        }
    }

    /**
     * @param  list<string>  $assignDates
     * @param  EloquentCollection<int, UserShiftDay>  $existingDays
     * @param  Collection<int, UserShiftDay>  $deleteDays
     * @return list<string>
     */
    private function datesNeedingTargetShift(
        array $assignDates,
        EloquentCollection $existingDays,
        Collection $deleteDays,
        Branch $branch,
        ShiftTemplate $targetTemplate,
    ): array {
        return array_values(collect($assignDates)
            ->reject(function (string $date) use ($existingDays, $deleteDays, $branch, $targetTemplate): bool {
                return $existingDays->contains(function (UserShiftDay $day) use ($date, $deleteDays, $branch, $targetTemplate): bool {
                    return ! $deleteDays->has($day->id)
                        && $day->branch_id === $branch->id
                        && $day->duty_on->toDateString() === $date
                        && $day->shift_template_id === $targetTemplate->id;
                });
            })
            ->values()
            ->all());
    }

    /**
     * @param  EloquentCollection<int, UserShiftDay>  $existingDays
     * @param  Collection<int, UserShiftDay>  $deleteDays
     * @param  list<string>  $targetDates
     */
    private function assertValidFinalSchedule(
        EloquentCollection $existingDays,
        Collection $deleteDays,
        array $targetDates,
        Branch $branch,
        ?ShiftTemplate $targetTemplate,
    ): void {
        $schedule = collect();

        foreach ($existingDays as $day) {
            if ($deleteDays->has($day->id)) {
                continue;
            }

            if ($day->shiftTemplate === null) {
                throw new InvalidArgumentException('The schedule contains a shift that is no longer available.');
            }

            $schedule->push([
                'key' => $day->branch_id.':'.$day->duty_on->toDateString().':'.$day->shift_template_id,
                'date' => $day->duty_on->toDateString(),
                'start_time' => $day->shiftTemplate->startTimeValue(),
                'end_time' => $day->shiftTemplate->endTimeValue(),
            ]);
        }

        if ($targetDates !== [] && $targetTemplate === null) {
            throw new InvalidArgumentException('Select a shift for assignments and changes.');
        }

        if ($targetTemplate !== null) {
            foreach ($targetDates as $date) {
                $key = $branch->id.':'.$date.':'.$targetTemplate->id;

                if ($schedule->contains('key', $key)) {
                    continue;
                }

                $schedule->push([
                    'key' => $key,
                    'date' => $date,
                    'start_time' => $targetTemplate->startTimeValue(),
                    'end_time' => $targetTemplate->endTimeValue(),
                ]);
            }
        }

        foreach ($schedule->groupBy('date') as $date => $dateSchedule) {
            if (! config('shifts.allow_multiple_per_day') && $dateSchedule->count() > 1) {
                throw new InvalidArgumentException('This person already has a shift on '.$date.'.');
            }

            if (! config('shifts.allow_multiple_per_day')) {
                continue;
            }

            $windows = $dateSchedule->values();

            for ($left = 0; $left < $windows->count(); $left++) {
                for ($right = $left + 1; $right < $windows->count(); $right++) {
                    if (DutyWindow::timesOverlap(
                        $windows[$left]['start_time'],
                        $windows[$left]['end_time'],
                        $windows[$right]['start_time'],
                        $windows[$right]['end_time'],
                    )) {
                        throw new InvalidArgumentException('This person already has an overlapping shift on '.$date.'.');
                    }
                }
            }
        }
    }

    /**
     * @param  Collection<int, UserShiftDay>  $deleteDays
     * @return EloquentCollection<int, UserShiftAssignment>
     */
    private function lockSourceAssignments(Collection $deleteDays): EloquentCollection
    {
        if ($deleteDays->isEmpty()) {
            return new EloquentCollection;
        }

        return UserShiftAssignment::query()
            ->whereKey($deleteDays->pluck('user_shift_assignment_id')->unique()->values()->all())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  EloquentCollection<int, UserShiftAssignment>  $assignments
     * @return Collection<int|string, EloquentCollection<int, UserShiftDay>>
     */
    private function lockAllAssignmentDays(EloquentCollection $assignments): Collection
    {
        if ($assignments->isEmpty()) {
            return collect();
        }

        return UserShiftDay::query()
            ->whereIn('user_shift_assignment_id', $assignments->modelKeys())
            ->orderBy('duty_on')
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->groupBy('user_shift_assignment_id');
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function normalizeDayIds(array $ids, string $duplicateMessage): array
    {
        $normalized = array_map(static fn (int $id): int => $id, $ids);

        if (count($normalized) !== count(array_unique($normalized))) {
            throw new InvalidArgumentException($duplicateMessage);
        }

        sort($normalized);

        return $normalized;
    }

    /**
     * @param  list<array{duty_on: string, shift_template_id: int|null, kind: string}>  $rows
     */
    private function writeDays(UserShiftAssignment $assignment, array $rows): void
    {
        $dates = collect($rows)->pluck('duty_on')->all();

        $assignment->days()
            ->when($dates !== [], fn ($query) => $query->whereNotIn('duty_on', $dates), fn ($query) => $query)
            ->delete();

        if ($dates === []) {
            $assignment->days()->delete();

            return;
        }

        $now = now();
        $payload = collect($rows)
            ->map(fn (array $row): array => [
                'user_shift_assignment_id' => $assignment->id,
                'user_id' => $assignment->user_id,
                'branch_id' => $assignment->branch_id,
                'duty_on' => $row['duty_on'],
                'shift_template_id' => $row['shift_template_id'],
                'kind' => $row['kind'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        foreach (array_chunk($payload, 100) as $chunk) {
            UserShiftDay::query()->upsert(
                $chunk,
                uniqueBy: ['user_shift_assignment_id', 'duty_on'],
                update: ['shift_template_id', 'kind', 'user_id', 'branch_id', 'updated_at'],
            );
        }
    }

    private function assertAssignableUser(
        User $user,
        Branch $branch,
        bool $lockMembership = false,
        bool $requireActive = false,
    ): void {
        if ($user->user_type === UserType::PLATFORM || $user->user_type === UserType::CUSTOMER) {
            abort(404);
        }

        if ($requireActive && ! $user->is_active) {
            throw new InvalidArgumentException('Only active people can be assigned shifts.');
        }

        if ($user->user_type === UserType::DRIVER) {
            $membership = DriverBranchAssignment::query()
                ->where('driver_id', $user->id)
                ->where('branch_id', $branch->id)
                ->where('status', DriverAssignmentStatus::Active);

            $hasActiveMembership = $lockMembership
                ? $membership->lockForUpdate()->first() !== null
                : $membership->exists();

            if (! $hasActiveMembership) {
                throw new InvalidArgumentException('Driver does not have an active assignment for this branch.');
            }

            return;
        }

        if ($user->branch_id !== $branch->id) {
            throw new InvalidArgumentException('Staff can only be assigned a shift at their home branch.');
        }
    }

    private function assertExpectedOwner(
        ShiftTemplate $template,
        ?ShiftOwnerType $expectedOwner,
        Branch $branch,
    ): void {
        if ($expectedOwner === null) {
            return;
        }

        if ($expectedOwner === ShiftOwnerType::Platform) {
            abort_unless($template->isPlatformOwned(), 404);

            return;
        }

        abort_unless(
            $template->isBranchOwned() && (int) $template->branch_id === $branch->id,
            404,
        );
    }

    private function assertTemplateVisible(ShiftTemplate $template, Branch $branch): void
    {
        if (! $template->is_active) {
            throw new InvalidArgumentException('This shift is inactive.');
        }

        $visible = $template->isPlatformOwned() || $template->branch_id === $branch->id;

        if (! $visible) {
            throw new InvalidArgumentException('This shift is not available for this branch.');
        }
    }

    private function assertDateRange(string $startDate, string $endDate): void
    {
        if ($endDate < $startDate) {
            throw new InvalidArgumentException('Assignment end date must be on or after the start date.');
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ((int) $start->diffInDays($end) + 1 > ShiftDutyCalendar::MAX_DAYS) {
            throw new InvalidArgumentException('A duty period cannot be longer than 366 days.');
        }
    }

    /**
     * @param  list<array{duty_on: string, shift_template_id: int|null, kind: string}>  $rows
     */
    private function assertNoDayOverlap(
        User $user,
        array $rows,
        ShiftTemplate $fallbackTemplate,
        ?int $exceptAssignmentId = null,
    ): void {
        $onDuty = collect($rows)
            ->filter(function (array $row): bool {
                $kind = ShiftDayKind::tryFrom($row['kind']);

                return $kind?->isOnDuty() === true && $row['shift_template_id'] !== null;
            })
            ->values();

        if ($onDuty->isEmpty()) {
            return;
        }

        $templates = ShiftTemplate::query()
            ->whereIn('id', $onDuty->pluck('shift_template_id')->unique()->filter()->all())
            ->get()
            ->keyBy('id');

        $existing = UserShiftDay::query()
            ->with('shiftTemplate')
            ->where('user_id', $user->id)
            ->onDuty()
            ->whereIn('duty_on', $onDuty->pluck('duty_on')->all())
            ->whereHas('assignment', function ($query) use ($exceptAssignmentId): void {
                $query->where('status', ShiftAssignmentStatus::Scheduled);

                if ($exceptAssignmentId !== null) {
                    $query->whereKeyNot($exceptAssignmentId);
                }
            })
            ->get();

        foreach ($onDuty as $row) {
            $template = $templates->get((int) $row['shift_template_id']) ?? $fallbackTemplate;

            $conflict = $existing->first(function (UserShiftDay $day) use ($row, $template): bool {
                if ($day->duty_on->toDateString() !== $row['duty_on'] || $day->shiftTemplate === null) {
                    return false;
                }

                return DutyWindow::timesOverlap(
                    $template->startTimeValue(),
                    $template->endTimeValue(),
                    $day->shiftTemplate->startTimeValue(),
                    $day->shiftTemplate->endTimeValue(),
                );
            });

            if ($conflict !== null) {
                throw new InvalidArgumentException('This person already has an overlapping shift on '.$row['duty_on'].'.');
            }
        }
    }

    /**
     * @return list<array{date: string, kind: string, shift_template_id: int|null}>
     */
    private function existingExceptions(UserShiftAssignment $assignment): array
    {
        return array_values($assignment->days()
            ->orderBy('duty_on')
            ->get()
            ->filter(function (UserShiftDay $day) use ($assignment): bool {
                $isWorking = in_array($day->duty_on->isoWeekday(), $assignment->normalizedDaysOfWeek(), true);

                return $day->kind === ShiftDayKind::Leave
                    || $day->kind === ShiftDayKind::Override
                    || ($day->kind === ShiftDayKind::WeeklyOff && $isWorking);
            })
            ->map(fn (UserShiftDay $day): array => [
                'date' => $day->duty_on->toDateString(),
                'kind' => $day->kind->value,
                'shift_template_id' => $day->shift_template_id,
            ])
            ->values()
            ->all());
    }

    private function recordHistory(
        User $user,
        Branch $branch,
        ?UserShiftAssignment $previous,
        ShiftTemplate $newTemplate,
        Carbon|string $effectiveDate,
        ?string $reason,
        ?string $note,
        ?User $actor,
        ?ShiftTemplate $previousTemplate = null,
    ): void {
        $previousTemplate ??= $previous?->shiftTemplate;

        UserShiftHistory::query()->create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'previous_shift_template_id' => $previousTemplate?->id,
            'new_shift_template_id' => $newTemplate->id,
            'previous_start_time' => $previousTemplate?->start_time?->format('H:i'),
            'previous_end_time' => $previousTemplate?->end_time?->format('H:i'),
            'new_start_time' => $newTemplate->start_time->format('H:i'),
            'new_end_time' => $newTemplate->end_time->format('H:i'),
            'effective_date' => $this->date($effectiveDate)->toDateString(),
            'reason' => $reason,
            'note' => $note,
            'changed_by' => $actor?->id,
        ]);
    }

    private function date(Carbon|string $value): Carbon
    {
        return $value instanceof Carbon ? $value->copy()->startOfDay() : Carbon::parse($value)->startOfDay();
    }
}

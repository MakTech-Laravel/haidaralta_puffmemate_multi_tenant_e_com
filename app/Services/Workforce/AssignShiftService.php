<?php

namespace App\Services\Workforce;

use App\Enums\DriverAssignmentStatus;
use App\Enums\ShiftAssignmentStatus;
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
use App\Support\DutyWindow;
use App\Support\ShiftDutyCalendar;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignShiftService
{
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

    private function assertAssignableUser(User $user, Branch $branch): void
    {
        if ($user->user_type === UserType::PLATFORM || $user->user_type === UserType::CUSTOMER) {
            abort(404);
        }

        if ($user->user_type === UserType::DRIVER) {
            $isActive = DriverBranchAssignment::query()
                ->where('driver_id', $user->id)
                ->where('branch_id', $branch->id)
                ->where('status', DriverAssignmentStatus::Active)
                ->exists();

            if (! $isActive) {
                throw new InvalidArgumentException('Driver does not have an active assignment for this branch.');
            }

            return;
        }

        if ($user->user_type !== UserType::BRANCH || $user->branch_id !== $branch->id) {
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
        return $assignment->days()
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
            ->all();
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

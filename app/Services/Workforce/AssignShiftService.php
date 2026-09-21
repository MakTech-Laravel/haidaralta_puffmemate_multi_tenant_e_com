<?php

namespace App\Services\Workforce;

use App\Enums\DriverAssignmentStatus;
use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftOwnerType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverRoster;
use App\Models\ShiftRosterPeriod;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftHistory;
use App\Support\DutyWindow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignShiftService
{
    /**
     * @param  array{user_id: int, branch_id: int, shift_template_id: int, shift_roster_period_id: int, start_date: string, end_date: string, days_of_week?: list<int>, reason?: string|null, note?: string|null, effective_date?: string|null}  $data
     */
    public function assign(array $data, ?User $actor = null, ?ShiftOwnerType $expectedOwner = null): UserShiftAssignment
    {
        $user = User::query()->findOrFail((int) $data['user_id']);
        $branch = Branch::query()->findOrFail((int) $data['branch_id']);
        $template = ShiftTemplate::query()->findOrFail((int) $data['shift_template_id']);
        $period = ShiftRosterPeriod::query()->findOrFail((int) $data['shift_roster_period_id']);
        $days = DriverRoster::normalizeDays($data['days_of_week'] ?? null);

        $this->assertAssignableUser($user, $branch);
        $this->assertExpectedOwner($template, $period, $branch, $expectedOwner);
        $this->assertTemplateVisible($template, $branch);
        $this->assertPeriodVisible($period, $branch);
        $this->assertDatesInsidePeriod($data['start_date'], $data['end_date'], $period);

        return DB::transaction(function () use ($user, $branch, $template, $period, $data, $days, $actor): UserShiftAssignment {
            $previous = $this->cancelOverlappingScheduled(
                $user,
                $branch,
                $data['start_date'],
                $data['end_date'],
                $template,
                $days,
            );
            $this->assertNoOverlap($user, $data['start_date'], $data['end_date'], $template, $days);

            $assignment = UserShiftAssignment::query()->create([
                'user_id' => $user->id,
                'branch_id' => $branch->id,
                'shift_template_id' => $template->id,
                'shift_roster_period_id' => $period->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'days_of_week' => $days,
                'status' => ShiftAssignmentStatus::Scheduled,
                'assigned_by' => $actor?->id,
            ]);

            $this->recordHistory(
                $user,
                $branch,
                $previous,
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
     * @param  array{shift_template_id?: int, shift_roster_period_id?: int, start_date?: string, end_date?: string, days_of_week?: list<int>, status?: string, reason?: string|null, note?: string|null, effective_date?: string|null}  $data
     */
    public function change(UserShiftAssignment $assignment, array $data, ?User $actor = null, ?ShiftOwnerType $expectedOwner = null): UserShiftAssignment
    {
        $assignment->loadMissing(['user', 'branch', 'shiftTemplate', 'shiftRosterPeriod']);

        $user = $assignment->user;
        $branch = $assignment->branch;
        $template = isset($data['shift_template_id'])
            ? ShiftTemplate::query()->findOrFail((int) $data['shift_template_id'])
            : $assignment->shiftTemplate;
        $period = isset($data['shift_roster_period_id'])
            ? ShiftRosterPeriod::query()->findOrFail((int) $data['shift_roster_period_id'])
            : $assignment->shiftRosterPeriod;

        if ($user === null || $branch === null || $template === null || $period === null) {
            throw new InvalidArgumentException('Shift assignment is missing required relations.');
        }

        $startDate = $data['start_date'] ?? $assignment->start_date->format('Y-m-d');
        $endDate = $data['end_date'] ?? $assignment->end_date->format('Y-m-d');
        $days = DriverRoster::normalizeDays($data['days_of_week'] ?? $assignment->normalizedDaysOfWeek());
        $status = isset($data['status'])
            ? ShiftAssignmentStatus::from($data['status'])
            : $assignment->status;

        $this->assertExpectedOwner($template, $period, $branch, $expectedOwner);
        $this->assertDatesInsidePeriod($startDate, $endDate, $period);
        $this->assertTemplateVisible($template, $branch);
        $this->assertPeriodVisible($period, $branch);

        if ($status === ShiftAssignmentStatus::Scheduled) {
            $this->assertNoOverlap($user, $startDate, $endDate, $template, $days, $assignment->id);
        }

        return DB::transaction(function () use ($assignment, $user, $branch, $template, $period, $startDate, $endDate, $days, $status, $data, $actor): UserShiftAssignment {
            $previousTemplate = $assignment->shiftTemplate;

            $assignment->update([
                'shift_template_id' => $template->id,
                'shift_roster_period_id' => $period->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days_of_week' => $days,
                'status' => $status->value,
            ]);

            if ($previousTemplate?->id !== $template->id) {
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
        ShiftRosterPeriod $period,
        Branch $branch,
        ?ShiftOwnerType $expectedOwner,
    ): void {
        if ($expectedOwner === null) {
            return;
        }

        if ($expectedOwner === ShiftOwnerType::Platform) {
            abort_unless($template->isPlatformOwned() && $period->isPlatformOwned(), 404);

            return;
        }

        abort_unless(
            $template->isBranchOwned()
                && $period->isBranchOwned()
                && (int) $template->branch_id === $branch->id
                && (int) $period->branch_id === $branch->id,
            404,
        );
    }

    private function assertTemplateVisible(ShiftTemplate $template, Branch $branch): void
    {
        if (! $template->is_active) {
            throw new InvalidArgumentException('This shift template is inactive.');
        }

        $visible = $template->isPlatformOwned() || $template->branch_id === $branch->id;

        if (! $visible) {
            throw new InvalidArgumentException('This shift template is not available for this branch.');
        }
    }

    private function assertPeriodVisible(ShiftRosterPeriod $period, Branch $branch): void
    {
        if (! $period->is_active) {
            throw new InvalidArgumentException('This roster period is inactive.');
        }

        $visible = $period->isPlatformOwned() || $period->branch_id === $branch->id;

        if (! $visible) {
            throw new InvalidArgumentException('This roster period is not available for this branch.');
        }
    }

    private function assertDatesInsidePeriod(string $startDate, string $endDate, ShiftRosterPeriod $period): void
    {
        if ($endDate < $startDate) {
            throw new InvalidArgumentException('Assignment end date must be on or after the start date.');
        }

        $periodStart = $period->start_date->format('Y-m-d');
        $periodEnd = $period->end_date->format('Y-m-d');

        if ($startDate < $periodStart || $endDate > $periodEnd) {
            throw new InvalidArgumentException('Assignment dates must fall inside the roster period.');
        }
    }

    /**
     * @param  list<int>  $days
     */
    private function assertNoOverlap(
        User $user,
        string $startDate,
        string $endDate,
        ShiftTemplate $template,
        array $days,
        ?int $exceptId = null,
    ): void {
        $candidates = UserShiftAssignment::query()
            ->with('shiftTemplate')
            ->where('user_id', $user->id)
            ->where('status', ShiftAssignmentStatus::Scheduled)
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->get();

        $overlaps = $candidates->contains(function (UserShiftAssignment $existing) use ($startDate, $endDate, $template, $days): bool {
            $other = $existing->shiftTemplate;

            if ($other === null) {
                return false;
            }

            return DutyWindow::overlaps(
                $startDate,
                $endDate,
                $template->startTimeValue(),
                $template->endTimeValue(),
                $days,
                $template->is_overnight,
                $existing->start_date->format('Y-m-d'),
                $existing->end_date->format('Y-m-d'),
                $other->startTimeValue(),
                $other->endTimeValue(),
                $existing->normalizedDaysOfWeek(),
                $other->is_overnight,
            );
        });

        if ($overlaps) {
            throw new InvalidArgumentException('This person already has an overlapping scheduled shift in this period.');
        }
    }

    /**
     * @param  list<int>  $days
     */
    private function cancelOverlappingScheduled(
        User $user,
        Branch $branch,
        string $startDate,
        string $endDate,
        ShiftTemplate $template,
        array $days,
    ): ?UserShiftAssignment {
        $previous = UserShiftAssignment::query()
            ->with('shiftTemplate')
            ->where('user_id', $user->id)
            ->where('branch_id', $branch->id)
            ->where('status', ShiftAssignmentStatus::Scheduled)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->orderByDesc('id')
            ->get()
            ->first(function (UserShiftAssignment $existing) use ($startDate, $endDate, $template, $days): bool {
                $other = $existing->shiftTemplate;

                if ($other === null) {
                    return false;
                }

                return DutyWindow::overlaps(
                    $startDate,
                    $endDate,
                    $template->startTimeValue(),
                    $template->endTimeValue(),
                    $days,
                    $template->is_overnight,
                    $existing->start_date->format('Y-m-d'),
                    $existing->end_date->format('Y-m-d'),
                    $other->startTimeValue(),
                    $other->endTimeValue(),
                    $existing->normalizedDaysOfWeek(),
                    $other->is_overnight,
                );
            });

        if ($previous !== null) {
            $previous->update(['status' => ShiftAssignmentStatus::Cancelled->value]);
        }

        return $previous;
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

<?php

namespace App\Services\Workforce;

use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftDayKind;
use App\Models\Branch;
use App\Models\DriverRoster;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserShiftDay;
use App\Support\ShiftDateRuns;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ShiftAssignmentCanonicalizer
{
    /**
     * @param  Collection<int, UserShiftDay>  $days
     */
    public function isCanonical(UserShiftAssignment $assignment, Collection $days): bool
    {
        if ($assignment->status !== ShiftAssignmentStatus::Scheduled || $days->isEmpty()) {
            return false;
        }

        if ($days->contains(fn (UserShiftDay $day): bool => (
            $day->kind !== ShiftDayKind::Scheduled
            || $day->shift_template_id !== $assignment->shift_template_id
        ))) {
            return false;
        }

        $dates = $this->dayDates($days);
        $runs = ShiftDateRuns::partition($dates);

        return count($runs) === 1
            && $runs[0]['start_date'] === $assignment->start_date->toDateString()
            && $runs[0]['end_date'] === $assignment->end_date->toDateString();
    }

    /**
     * Normalize one assignment after selected day rows have been removed.
     *
     * @return list<int> Shift template IDs whose canonical groups may now be mergeable.
     */
    public function normalizeAffected(UserShiftAssignment $assignment, bool $wasCanonical): array
    {
        /** @var EloquentCollection<int, UserShiftDay> $remainingDays */
        $remainingDays = $assignment->days()
            ->orderBy('duty_on')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($wasCanonical) {
            return $this->normalizeCanonicalAssignment($assignment, $remainingDays);
        }

        return $this->archiveAndRebuildLegacyAssignment($assignment, $remainingDays);
    }

    /**
     * @param  list<string>  $dates
     * @return list<UserShiftAssignment>
     */
    public function createCanonicalGroups(
        User $user,
        Branch $branch,
        ShiftTemplate $template,
        array $dates,
        ?User $actor = null,
    ): array {
        $assignments = [];

        foreach (ShiftDateRuns::partition($dates) as $run) {
            $assignment = $this->createHeader(
                userId: $user->id,
                branchId: $branch->id,
                templateId: $template->id,
                startDate: $run['start_date'],
                endDate: $run['end_date'],
                assignedBy: $actor?->id,
            );

            foreach ($run['dates'] as $date) {
                UserShiftDay::query()->create([
                    'user_shift_assignment_id' => $assignment->id,
                    'user_id' => $user->id,
                    'branch_id' => $branch->id,
                    'duty_on' => $date,
                    'shift_template_id' => $template->id,
                    'kind' => ShiftDayKind::Scheduled,
                ]);
            }

            $assignments[] = $assignment;
        }

        return $assignments;
    }

    public function mergeAdjacent(User $user, Branch $branch, int $templateId): void
    {
        /** @var EloquentCollection<int, UserShiftAssignment> $assignments */
        $assignments = UserShiftAssignment::query()
            ->where('user_id', $user->id)
            ->where('branch_id', $branch->id)
            ->where('shift_template_id', $templateId)
            ->where('status', ShiftAssignmentStatus::Scheduled)
            ->orderBy('start_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($assignments->count() < 2) {
            return;
        }

        /** @var EloquentCollection<int, UserShiftDay> $days */
        $days = UserShiftDay::query()
            ->whereIn('user_shift_assignment_id', $assignments->modelKeys())
            ->orderBy('duty_on')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $daysByAssignment = $days->groupBy('user_shift_assignment_id');

        $canonicalAssignments = $assignments
            ->filter(fn (UserShiftAssignment $assignment): bool => $this->isCanonical(
                $assignment,
                $daysByAssignment->get($assignment->id, collect()),
            ))
            ->values();

        $group = collect();
        $groupEnd = null;

        foreach ($canonicalAssignments as $assignment) {
            $startsNewGroup = $groupEnd !== null
                && $assignment->start_date->copy()->subDay()->gt($groupEnd);

            if ($startsNewGroup) {
                $this->mergeGroup($group, $daysByAssignment);
                $group = collect();
            }

            $group->push($assignment);
            $groupEnd = $groupEnd === null || $assignment->end_date->gt($groupEnd)
                ? $assignment->end_date->copy()
                : $groupEnd;
        }

        $this->mergeGroup($group, $daysByAssignment);
    }

    /**
     * @param  Collection<int, UserShiftDay>  $remainingDays
     * @return list<int>
     */
    private function normalizeCanonicalAssignment(
        UserShiftAssignment $assignment,
        Collection $remainingDays,
    ): array {
        $runs = ShiftDateRuns::partition($this->dayDates($remainingDays));

        if ($runs === []) {
            $assignment->update(['status' => ShiftAssignmentStatus::Cancelled]);

            return [$assignment->shift_template_id];
        }

        $firstRun = array_shift($runs);
        $assignment->update([
            'start_date' => $firstRun['start_date'],
            'end_date' => $firstRun['end_date'],
            'days_of_week' => DriverRoster::DAYS_ALL,
            'status' => ShiftAssignmentStatus::Scheduled,
        ]);

        $daysByDate = $remainingDays->keyBy(
            fn (UserShiftDay $day): string => $day->duty_on->toDateString(),
        );

        foreach ($runs as $run) {
            $splitAssignment = $this->createHeader(
                userId: $assignment->user_id,
                branchId: $assignment->branch_id,
                templateId: $assignment->shift_template_id,
                startDate: $run['start_date'],
                endDate: $run['end_date'],
                assignedBy: $assignment->assigned_by,
            );

            foreach ($run['dates'] as $date) {
                $daysByDate->get($date)?->update([
                    'user_shift_assignment_id' => $splitAssignment->id,
                ]);
            }
        }

        return [$assignment->shift_template_id];
    }

    /**
     * @param  Collection<int, UserShiftDay>  $remainingDays
     * @return list<int>
     */
    private function archiveAndRebuildLegacyAssignment(
        UserShiftAssignment $assignment,
        Collection $remainingDays,
    ): array {
        $assignment->update(['status' => ShiftAssignmentStatus::Cancelled]);

        $onDutyDays = $remainingDays->filter(
            fn (UserShiftDay $day): bool => $day->kind->isOnDuty() && $day->shift_template_id !== null,
        );
        $templateIds = [];

        foreach ($onDutyDays->groupBy('shift_template_id') as $templateId => $templateDays) {
            $templateId = (int) $templateId;
            $templateIds[] = $templateId;
            $daysByDate = $templateDays->keyBy(
                fn (UserShiftDay $day): string => $day->duty_on->toDateString(),
            );

            foreach (ShiftDateRuns::partition($this->dayDates($templateDays)) as $run) {
                $rebuiltAssignment = $this->createHeader(
                    userId: $assignment->user_id,
                    branchId: $assignment->branch_id,
                    templateId: $templateId,
                    startDate: $run['start_date'],
                    endDate: $run['end_date'],
                    assignedBy: $assignment->assigned_by,
                );

                foreach ($run['dates'] as $date) {
                    $daysByDate->get($date)?->update([
                        'user_shift_assignment_id' => $rebuiltAssignment->id,
                        'kind' => ShiftDayKind::Scheduled,
                    ]);
                }
            }
        }

        sort($templateIds);

        return array_values(array_unique($templateIds));
    }

    /**
     * @param  Collection<int, UserShiftAssignment>  $assignments
     * @param  Collection<int|string, EloquentCollection<int, UserShiftDay>>  $daysByAssignment
     */
    private function mergeGroup(Collection $assignments, Collection $daysByAssignment): void
    {
        if ($assignments->count() < 2) {
            return;
        }

        /** @var UserShiftAssignment $primary */
        $primary = $assignments->first();
        $endDate = $assignments->max(
            fn (UserShiftAssignment $assignment): string => $assignment->end_date->toDateString(),
        );

        foreach ($assignments->skip(1) as $assignment) {
            foreach ($daysByAssignment->get($assignment->id, collect()) as $day) {
                $day->update(['user_shift_assignment_id' => $primary->id]);
            }

            $assignment->update(['status' => ShiftAssignmentStatus::Cancelled]);
        }

        $primary->update([
            'start_date' => $primary->start_date->toDateString(),
            'end_date' => $endDate,
            'days_of_week' => DriverRoster::DAYS_ALL,
            'status' => ShiftAssignmentStatus::Scheduled,
        ]);
    }

    private function createHeader(
        int $userId,
        int $branchId,
        int $templateId,
        string $startDate,
        string $endDate,
        ?int $assignedBy,
    ): UserShiftAssignment {
        return UserShiftAssignment::query()->create([
            'user_id' => $userId,
            'branch_id' => $branchId,
            'shift_template_id' => $templateId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_of_week' => DriverRoster::DAYS_ALL,
            'status' => ShiftAssignmentStatus::Scheduled,
            'assigned_by' => $assignedBy,
        ]);
    }

    /**
     * @param  Collection<int, UserShiftDay>  $days
     * @return list<string>
     */
    private function dayDates(Collection $days): array
    {
        return array_values($days
            ->map(fn (UserShiftDay $day): string => $day->duty_on->toDateString())
            ->values()
            ->all());
    }
}

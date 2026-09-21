<?php

namespace App\Console\Commands;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverRosterStatus;
use App\Enums\ShiftAssignmentStatus;
use App\Enums\ShiftOwnerType;
use App\Enums\TransferSubjectType;
use App\Enums\TransferType;
use App\Models\DriverBranchAssignment;
use App\Models\DriverRoster;
use App\Models\ShiftRosterPeriod;
use App\Models\ShiftTemplate;
use App\Models\UserShiftAssignment;
use App\Models\UserTransferHistory;
use App\Services\Workforce\TransferEmployeeService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('workforce:backfill-transfers-and-shifts')]
#[Description('Idempotently backfill transfer dates/history and migrate scheduled driver rosters into shift tables')]
class BackfillTransfersAndShiftsCommand extends Command
{
    public function handle(): int
    {
        $assignments = $this->backfillAssignmentDates();
        $transfers = $this->backfillInferredDriverTransfers();
        $shifts = $this->backfillScheduledRosters();

        $this->components->info("Backfilled {$assignments} assignment date(s), {$transfers} transfer(s), and {$shifts} shift assignment(s).");

        return self::SUCCESS;
    }

    private function backfillAssignmentDates(): int
    {
        $updated = 0;

        DriverBranchAssignment::query()
            ->whereNull('started_at')
            ->orderBy('id')
            ->each(function (DriverBranchAssignment $assignment) use (&$updated): void {
                $assignment->started_at = $assignment->created_at?->toDateString() ?? now()->toDateString();

                if (
                    $assignment->ended_at === null
                    && in_array($assignment->status, [DriverAssignmentStatus::Inactive, DriverAssignmentStatus::Suspended], true)
                ) {
                    $assignment->ended_at = $assignment->updated_at?->toDateString() ?? $assignment->started_at;
                }

                $assignment->save();
                $updated++;
            });

        return $updated;
    }

    private function backfillInferredDriverTransfers(): int
    {
        $created = 0;
        $driverIds = DriverBranchAssignment::query()
            ->select('driver_id')
            ->groupBy('driver_id')
            ->pluck('driver_id');

        foreach ($driverIds as $driverId) {
            $rows = DriverBranchAssignment::query()
                ->where('driver_id', $driverId)
                ->orderBy('id')
                ->get();

            $inactive = $rows->filter(
                fn (DriverBranchAssignment $row): bool => $row->status !== DriverAssignmentStatus::Active,
            );
            $active = $rows->filter(
                fn (DriverBranchAssignment $row): bool => $row->status === DriverAssignmentStatus::Active,
            );

            if ($inactive->count() !== 1 || $active->count() !== 1) {
                continue;
            }

            $from = $inactive->first();
            $to = $active->first();

            if ($from === null || $to === null || $from->branch_id === $to->branch_id) {
                continue;
            }

            $exists = UserTransferHistory::query()
                ->where('user_id', $driverId)
                ->where('subject_type', TransferSubjectType::Driver)
                ->where('from_branch_id', $from->branch_id)
                ->where('to_branch_id', $to->branch_id)
                ->exists();

            if ($exists) {
                continue;
            }

            UserTransferHistory::query()->create([
                'user_id' => $driverId,
                'subject_type' => TransferSubjectType::Driver,
                'transfer_type' => TransferType::BranchChange,
                'from_branch_id' => $from->branch_id,
                'to_branch_id' => $to->branch_id,
                'from_role' => TransferEmployeeService::DRIVER_ROLE,
                'to_role' => TransferEmployeeService::DRIVER_ROLE,
                'effective_date' => $to->started_at?->toDateString() ?? $to->created_at?->toDateString() ?? now()->toDateString(),
                'reason' => 'Migrated from previous branch assignment.',
                'transferred_by' => $to->assigned_by,
            ]);

            $created++;
        }

        return $created;
    }

    private function backfillScheduledRosters(): int
    {
        $created = 0;

        DriverRoster::query()
            ->with('branch:id,name')
            ->where('status', DriverRosterStatus::Scheduled)
            ->orderBy('id')
            ->each(function (DriverRoster $roster) use (&$created): void {
                $alreadyMigrated = UserShiftAssignment::query()
                    ->where('user_id', $roster->driver_id)
                    ->where('branch_id', $roster->branch_id)
                    ->whereDate('start_date', $roster->start_date?->toDateString())
                    ->whereDate('end_date', $roster->end_date?->toDateString())
                    ->where('status', ShiftAssignmentStatus::Scheduled)
                    ->exists();

                if ($alreadyMigrated) {
                    return;
                }

                DB::transaction(function () use ($roster, &$created): void {
                    $startTime = $roster->start_time?->format('H:i') ?? '09:00';
                    $endTime = $roster->end_time?->format('H:i') ?? '17:00';
                    $startDate = $roster->start_date?->toDateString() ?? now()->toDateString();
                    $endDate = $roster->end_date?->toDateString() ?? $startDate;
                    $templateName = "Migrated {$startTime}–{$endTime}";

                    $template = ShiftTemplate::query()->firstOrCreate(
                        [
                            'owner_type' => ShiftOwnerType::Branch->value,
                            'branch_id' => $roster->branch_id,
                            'name' => $templateName,
                        ],
                        [
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'is_overnight' => false,
                            'is_active' => true,
                            'created_by' => null,
                        ],
                    );

                    $periodName = "Migrated {$roster->branch?->name} {$startDate}–{$endDate}";

                    $period = ShiftRosterPeriod::query()->firstOrCreate(
                        [
                            'owner_type' => ShiftOwnerType::Branch->value,
                            'branch_id' => $roster->branch_id,
                            'name' => $periodName,
                        ],
                        [
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'is_active' => true,
                            'created_by' => null,
                        ],
                    );

                    UserShiftAssignment::query()->create([
                        'user_id' => $roster->driver_id,
                        'branch_id' => $roster->branch_id,
                        'shift_template_id' => $template->id,
                        'shift_roster_period_id' => $period->id,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'days_of_week' => $roster->normalizedDaysOfWeek(),
                        'status' => ShiftAssignmentStatus::Scheduled,
                        'assigned_by' => null,
                    ]);

                    $created++;
                });
            });

        return $created;
    }
}

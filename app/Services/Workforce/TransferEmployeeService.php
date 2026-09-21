<?php

namespace App\Services\Workforce;

use App\Enums\DriverAssignmentStatus;
use App\Enums\TransferSubjectType;
use App\Enums\TransferType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\User;
use App\Models\UserTransferHistory;
use App\Services\Driver\DriverService;
use App\Support\SuperAdmin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

class TransferEmployeeService
{
    public const DRIVER_ROLE = 'driver';

    public function __construct(private DriverService $drivers) {}

    public function transferDriverBranch(
        User $driver,
        Branch $to,
        Carbon|string $effectiveDate,
        string $reason,
        User $actor,
    ): UserTransferHistory {
        $this->assertDriver($driver);

        $from = $this->currentActiveBranch($driver);

        if ($from === null) {
            throw new InvalidArgumentException('Driver has no active branch to transfer from.');
        }

        if ($from->id === $to->id) {
            throw new InvalidArgumentException('Driver is already assigned to this branch.');
        }

        $effective = $this->date($effectiveDate);

        return DB::transaction(function () use ($driver, $from, $to, $effective, $reason, $actor): UserTransferHistory {
            $this->drivers->assignToBranch(
                $driver,
                $to,
                assignedBy: $actor,
                status: DriverAssignmentStatus::Active,
            );

            $this->stampAssignmentDates($driver, $to, $effective);

            return UserTransferHistory::query()->create([
                'user_id' => $driver->id,
                'subject_type' => TransferSubjectType::Driver,
                'transfer_type' => TransferType::BranchChange,
                'from_branch_id' => $from->id,
                'to_branch_id' => $to->id,
                'from_role' => self::DRIVER_ROLE,
                'to_role' => self::DRIVER_ROLE,
                'effective_date' => $effective->toDateString(),
                'reason' => $reason,
                'transferred_by' => $actor->id,
            ]);
        });
    }

    /**
     * @param  list<int>  $branchIds
     */
    public function syncDriverBranches(
        User $driver,
        array $branchIds,
        User $actor,
        ?string $reason = null,
        Carbon|string|null $effectiveDate = null,
    ): void {
        $this->assertDriver($driver);

        $branchIds = array_values(array_unique(array_map('intval', $branchIds)));
        $currentActiveIds = $this->activeBranchIds($driver);
        $effective = $this->date($effectiveDate ?? now());
        $reason = filled($reason) ? $reason : 'Branch assignment updated.';
        $added = array_values(array_diff($branchIds, $currentActiveIds));
        $removed = array_values(array_diff($currentActiveIds, $branchIds));

        if ($added === [] && $removed === []) {
            return;
        }

        if (count($currentActiveIds) === 1 && count($added) === 1 && count($branchIds) === 1) {
            $this->transferDriverBranch(
                $driver,
                Branch::query()->findOrFail($added[0]),
                $effective,
                $reason,
                $actor,
            );

            return;
        }

        $this->drivers->syncBranchAssignments(
            $driver,
            $branchIds,
            DriverAssignmentStatus::Active,
            $actor,
        );

        foreach ($added as $branchId) {
            $to = Branch::query()->findOrFail($branchId);
            $this->stampAssignmentDates($driver, $to, $effective);

            $fromId = $removed[0] ?? ($currentActiveIds[0] ?? null);

            if ($fromId === null || $fromId === $branchId) {
                continue;
            }

            UserTransferHistory::query()->create([
                'user_id' => $driver->id,
                'subject_type' => TransferSubjectType::Driver,
                'transfer_type' => TransferType::BranchChange,
                'from_branch_id' => $fromId,
                'to_branch_id' => $branchId,
                'from_role' => self::DRIVER_ROLE,
                'to_role' => self::DRIVER_ROLE,
                'effective_date' => $effective->toDateString(),
                'reason' => $reason,
                'transferred_by' => $actor->id,
            ]);
        }

        $this->deactivateUnselected($driver, $branchIds, $effective);
    }

    /**
     * @param  list<string>  $roles
     */
    public function transferBranchStaff(
        User $staff,
        Branch $to,
        array $roles,
        Carbon|string $effectiveDate,
        string $reason,
        User $actor,
    ): UserTransferHistory {
        $this->assertBranchStaff($staff);

        $fromBranchId = $staff->branch_id;

        if ($fromBranchId === null) {
            throw new InvalidArgumentException('Staff member has no home branch to transfer from.');
        }

        $fromBranch = Branch::query()->findOrFail($fromBranchId);
        $fromRoles = $this->roleNames($staff, $fromBranch);
        $fromRole = $fromRoles[0] ?? 'staff';
        $toRoles = array_values(array_unique($roles));
        $toRole = $toRoles[0] ?? $fromRole;
        $sameBranch = $fromBranch->id === $to->id;
        $sameRoles = $this->sameRoleSet($fromRoles, $toRoles === [] ? $fromRoles : $toRoles);

        if ($sameBranch && $sameRoles) {
            throw new InvalidArgumentException('Choose a different branch or a different role.');
        }

        if ($sameBranch && SuperAdmin::isLastInTeam($staff, $fromBranch->id) && ! in_array('super-admin', $toRoles, true)) {
            throw new InvalidArgumentException('Cannot remove the last super-admin for this branch.');
        }

        if (! $sameBranch && SuperAdmin::isLastInTeam($staff, $fromBranch->id)) {
            throw new InvalidArgumentException('Cannot transfer the last super-admin away from this branch.');
        }

        $effective = $this->date($effectiveDate);
        $destinationRoles = $toRoles === [] ? $fromRoles : $toRoles;
        $transferType = $sameBranch ? TransferType::RoleChange : TransferType::BranchChange;

        return DB::transaction(function () use ($staff, $fromBranch, $to, $fromRole, $toRole, $destinationRoles, $transferType, $effective, $reason, $actor): UserTransferHistory {
            $this->useTeam($fromBranch->id);
            $staff->syncRoles([]);

            $staff->update(['branch_id' => $to->id]);
            $staff->unsetRelation('roles');

            $this->useTeam($to->id);
            $staff->syncRoles($destinationRoles);
            $this->clearTeam();

            return UserTransferHistory::query()->create([
                'user_id' => $staff->id,
                'subject_type' => TransferSubjectType::BranchStaff,
                'transfer_type' => $transferType,
                'from_branch_id' => $fromBranch->id,
                'to_branch_id' => $to->id,
                'from_role' => $fromRole,
                'to_role' => $toRole,
                'effective_date' => $effective->toDateString(),
                'reason' => $reason,
                'transferred_by' => $actor->id,
            ]);
        });
    }

    private function assertDriver(User $driver): void
    {
        if ($driver->user_type !== UserType::DRIVER) {
            abort(404);
        }
    }

    private function assertBranchStaff(User $staff): void
    {
        if ($staff->user_type !== UserType::BRANCH) {
            abort(404);
        }
    }

    private function currentActiveBranch(User $driver): ?Branch
    {
        $assignment = DriverBranchAssignment::query()
            ->with('branch')
            ->where('driver_id', $driver->id)
            ->where('status', DriverAssignmentStatus::Active)
            ->orderByDesc('updated_at')
            ->first();

        return $assignment?->branch;
    }

    /**
     * @return list<int>
     */
    private function activeBranchIds(User $driver): array
    {
        return DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('status', DriverAssignmentStatus::Active)
            ->orderBy('id')
            ->pluck('branch_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    private function stampAssignmentDates(User $driver, Branch $to, Carbon $effective): void
    {
        DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('status', DriverAssignmentStatus::Active)
            ->where('branch_id', $to->id)
            ->update([
                'started_at' => $effective->toDateString(),
                'ended_at' => null,
            ]);

        DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('branch_id', '!=', $to->id)
            ->where('status', '!=', DriverAssignmentStatus::Active->value)
            ->whereNull('ended_at')
            ->update(['ended_at' => $effective->toDateString()]);
    }

    /**
     * @param  list<int>  $keepBranchIds
     */
    private function deactivateUnselected(User $driver, array $keepBranchIds, Carbon $effective): void
    {
        DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->whereNotIn('branch_id', $keepBranchIds === [] ? [0] : $keepBranchIds)
            ->whereNull('ended_at')
            ->update(['ended_at' => $effective->toDateString()]);
    }

    /**
     * @return list<string>
     */
    private function roleNames(User $user, Branch $branch): array
    {
        $this->useTeam($branch->id);
        $roles = $user->getRoleNames()->values()->all();
        $this->clearTeam();

        return $roles;
    }

    /**
     * @param  list<string>  $left
     * @param  list<string>  $right
     */
    private function sameRoleSet(array $left, array $right): bool
    {
        $normalize = fn (array $roles): array => collect($roles)->sort()->values()->all();

        return $normalize($left) === $normalize($right);
    }

    private function date(Carbon|string $value): Carbon
    {
        return $value instanceof Carbon ? $value->copy()->startOfDay() : Carbon::parse($value)->startOfDay();
    }

    private function useTeam(int $teamId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);
    }

    private function clearTeam(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}

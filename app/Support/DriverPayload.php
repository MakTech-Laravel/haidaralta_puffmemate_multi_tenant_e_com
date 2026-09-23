<?php

namespace App\Support;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserShiftAssignment;
use App\Models\UserTransferHistory;

final class DriverPayload
{
    public static function resolveDriver(User $user): User
    {
        if ($user->user_type !== UserType::DRIVER) {
            abort(404);
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(User $driver, ?Branch $scopedBranch = null): array
    {
        $driver->loadMissing([
            'driverProfile',
            'driverBranchAssignments.branch:id,name,slug,status',
            'shiftAssignments' => function ($query) use ($scopedBranch): void {
                $query->with(['shiftTemplate', 'days.shiftTemplate', 'branch:id,name,slug'])
                    ->orderByDesc('start_date');

                if ($scopedBranch !== null) {
                    $query->where('branch_id', $scopedBranch->id);
                }
            },
            'transferHistories' => function ($query) use ($scopedBranch): void {
                $query->with(['fromBranch:id,name,slug', 'toBranch:id,name,slug', 'transferredBy:id,name'])
                    ->orderByDesc('effective_date')
                    ->orderByDesc('id');

                if ($scopedBranch !== null) {
                    $query->where(function ($scope) use ($scopedBranch): void {
                        $scope->where('from_branch_id', $scopedBranch->id)
                            ->orWhere('to_branch_id', $scopedBranch->id);
                    });
                }
            },
        ]);

        $profile = $driver->driverProfile;
        $effective = DriverAvailability::effectivePayload($driver);

        $assignments = $driver->driverBranchAssignments
            ->map(fn ($assignment): array => [
                'id' => $assignment->id,
                'branch_id' => $assignment->branch_id,
                'branch_name' => $assignment->branch?->name,
                'branch_slug' => $assignment->branch?->slug,
                'status' => $assignment->status?->value,
                'status_label' => $assignment->status?->label(),
            ])
            ->values()
            ->all();

        if ($scopedBranch !== null) {
            $assignments = collect($assignments)
                ->filter(fn (array $row): bool => $row['branch_id'] === $scopedBranch->id)
                ->values()
                ->all();
        }

        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
            'email' => $driver->email,
            'is_active' => (bool) $driver->is_active,
            'license_number' => $profile?->license_number,
            'vehicle_type' => $profile?->vehicle_type?->value,
            'vehicle_type_label' => $profile?->vehicle_type?->label(),
            'availability_status' => $profile?->availability_status?->value,
            'availability_status_label' => $profile?->availability_status?->label(),
            'effective_availability' => $effective['value'],
            'effective_availability_label' => $effective['label'],
            'notes' => $profile?->notes,
            'address' => $profile?->address,
            'avatar' => $driver->avatar,
            'avatar_url' => MediaUrl::resolve($driver->avatar, fallback: null),
            'vehicle_image_path' => $profile?->vehicle_image_path,
            'vehicle_image_url' => MediaUrl::resolve($profile?->vehicle_image_path, fallback: null),
            'assignments' => $assignments,
            'branch_ids' => $driver->driverBranchAssignments
                ->filter(fn ($assignment): bool => $assignment->status === DriverAssignmentStatus::Active)
                ->pluck('branch_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all(),
            'current_duty' => WorkforcePayload::currentDuty($driver),
            'shift_assignments' => $driver->shiftAssignments
                ->map(fn (UserShiftAssignment $assignment): array => WorkforcePayload::assignment($assignment))
                ->values()
                ->all(),
            'transfers' => $driver->transferHistories
                ->map(fn (UserTransferHistory $history): array => WorkforcePayload::transfer($history))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function vehicleTypeOptions(): array
    {
        return collect(DriverVehicleType::cases())
            ->map(fn (DriverVehicleType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function availabilityOptions(): array
    {
        return collect(DriverAvailabilityStatus::cases())
            ->map(fn (DriverAvailabilityStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function assignmentStatusOptions(): array
    {
        return collect(DriverAssignmentStatus::cases())
            ->map(fn (DriverAssignmentStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, slug: string, code: ?string, status: bool}>
     */
    public static function branchOptions(): array
    {
        return Branch::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'code', 'status'])
            ->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'code' => $branch->code,
                'status' => (bool) $branch->status,
            ])
            ->all();
    }
}

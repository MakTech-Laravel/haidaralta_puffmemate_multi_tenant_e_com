<?php

namespace App\Support;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverRosterStatus;
use App\Enums\DriverVehicleType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverRoster;
use App\Models\User;

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
            'driverRosters' => function ($query) use ($scopedBranch): void {
                $query->with('branch:id,name,slug')->orderByDesc('start_date')->orderBy('start_time');

                if ($scopedBranch !== null) {
                    $query->where('branch_id', $scopedBranch->id);
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
            'avatar_path' => $profile?->avatar_path,
            'avatar_url' => MediaUrl::resolve($profile?->avatar_path, fallback: null),
            'vehicle_image_path' => $profile?->vehicle_image_path,
            'vehicle_image_url' => MediaUrl::resolve($profile?->vehicle_image_path, fallback: null),
            'assignments' => $assignments,
            'branch_ids' => $driver->driverBranchAssignments
                ->filter(fn ($assignment): bool => $assignment->status === DriverAssignmentStatus::Active)
                ->pluck('branch_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all(),
            'rosters' => $driver->driverRosters
                ->map(function (DriverRoster $roster): array {
                    $startDate = $roster->start_date?->format('Y-m-d');
                    $endDate = $roster->end_date?->format('Y-m-d');
                    $days = $roster->normalizedDaysOfWeek();
                    $periodLabel = $startDate === $endDate
                        ? $roster->start_date?->format('j M Y')
                        : $roster->start_date?->format('j M Y').' – '.$roster->end_date?->format('j M Y');

                    return [
                        'id' => $roster->id,
                        'branch_id' => $roster->branch_id,
                        'branch_name' => $roster->branch?->name,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'start_time' => $roster->start_time?->format('H:i'),
                        'end_time' => $roster->end_time?->format('H:i'),
                        'days_of_week' => $days,
                        'days_label' => DriverRoster::daysLabel($days),
                        'period_label' => $periodLabel,
                        'status' => $roster->status?->value,
                        'status_label' => $roster->status?->label(),
                    ];
                })
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
     * @return list<array{value: string, label: string}>
     */
    public static function rosterStatusOptions(): array
    {
        return collect(DriverRosterStatus::cases())
            ->map(fn (DriverRosterStatus $status): array => [
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

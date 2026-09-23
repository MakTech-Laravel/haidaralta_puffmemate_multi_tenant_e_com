<?php

namespace App\Services\Driver;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\User;
use App\Support\PublicDiskMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DriverService
{
    public const ONE_ACTIVE_BRANCH_MESSAGE = 'A driver can only be assigned to one branch at a time.';

    /**
     * @param  array{name: string, phone: string, email?: string|null, password: string, license_number?: string|null, vehicle_type: string, availability_status?: string, notes?: string|null, address?: string|null, branch_ids?: list<int>}  $data
     */
    public function create(
        array $data,
        ?User $assignedBy = null,
        ?Branch $autoAssignBranch = null,
        ?UploadedFile $avatar = null,
        ?UploadedFile $vehicleImage = null,
    ): User {
        $branchIds = array_values(array_unique(array_map('intval', $data['branch_ids'] ?? [])));

        if ($branchIds === [] && $autoAssignBranch === null) {
            throw new InvalidArgumentException('At least one branch must be assigned to the driver.');
        }

        if ($branchIds === [] && $autoAssignBranch !== null) {
            $branchIds = [$autoAssignBranch->id];
        }

        if ($this->restrictsToSingleActiveBranch() && count($branchIds) > 1) {
            throw new InvalidArgumentException(self::ONE_ACTIVE_BRANCH_MESSAGE);
        }

        return DB::transaction(function () use ($data, $assignedBy, $branchIds, $avatar, $vehicleImage): User {
            $driver = User::query()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'user_type' => UserType::DRIVER,
                'branch_id' => null,
                'is_active' => $data['is_active'] ?? true,
                'phone_verified_at' => now(),
                'avatar' => PublicDiskMedia::store($avatar, PublicDiskMedia::USER_AVATARS),
            ]);

            DriverProfile::query()->create([
                'user_id' => $driver->id,
                'license_number' => $data['license_number'] ?? null,
                'vehicle_type' => $data['vehicle_type'],
                'availability_status' => $data['availability_status'] ?? DriverAvailabilityStatus::Offline->value,
                'notes' => $data['notes'] ?? null,
                'address' => $data['address'] ?? null,
                'vehicle_image_path' => PublicDiskMedia::store($vehicleImage, PublicDiskMedia::DRIVER_VEHICLES),
            ]);

            $this->syncBranchAssignments($driver, $branchIds, DriverAssignmentStatus::Active, $assignedBy);

            return $driver->refresh();
        });
    }

    /**
     * @param  array{name?: string, phone?: string, email?: string|null, password?: string|null, is_active?: bool, license_number?: string|null, vehicle_type?: string, availability_status?: string, notes?: string|null, address?: string|null, remove_avatar?: bool, remove_vehicle_image?: bool}  $data
     */
    public function update(
        User $driver,
        array $data,
        ?UploadedFile $avatar = null,
        ?UploadedFile $vehicleImage = null,
    ): User {
        $this->assertIsDriver($driver);

        return DB::transaction(function () use ($driver, $data, $avatar, $vehicleImage): User {
            $userPayload = array_filter([
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? null,
            ], fn (mixed $value): bool => $value !== null);

            if (array_key_exists('email', $data)) {
                $userPayload['email'] = $data['email'];
            }

            if (filled($data['password'] ?? null)) {
                $userPayload['password'] = $data['password'];
            }

            $userPayload['avatar'] = PublicDiskMedia::replace(
                $driver->avatar,
                $avatar,
                PublicDiskMedia::USER_AVATARS,
                (bool) ($data['remove_avatar'] ?? false),
            );

            $driver->update($userPayload);

            $profile = $driver->driverProfile;

            if ($profile === null) {
                throw new InvalidArgumentException('Driver profile does not exist.');
            }

            $vehiclePath = $profile->vehicle_image_path;

            if (($data['remove_vehicle_image'] ?? false) === true) {
                PublicDiskMedia::delete($vehiclePath);
                $vehiclePath = null;
            } elseif ($vehicleImage !== null) {
                PublicDiskMedia::delete($vehiclePath);
                $vehiclePath = PublicDiskMedia::store($vehicleImage, PublicDiskMedia::DRIVER_VEHICLES);
            }

            $profilePayload = [
                'vehicle_image_path' => $vehiclePath,
            ];

            foreach (['license_number', 'vehicle_type', 'availability_status', 'notes', 'address'] as $field) {
                if (array_key_exists($field, $data)) {
                    $profilePayload[$field] = $data[$field];
                }
            }

            $profile->update($profilePayload);

            return $driver->refresh();
        });
    }

    /**
     * @param  list<int>  $branchIds
     */
    public function syncBranchAssignments(
        User $driver,
        array $branchIds,
        DriverAssignmentStatus $status,
        ?User $assignedBy = null,
    ): void {
        $this->assertIsDriver($driver);

        $branchIds = array_values(array_unique(array_map('intval', $branchIds)));

        if (
            $this->restrictsToSingleActiveBranch()
            && $status === DriverAssignmentStatus::Active
            && count($branchIds) > 1
        ) {
            throw new InvalidArgumentException(self::ONE_ACTIVE_BRANCH_MESSAGE);
        }

        DB::transaction(function () use ($driver, $branchIds, $status, $assignedBy): void {
            if ($this->restrictsToSingleActiveBranch() && $status === DriverAssignmentStatus::Active) {
                $this->deactivateOtherActiveAssignments($driver, $branchIds[0] ?? null);
            }

            foreach ($branchIds as $branchId) {
                $assignment = DriverBranchAssignment::query()->updateOrCreate(
                    ['driver_id' => $driver->id, 'branch_id' => $branchId],
                    ['status' => $status->value, 'assigned_by' => $assignedBy?->id],
                );

                if ($status === DriverAssignmentStatus::Active && $assignment->started_at === null) {
                    $assignment->update(['started_at' => now()->toDateString(), 'ended_at' => null]);
                }
            }

            DriverBranchAssignment::query()
                ->where('driver_id', $driver->id)
                ->whereNotIn('branch_id', $branchIds === [] ? [0] : $branchIds)
                ->whereNull('ended_at')
                ->update([
                    'status' => DriverAssignmentStatus::Inactive->value,
                    'ended_at' => now()->toDateString(),
                ]);
        });
    }

    public function assignToBranch(
        User $driver,
        Branch $branch,
        ?User $assignedBy = null,
        DriverAssignmentStatus $status = DriverAssignmentStatus::Active,
    ): DriverBranchAssignment {
        $this->assertIsDriver($driver);

        return DB::transaction(function () use ($driver, $branch, $assignedBy, $status): DriverBranchAssignment {
            if ($this->restrictsToSingleActiveBranch() && $status === DriverAssignmentStatus::Active) {
                $this->deactivateOtherActiveAssignments($driver, $branch->id);
            }

            $assignment = DriverBranchAssignment::query()->updateOrCreate(
                ['driver_id' => $driver->id, 'branch_id' => $branch->id],
                ['status' => $status->value, 'assigned_by' => $assignedBy?->id],
            );

            if ($status === DriverAssignmentStatus::Active) {
                $assignment->forceFill([
                    'started_at' => $assignment->started_at ?? now()->toDateString(),
                    'ended_at' => null,
                ])->save();
            }

            return $assignment->refresh();
        });
    }

    public function unassignFromBranch(User $driver, Branch $branch): void
    {
        $this->setAssignmentStatus($driver, $branch, DriverAssignmentStatus::Inactive);
    }

    public function setAssignmentStatus(User $driver, Branch $branch, DriverAssignmentStatus $status): DriverBranchAssignment
    {
        $this->assertIsDriver($driver);

        return DB::transaction(function () use ($driver, $branch, $status): DriverBranchAssignment {
            $assignment = DriverBranchAssignment::query()
                ->where('driver_id', $driver->id)
                ->where('branch_id', $branch->id)
                ->first();

            if ($assignment === null) {
                throw new InvalidArgumentException('Driver is not assigned to this branch.');
            }

            if ($this->restrictsToSingleActiveBranch() && $status === DriverAssignmentStatus::Active) {
                $this->deactivateOtherActiveAssignments($driver, $branch->id);
            }

            $assignment->update(['status' => $status->value]);

            return $assignment->refresh();
        });
    }

    private function restrictsToSingleActiveBranch(): bool
    {
        return (bool) config('drivers.single_active_branch');
    }

    private function deactivateOtherActiveAssignments(User $driver, ?int $exceptBranchId = null): void
    {
        DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('status', DriverAssignmentStatus::Active->value)
            ->when($exceptBranchId !== null, fn ($query) => $query->where('branch_id', '!=', $exceptBranchId))
            ->update([
                'status' => DriverAssignmentStatus::Inactive->value,
                'ended_at' => now()->toDateString(),
            ]);
    }

    private function assertIsDriver(User $driver): void
    {
        if ($driver->user_type !== UserType::DRIVER) {
            throw new InvalidArgumentException('User is not a driver.');
        }
    }
}

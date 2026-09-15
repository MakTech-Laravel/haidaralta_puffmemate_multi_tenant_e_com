<?php

namespace App\Services\Driver;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverRosterStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\DriverRoster;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class DriverService
{
    /**
     * @param  array{name: string, phone: string, email?: string|null, password: string, license_number?: string|null, vehicle_type: string, availability_status?: string, notes?: string|null, address?: string|null, branch_ids?: list<int>, rosters?: list<array{branch_id: int, start_date: string, end_date: string, start_time: string, end_time: string, days_of_week?: list<int>, status?: string}>}  $data
     */
    public function create(
        array $data,
        ?User $assignedBy = null,
        ?Branch $autoAssignBranch = null,
        ?UploadedFile $avatar = null,
        ?UploadedFile $vehicleImage = null,
    ): User {
        $branchIds = $data['branch_ids'] ?? [];

        if ($branchIds === [] && $autoAssignBranch === null) {
            throw new InvalidArgumentException('At least one branch must be assigned to the driver.');
        }

        if ($branchIds === [] && $autoAssignBranch !== null) {
            $branchIds = [$autoAssignBranch->id];
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
            ]);

            DriverProfile::query()->create([
                'user_id' => $driver->id,
                'license_number' => $data['license_number'] ?? null,
                'vehicle_type' => $data['vehicle_type'],
                'availability_status' => $data['availability_status'] ?? DriverAvailabilityStatus::Offline->value,
                'notes' => $data['notes'] ?? null,
                'address' => $data['address'] ?? null,
                'avatar_path' => $this->storeMedia($avatar, 'drivers/avatars'),
                'vehicle_image_path' => $this->storeMedia($vehicleImage, 'drivers/vehicles'),
            ]);

            $this->syncBranchAssignments($driver, $branchIds, DriverAssignmentStatus::Active, $assignedBy);

            foreach ($data['rosters'] ?? [] as $roster) {
                $branch = Branch::query()->findOrFail((int) $roster['branch_id']);
                $this->createRoster($driver, $branch, $roster);
            }

            return $driver->refresh();
        });
    }

    /**
     * @param  array{name?: string, phone?: string, email?: string|null, password?: string|null, is_active?: bool, license_number?: string|null, vehicle_type?: string, availability_status?: string, notes?: string|null, address?: string|null, remove_avatar?: bool, remove_vehicle_image?: bool, rosters?: list<array{branch_id: int, start_date: string, end_date: string, start_time: string, end_time: string, days_of_week?: list<int>, status?: string}>}  $data
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
                'email' => $data['email'] ?? null,
                'is_active' => $data['is_active'] ?? null,
            ], fn (mixed $value): bool => $value !== null);

            if (filled($data['password'] ?? null)) {
                $userPayload['password'] = $data['password'];
            }

            if ($userPayload !== []) {
                $driver->update($userPayload);
            }

            $profile = $driver->driverProfile;

            if ($profile === null) {
                throw new InvalidArgumentException('Driver profile does not exist.');
            }

            $avatarPath = $profile->avatar_path;
            $vehiclePath = $profile->vehicle_image_path;

            if (($data['remove_avatar'] ?? false) === true) {
                $this->deleteMedia($avatarPath);
                $avatarPath = null;
            } elseif ($avatar !== null) {
                $this->deleteMedia($avatarPath);
                $avatarPath = $this->storeMedia($avatar, 'drivers/avatars');
            }

            if (($data['remove_vehicle_image'] ?? false) === true) {
                $this->deleteMedia($vehiclePath);
                $vehiclePath = null;
            } elseif ($vehicleImage !== null) {
                $this->deleteMedia($vehiclePath);
                $vehiclePath = $this->storeMedia($vehicleImage, 'drivers/vehicles');
            }

            $profilePayload = [
                'avatar_path' => $avatarPath,
                'vehicle_image_path' => $vehiclePath,
            ];

            foreach (['license_number', 'vehicle_type', 'availability_status', 'notes', 'address'] as $field) {
                if (array_key_exists($field, $data)) {
                    $profilePayload[$field] = $data[$field];
                }
            }

            $profile->update($profilePayload);

            foreach ($data['rosters'] ?? [] as $roster) {
                $branch = Branch::query()->findOrFail((int) $roster['branch_id']);
                $this->createRoster($driver, $branch, $roster);
            }

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

        DB::transaction(function () use ($driver, $branchIds, $status, $assignedBy): void {
            foreach ($branchIds as $branchId) {
                DriverBranchAssignment::query()->updateOrCreate(
                    ['driver_id' => $driver->id, 'branch_id' => $branchId],
                    ['status' => $status->value, 'assigned_by' => $assignedBy?->id],
                );
            }

            DriverBranchAssignment::query()
                ->where('driver_id', $driver->id)
                ->whereNotIn('branch_id', $branchIds === [] ? [0] : $branchIds)
                ->update(['status' => DriverAssignmentStatus::Inactive->value]);
        });
    }

    public function assignToBranch(
        User $driver,
        Branch $branch,
        ?User $assignedBy = null,
        DriverAssignmentStatus $status = DriverAssignmentStatus::Active,
    ): DriverBranchAssignment {
        $this->assertIsDriver($driver);

        return DriverBranchAssignment::query()->updateOrCreate(
            ['driver_id' => $driver->id, 'branch_id' => $branch->id],
            ['status' => $status->value, 'assigned_by' => $assignedBy?->id],
        );
    }

    public function unassignFromBranch(User $driver, Branch $branch): void
    {
        $this->setAssignmentStatus($driver, $branch, DriverAssignmentStatus::Inactive);
    }

    public function setAssignmentStatus(User $driver, Branch $branch, DriverAssignmentStatus $status): DriverBranchAssignment
    {
        $this->assertIsDriver($driver);

        $assignment = DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('branch_id', $branch->id)
            ->first();

        if ($assignment === null) {
            throw new InvalidArgumentException('Driver is not assigned to this branch.');
        }

        $assignment->update(['status' => $status->value]);

        return $assignment->refresh();
    }

    /**
     * @param  array{start_date: string, end_date: string, start_time: string, end_time: string, days_of_week?: list<int>, status?: string}  $data
     */
    public function createRoster(User $driver, Branch $branch, array $data): DriverRoster
    {
        $this->assertIsDriver($driver);
        $this->assertActiveAssignment($driver, $branch);
        $this->assertValidShiftTimes($data['start_time'], $data['end_time']);
        $this->assertValidPeriod($data['start_date'], $data['end_date']);

        $days = DriverRoster::normalizeDays($data['days_of_week'] ?? null);

        $this->assertNoOverlappingShift(
            $driver,
            $data['start_date'],
            $data['end_date'],
            $data['start_time'],
            $data['end_time'],
            $days,
        );

        return DriverRoster::query()->create([
            'driver_id' => $driver->id,
            'branch_id' => $branch->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'days_of_week' => $days,
            'status' => $data['status'] ?? DriverRosterStatus::Scheduled->value,
        ]);
    }

    /**
     * @param  array{start_date?: string, end_date?: string, start_time?: string, end_time?: string, days_of_week?: list<int>, status?: string}  $data
     */
    public function updateRoster(DriverRoster $roster, array $data): DriverRoster
    {
        $startDate = $data['start_date'] ?? $roster->start_date->format('Y-m-d');
        $endDate = $data['end_date'] ?? $roster->end_date->format('Y-m-d');
        $startTime = $data['start_time'] ?? $roster->start_time->format('H:i');
        $endTime = $data['end_time'] ?? $roster->end_time->format('H:i');
        $days = DriverRoster::normalizeDays($data['days_of_week'] ?? $roster->normalizedDaysOfWeek());

        $this->assertValidShiftTimes($startTime, $endTime);
        $this->assertValidPeriod($startDate, $endDate);
        $this->assertNoOverlappingShift(
            $roster->driver,
            $startDate,
            $endDate,
            $startTime,
            $endTime,
            $days,
            exceptRosterId: $roster->id,
        );

        $roster->update([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'days_of_week' => $days,
            'status' => $data['status'] ?? $roster->status->value,
        ]);

        return $roster->refresh();
    }

    public function deleteRoster(DriverRoster $roster): void
    {
        $roster->delete();
    }

    private function storeMedia(?UploadedFile $file, string $directory): ?string
    {
        if ($file === null) {
            return null;
        }

        return $file->store($directory, 'public');
    }

    private function deleteMedia(?string $path): void
    {
        if ($path === null || $path === '' || ! str_contains($path, '/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function assertIsDriver(User $driver): void
    {
        if ($driver->user_type !== UserType::DRIVER) {
            throw new InvalidArgumentException('User is not a driver.');
        }
    }

    private function assertActiveAssignment(User $driver, Branch $branch): void
    {
        $isActive = DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('branch_id', $branch->id)
            ->where('status', DriverAssignmentStatus::Active->value)
            ->exists();

        if (! $isActive) {
            throw new InvalidArgumentException('Driver does not have an active assignment for this branch.');
        }
    }

    private function assertValidShiftTimes(string $startTime, string $endTime): void
    {
        if (strtotime($endTime) <= strtotime($startTime)) {
            throw new InvalidArgumentException('Shift end time must be after the start time.');
        }
    }

    private function assertValidPeriod(string $startDate, string $endDate): void
    {
        if ($endDate < $startDate) {
            throw new InvalidArgumentException('Shift end date must be on or after the start date.');
        }
    }

    /**
     * @param  list<int>  $daysOfWeek
     */
    private function assertNoOverlappingShift(
        User $driver,
        string $startDate,
        string $endDate,
        string $startTime,
        string $endTime,
        array $daysOfWeek,
        ?int $exceptRosterId = null,
    ): void {
        $normalizedStart = strlen($startTime) === 5 ? "{$startTime}:00" : $startTime;
        $normalizedEnd = strlen($endTime) === 5 ? "{$endTime}:00" : $endTime;
        $days = DriverRoster::normalizeDays($daysOfWeek);

        $candidates = DriverRoster::query()
            ->where('driver_id', $driver->id)
            ->where('status', DriverRosterStatus::Scheduled->value)
            ->when($exceptRosterId !== null, fn ($query) => $query->whereKeyNot($exceptRosterId))
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->whereRaw('start_time < ?', [$normalizedEnd])
            ->whereRaw('end_time > ?', [$normalizedStart])
            ->get();

        $overlaps = $candidates->contains(
            fn (DriverRoster $roster): bool => array_intersect($days, $roster->normalizedDaysOfWeek()) !== [],
        );

        if ($overlaps) {
            throw new InvalidArgumentException('This driver already has an overlapping scheduled shift in this period.');
        }
    }
}

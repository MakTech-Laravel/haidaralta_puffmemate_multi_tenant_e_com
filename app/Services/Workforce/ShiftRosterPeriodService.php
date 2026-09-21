<?php

namespace App\Services\Workforce;

use App\Enums\ShiftOwnerType;
use App\Models\ShiftRosterPeriod;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ShiftRosterPeriodService
{
    /**
     * @param  array{name: string, start_date: string, end_date: string, owner_type?: string, branch_id?: int|null, is_active?: bool}  $data
     */
    public function create(array $data, ?User $actor = null): ShiftRosterPeriod
    {
        $this->assertDateRange($data['start_date'], $data['end_date']);

        $ownerType = $this->ownerType($data);
        $branchId = $this->branchId($ownerType, $data['branch_id'] ?? null);

        $this->assertUniqueName($data['name'], $branchId);

        return ShiftRosterPeriod::query()->create([
            'owner_type' => $ownerType->value,
            'branch_id' => $branchId,
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $actor?->id,
        ]);
    }

    /**
     * @param  array{name?: string, start_date?: string, end_date?: string, is_active?: bool}  $data
     */
    public function update(ShiftRosterPeriod $period, array $data): ShiftRosterPeriod
    {
        $name = $data['name'] ?? $period->name;
        $startDate = $data['start_date'] ?? $period->start_date->format('Y-m-d');
        $endDate = $data['end_date'] ?? $period->end_date->format('Y-m-d');

        $this->assertDateRange($startDate, $endDate);
        $this->assertUniqueName($name, $period->branch_id, $period->id);

        $period->update([
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $data['is_active'] ?? $period->is_active,
        ]);

        return $period->refresh();
    }

    private function ownerType(array $data): ShiftOwnerType
    {
        $value = $data['owner_type'] ?? ($data['branch_id'] ? ShiftOwnerType::Branch->value : ShiftOwnerType::Platform->value);

        return ShiftOwnerType::from($value);
    }

    private function branchId(ShiftOwnerType $ownerType, mixed $branchId): ?int
    {
        if ($ownerType === ShiftOwnerType::Platform) {
            return null;
        }

        $id = $branchId !== null ? (int) $branchId : null;

        if ($id === null) {
            throw new InvalidArgumentException('A branch is required for branch-owned roster periods.');
        }

        return $id;
    }

    private function assertDateRange(string $startDate, string $endDate): void
    {
        if ($endDate < $startDate) {
            throw new InvalidArgumentException('Period end date must be on or after the start date.');
        }
    }

    private function assertUniqueName(string $name, ?int $branchId, ?int $exceptId = null): void
    {
        $exists = ShiftRosterPeriod::query()
            ->where('name', $name)
            ->when(
                $branchId === null,
                fn ($query) => $query->whereNull('branch_id'),
                fn ($query) => $query->where('branch_id', $branchId),
            )
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'A roster period with this name already exists for this owner.',
            ]);
        }
    }
}

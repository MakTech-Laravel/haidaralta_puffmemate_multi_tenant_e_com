<?php

namespace App\Services\Workforce;

use App\Enums\ShiftOwnerType;
use App\Models\ShiftTemplate;
use App\Models\User;
use App\Support\DutyWindow;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ShiftTemplateService
{
    /**
     * @param  array{name: string, start_time: string, end_time: string, owner_type?: string, branch_id?: int|null, is_active?: bool}  $data
     */
    public function create(array $data, ?User $actor = null): ShiftTemplate
    {
        $ownerType = $this->ownerType($data);
        $branchId = $this->branchId($ownerType, $data['branch_id'] ?? null);
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        $this->assertUniqueName($data['name'], $branchId);

        return ShiftTemplate::query()->create([
            'owner_type' => $ownerType->value,
            'branch_id' => $branchId,
            'name' => $data['name'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_overnight' => DutyWindow::isOvernight($startTime, $endTime),
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $actor?->id,
        ]);
    }

    /**
     * @param  array{name?: string, start_time?: string, end_time?: string, is_active?: bool}  $data
     */
    public function update(ShiftTemplate $template, array $data): ShiftTemplate
    {
        $name = $data['name'] ?? $template->name;
        $startTime = $data['start_time'] ?? $template->start_time->format('H:i');
        $endTime = $data['end_time'] ?? $template->end_time->format('H:i');

        $this->assertUniqueName($name, $template->branch_id, $template->id);

        $template->update([
            'name' => $name,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_overnight' => DutyWindow::isOvernight($startTime, $endTime),
            'is_active' => $data['is_active'] ?? $template->is_active,
        ]);

        return $template->refresh();
    }

    public function deactivate(ShiftTemplate $template): ShiftTemplate
    {
        $template->update(['is_active' => false]);

        return $template->refresh();
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
            throw new InvalidArgumentException('A branch is required for branch-owned shift templates.');
        }

        return $id;
    }

    private function assertUniqueName(string $name, ?int $branchId, ?int $exceptId = null): void
    {
        $exists = ShiftTemplate::query()
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
                'name' => 'A shift template with this name already exists for this owner.',
            ]);
        }
    }
}

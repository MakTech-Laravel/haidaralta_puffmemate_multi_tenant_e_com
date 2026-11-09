<?php

namespace App\Services\Application;

use App\Models\DeliveryShift;
use App\Models\User;
use Illuminate\Support\Collection;

class DeliveryShiftService
{
    /**
     * @return Collection<int, DeliveryShift>
     */
    public function all(): Collection
    {
        return DeliveryShift::query()->orderBy('start_time')->orderBy('name')->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function indexPayload(): array
    {
        return $this->all()
            ->map(fn (DeliveryShift $shift): array => $this->row($shift))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function formPayload(DeliveryShift $shift): array
    {
        return $this->row($shift);
    }

    /**
     * @return array<string, mixed>
     */
    public function detailPayload(DeliveryShift $shift): array
    {
        $shift->loadMissing('createdBy:id,name');

        return [
            ...$this->row($shift),
            'created_by_name' => $shift->createdBy?->name,
            'created_at' => $shift->created_at?->toIso8601String(),
            'updated_at' => $shift->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array{name: string, start_time: string, end_time: string, is_active?: bool}  $data
     */
    public function create(array $data, ?User $actor = null): DeliveryShift
    {
        return DeliveryShift::query()->create([
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $actor?->id,
        ]);
    }

    /**
     * @param  array{name: string, start_time: string, end_time: string, is_active?: bool}  $data
     */
    public function update(DeliveryShift $shift, array $data): DeliveryShift
    {
        $shift->fill([
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_active' => $data['is_active'] ?? $shift->is_active,
        ])->save();

        return $shift->refresh();
    }

    public function delete(DeliveryShift $shift): void
    {
        $shift->delete();
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     start_time: string,
     *     end_time: string,
     *     is_active: bool
     * }
     */
    private function row(DeliveryShift $shift): array
    {
        return [
            'id' => $shift->id,
            'name' => $shift->name,
            'start_time' => $shift->start_time->format('H:i'),
            'end_time' => $shift->end_time->format('H:i'),
            'is_active' => $shift->is_active,
        ];
    }
}

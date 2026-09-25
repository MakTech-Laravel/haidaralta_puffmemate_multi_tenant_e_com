<?php

namespace App\Services\Catalog;

use App\Models\DeliveryMethod;
use App\Models\DeliveryTimeSlot;
use Illuminate\Support\Collection;

class DeliverySettingsService
{
    /**
     * @return Collection<int, DeliveryMethod>
     */
    public function methods(): Collection
    {
        $this->ensureDefaults();

        return DeliveryMethod::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->with(['timeSlots' => fn ($query) => $query->orderBy('sort')->orderBy('id')])
            ->get();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function indexPayload(): array
    {
        return $this->methods()
            ->map(fn (DeliveryMethod $method): array => [
                'id' => $method->id,
                'key' => $method->key,
                'label' => $method->label,
                'description' => $method->description,
                'fee' => (string) $method->fee,
                'requires_slot' => $method->requires_slot,
                'status' => $method->status,
                'sort' => $method->sort,
                'slots' => $method->timeSlots
                    ->map(fn (DeliveryTimeSlot $slot): array => [
                        'id' => $slot->id,
                        'label' => $slot->label,
                        'status' => $slot->status,
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{label: string, description?: string|null, fee: float|int|string, requires_slot: bool, status: bool}  $data
     */
    public function update(DeliveryMethod $method, array $data): DeliveryMethod
    {
        $method->fill([
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'fee' => $data['fee'],
            'requires_slot' => $data['requires_slot'],
            'status' => $data['status'],
        ])->save();

        return $method->refresh();
    }

    public function ensureDefaults(): void
    {
        $sameDay = DeliveryMethod::query()->firstOrCreate(
            ['key' => 'same-day'],
            [
                'label' => 'Same-day local delivery',
                'description' => 'Fast local delivery in selected areas.',
                'fee' => 0,
                'requires_slot' => true,
                'status' => true,
                'sort' => 0,
            ],
        );

        $shipping = DeliveryMethod::query()->firstOrCreate(
            ['key' => 'australia-wide'],
            [
                'label' => 'Australia-wide shipping',
                'description' => 'Shipping available for eligible orders.',
                'fee' => 0,
                'requires_slot' => false,
                'status' => true,
                'sort' => 1,
            ],
        );

        if ($sameDay->timeSlots()->doesntExist()) {
            foreach (['9:00 AM – 12:00 PM', '12:00 PM – 3:00 PM', '3:00 PM – 6:00 PM', '6:00 PM – 9:00 PM'] as $index => $label) {
                $sameDay->timeSlots()->create([
                    'label' => $label,
                    'status' => true,
                    'sort' => $index,
                ]);
            }
        }

        unset($shipping);
    }
}

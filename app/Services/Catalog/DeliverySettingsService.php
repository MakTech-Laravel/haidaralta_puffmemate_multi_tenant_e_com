<?php

namespace App\Services\Catalog;

use App\Models\Branch;
use App\Models\BranchShippingRule;
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
        DeliveryMethod::query()->firstOrCreate(
            ['key' => 'regular'],
            [
                'label' => 'Regular delivery',
                'description' => 'Standard delivery for eligible orders.',
                'fee' => 0,
                'requires_slot' => false,
                'status' => true,
                'sort' => 0,
            ],
        );

        $express = DeliveryMethod::query()->firstOrCreate(
            ['key' => 'express'],
            [
                'label' => 'Express delivery',
                'description' => 'Priority delivery in selected areas with a chosen time slot.',
                'fee' => 0,
                'requires_slot' => true,
                'status' => true,
                'sort' => 1,
            ],
        );

        if ($express->timeSlots()->doesntExist()) {
            foreach (['9:00 AM – 12:00 PM', '12:00 PM – 3:00 PM', '3:00 PM – 6:00 PM', '6:00 PM – 9:00 PM'] as $index => $label) {
                $express->timeSlots()->create([
                    'label' => $label,
                    'status' => true,
                    'sort' => $index,
                ]);
            }
        }
    }

    /**
     * Every branch's shipping formula, creating a default-config row for any
     * branch that hasn't been configured yet.
     *
     * @return list<array<string, mixed>>
     */
    public function branchShippingRulesPayload(): array
    {
        return Branch::query()
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch): array {
                $rule = $this->shippingRuleFor($branch);

                return [
                    'id' => $rule->id,
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'base_fee' => (string) $rule->base_fee,
                    'free_delivery_distance_km' => $rule->free_delivery_distance_km === null
                        ? ''
                        : (string) $rule->free_delivery_distance_km,
                    'cost_per_km' => (string) $rule->cost_per_km,
                    'minimum_delivery_charge' => (string) $rule->minimum_delivery_charge,
                    'status' => $rule->status,
                ];
            })
            ->values()
            ->all();
    }

    public function shippingRuleFor(Branch $branch): BranchShippingRule
    {
        return BranchShippingRule::query()->firstOrCreate(
            ['branch_id' => $branch->id],
            [
                'base_fee' => (float) config('catalog.default_shipping_base_fee'),
                'free_delivery_distance_km' => (float) config('catalog.default_shipping_free_distance_km') ?: null,
                'cost_per_km' => (float) config('catalog.default_shipping_cost_per_km'),
                'minimum_delivery_charge' => (float) config('catalog.default_shipping_minimum_charge'),
                'status' => true,
            ],
        );
    }

    /**
     * @param  array{base_fee: float|int|string, free_delivery_distance_km?: float|int|string|null, cost_per_km: float|int|string, minimum_delivery_charge: float|int|string, status: bool}  $data
     */
    public function updateShippingRule(BranchShippingRule $rule, array $data): BranchShippingRule
    {
        $rule->fill([
            'base_fee' => $data['base_fee'],
            'free_delivery_distance_km' => $data['free_delivery_distance_km'] ?? null,
            'cost_per_km' => $data['cost_per_km'],
            'minimum_delivery_charge' => $data['minimum_delivery_charge'],
            'status' => $data['status'],
        ])->save();

        return $rule->refresh();
    }
}

<?php

namespace App\Services\Order;

use App\Contracts\DistanceCalculator;
use App\Enums\FulfillmentStatus;
use App\Models\Branch;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\OrderFulfillment;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrderAllocationEngine
{
    public function __construct(
        private DistanceCalculator $distance,
        private ShippingCalculator $shipping,
    ) {}

    /**
     * @param  Collection<int, CartItem>  $cartItems
     * @return array{
     *     branch: Branch,
     *     distance_km: float,
     *     shipping_cost: float,
     *     operational_cost: float,
     *     transferred_units: int,
     *     lines: list<array{product_id: int, variant_id: int|null, quantity: int, local_quantity: int}>,
     *     transfers: list<array{
     *         source_branch_id: int,
     *         destination_branch_id: int,
     *         distance_km: float,
     *         eta_hours: float,
     *         estimated_cost: float,
     *         items: list<array{product_id: int, variant_id: int|null, quantity: int}>
     *     }>
     * }
     */
    public function allocate(
        float $latitude,
        float $longitude,
        Collection $cartItems,
        ?string $deliveryMethodKey = null,
    ): array {
        $lines = $cartItems
            ->map(fn (CartItem $item): array => [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
            ])
            ->values()
            ->all();

        if ($lines === []) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $branches = Branch::query()
            ->where('status', true)
            ->with('shippingRule')
            ->get();

        if ($branches->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'No branch is currently available to fulfil your order.',
            ]);
        }

        $stock = $this->sellableByBranch($lines);
        $loads = $this->openFulfillmentCounts($branches->pluck('id')->all());
        $maxSlaHours = $this->slaHours($deliveryMethodKey);

        $candidates = [];

        foreach ($branches as $branch) {
            $distanceKm = $this->customerDistance($branch, $latitude, $longitude);

            if (! $this->canDeliverToPin($branch, $distanceKm)) {
                continue;
            }

            $cap = $branch->max_active_fulfillments;
            $load = $loads[$branch->id] ?? 0;

            if ($cap !== null && $load >= $cap) {
                continue;
            }

            $plan = $this->cover($branch, $lines, $stock, $branches);

            if ($plan === null) {
                continue;
            }

            $etaHours = $plan['eta_hours'];

            if ($etaHours > $maxSlaHours) {
                continue;
            }

            $candidates[] = [
                'branch' => $branch,
                'distance_km' => $distanceKm ?? PHP_FLOAT_MAX,
                'shipping_cost' => $this->shipping->cost($branch, $distanceKm),
                'needs_transfer' => $plan['transferred_units'] > 0 ? 1 : 0,
                'operational_cost' => $plan['operational_cost'],
                'transferred_units' => $plan['transferred_units'],
                'load' => $load,
                'priority' => (int) $branch->fulfillment_priority,
                'lines' => $plan['lines'],
                'transfers' => $plan['transfers'],
            ];
        }

        if ($candidates === []) {
            throw ValidationException::withMessages([
                'cart' => 'No branch can fulfil this order within the delivery window.',
            ]);
        }

        usort($candidates, function (array $left, array $right): int {
            return [
                $left['needs_transfer'],
                $left['operational_cost'],
                $left['distance_km'],
                $left['shipping_cost'],
                $left['load'],
                $left['transferred_units'],
                $left['priority'],
            ] <=> [
                $right['needs_transfer'],
                $right['operational_cost'],
                $right['distance_km'],
                $right['shipping_cost'],
                $right['load'],
                $right['transferred_units'],
                $right['priority'],
            ];
        });

        $winner = $candidates[0];

        if ($winner['distance_km'] === PHP_FLOAT_MAX) {
            throw ValidationException::withMessages([
                'cart' => 'No branch can fulfil this order within the delivery window.',
            ]);
        }

        return [
            'branch' => $winner['branch'],
            'distance_km' => round((float) $winner['distance_km'], 2),
            'shipping_cost' => $winner['shipping_cost'],
            'operational_cost' => $winner['operational_cost'],
            'transferred_units' => $winner['transferred_units'],
            'lines' => $winner['lines'],
            'transfers' => $winner['transfers'],
        ];
    }

    /**
     * @param  list<array{product_id: int, variant_id: int|null, quantity: int}>  $lines
     * @param  array<int, array<string, int>>  $stock
     * @param  Collection<int, Branch>  $branches
     * @return array{
     *     lines: list<array{product_id: int, variant_id: int|null, quantity: int, local_quantity: int}>,
     *     transfers: list<array{
     *         source_branch_id: int,
     *         destination_branch_id: int,
     *         distance_km: float,
     *         eta_hours: float,
     *         estimated_cost: float,
     *         items: list<array{product_id: int, variant_id: int|null, quantity: int}>
     *     }>,
     *     operational_cost: float,
     *     transferred_units: int,
     *     eta_hours: float
     * }|null
     */
    private function cover(Branch $destination, array $lines, array $stock, Collection $branches): ?array
    {
        $remaining = $stock;
        $covered = [];
        $legs = [];
        $transferredUnits = 0;

        foreach ($lines as $line) {
            $sku = $this->skuKey($line['product_id'], $line['variant_id']);
            $need = $line['quantity'];
            $local = min($need, $remaining[$destination->id][$sku] ?? 0);
            $remaining[$destination->id][$sku] = ($remaining[$destination->id][$sku] ?? 0) - $local;
            $missing = $need - $local;

            $covered[] = [
                'product_id' => $line['product_id'],
                'variant_id' => $line['variant_id'],
                'quantity' => $need,
                'local_quantity' => $local,
            ];

            if ($missing <= 0) {
                continue;
            }

            $sources = $branches
                ->filter(fn (Branch $branch): bool => $branch->id !== $destination->id)
                ->sortBy(fn (Branch $branch): float => $this->branchDistance($destination, $branch) ?? PHP_FLOAT_MAX)
                ->values();

            foreach ($sources as $source) {
                if ($missing <= 0) {
                    break;
                }

                $available = $remaining[$source->id][$sku] ?? 0;

                if ($available <= 0) {
                    continue;
                }

                $take = min($missing, $available);
                $remaining[$source->id][$sku] = $available - $take;
                $missing -= $take;
                $transferredUnits += $take;

                $sourceId = $source->id;

                if (! isset($legs[$sourceId])) {
                    $distanceKm = $this->branchDistance($destination, $source) ?? 0.0;
                    $legs[$sourceId] = [
                        'source_branch_id' => $sourceId,
                        'destination_branch_id' => $destination->id,
                        'distance_km' => round($distanceKm, 2),
                        'eta_hours' => $this->etaHours($distanceKm),
                        'estimated_cost' => $this->legCost($source, $distanceKm),
                        'items' => [],
                    ];
                }

                $legs[$sourceId]['items'][] = [
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'],
                    'quantity' => $take,
                ];
            }

            if ($missing > 0) {
                return null;
            }
        }

        $transfers = array_values($legs);
        $etaHours = $transfers === []
            ? 0.0
            : max(array_map(fn (array $leg): float => $leg['eta_hours'], $transfers));

        return [
            'lines' => $covered,
            'transfers' => $transfers,
            'operational_cost' => round(array_sum(array_map(fn (array $leg): float => $leg['estimated_cost'], $transfers)), 2),
            'transferred_units' => $transferredUnits,
            'eta_hours' => $etaHours,
        ];
    }

    /**
     * @param  list<array{product_id: int, variant_id: int|null, quantity: int}>  $lines
     * @return array<int, array<string, int>>
     */
    private function sellableByBranch(array $lines): array
    {
        $productIds = array_values(array_unique(array_map(fn (array $line): int => $line['product_id'], $lines)));

        $rows = Inventory::query()
            ->whereIn('product_id', $productIds)
            ->where('status', true)
            ->get();

        $stock = [];

        foreach ($rows as $row) {
            $sku = $this->skuKey($row->product_id, $row->variant_id);
            $stock[$row->branch_id][$sku] = max(0, $row->quantity - $row->reserved_quantity);
        }

        return $stock;
    }

    /**
     * @param  list<int>  $branchIds
     * @return array<int, int>
     */
    private function openFulfillmentCounts(array $branchIds): array
    {
        return OrderFulfillment::query()
            ->whereIn('branch_id', $branchIds)
            ->whereNotIn('status', [
                FulfillmentStatus::Delivered->value,
                FulfillmentStatus::Cancelled->value,
            ])
            ->get()
            ->countBy('branch_id')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    private function canDeliverToPin(Branch $branch, ?float $distanceKm): bool
    {
        if ($branch->delivery_radius_km === null) {
            return true;
        }

        return $distanceKm !== null && $distanceKm <= (float) $branch->delivery_radius_km;
    }

    private function customerDistance(Branch $branch, float $latitude, float $longitude): ?float
    {
        if ($branch->latitude === null || $branch->longitude === null) {
            return null;
        }

        return $this->distance->distanceKm(
            $latitude,
            $longitude,
            (float) $branch->latitude,
            (float) $branch->longitude,
        );
    }

    private function branchDistance(Branch $from, Branch $to): ?float
    {
        if ($from->latitude === null || $from->longitude === null || $to->latitude === null || $to->longitude === null) {
            return null;
        }

        return $this->distance->distanceKm(
            (float) $from->latitude,
            (float) $from->longitude,
            (float) $to->latitude,
            (float) $to->longitude,
        );
    }

    private function etaHours(float $distanceKm): float
    {
        $hoursPerKm = (float) config('catalog.transfer_hours_per_km', 0.02);
        $buffer = (float) config('catalog.transfer_handling_buffer_hours', 1);

        return round(($hoursPerKm * $distanceKm) + $buffer, 2);
    }

    private function legCost(Branch $source, float $distanceKm): float
    {
        $handling = $source->transfer_handling_fee !== null
            ? (float) $source->transfer_handling_fee
            : (float) config('catalog.default_transfer_handling_fee', 0);
        $perKm = $source->transfer_cost_per_km !== null
            ? (float) $source->transfer_cost_per_km
            : (float) config('catalog.default_transfer_cost_per_km', 0);

        return round($handling + ($perKm * $distanceKm), 2);
    }

    private function slaHours(?string $deliveryMethodKey): float
    {
        if ($deliveryMethodKey === 'same-day') {
            return (float) config('catalog.same_day_max_transfer_hours', 4);
        }

        return (float) config('catalog.australia_wide_max_transfer_hours', 72);
    }

    private function skuKey(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId ?? '0');
    }
}

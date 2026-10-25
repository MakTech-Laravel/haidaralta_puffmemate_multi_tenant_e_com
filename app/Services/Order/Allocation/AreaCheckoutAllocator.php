<?php

namespace App\Services\Order\Allocation;

use App\Models\Branch;
use App\Models\CartItem;
use App\Services\Order\ShippingCalculator;
use App\Support\Geo\Australia;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Storefront cart allocation: AU-only pin, prefer BranchArea polygon branch,
 * else nearest active branch; local stock + late-delivery opt-in (no transfers).
 */
class AreaCheckoutAllocator
{
    public function __construct(
        private AreaBranchResolver $areas,
        private InventoryAvailabilityChecker $inventory,
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
     *     requires_late_delivery: bool,
     *     lines: list<array{
     *         product_id: int,
     *         variant_id: int|null,
     *         quantity: int,
     *         local_quantity: int,
     *         shortfall: int,
     *         product_name: string,
     *         is_late_delivery: bool
     *     }>,
     *     transfers: list<array<string, mixed>>,
     *     decision_trace: array{
     *         considered: list<array<string, mixed>>,
     *         rejected: list<array{branch_id: int, reason: string}>
     *     }
     * }
     */
    public function allocate(
        float $latitude,
        float $longitude,
        Collection $cartItems,
        bool $acceptLateDelivery = false,
        bool $enforceLateAcceptance = false,
    ): array {
        $cartItems->loadMissing('product');

        $linesInput = $cartItems
            ->map(fn (CartItem $item): array => [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                'product_name' => (string) ($item->product?->name ?? 'Item'),
            ])
            ->values()
            ->all();

        if ($linesInput === []) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        if (! Australia::contains($latitude, $longitude)) {
            throw ValidationException::withMessages([
                'address' => 'We only deliver within Australia.',
            ]);
        }

        $match = $this->areas->resolveForCheckout($latitude, $longitude);

        if ($match === null) {
            throw ValidationException::withMessages([
                'cart' => 'No branch is currently available to fulfil your order.',
            ]);
        }

        /** @var Branch $branch */
        $branch = $match['branch'];
        $distanceKm = $match['distance_km'];
        $allocation = $match['allocation'];
        $stock = $this->inventory->sellableByBranch($linesInput);
        $branchStock = $stock[$branch->id] ?? [];

        $lines = [];
        $requiresLate = false;

        foreach ($linesInput as $line) {
            $sku = InventoryAvailabilityChecker::skuKey($line['product_id'], $line['variant_id']);
            $available = $branchStock[$sku] ?? 0;
            $local = min($line['quantity'], $available);
            $shortfall = $line['quantity'] - $local;

            if ($shortfall > 0) {
                $requiresLate = true;
            }

            $lines[] = [
                'product_id' => $line['product_id'],
                'variant_id' => $line['variant_id'],
                'quantity' => $line['quantity'],
                'local_quantity' => $local,
                'shortfall' => $shortfall,
                'product_name' => $line['product_name'],
                'is_late_delivery' => $shortfall > 0,
            ];
        }

        if ($enforceLateAcceptance && $requiresLate && ! $acceptLateDelivery) {
            $names = collect($lines)
                ->filter(fn (array $line): bool => $line['shortfall'] > 0)
                ->pluck('product_name')
                ->unique()
                ->values()
                ->all();

            throw ValidationException::withMessages([
                'accept_late_delivery' => 'Some items are not in stock at your delivery branch. Remove them from your cart or accept late delivery.',
                'late_delivery_products' => implode(', ', $names),
            ]);
        }

        $shippingCost = $this->shipping->cost($branch, $distanceKm);

        return [
            'branch' => $branch,
            'distance_km' => $distanceKm,
            'shipping_cost' => $shippingCost,
            'operational_cost' => 0.0,
            'transferred_units' => 0,
            'requires_late_delivery' => $requiresLate,
            'lines' => $lines,
            'transfers' => [],
            'decision_trace' => [
                'considered' => [[
                    'branch_id' => $branch->id,
                    'needs_transfer' => 0,
                    'operational_cost' => 0.0,
                    'distance_km' => $distanceKm,
                    'shipping_cost' => $shippingCost,
                    'load' => 0,
                    'transferred_units' => 0,
                    'priority' => (int) $branch->fulfillment_priority,
                    'allocation' => $allocation,
                    'requires_late_delivery' => $requiresLate ? 1 : 0,
                ]],
                'rejected' => [],
            ],
        ];
    }
}

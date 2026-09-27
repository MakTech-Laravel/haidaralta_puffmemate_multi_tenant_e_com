<?php

namespace App\Services\Order;

use App\Contracts\DistanceCalculator;
use App\Models\Branch;
use App\Models\CartItem;
use App\Models\Inventory;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Splits a cart across the branch(es) that can actually fulfil it.
 *
 * Candidates are active branches ranked nearest-first by
 * {@see DistanceCalculator} from the delivery address. Branches within their
 * own `delivery_radius_km` are preferred; if none are in radius for anyone,
 * the single nearest active branch overall is used instead so an order is
 * never hard-rejected for a radius reason alone. Each cart line is then
 * walked nearest-first and assigned to (or split across) whichever
 * candidates hold enough {@see Inventory}.
 */
class BranchAllocationService
{
    public function __construct(private DistanceCalculator $distance) {}

    /**
     * @param  Collection<int, CartItem>  $cartItems
     * @return list<array{
     *     branch: Branch,
     *     distance_km: float|null,
     *     lines: list<array{product_id: int, variant_id: int|null, quantity: int}>,
     * }>
     */
    public function allocate(float $latitude, float $longitude, Collection $cartItems): array
    {
        $branches = Branch::query()->where('status', true)->get();

        if ($branches->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'No branch is currently available to fulfil your order.',
            ]);
        }

        $ranked = $branches
            ->map(function (Branch $branch) use ($latitude, $longitude): array {
                $distanceKm = $branch->latitude !== null && $branch->longitude !== null
                    ? $this->distance->distanceKm($latitude, $longitude, (float) $branch->latitude, (float) $branch->longitude)
                    : null;

                return ['branch' => $branch, 'distance_km' => $distanceKm];
            })
            ->sortBy(fn (array $row): float => $row['distance_km'] ?? PHP_FLOAT_MAX)
            ->values();

        $withinRadius = $ranked->filter(function (array $row): bool {
            $branch = $row['branch'];

            return $row['distance_km'] !== null
                && $branch->delivery_radius_km !== null
                && $row['distance_km'] <= (float) $branch->delivery_radius_km;
        });

        // Never hard-fail an order over radius alone — fall back to the
        // single nearest active branch overall.
        $candidates = $withinRadius->isNotEmpty() ? $withinRadius : $ranked->take(1);

        $remaining = $cartItems
            ->map(fn (CartItem $item): array => [
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
            ])
            ->values()
            ->all();

        /** @var array<int, array{branch: Branch, distance_km: float|null, lines: list<array{product_id: int, variant_id: int|null, quantity: int}>}> $groups */
        $groups = [];

        foreach ($candidates as $row) {
            if ($this->isFullyAllocated($remaining)) {
                break;
            }

            $branch = $row['branch'];

            foreach ($remaining as $index => $line) {
                if ($line['quantity'] <= 0) {
                    continue;
                }

                $available = $this->availableQuantity($branch->id, $line['product_id'], $line['variant_id']);

                if ($available <= 0) {
                    continue;
                }

                $take = min($available, $line['quantity']);

                $groups[$branch->id] ??= [
                    'branch' => $branch,
                    'distance_km' => $row['distance_km'],
                    'lines' => [],
                ];

                $groups[$branch->id]['lines'][] = [
                    'product_id' => $line['product_id'],
                    'variant_id' => $line['variant_id'],
                    'quantity' => $take,
                ];

                $remaining[$index]['quantity'] -= $take;
            }
        }

        if (! $this->isFullyAllocated($remaining)) {
            throw ValidationException::withMessages([
                'cart' => 'One or more items in your cart are out of stock at every branch that can deliver to you.',
            ]);
        }

        return array_values($groups);
    }

    /**
     * @param  list<array{product_id: int, variant_id: int|null, quantity: int}>  $remaining
     */
    private function isFullyAllocated(array $remaining): bool
    {
        foreach ($remaining as $line) {
            if ($line['quantity'] > 0) {
                return false;
            }
        }

        return true;
    }

    private function availableQuantity(int $branchId, int $productId, ?int $variantId): int
    {
        return (int) (Inventory::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('status', true)
            ->when(
                $variantId !== null,
                fn ($query) => $query->where('variant_id', $variantId),
                fn ($query) => $query->whereNull('variant_id'),
            )
            ->value('quantity') ?? 0);
    }
}

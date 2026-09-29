<?php

namespace App\Services\Order\Allocation;

use App\Models\Branch;
use Illuminate\Support\Collection;

/**
 * For a candidate destination branch, covers each cart line from local stock
 * first, then plans transfer legs for any shortfall from other branches.
 * Returns null when any line's shortfall cannot be fully sourced anywhere —
 * the destination branch is then not a valid candidate at all.
 */
class StockTransferPlanner
{
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
    public function plan(Branch $destination, array $lines, array $stock, Collection $branches, BranchDistanceMatrix $matrix): ?array
    {
        $remaining = $stock;
        $covered = [];
        $legs = [];
        $transferredUnits = 0;

        // Sorted once per destination and reused across every missing line,
        // instead of re-sorting the branch list per line.
        $sources = $matrix->sortByDistanceFrom($destination->id, $branches);

        foreach ($lines as $line) {
            $sku = InventoryAvailabilityChecker::skuKey($line['product_id'], $line['variant_id']);
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
                    $distanceKm = $matrix->between($destination->id, $sourceId) ?? 0.0;
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
}

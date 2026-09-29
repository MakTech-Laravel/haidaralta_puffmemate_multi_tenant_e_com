<?php

namespace App\Services\Order\Allocation;

/**
 * Builds the lexicographic ranking tuple used to compare two candidate
 * branches. This is a deterministic, rule-based comparison — not a weighted
 * score — so there is nothing to "tune" and the same inputs always produce
 * the same ordering.
 *
 * Order matters:
 *  0. needs_transfer   — absolute veto: any branch needing a transfer always
 *                         loses to any branch that can fulfil locally.
 *  1. operational_cost — internal transfer cost.
 *  2. distance_km      — customer distance.
 *  3. shipping_cost     — customer-facing delivery fee.
 *  4. load              — branch workload (open fulfillments).
 *  5. transferred_units — stock quality (fewer transferred units is better).
 *  6. priority           — manual fulfillment_priority tie-break.
 *  7. branch_id           — final, always-present deterministic tie-break.
 */
class AllocationScoreCalculator
{
    /**
     * @param  array{needs_transfer: int, operational_cost: float, distance_km: float, shipping_cost: float, load: int, transferred_units: int, priority: int, branch_id: int}  $candidate
     * @return list<int|float>
     */
    public function scoreTuple(array $candidate): array
    {
        return [
            $candidate['needs_transfer'],
            $candidate['operational_cost'],
            $candidate['distance_km'],
            $candidate['shipping_cost'],
            $candidate['load'],
            $candidate['transferred_units'],
            $candidate['priority'],
            $candidate['branch_id'],
        ];
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     */
    public function compare(array $left, array $right): int
    {
        return $this->scoreTuple($left) <=> $this->scoreTuple($right);
    }
}

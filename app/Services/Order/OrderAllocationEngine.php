<?php

namespace App\Services\Order;

use App\Models\Branch;
use App\Models\CartItem;
use App\Services\Order\Allocation\BranchCandidateFinder;
use App\Services\Order\Allocation\BranchDistanceMatrix;
use App\Services\Order\Allocation\BranchSelector;
use App\Services\Order\Allocation\BranchWorkloadProvider;
use App\Services\Order\Allocation\DeliverySLACalculator;
use App\Services\Order\Allocation\InventoryAvailabilityChecker;
use App\Services\Order\Allocation\StockTransferPlanner;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Thin orchestrator for the rule-based, deterministic branch allocation
 * decision. All the actual rules live in the focused collaborators under
 * App\Services\Order\Allocation — this class only wires them together in
 * the documented priority order and returns the winning fulfillment plan.
 *
 * No AI/ML: this is a lexicographic tuple comparison over fixed business
 * rules, so identical inputs always produce identical output.
 */
class OrderAllocationEngine
{
    public function __construct(
        private BranchCandidateFinder $candidateFinder,
        private InventoryAvailabilityChecker $inventory,
        private BranchWorkloadProvider $workload,
        private BranchDistanceMatrix $matrix,
        private StockTransferPlanner $planner,
        private DeliverySLACalculator $sla,
        private BranchSelector $selector,
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
     *     }>,
     *     decision_trace: array{
     *         considered: list<array{branch_id: int, needs_transfer: int, operational_cost: float, distance_km: float|null, shipping_cost: float, load: int, transferred_units: int, priority: int}>,
     *         rejected: list<array{branch_id: int, reason: string}>
     *     }
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

        $branches = $this->candidateFinder->activeBranches();

        if ($branches->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'No branch is currently available to fulfil your order.',
            ]);
        }

        $stock = $this->inventory->sellableByBranch($lines);
        $loads = $this->workload->openFulfillmentCounts($branches->pluck('id')->all());
        $maxSlaHours = $this->sla->maxHours($deliveryMethodKey);

        $this->matrix->build($branches, $latitude, $longitude);

        $candidates = [];
        $rejected = [];

        foreach ($branches as $branch) {
            $distanceKm = $this->matrix->customerDistanceTo($branch->id);

            if (! $this->candidateFinder->canDeliverToPin($branch, $distanceKm)) {
                $rejected[] = ['branch_id' => $branch->id, 'reason' => 'out_of_radius'];

                continue;
            }

            $load = $loads[$branch->id] ?? 0;

            if (! $this->candidateFinder->isUnderWorkloadCap($branch, $load)) {
                $rejected[] = ['branch_id' => $branch->id, 'reason' => 'over_workload_cap'];

                continue;
            }

            $plan = $this->planner->plan($branch, $lines, $stock, $branches, $this->matrix);

            if ($plan === null) {
                $rejected[] = ['branch_id' => $branch->id, 'reason' => 'insufficient_total_stock'];

                continue;
            }

            if (! $this->sla->withinSla($plan['eta_hours'], $deliveryMethodKey)) {
                $rejected[] = ['branch_id' => $branch->id, 'reason' => 'sla_exceeded'];

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
                'branch_id' => $branch->id,
                'lines' => $plan['lines'],
                'transfers' => $plan['transfers'],
            ];
        }

        if ($candidates === []) {
            throw ValidationException::withMessages([
                'cart' => 'No branch can fulfil this order within the delivery window.',
            ]);
        }

        $ranked = $this->selector->rank($candidates);
        $winner = $ranked[0];

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
            'decision_trace' => [
                'considered' => array_map(fn (array $candidate): array => [
                    'branch_id' => $candidate['branch_id'],
                    'needs_transfer' => $candidate['needs_transfer'],
                    'operational_cost' => $candidate['operational_cost'],
                    'distance_km' => $candidate['distance_km'] === PHP_FLOAT_MAX ? null : round((float) $candidate['distance_km'], 2),
                    'shipping_cost' => $candidate['shipping_cost'],
                    'load' => $candidate['load'],
                    'transferred_units' => $candidate['transferred_units'],
                    'priority' => $candidate['priority'],
                ], $ranked),
                'rejected' => $rejected,
            ],
        ];
    }
}

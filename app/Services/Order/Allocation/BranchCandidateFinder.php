<?php

namespace App\Services\Order\Allocation;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;

/**
 * Loads active branches deterministically and applies the two hard
 * candidate filters: pin reachability (delivery radius) and workload cap.
 */
class BranchCandidateFinder
{
    /**
     * @return Collection<int, Branch>
     */
    public function activeBranches(): Collection
    {
        return Branch::query()
            ->where('status', true)
            ->with('shippingRule')
            ->orderBy('id')
            ->get();
    }

    /**
     * Null delivery_radius_km means the branch can deliver anywhere.
     */
    public function canDeliverToPin(Branch $branch, ?float $distanceKm): bool
    {
        if ($branch->delivery_radius_km === null) {
            return true;
        }

        return $distanceKm !== null && $distanceKm <= (float) $branch->delivery_radius_km;
    }

    public function isUnderWorkloadCap(Branch $branch, int $currentLoad): bool
    {
        $cap = $branch->max_active_fulfillments;

        return $cap === null || $currentLoad < $cap;
    }
}

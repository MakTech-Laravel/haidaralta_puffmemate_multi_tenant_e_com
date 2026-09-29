<?php

namespace App\Services\Order\Allocation;

use App\Enums\FulfillmentStatus;
use App\Models\OrderFulfillment;

/**
 * Counts each branch's active (not yet delivered/cancelled) fulfillments,
 * used to avoid overloading a single branch. Aggregated at the database
 * level so this stays a single, flat query regardless of table size.
 */
class BranchWorkloadProvider
{
    /**
     * @param  list<int>  $branchIds
     * @return array<int, int>
     */
    public function openFulfillmentCounts(array $branchIds): array
    {
        if ($branchIds === []) {
            return [];
        }

        return OrderFulfillment::query()
            ->select('branch_id')
            ->selectRaw('count(*) as aggregate')
            ->whereIn('branch_id', $branchIds)
            ->whereNotIn('status', [
                FulfillmentStatus::Delivered->value,
                FulfillmentStatus::Cancelled->value,
            ])
            ->groupBy('branch_id')
            ->pluck('aggregate', 'branch_id')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }
}

<?php

namespace App\Services\Order;

use App\Models\AllocationDecisionLog;
use App\Models\Order;

/**
 * Persists the OrderAllocationEngine decision trace for one order: every
 * branch considered (winner first, with its scoring dimensions) and every
 * branch rejected (with why). Written by the caller inside the same DB
 * transaction as order placement, so a rolled-back order leaves no log.
 */
class AllocationDecisionRecorder
{
    /**
     * @param  array{considered: list<array<string, mixed>>, rejected: list<array{branch_id: int, reason: string}>}  $trace
     */
    public function record(Order $order, int $winningBranchId, array $trace): AllocationDecisionLog
    {
        return AllocationDecisionLog::query()->create([
            'order_id' => $order->id,
            'winning_branch_id' => $winningBranchId,
            'considered_branches' => $trace['considered'],
            'rejected_branches' => $trace['rejected'],
        ]);
    }
}

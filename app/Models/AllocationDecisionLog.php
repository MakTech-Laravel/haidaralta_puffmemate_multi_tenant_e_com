<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Explainability trace for one order's OrderAllocationEngine decision:
 * every branch considered (with its scoring dimensions, winner first) and
 * every branch rejected (with why), for support/ops troubleshooting.
 *
 * @property int $id
 * @property int $order_id
 * @property int $winning_branch_id
 * @property list<array<string, mixed>> $considered_branches
 * @property list<array{branch_id: int, reason: string}> $rejected_branches
 * @property-read Order $order
 * @property-read Branch $winningBranch
 */
class AllocationDecisionLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'winning_branch_id',
        'considered_branches',
        'rejected_branches',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function winningBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'winning_branch_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'considered_branches' => 'array',
            'rejected_branches' => 'array',
            'created_at' => 'datetime',
        ];
    }
}

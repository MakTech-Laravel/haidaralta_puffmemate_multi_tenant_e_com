<?php

namespace App\Models;

use Database\Factories\BranchAreaTransferHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_area_id
 * @property int $from_branch_id
 * @property int $to_branch_id
 * @property Carbon $effective_date
 * @property string $reason
 * @property int|null $transferred_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BranchArea $area
 * @property-read Branch $fromBranch
 * @property-read Branch $toBranch
 * @property-read User|null $transferredBy
 */
class BranchAreaTransferHistory extends Model
{
    /** @use HasFactory<BranchAreaTransferHistoryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_area_id',
        'from_branch_id',
        'to_branch_id',
        'effective_date',
        'reason',
        'transferred_by',
    ];

    /**
     * @return BelongsTo<BranchArea, $this>
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(BranchArea::class, 'branch_area_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
        ];
    }
}

<?php

namespace App\Models;

use Database\Factories\BranchShippingRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform-editable per-branch shipping/delivery fee configuration.
 *
 * @property int $id
 * @property int $branch_id
 * @property string $base_fee
 * @property string|null $free_delivery_distance_km
 * @property string $cost_per_km
 * @property string $minimum_delivery_charge
 * @property bool $status
 * @property-read Branch $branch
 */
class BranchShippingRule extends Model
{
    /** @use HasFactory<BranchShippingRuleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'base_fee',
        'free_delivery_distance_km',
        'cost_per_km',
        'minimum_delivery_charge',
        'status',
    ];

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_fee' => 'decimal:2',
            'free_delivery_distance_km' => 'decimal:2',
            'cost_per_km' => 'decimal:2',
            'minimum_delivery_charge' => 'decimal:2',
            'status' => 'boolean',
        ];
    }
}

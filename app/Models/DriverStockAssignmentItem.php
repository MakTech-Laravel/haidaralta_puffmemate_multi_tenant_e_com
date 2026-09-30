<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Database\Factories\DriverStockAssignmentItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $driver_stock_assignment_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DriverStockAssignment|null $assignment
 * @property-read Product|null $product
 * @property-read ProductVariant|null $variant
 */
class DriverStockAssignmentItem extends Model
{
    /** @use HasFactory<DriverStockAssignmentItemFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'driver_stock_assignment_id',
        'product_id',
        'variant_id',
        'quantity',
    ];

    /**
     * @return BelongsTo<DriverStockAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(DriverStockAssignment::class, 'driver_stock_assignment_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}

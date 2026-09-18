<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Models\Concerns\AuditsChanges;
use Database\Factories\BranchProductPriceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property string|null $buying_price
 * @property string|null $regular_price
 * @property string $selling_price
 * @property DiscountType $discount_type
 * @property string|null $discount_price
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property bool $status
 * @property-read Branch|null $branch
 * @property-read Product|null $product
 * @property-read ProductVariant|null $variant
 */
class BranchProductPrice extends Model
{
    /** @use HasFactory<BranchProductPriceFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'product_id',
        'variant_id',
        'buying_price',
        'regular_price',
        'selling_price',
        'discount_type',
        'discount_price',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'discount_type' => DiscountType::None->value,
    ];

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
            'buying_price' => 'decimal:2',
            'regular_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'discount_type' => DiscountType::class,
            'discount_price' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'boolean',
        ];
    }
}

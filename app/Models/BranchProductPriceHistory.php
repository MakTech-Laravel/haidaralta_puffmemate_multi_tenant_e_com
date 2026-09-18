<?php

namespace App\Models;

use App\Enums\PriceChangeSource;
use Database\Factories\BranchProductPriceHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property PriceChangeSource $source
 * @property array<string, mixed> $changes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read Branch|null $branch
 * @property-read Product|null $product
 * @property-read ProductVariant|null $variant
 * @property-read User|null $creator
 */
class BranchProductPriceHistory extends Model
{
    /** @use HasFactory<BranchProductPriceHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'product_id',
        'variant_id',
        'source',
        'changes',
        'created_by',
        'created_at',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => PriceChangeSource::class,
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }
}

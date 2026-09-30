<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Database\Factories\DriverInventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $branch_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $driver
 * @property-read Branch|null $branch
 * @property-read Product|null $product
 * @property-read ProductVariant|null $variant
 */
class DriverInventory extends Model
{
    /** @use HasFactory<DriverInventoryFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'branch_id',
        'product_id',
        'variant_id',
        'quantity',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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
            'quantity' => 'integer',
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryTransactionType;
use Database\Factories\DriverInventoryTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $user_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property InventoryTransactionType $type
 * @property InventoryAdjustmentReason|null $reason
 * @property string|null $reason_note
 * @property int $quantity
 * @property int $before_quantity
 * @property int $after_quantity
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read Branch|null $branch
 * @property-read User|null $driver
 * @property-read Product|null $product
 * @property-read ProductVariant|null $variant
 * @property-read User|null $creator
 * @property-read Model|null $reference
 */
class DriverInventoryTransaction extends Model
{
    /** @use HasFactory<DriverInventoryTransactionFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'branch_id',
        'user_id',
        'product_id',
        'variant_id',
        'type',
        'reason',
        'reason_note',
        'quantity',
        'before_quantity',
        'after_quantity',
        'reference_type',
        'reference_id',
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
     * @return BelongsTo<User, $this>
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InventoryTransactionType::class,
            'reason' => InventoryAdjustmentReason::class,
            'quantity' => 'integer',
            'before_quantity' => 'integer',
            'after_quantity' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function reasonLabel(): ?string
    {
        return $this->reason?->displayLabel($this->reason_note);
    }
}

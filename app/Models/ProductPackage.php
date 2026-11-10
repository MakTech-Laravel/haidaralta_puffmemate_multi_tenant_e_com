<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Database\Factories\ProductPackageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $product_id
 * @property string $label
 * @property int $quantity
 * @property string $price
 * @property bool $status
 * @property int $sort_order
 * @property-read Product|null $product
 */
class ProductPackage extends Model
{
    /** @use HasFactory<ProductPackageFactory> */
    use AuditsChanges, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'label',
        'quantity',
        'price',
        'status',
        'sort_order',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}

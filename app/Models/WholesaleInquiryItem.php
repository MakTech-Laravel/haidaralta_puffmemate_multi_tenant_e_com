<?php

namespace App\Models;

use Database\Factories\WholesaleInquiryItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $wholesale_inquiry_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property string $product_name_snapshot
 * @property string|null $sku_snapshot
 * @property int $quantity
 * @property-read WholesaleInquiry|null $inquiry
 * @property-read Product|null $product
 * @property-read ProductVariant|null $variant
 */
class WholesaleInquiryItem extends Model
{
    /** @use HasFactory<WholesaleInquiryItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'wholesale_inquiry_id',
        'product_id',
        'variant_id',
        'product_name_snapshot',
        'sku_snapshot',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<WholesaleInquiry, $this>
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(WholesaleInquiry::class, 'wholesale_inquiry_id');
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
}

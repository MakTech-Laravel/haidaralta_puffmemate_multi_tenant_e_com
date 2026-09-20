<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Support\MediaUrl;
use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string $path
 * @property int $sort_order
 * @property-read Product|null $product
 */
class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'path',
        'sort_order',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): string
    {
        return MediaUrl::resolve($this->path) ?? MediaUrl::DEFAULT_FALLBACK;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}

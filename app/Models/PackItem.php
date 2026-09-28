<?php

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use Database\Factories\PackItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $pack_id
 * @property int $product_id
 * @property int $quantity
 * @property-read Pack|null $pack
 * @property-read Product|null $product
 */
class PackItem extends Model
{
    /** @use HasFactory<PackItemFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'pack_id',
        'product_id',
        'quantity',
    ];

    /**
     * @return BelongsTo<Pack, $this>
     */
    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

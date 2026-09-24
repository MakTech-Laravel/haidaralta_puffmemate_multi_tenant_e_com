<?php

namespace App\Models;

use Database\Factories\PriceRangeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $label
 * @property string $slug
 * @property string|null $min_amount
 * @property string|null $max_amount
 * @property int $sort_order
 * @property bool $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PriceRange extends Model
{
    /** @use HasFactory<PriceRangeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'label',
        'slug',
        'min_amount',
        'max_amount',
        'sort_order',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'sort_order' => 'integer',
            'status' => 'boolean',
        ];
    }
}

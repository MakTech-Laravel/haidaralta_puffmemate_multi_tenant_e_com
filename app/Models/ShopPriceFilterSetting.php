<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Singleton row for storefront shop dual-range slider floor and ceiling.
 *
 * @property int $id
 * @property string $slider_min
 * @property string $slider_max
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ShopPriceFilterSetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'slider_min',
        'slider_max',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slider_min' => 'decimal:2',
            'slider_max' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        $existing = static::query()->first();

        if ($existing !== null) {
            return $existing;
        }

        return static::query()->create([
            'slider_min' => (float) config('catalog.shop_price_slider_min', 0),
            'slider_max' => (float) config('catalog.shop_price_slider_max', 50_000),
        ]);
    }
}

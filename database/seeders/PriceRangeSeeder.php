<?php

namespace Database\Seeders;

use App\Models\PriceRange;
use App\Models\ShopPriceFilterSetting;
use Database\Seeders\Concerns\ReportsSeedProgress;
use Illuminate\Database\Seeder;

class PriceRangeSeeder extends Seeder
{
    use ReportsSeedProgress;

    /**
     * Seed shop price-range presets (AUD bands) and slider bounds.
     */
    public function run(): void
    {
        if (! ShopPriceFilterSetting::query()->exists()) {
            ShopPriceFilterSetting::query()->create([
                'slider_min' => (float) config('catalog.shop_price_slider_min', 0),
                'slider_max' => (float) config('catalog.shop_price_slider_max', 50_000),
            ]);
        }

        $ranges = [
            ['label' => 'Under $20', 'slug' => 'under-20', 'min_amount' => null, 'max_amount' => 20, 'sort_order' => 1],
            ['label' => '$25 to $100', 'slug' => '25-100', 'min_amount' => 25, 'max_amount' => 100, 'sort_order' => 2],
            ['label' => '$100 to $300', 'slug' => '100-300', 'min_amount' => 100, 'max_amount' => 300, 'sort_order' => 3],
            ['label' => '$300 to $500', 'slug' => '300-500', 'min_amount' => 300, 'max_amount' => 500, 'sort_order' => 4],
            ['label' => '$500 to $1,000', 'slug' => '500-1000', 'min_amount' => 500, 'max_amount' => 1000, 'sort_order' => 5],
            ['label' => '$1,000 to $10,000', 'slug' => '1000-10000', 'min_amount' => 1000, 'max_amount' => 10000, 'sort_order' => 6],
            ['label' => '$10,000 & above', 'slug' => '10000-above', 'min_amount' => 10000, 'max_amount' => null, 'sort_order' => 7],
        ];

        $this->withSeedProgress('Price bands', $ranges, fn (array $range) => PriceRange::query()->updateOrCreate(
            ['slug' => $range['slug']],
            [
                'label' => $range['label'],
                'min_amount' => $range['min_amount'],
                'max_amount' => $range['max_amount'],
                'sort_order' => $range['sort_order'],
                'status' => true,
            ],
        ), fn (): string => sprintf('%d shop price bands ready.', PriceRange::query()->count()));
    }
}

<?php

namespace Database\Seeders;

use App\Models\PriceRange;
use Illuminate\Database\Seeder;

class PriceRangeSeeder extends Seeder
{
    /**
     * Seed shop price-range presets (AUD bands).
     */
    public function run(): void
    {
        $ranges = [
            ['label' => 'Under $20', 'slug' => 'under-20', 'min_amount' => null, 'max_amount' => 20, 'sort_order' => 1],
            ['label' => '$25 to $100', 'slug' => '25-100', 'min_amount' => 25, 'max_amount' => 100, 'sort_order' => 2],
            ['label' => '$100 to $300', 'slug' => '100-300', 'min_amount' => 100, 'max_amount' => 300, 'sort_order' => 3],
            ['label' => '$300 to $500', 'slug' => '300-500', 'min_amount' => 300, 'max_amount' => 500, 'sort_order' => 4],
            ['label' => '$500 to $1,000', 'slug' => '500-1000', 'min_amount' => 500, 'max_amount' => 1000, 'sort_order' => 5],
            ['label' => '$1,000 to $10,000', 'slug' => '1000-10000', 'min_amount' => 1000, 'max_amount' => 10000, 'sort_order' => 6],
        ];

        foreach ($ranges as $range) {
            PriceRange::query()->updateOrCreate(
                ['slug' => $range['slug']],
                [
                    'label' => $range['label'],
                    'min_amount' => $range['min_amount'],
                    'max_amount' => $range['max_amount'],
                    'sort_order' => $range['sort_order'],
                    'status' => true,
                ],
            );
        }
    }
}

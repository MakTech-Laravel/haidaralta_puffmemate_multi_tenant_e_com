<?php

namespace Database\Factories;

use App\Enums\PriceChangeSource;
use App\Models\Branch;
use App\Models\BranchProductPriceHistory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchProductPriceHistory>
 */
class BranchProductPriceHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = fake()->randomFloat(2, 5, 40);
        $to = $from + fake()->randomFloat(2, 1, 10);

        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'source' => PriceChangeSource::PriceUpsert,
            'changes' => [
                'selling_price' => [
                    'from' => number_format($from, 2, '.', ''),
                    'to' => number_format($to, 2, '.', ''),
                ],
            ],
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}

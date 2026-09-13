<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BranchProductPrice>
 */
class BranchProductPriceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $selling = fake()->randomFloat(2, 5, 200);

        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'selling_price' => $selling,
            'discount_price' => fake()->optional()->randomFloat(2, 1, $selling),
            'start_date' => null,
            'end_date' => null,
            'status' => true,
        ];
    }
}

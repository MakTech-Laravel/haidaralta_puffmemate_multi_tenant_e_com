<?php

namespace Database\Factories;

use App\Enums\DiscountType;
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
        $buying = round($selling * fake()->randomFloat(2, 0.4, 0.75), 2);
        $regular = fake()->boolean(40)
            ? round($selling * fake()->randomFloat(2, 1.05, 1.3), 2)
            : null;
        $discount = fake()->boolean(40)
            ? fake()->randomFloat(2, 1, $selling)
            : null;

        return [
            'branch_id' => Branch::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'buying_price' => $buying,
            'regular_price' => $regular,
            'selling_price' => $selling,
            'discount_type' => $discount === null
                ? DiscountType::None
                : DiscountType::Fixed,
            'discount_price' => $discount,
            'start_date' => null,
            'end_date' => null,
            'status' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\WholesaleInquiry;
use App\Models\WholesaleInquiryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WholesaleInquiryItem>
 */
class WholesaleInquiryItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wholesale_inquiry_id' => WholesaleInquiry::factory(),
            'product_id' => Product::factory(),
            'variant_id' => null,
            'product_name_snapshot' => fake()->words(3, true),
            'sku_snapshot' => fake()->bothify('PMM-????-##'),
            'quantity' => fake()->numberBetween(10, 100),
        ];
    }
}

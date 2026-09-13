<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->randomElement(['Default', 'Small', 'Medium', 'Large']),
            'sku' => strtoupper(fake()->unique()->bothify('VAR-####??')),
            'attributes' => ['size' => fake()->randomElement(['S', 'M', 'L'])],
            'status' => true,
        ];
    }
}

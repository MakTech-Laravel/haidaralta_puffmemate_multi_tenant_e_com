<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductViewLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductViewLog>
 */
class ProductViewLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'viewed_at' => now(),
        ];
    }
}
